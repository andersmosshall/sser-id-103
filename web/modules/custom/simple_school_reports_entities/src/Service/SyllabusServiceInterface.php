<?php

namespace Drupal\simple_school_reports_entities\Service;

/**
 * Provides an interface defining SyllabusService.
 */
interface SyllabusServiceInterface {

  /**
   * Get syllabus ids that are related to the given syllabus ids.
   *
   * @param array $syllabus_ids
   *
   * @return array
   *   The complete list of given syllabus ids and their related syllabus ids.
   */
  public function getSyllabusAssociations(array $syllabus_ids): array;

  public function getSyllabusInfo(int $syllabus_id): array;

  /**
   * @param int $syllabus_id
   *
   * @return int[]
   */
  public function getSyllabusLevelIds(int $syllabus_id): array;

  /**
   * @param int $syllabus_id
   *
   * @return int[]
   */
  public function getSyllabusPreviousLevelIds(int $syllabus_id): array;

  /**
   * @param array|null $syllabus_ids
   *
   * @return string[]
   *   Keyed by syllabus id.
   */
  public function getSyllabusLabelsInOrder(?array $syllabus_ids = NULL): array;

  /**
   * @param array|null $syllabus_ids
   *
   * @return string[]
   *   Keyed by syllabus id.
   */
  public function getSyllabusCourseCodesInOrder(?array $syllabus_ids = NULL): array;

  /**
   * @param array|null $syllabus_ids
   *
   * @return string[]
   *   Keyed by syllabus id.
   */
  public function getSyllabusWeight(?array $syllabus_ids = NULL): array;

  /**
   * @param int $syllabus_id
   *
   * @return array
   *  Keyed by level id, each entry is an array with the following keys:
   *  - points
   *  - aggregated_points
   */
  public function getSyllabusPreviousPoints(int $syllabus_id): array;

  /**
   * @param int $syllabus_id
   *
   * @return bool
   */
  public function useDiplomaProject(int $syllabus_id): bool;

  /**
   * @param array $school_type_versions
   *   May be school types or school type versions.
   *
   * @return array
   */
  public function getSyllabusIdsFromSchoolTypes(array $school_type_versions): array;

}
