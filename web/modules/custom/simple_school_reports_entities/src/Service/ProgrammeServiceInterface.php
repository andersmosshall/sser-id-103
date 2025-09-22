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
   * @param bool $include_inactive
   *
   * @return array
   */
  public function getProgrammeIds(bool $include_inactive = FALSE): array;

}
