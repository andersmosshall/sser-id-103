<?php

namespace Drupal\simple_school_reports_dnp_support\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\simple_school_reports_core\SchoolSubjectHelper;

/**
 * Support methods for DNP related stuff.
 */
class DnpSupportService implements DnpSupportServiceInterface {
  use StringTranslationTrait;

  private bool $provisioningIsEnabled;
  private array $lookup = [];

  public function __construct(
    protected ModuleHandlerInterface $moduleHandler,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
    $this->provisioningIsEnabled = $this->moduleHandler->moduleExists('simple_school_reports_dnp_provisioning');
  }

  /**
   * {@inheritdoc}
   */
  public function isDnpProvisioningEnabled(): bool {
    return $this->provisioningIsEnabled;
  }

  /**
   * {@inheritdoc}
   */
  public function getDnpTestOptions(): array {
    if (!$this->isDnpProvisioningEnabled()) {
      return [];
    }

    $cid = 'test_options';
    if (array_key_exists($cid, $this->lookup)) {
      return $this->lookup[$cid];
    }

    $options = [];

    $subject_name_map = SchoolSubjectHelper::getSupportedSubjectCodes(FALSE);
    $language_name_map = SchoolSubjectHelper::getSupportedLanguageCodes(FALSE);

    foreach (self::TEST_ACTIVITY_MAP as $grade => $subjects_map) {
      if ($grade > Settings::get('ssr_grade_to', 9)) {
        continue;
      }

      foreach ($subjects_map as $subject_code => $test_id) {
        if (!isset($subject_name_map[$subject_code]) && !isset($language_name_map[$subject_code])) {
          continue;
        }

        // Truncate to 50 characters, use '...' if needed.
        $subject_name = $subject_name_map[$subject_code] ?? $language_name_map[$subject_code];
        if (strlen($subject_name) >= 50) {
          $subject_name = substr($subject_name, 0, 47) . '...';
        }

        $key = $grade . ':' . $subject_code;
        $options[$key] = $this->t('@subject in gr @grade', [
          '@grade' => $grade,
          '@subject' => $subject_name,
        ]);
      }
    }

    $this->lookup[$cid] = $options;
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getGradeFromDnpTestOption(string $dnp_test_option): ?string {
    $cid = 'grade_from_' . $dnp_test_option;
    if (array_key_exists($cid, $this->lookup)) {
      return $this->lookup[$cid];
    }

    $valid_options = $this->getDnpTestOptions();
    if (!isset($valid_options[$dnp_test_option])) {
      $this->lookup[$cid] = NULL;
      return NULL;
    }

    $grade = explode(':', $dnp_test_option)[0];
    if (!is_numeric($grade)) {
      $this->lookup[$cid] = NULL;
      return NULL;
    }
    $grade = (int) $grade;

    if (!isset(self::TEST_ACTIVITY_MAP[$grade])) {
      $this->lookup[$cid] = NULL;
      return NULL;
    }

    if ($grade > Settings::get('ssr_grade_to', 9)) {
      $this->lookup[$cid] = NULL;
      return NULL;
    }

    $this->lookup[$cid] = (string) $grade;
    return (string) $grade;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubjectFromDnpTestOption(string $dnp_test_option): ?string {
    $cid = 'subject_from_' . $dnp_test_option;
    if (array_key_exists($cid, $this->lookup)) {
      return $this->lookup[$cid];
    }

    $grade = $this->getGradeFromDnpTestOption($dnp_test_option);
    if ($grade === NULL) {
      $this->lookup[$cid] = NULL;
      return NULL;
    }

    $subject = explode(':', $dnp_test_option)[1] ?? '';

    if (!isset(self::TEST_ACTIVITY_MAP[$grade][$subject])) {
      $this->lookup[$cid] = NULL;
      return NULL;
    }

    $this->lookup[$cid] = $subject;
    return $subject;
  }

  /**
   * {@inheritdoc}
   */
  public function getStudentUidsForTest(string $dnp_test_option): array {
    $grade = $this->getGradeFromDnpTestOption($dnp_test_option);
    if (!$grade) {
      return [];
    }

    $student_uids = $this->entityTypeManager->getStorage('user')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 1)
      ->condition('roles', 'student')
      ->condition('field_grade', $grade)
      ->execute();

    return array_values($student_uids);
  }

  /**
   * {@inheritdoc}
   */
  public function getStudentListBehaviourOptions(): array {
    if (!$this->isDnpProvisioningEnabled()) {
      return [];
    }
    return [
      self::DNP_LIST_EXCLUDE => $this->t('Use all relevant students for the test but exclude the above selected students'),
      self::DNP_LIST_INCLUDE => $this->t('Only include the above selected students in the list, exclude all others'),
    ];
  }

}
