<?php

namespace Drupal\simple_school_reports_core_gr\Form;

use Drupal\Core\Url;
use Drupal\simple_school_reports_core\Form\ActivateSyllabusFormBase;
use Drupal\simple_school_reports_core_gr\Service\CourseDataServiceGrInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a confirmation form for enabling a syllabuses.
 */
class ActivateSyllabusFormGr extends ActivateSyllabusFormBase {

  protected CourseDataServiceGrInterface $courseDataService;

  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->courseDataService = $container->get('simple_school_reports_core_gr.course_data');
    return $instance;
  }

  public function getCancelRoute(): string {
    return 'view.syllabus.gr';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url($this->getCancelRoute());
  }

  public function getQuestion() {
    return $this->t('Activate school subjects');
  }

  protected function getSchoolTypeVersioned(): string {
    return 'GR:22';
  }

  public function getFormId() {
    return 'enable_syllabus_form_gr';
  }

  protected function getCourseData(): array {
    return $this->courseDataService->getCourseData();
  }

  public static function getSuccessMessage(): string {
    return t('Subjects have been activated.');
  }

  protected function getCourseShortLabel(array $course_data): string {
    $subject_code = $course_data['subject_code'] ?? '?';
    $language_code = $course_data['language_code'] ?? NULL;

    return self::calculateCourseShortLabel($subject_code, $language_code);
  }

  public static function calculateCourseShortLabel(?string $subject_code, ?string $language_code = NULL): string {
    $subject_code = $subject_code ?? '?';
    $subject_code = mb_strtoupper($subject_code);
    if ($subject_code && str_starts_with($subject_code, 'C')) {
      // Remove 'C' prefix.
      $subject_code = mb_substr($subject_code, 1);
    }

    $short_name = $subject_code;
    if ($language_code) {
      $short_name .= ':' . mb_strtoupper($language_code);
    }

    return $short_name;
  }

}
