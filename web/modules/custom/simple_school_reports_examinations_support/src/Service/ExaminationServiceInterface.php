<?php

namespace Drupal\simple_school_reports_examinations_support\Service;

/**
 * Provides an interface defining ExaminationService.
 */
interface ExaminationServiceInterface {

  /**
   * @param int $examinationId
   *
   * @return array
   *   An array of examination result stats.
   *   There is a key 'states' that has a list of states keyed by state value
   *   (results).
   *   Each entry has, in key students, a list of publish statuses, keyed by
   *   user id.
   */
  public function getExaminationResultStats(int $examinationId): array;

  /**
   * @param int $examinationId
   *
   * @return string
   *   A numeric string of percentage of examination results reported.
   */
  public function getProgress(int $examinationId): string;

  /**
   * @param int $examinationId
   *
   * @return array
   *   An array of user IDs relevant for the examination.
   */
  public function getStudentsRelevantForExamination(int $examinationId): array;

  /**
   * @param string $uid
   * @param string $examination_id
   * @param bool $only_published
   * @param bool $skip_not_applicable
   *
   * @return array|null
   */
  public function getExaminationResultValueDataForUser(string $uid, string $examination_id, bool $only_published = FALSE, bool $skip_not_applicable = FALSE): ?array;

  /**
   * @param string $uid
   *   The user ID.
   * @param bool $only_published
   * @param bool $skip_not_applicable
   *
   * @return array
   *   An array of examination result values keyed by examination ID.
   */
  public function getExaminationResultValuesForUser(string $uid, bool $only_published = FALSE, bool $skip_not_applicable = FALSE): array;

}
