<?php

namespace Drupal\simple_school_reports_core\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use Drupal\simple_school_reports_core\Form\ResetInvalidAbsenceMultipleForm;

/**
 * Interface for SchoolSubjectService
 */
interface SchoolSubjectServiceInterface {

  /**
   * @param array|null $school_types
   * @param bool $include_unpublished
   *
   * @return array
   */
  public function getSchoolSubjectOptionList(?array $school_types_filter = NULL, bool $include_unpublished = FALSE): array;

  /**
   * Get the list of school subject short names.
   *
   * @return array
   *   An associative array of subject names keyed by subject ID.
   */
  public function getSubjectShortNames(): array;

  /**
   * Get the short name of a subject by its term ID.
   *
   * @param string|null $subject_tid
   *   The term ID of the subject.
   *
   * @return string
   *   The short name of the subject, or an empty string if not found.
   */
  public function getSubjectShortName(?string $subject_tid): string;
}
