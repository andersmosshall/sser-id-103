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
   * @param bool $include_unpublished
   *
   * @return array
   */
  public function getSchoolSubjectOptionList(bool $include_unpublished = FALSE): array;
}
