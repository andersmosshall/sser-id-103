<?php

namespace Drupal\simple_school_reports_dnp_support\Service;

use Drupal\simple_school_reports_dnp_support\DnpProvisioningConstantsInterface;

/**
 * Provides an interface defining DnpSupportService.
 */
interface DnpSupportServiceInterface extends DnpProvisioningConstantsInterface {

  /**
   * @return bool
   */
  public function isDnpProvisioningEnabled(): bool;

  /**
   * @return string[]|\Drupal\Core\StringTranslation\TranslatableMarkup[]
   */
  public function getDnpTestOptions(): array;

  /**
   * @param string $dnp_test_option
   *
   * @return string|null
   */
  public function getGradeFromDnpTestOption(string $dnp_test_option): ?string;

  /**
   * @param string $dnp_test_option
   *
   * @return string|null
   */
  public function getSubjectFromDnpTestOption(string $dnp_test_option): ?string;

  /**
   * @param string $dnp_test_option
   *
   * @return int[]
   */
  public function getStudentUidsForTest(string $dnp_test_option): array;

  /**
   * @return string[]|\Drupal\Core\StringTranslation\TranslatableMarkup[]
   */
  public function getStudentListBehaviourOptions(): array;

}
