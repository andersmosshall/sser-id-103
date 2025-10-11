<?php

namespace Drupal\simple_school_reports_entities\Service;

/**
 * Provides an interface defining SsrProgrammeService.
 */
interface ProgrammeServiceInterface {

  /**
   * @param string $programme_id
   *
   * @return array
   */
  public function getStudentIdsByProgrammeId(string $programme_id): array;

  /**
   * @param string $grade
   *
   * @return array
   */
  public function getProgrammeIdsByGrade(string $grade): array;

  /**
   * @param string $student_id
   *
   * @return string|null
   */
  public function getStudentProgrammeId(string $student_id): ?string;

  /**
   * @return string[]
   *   Keyed by student id.
   */
  public function getProgrammeIdsInUse(): array;

  /**
   * @param array $student_ids
   *
   * @return array
   *   Keyed by student id.
   */
  public function getProgrammeIdsInUseBy(array $student_ids): array;

  /**
   * @param bool $include_inactive
   *
   * @return string[]
   */
  public function getProgrammeIds(bool $include_inactive = FALSE): array;

}
