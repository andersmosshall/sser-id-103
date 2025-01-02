<?php

namespace Drupal\simple_school_reports_entities;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a school week entity type.
 */
interface SchoolWeekInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * @param bool $show_lessons
   * @param bool $show_deviations
   *
   * @return array
   */
  public function toTable(bool $show_lessons = FALSE, bool $show_deviations = TRUE): array;

  /**
   * @param \DateTimeInterface|null $date_time
   *
   * @return array
   */
  public function getSchoolDayInfo(?\DateTimeInterface $date_time = NULL): array;

  /**
   * @param \Drupal\simple_school_reports_entities\SchoolWeekInterface $school_week
   *
   * @return self
   */
  public function setParentSchoolWeek(SchoolWeekInterface|null $school_week): self;

  /**
   * @return \Drupal\simple_school_reports_entities\SchoolWeekInterface|null
   */
  public function getParentSchoolWeek(): ?SchoolWeekInterface;

}
