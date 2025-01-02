<?php

namespace Drupal\simple_school_reports_extension_proxy\Service;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\simple_school_reports_core\AbsenceDayHandler;
use Drupal\simple_school_reports_grade_registration\GradeRoundFormAlter;

/**
 * Class GradeSupportService
 *
 * @package Drupal\simple_school_reports_grade_stats\Service
 */
class GradeSupportService implements GradeSupportServiceInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * A cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;


  protected $gradeSystemTerms = [];

  protected $genderMap = [];

  public function __construct(
    Connection $connection,
    EntityTypeManagerInterface $entity_type_manager,
    CacheBackendInterface $cache
  ) {
    $this->connection = $connection;
    $this->entityTypeManager = $entity_type_manager;
    $this->cache = $cache;
  }

  /**
   * {@inheritDoc}
   */
  public function getDefaultGradeRoundData(string $grade_round_nid, string $subject_id, string $grade_system, array $student_ids): array {

    $local_student_ids = [];
    $student_ids = array_unique($student_ids);
    foreach ($student_ids as $user_id) {
      $local_student_ids[$user_id] = $user_id;
    }

    $cid_parts = [];
    $cid_parts[] = $subject_id;
    $cid_parts[] = $grade_system;
    $cid_parts[] = $local_student_ids;

    $cid = 'default_grade_round_data:' . sha1(json_encode($cid_parts));

    $cached_data = $this->cache->get($cid);
    if (!empty($cached_data)) {
      return $cached_data->data;
    }

    $grade_data = [];


    $trace = [];
    $this->doGetDefaultGradeRoundData($grade_data, $grade_round_nid, $subject_id, $grade_system, $local_student_ids, $trace);
    $tags = [
      'user_gender_change',
    ];
    foreach ($trace as $nid) {
      $tags[] = 'node:' . $nid;
    }
    $this->cache->set($cid, $grade_data, Cache::PERMANENT, $tags);

    return $grade_data;
  }

  /**
   * @param string $grade_term_id
   * @param string $grade_system
   *
   * @return bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function isValidGrade(string $grade_term_id, string $grade_system): bool {
    if (!isset($this->gradeSystemTerms[$grade_system])) {
      $grade_options = [];
      $grade_items = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($grade_system, 0, NULL, TRUE);
      /** @var \Drupal\taxonomy\TermInterface $grade_item */
      foreach ($grade_items as $grade_item) {
        $grade_options[$grade_item->id()] = $grade_item->id();
      }
      $this->gradeSystemTerms[$grade_system] = $grade_options;
    }

    return isset($this->gradeSystemTerms[$grade_system][$grade_term_id]);
  }

  /**
   * {@inheritDoc}
   */
  public function resolveGender(?string $student_uid, \stdClass $grade_data): ?string {
    if (!$this->genderMap) {
      $gender_map = [];
      $results = $this->connection->select('user__field_gender', 'g')
        ->fields('g', ['entity_id', 'field_gender_value'])
        ->execute();
      foreach ($results as $result) {
        $gender_map[$result->entity_id] = $result->field_gender_value;
      }
      $this->genderMap = $gender_map;
    }

    $gender = $student_uid && isset($this->genderMap[$student_uid]) ? $this->genderMap[$student_uid] : NULL;
    if (!$gender && $grade_data->field_gender_value) {
      $gender = $grade_data->field_gender_value;

      if ($gender === 'F' || $gender === 'f') {
        $gender = 'female';
      }
      if ($gender === 'P' || $gender === 'p') {
        $gender = 'male';
      }
    }

    return $gender;
  }

  /**
   * @param string $grade_round_nid
   * @param string $subject_id
   * @param array $student_ids
   * @param array $trace
   */
  protected function doGetDefaultGradeRoundData(array &$grade_data, string $grade_round_nid, string $subject_id, string $grade_system, array &$student_ids, array &$trace) {
    if (empty($student_ids) || in_array($grade_round_nid, $trace)) {
      return;
    }
    // Prevent infinite loop by adding grade_round_id to trace.
    $trace[$grade_round_nid] = $grade_round_nid;

    /** @var \Drupal\node\NodeStorageInterface $node_storage */
    $node_storage = $this->entityTypeManager->getStorage('node');

    $grade_round = NULL;

    $query = $this->connection->select('paragraphs_item', 'p');
    $query->innerJoin('paragraph__field_grade', 'g', 'g.entity_id = p.id');
    $query->innerJoin('paragraph__field_grade_round', 'round', 'round.entity_id = p.id');
    $query->innerJoin('node__field_grade_registration', 'gr', 'gr.field_grade_registration_target_id = p.id');
    $query->innerJoin('paragraph__field_school_subject', 'sub', 'sub.entity_id = p.id');
    $query->innerJoin('paragraph__field_student', 's', 's.entity_id = p.id');
    $query->leftJoin('paragraph__field_exclude_reason', 'er', 'er.entity_id = p.id');
    $query->leftJoin('paragraph__field_comment', 'c', 'c.entity_id = p.id');
    $query->leftJoin('paragraph__field_gender', 'gen', 'gen.entity_id = p.id');

    $results = $query->condition('p.type', 'grade_registration')
      ->condition('round.field_grade_round_target_id', $grade_round_nid)
      ->fields('gen', ['field_gender_value'])
      ->fields('g', ['field_grade_target_id'])
      ->fields('sub', ['field_school_subject_target_id'])
      ->fields('s', ['field_student_target_id'])
      ->fields('p', ['id'])
      ->fields('er', ['field_exclude_reason_value'])
      ->fields('c', ['field_comment_value'])
      ->execute();


    $handled_pids = [];


    foreach ($results as $grade) {
      // Just in case.
      if (isset($handled_pids[$grade->id])) {
        continue;
      }
      $handled_pids[$grade->id] = TRUE;

      $student_uid = $grade->field_student_target_id;
      $grade_subject_id = $grade->field_school_subject_target_id;


      // Skip excluded or not relevant.
      if ($grade->field_exclude_reason_value || $grade_subject_id !== $subject_id || !in_array($student_uid, $student_ids)) {
        continue;
      }

      // Only default value in same grade system is relevant, for now.
      if (!$this->isValidGrade($grade->field_grade_target_id, $grade_system)) {
        unset($student_ids[$student_uid]);
        continue;
      }

      if (!$grade_round) {
        /** @var \Drupal\node\NodeInterface $grade_round */
        $grade_round = $node_storage->load($grade_round_nid);
        if (!$grade_round) {
          continue;
        }
      }

      $grade_data[$student_uid] = [
        'grade' => $grade->field_grade_target_id,
        'exclude_reason' => $grade->field_exclude_reason_value,
        'grade_round_nid' => $grade_round_nid,
        'term_info' => mb_strtoupper(GradeRoundFormAlter::getFullTermStamp($grade_round)),
        'gender' => $this->resolveGender($student_uid, $grade),
        'paragraph_id' => $grade->id,
      ];

      $processed_comment = $grade->field_comment_value ?? '';

      if ($processed_comment) {
        $paragraph = $this->entityTypeManager->getStorage('paragraph')->load($grade->id);
        $processed_comment = $paragraph->get('field_comment')->value ?? '';
      }

      $analyze = mb_strtolower($processed_comment);

      $comment_suffix = '';

      $set_comment_suffix = strpos($analyze, ' ht') === false && strpos($analyze, ' vt') === false;
      if ($set_comment_suffix && strlen($analyze) === 4 && (strpos($analyze, 'ht') === 0 || strpos($analyze, 'vt') === 0)) {
        $set_comment_suffix = FALSE;
      }

      if ($set_comment_suffix) {
        $comment_suffix = $processed_comment ? ' ' . $grade_data[$student_uid]['term_info'] : $grade_data[$student_uid]['term_info'];
      }
      $grade_data[$student_uid]['clean_comment'] = $processed_comment;
      $grade_data[$student_uid]['comment'] = $processed_comment . $comment_suffix;

      if (strlen($grade_data[$student_uid]['comment']) > 10) {
        $truncated_processed_comment = substr($processed_comment,0,10 - 3 - strlen($comment_suffix)) . '...';
        $grade_data[$student_uid]['comment'] = $truncated_processed_comment . $comment_suffix;
      }
      $grade_data[$student_uid]['comment_suffix'] = $comment_suffix;

      unset($student_ids[$student_uid]);
    }

    if (empty($student_ids)) {
      return;
    }

    if (!$grade_round) {
      /** @var \Drupal\node\NodeInterface $grade_round */
      $grade_round = $node_storage->load($grade_round_nid);
      if (!$grade_round) {
        return;
      }
    }

    foreach ($grade_round->get('field_student_groups')->referencedEntities() as $student_group) {
      $group_students = array_column($student_group->get('field_student')->getValue(), 'target_id');
      if (!empty(array_intersect($student_ids, $group_students))) {

        $grade_subject_nids = array_column($student_group->get('field_grade_subject')->getValue(), 'target_id');

        if (empty($grade_subject_nids)) {
          continue;
        }

        $school_subjects = $node_storage->getQuery()
          ->condition('type', 'grade_subject')
          ->condition('field_school_subject', $subject_id)
          ->exists('field_default_grade_round')
          ->condition('nid', $grade_subject_nids, 'IN')
          ->accessCheck(FALSE)
          ->execute();

        if (!empty($school_subjects)) {
          /** @var \Drupal\node\NodeInterface $school_subject */
          foreach ($node_storage->loadMultiple($school_subjects) as $school_subject) {
            $default_grade_round = $school_subject->get('field_default_grade_round')->target_id;
            if ($default_grade_round) {
              $this->doGetDefaultGradeRoundData($grade_data, $default_grade_round, $subject_id, $grade_system, $student_ids, $trace);
              if (empty($student_ids)) {
                return;
              }
            }
          }
        }
      }
    }
  }

}
