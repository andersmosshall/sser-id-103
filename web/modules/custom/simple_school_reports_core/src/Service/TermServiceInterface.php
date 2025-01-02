<?php

namespace Drupal\simple_school_reports_core\Service;

/**
 * Provides an interface defining UnionenFeedbackService.
 */
interface TermServiceInterface {

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

}
