<?php

namespace Drupal\simple_school_reports_entities;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a school week entity type.
 */
interface SchoolWeekInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  const CALCULATE_LENGTH = -1;

  /**
   * @param bool $show_lessons
   * @param bool $show_deviations
   *
   * @return array
   */
  public function toTable(bool $show_lessons = FALSE, bool $show_deviations = TRUE): array;

  /**
   * @param \DateTimeInterface|null $date_time
   * @param bool $include_day_lessons
   *
   * @return array
   */
  public function getSchoolDayInfo(?\DateTimeInterface $date_time = NULL, bool $include_day_lessons = TRUE): array;

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

  /**
   * @return string
   */
  public function getType(): string;

  /**
   * @return mixed
   */
  public function isStudentSchema();

  /**
   * @return mixed
   */
  public function calculateFromSchema();

}
