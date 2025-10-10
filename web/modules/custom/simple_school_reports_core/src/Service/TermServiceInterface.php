<?php

namespace Drupal\simple_school_reports_core\Service;

/**
 * Provides an interface defining UnionenFeedbackService.
 */
interface TermServiceInterface {

  public const SEMESTER_HT = 'ht';
  public const SEMESTER_VT = 'vt';

  /**
   * @param bool $as_object
   *
   * @return null|int|\Drupal\Core\Datetime\DrupalDateTime
   */
  public function getCurrentTermStart(bool $as_object = TRUE);

  /**
   * @param bool $as_object
   *
   * @return null|int|\Drupal\Core\Datetime\DrupalDateTime
   */
  public function getCurrentTermEnd(bool $as_object = TRUE);

  /**
   * @param bool $as_object
   * @param \DateTime|null $relative_date
   *
   * @return \DateTime|int
   */
  public function getDefaultSchoolYearStart(bool $as_object = TRUE, ?\DateTime $relative_date = NULL): \DateTime | int;

  /**
   * @param bool $as_object
   * @param \DateTime|null $relative_date
   *
   * @return \DateTime|int
   */
  public function getDefaultSchoolYearEnd(bool $as_object = TRUE, ?\DateTime $relative_date = NULL): \DateTime | int;

  /**
   * This is an internal term index feature that does not use the term set by administer.
   *
   * @param \DateTime|null $date
   *   The date to get the term index for, defaults to current date.
   *
   * @return int
   */
  public function getDefaultTermIndex(?\DateTime $date = NULL): int;

  /**
   * @param int $term_index
   *
   * @return array
   *   Array with keys:
   *    - term_start (programmatically calculated)
   *    - term_end (programmatically calculated)
   *    - arbitrary_term_data (programmatically calculated)
   *    - school_year
   *    - semester: HT or VT.
   *    - semester_name: ex. HT2021
   *    - semester_name_short: ex. HT21
   */
  public function parseDefaultTermIndex(int $term_index): array;

}
