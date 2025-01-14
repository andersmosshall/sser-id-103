<?php

namespace Drupal\simple_school_reports_examinations_support\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings;

/**
 * Support methods for examinations stuff.
 */
class ExaminationService implements ExaminationServiceInterface {

  protected array $lookup = [];

  public function __construct(
    protected Connection $connection,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountInterface $currentUser,
  ) {}

  protected function getAllExaminationResultStats(): array {
    $cid = 'examination_result_stats';
    if (is_array($this->lookup[$cid] ?? NULL)) {
      return $this->lookup[$cid];
    }

    $state_not_completed_state = Settings::get('ssr_examination_result_not_completed');
    $state_not_applicable = Settings::get('ssr_examination_result_not_applicable');
    if (!$state_not_completed_state) {
      $this->lookup[$cid] = [];
      return [];
    }

    $students_map = [];
    $results = $this->connection->select('ssr_assessment_group__students', 'as')
      ->fields('as', ['entity_id', 'students_target_id'])
      ->execute();
    foreach ($results as $result) {
      $students_map[$result->entity_id][] = $result->students_target_id;
    }

    $stats = [];

    $query = $this->connection->select('ssr_examination_field_data', 'e');
    $query->innerJoin('ssr_assessment_group', 'ag', 'e.assessment_group = ag.id');
    $query->leftJoin('ssr_examination_result_field_data', 'er', 'e.id = er.examination');
    $query->fields('e', ['id', 'assessment_group', 'status']);
    $query->fields('er', ['student', 'state', 'status']);
    $results = $query->execute();

    foreach ($results as $result) {
      $examination_id = $result->id;
      $examination_published = !!$result->status;

      if (!isset($stats[$examination_id])) {
        $stats[$examination_id]['published'] = $examination_published;
        $assessment_group_id = $result->assessment_group;
        $student_uids = $students_map[$assessment_group_id] ?? [];
        foreach ($student_uids as $student_uid) {
          $stats[$examination_id]['states'][$state_not_completed_state]['students'][$student_uid] = [
            'published' => $examination_published,
            'in_group' => TRUE,
          ];
        }
      }

      $student_uid = $result->student;
      if (!$student_uid) {
        continue;
      }

      $in_group = isset($stats[$examination_id]['states'][$state_not_completed_state]['students'][$student_uid]);

      $state = $result->state ?: $state_not_completed_state;
      $examination_result_published = $examination_published && !!$result->er_status;

      if ($state !== $state_not_completed_state) {
        unset($stats[$examination_id]['states'][$state_not_completed_state]['students'][$student_uid]);
      }
      if ($state === $state_not_applicable && !$in_group) {
        continue;
      }

      if (empty($stats[$examination_id]['states'][$state]['students'][$student_uid])) {
        $stats[$examination_id]['states'][$state]['students'][$student_uid]['in_group'] = $in_group;
      }
      $stats[$examination_id]['states'][$state]['students'][$student_uid]['published'] = $examination_result_published;
    }

    $this->lookup[$cid] = $stats;
    return $stats;
  }

  /**
   * {@inheritdoc}
   */
  public function getExaminationResultStats(int $examinationId): array {
    $stats = $this->getAllExaminationResultStats();
    return $stats[$examinationId] ?? [];
  }

  public function getProgress(int $examinationId): string {
    $examination_stats = $this->getExaminationResultStats($examinationId);

    $state_not_applicable = Settings::get('ssr_examination_result_not_applicable');
    $state_not_completed_state = Settings::get('ssr_examination_result_not_completed');
    if (!$state_not_applicable || !$state_not_completed_state) {
      return '0';
    }

    $total = 0;
    $handled = 0;

    foreach ($examination_stats['states'] ?? [] as $state => $state_data) {
      if ($state === $state_not_applicable) {
        continue;
      }
      $uids = $state_data['students'] ?? [];
      $total += count($uids);
      if ($state !== $state_not_completed_state) {
        $handled += count($uids);
      }
    }

    return $total ? round(($handled / $total) * 100, 1) : '0';
  }


  protected function getExaminationResultValuesDataForUser(string $uid, bool $only_published = FALSE, $skip_not_applicable = FALSE): array {
    $cid = 'examination_result_values_for_user:' . $uid;
    $cid .= $only_published ? ':published' : '';
    if (is_array($this->lookup[$cid] ?? NULL)) {
      return $this->lookup[$cid];
    }

    /** @var \Drupal\user\UserInterface|null $student */
    $student = $this->entityTypeManager->getStorage('user')->load($uid);
    if (!$student || !$student->hasRole('student')) {
      $this->lookup[$cid] = [];
      return [];
    }

    $has_caregiver_access = $student->access('caregiver_access', $this->currentUser);
    $is_school_staff = $this->currentUser->hasPermission('school staff permissions');

    $state_not_applicable = Settings::get('ssr_examination_result_not_applicable');
    if (!$state_not_applicable || (!$has_caregiver_access && !$is_school_staff)) {
      $this->lookup[$cid] = [];
      return [];
    }

    $data = [];
    $stats = $this->getAllExaminationResultStats();

    foreach ($stats as $examination_id => $examination_stats) {
      if ($only_published && empty($examination_stats['published'])) {
        continue;
      }

      foreach ($examination_stats['states'] ?? [] as $state => $state_data) {
        if ($skip_not_applicable && $state === $state_not_applicable) {
          continue;
        }

        $uids = $state_data['students'] ?? [];
        if (!array_key_exists($uid, $uids)) {
          continue;
        }

        $is_published = $state_data['students'][$uid]['published'] ?? FALSE;

        if ($only_published && !$is_published) {
          continue;
        }

        // Skip not published results non staff.
        if (!$is_published && !$is_school_staff) {
          continue;
        }

        $data[$examination_id] = [
          'value' => $state,
          'in_group' => $state_data['students'][$uid]['in_group'] ?? FALSE,
          'published' => $is_published,
        ];
        break;
      }
    }

    $this->lookup[$cid] = $data;
    return $data;
  }

  public function getStudentsRelevantForExamination(int $examinationId): array {
    $stats = $this->getExaminationResultStats($examinationId);

    $uids = [];
    foreach ($stats['states'] ?? [] as $state => $state_data) {
      $uids = array_merge($uids, array_keys($state_data['students'] ?? []));
    }

    return array_unique($uids);
  }

  /**
   * {@inheritdoc}
   */
  public function getExaminationResultValueDataForUser(string $uid, string $examination_id, bool $only_published = FALSE, bool $skip_not_applicable = FALSE): ?array {
    $data = $this->getExaminationResultValuesDataForUser($uid, $only_published, $skip_not_applicable);
    return $data[$examination_id] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getExaminationResultValuesForUser(string $uid, bool $only_published = FALSE, bool $skip_not_applicable = FALSE): array {
    $data = $this->getExaminationResultValuesDataForUser($uid, $only_published, $skip_not_applicable);

    return array_map(function($examination_data) {
      return $examination_data['value'];
    }, $data);
  }

}
