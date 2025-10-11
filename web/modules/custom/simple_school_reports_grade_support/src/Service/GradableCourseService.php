<?php

namespace Drupal\simple_school_reports_grade_support\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\simple_school_reports_core\SchoolTypeHelper;
use Drupal\simple_school_reports_grade_support\GradeRegistrationCourseInterface;
use Drupal\user\UserInterface;

/**
 * Provides a service for managing gradable courses.
 */
class GradableCourseService implements GradableCourseServiceInterface {

  protected array $lookup = [];

  public function __construct(
    protected Connection $connection,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getGradableSyllabusIds(array $school_types): array {
    if (empty($school_types)) {
      return [];
    }

    $cid = 'gradable_sids:' . implode(':', $school_types);
    if (array_key_exists($cid, $this->lookup)) {
      return $this->lookup[$cid];
    }

    $syllabus_storage = $this->entityTypeManager->getStorage('ssr_syllabus');
    $syllabus_ids = $syllabus_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('grade_vid', 'none', '<>')
      ->condition('status', 1)
      ->condition('school_type_versioned', $school_types, 'IN')
      ->execute();

    $syllabus_ids = array_values($syllabus_ids);
    $this->lookup[$cid] = $syllabus_ids;

    return $syllabus_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function courseIsGradable(NodeInterface $course): bool {
    if ($course->bundle() !== 'course') {
      return FALSE;
    }

    $syllabus_id = $course->get('field_syllabus')->target_id;
    if (!$syllabus_id) {
      return FALSE;
    }

    $school_types = array_keys(SchoolTypeHelper::getSchoolTypesVersioned());
    return in_array($syllabus_id, $this->getGradableSyllabusIds($school_types));
  }

  /**
   * {@inheritdoc}
   */
  public function validRegistrationCourseNids(): array {
    $school_types = array_keys(SchoolTypeHelper::getSchoolTypesVersioned());
    $gradable_syllabus_ids = $this->getGradableSyllabusIds($school_types);

    if (empty($gradable_syllabus_ids)) {
      return [];
    }

    $query = $this->connection->select('ssr_grade_reg_course', 'rc');
    $query->innerJoin('node__field_syllabus', 'cs', 'cs.entity_id = rc.course');
    $query->innerJoin('ssr_grade_reg_round__field_grade_reg_course', 'r', 'r.field_grade_reg_course_target_id = rc.id');
    $query->condition('cs.field_syllabus_target_id', $gradable_syllabus_ids, 'IN');
    $query->condition('cs.bundle', 'course');
    $query->fields('cs', ['entity_id']);
    $results = $query->execute();

    $ids = [];
    foreach ($results as $result) {
      $ids[] = $result->entity_id;
    }
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function getCourseNidsToGradeSuggestions(array $school_types): array {
    $cid = 'suggested_course_nids:' . implode(':', $school_types);
    if (array_key_exists($cid, $this->lookup)) {
      return $this->lookup[$cid];
    }

    $syllabus_ids = $this->getGradableSyllabusIds($school_types);

    if (empty($syllabus_ids)) {
      return [];
    }

    $occupied_registration_course_ids = $this->validRegistrationCourseNids();
    if (empty($occupied_registration_course_ids)) {
      $occupied_registration_course_ids = [-1];
    }

    $course_end_limit = strtotime('+60 days');

    $nids = $this->entityTypeManager->getStorage('node')->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'course')
      ->condition('field_syllabus', $syllabus_ids, 'IN')
      ->condition('field_to', $course_end_limit, '<=')
      ->condition('nid', $occupied_registration_course_ids, 'NOT IN')
      ->execute();

    $this->lookup[$cid] = $nids;
    return array_values($nids);
  }

  /**
   * {@inheritdoc}
   */
  public function allowGradeRegistration(NodeInterface $course, ?AccountInterface $account = NULL): bool {
    if (!$account) {
      $account = $this->currentUser;
    }

    $cid = 'agr:' . $course->id() . ':' . $account->id();
    if (array_key_exists($cid, $this->lookup)) {
      return $this->lookup[$cid];
    }

    if (!$this->allowViewGrades($course, $account)) {
      $this->lookup[$cid] = FALSE;
      return FALSE;
    }

    $query = $this->connection->select('ssr_grade_reg_course', 'rc');
    $query->innerJoin('ssr_grade_reg_round__field_grade_reg_course', 'r', 'r.field_grade_reg_course_target_id = rc.id');
    $query->innerJoin('ssr_grade_reg_round_field_data', 'rd', 'r.entity_id = rd.id');
    $query->condition('rc.course', $course->id());
    $query->condition('rc.registration_status', GradeRegistrationCourseInterface::REGISTRATION_STATUS_DONE, '<>');
    $query->condition('rd.open', TRUE);
    $query->fields('rd', ['id']);
    $count = $query->countQuery()->execute()->fetchField();

    $access = $count > 0;
    $this->lookup[$cid] = $access;
    return $access;
  }

  /**
   * {@inheritdoc}
   */
  public function allowUnlockGradeRegistration(NodeInterface $course, ?AccountInterface $account = NULL): bool {
    if (!$account) {
      $account = $this->currentUser;
    }

    $cid = 'agu:' . $course->id() . ':' . $account->id();
    if (array_key_exists($cid, $this->lookup)) {
      return $this->lookup[$cid];
    }

    if ($this->allowGradeRegistration($course, $account)) {
      $this->lookup[$cid] = FALSE;
      return FALSE;
    }

    $query = $this->connection->select('ssr_grade_reg_course', 'rc');
    $query->innerJoin('ssr_grade_reg_round__field_grade_reg_course', 'r', 'r.field_grade_reg_course_target_id = rc.id');
    $query->innerJoin('ssr_grade_reg_round_field_data', 'rd', 'r.entity_id = rd.id');
    $query->condition('rc.course', $course->id());
    $query->condition('rc.registration_status', GradeRegistrationCourseInterface::REGISTRATION_STATUS_DONE);
    $query->condition('rd.open', TRUE);
    $query->fields('rd', ['id']);
    $count = $query->countQuery()->execute()->fetchField();

    $access = $count > 0;
    $this->lookup[$cid] = $access;
    return $access;
  }

  /**
   * {@inheritdoc}
   */
  public function allowViewGrades(NodeInterface $course, ?AccountInterface $account = NULL): bool {
    if (!$account) {
      $account = $this->currentUser;
    }
    $cid = 'agv:' . $course->id() . ':' . $account->id();
    if (array_key_exists($cid, $this->lookup)) {
      return $this->lookup[$cid];
    }

    if (!$this->courseIsGradable($course)) {
      $this->lookup[$cid] = FALSE;
      return FALSE;
    }

    if ($account->hasPermission('administer simple school reports settings')) {
      $this->lookup[$cid] = TRUE;
      return TRUE;
    }

    $grading_teacher_uids = array_column($course->get('field_grading_teacher')->getValue(), 'target_id');
    $allowed = in_array($account->id(), $grading_teacher_uids);
    $this->lookup[$cid] = $allowed;
    return $allowed;
  }

  public function getGradeRoundStatus(int|string $grade_round_id, ?AccountInterface $user = NULL): float {
    if (empty($grade_round_id)) {
      return 0;
    }

    $cid = 'grade_round_statuses';
    if ($user) {
      $cid .= ':' . $user->id();
    }

    if (is_array($this->lookup[$cid] ?? NULL)) {
      return $this->lookup[$cid][$grade_round_id] ?? 0;
    }

    $course_ids = [];
    if ($user->hasPermission('administer simple school reports settings')) {
      $course_ids = 'all';
    }
    else {
      $course_ids = $this->entityTypeManager->getStorage('node')->getQuery()
        ->accessCheck(FALSE)
        ->condition('type', 'course')
        ->condition('field_grading_teacher', $user->id())
        ->execute();
      $course_ids = array_values($course_ids);
    }

    if (is_array($course_ids) && empty($course_ids)) {
      $this->lookup[$cid] = [];
      return 0;
    }

    $statuses = [];

    $query = $this->connection->select('ssr_grade_reg_course', 'rc');
    $query->innerJoin('ssr_grade_reg_round__field_grade_reg_course', 'r', 'r.field_grade_reg_course_target_id = rc.id');
    $query->innerJoin('ssr_grade_reg_round_field_data', 'rd', 'r.entity_id = rd.id');
    if (is_array($course_ids)) {
      $query->condition('rc.course', $course_ids, ['IN']);
    }
    $query->fields('rc', ['registration_status']);
    $query->fields('rd', ['id']);

    $results = $query->execute();


    $data = [];
    foreach ($results as $result) {
      $round = $result->id;
      if (!isset($data[$round])) {
        $data[$round] = [
          'done' => 0,
          'total' => 0,
        ];
      }
      $done = $result->registration_status === GradeRegistrationCourseInterface::REGISTRATION_STATUS_DONE;
      $data[$round]['done'] += $done ? 1 : 0;
      $data[$round]['total'] += 1;
    }

    foreach ($data as $round => $status) {
      if ($status['total'] === 0) {
        $statuses[$round] = 0;
        continue;
      }
      $statuses[$round] = ($status['done'] / $status['total']) * 100;
    }

    $this->lookup[$cid] = $statuses;
    return $this->lookup[$cid][$grade_round_id] ?? 0;
  }

  /**
   * {@inheritdoc}
   */
  public function clearLookup(): void {
    $this->lookup = [];
  }

}
