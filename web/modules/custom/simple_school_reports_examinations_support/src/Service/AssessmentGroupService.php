<?php

namespace Drupal\simple_school_reports_examinations_support\Service;

use Drupal\Core\Database\Connection;

/**
 * Support methods for assessment group stuff.
 */
class AssessmentGroupService implements AssessmentGroupServiceInterface {

  protected array $lookup = [];

  public function __construct(
    protected Connection $connection,
  ) {}

  /**
   * Get a map of assessment group IDs keyed by group user ID.
   *
   * @return int[]
   *   An array of assessment group IDs keyed by group user ID.
   */
  protected function getAssessmentGroupUserMap(): array {
    $cid = 'group_user_map';
    if (is_array($this->lookup[$cid] ?? NULL)) {
      return $this->lookup[$cid];
    }

    $results = $this->connection->select('ssr_assessment_group__other_teachers', 'ot')
      ->fields('ot', ['entity_id', 'other_teachers_target_id'])
      ->execute();

    $map = [];
    foreach ($results as $result) {
      $map[$result->other_teachers_target_id] = (int) $result->entity_id;
    }
    $this->lookup[$cid] = $map;
    return $map;
  }

  /**
   * {@inheritdoc}
   */
  public function getAssessmentGroupIdFromGroupUserId(int $group_user_id): ?int {
    $map = $this->getAssessmentGroupUserMap();
    return $map[$group_user_id] ?? NULL;
  }


  protected function getAssessmentGroupTeacherMap(): array {
    $cid = 'group_teacher_map';
    if (is_array($this->lookup[$cid] ?? NULL)) {
      return $this->lookup[$cid];
    }

    // Add main teacher to the map.
    $results = $this->connection->select('ssr_assessment_group_field_data', 'ag')
      ->fields('ag', ['id', 'main_teacher'])
      ->execute();

    $map = [];
    foreach ($results as $result) {
      $map[$result->main_teacher][] = (int) $result->id;
    }

    // Add for each relevant group user.
    $results = $this->connection->select('ssr_assessment_group_user__teachers', 'gt')
      ->fields('gt', ['entity_id', 'teachers_target_id'])
      ->execute();

    foreach ($results as $result) {
      $assessment_group_user_id = (int) $result->entity_id;
      $assessment_group_id = $this->getAssessmentGroupIdFromGroupUserId($assessment_group_user_id);
      if ($assessment_group_id) {
        $map[$result->teachers_target_id][] = $assessment_group_id;
      }
    }

    $this->lookup[$cid] = $map;
    return $map;
  }

  /**
   * {@inheritdoc}
   */
  public function getRelatedAssessmentGroupsByTeacher(int $teacher_uid): array {
    $map = $this->getAssessmentGroupTeacherMap();
    return $map[$teacher_uid] ?? [];
  }
}
