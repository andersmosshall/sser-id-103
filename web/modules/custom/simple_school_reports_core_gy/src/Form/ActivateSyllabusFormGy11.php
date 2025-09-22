<?php

namespace Drupal\simple_school_reports_core_gy\Form;

use Drupal\Core\Url;
use Drupal\simple_school_reports_core\Form\ActivateSyllabusFormBase;
use Drupal\simple_school_reports_core_gy\Service\CourseDataServiceGyInterface;
use Drupal\simple_school_reports_core_gy\Service\CourseDataServiceGrInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a confirmation form for enabling a syllabuses.
 */
class ActivateSyllabusFormGy11 extends ActivateSyllabusFormBase {

  protected CourseDataServiceGyInterface $courseDataService;

  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->courseDataService = $container->get('simple_school_reports_core_gy11.course_data');
    return $instance;
  }

  public function getCancelRoute(): string {
    return 'view.syllabus.gy11';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url($this->getCancelRoute());
  }

  public function getQuestion() {
    return $this->t('Activate syllabus');
  }

  protected function getSchoolTypeVersioned(): string {
    return 'GY:2011';
  }

  public function getFormId() {
    return 'activate_syllabus_form_gy11';
  }

  protected function getCourseData(): array {
    return $this->courseDataService->getCourseData();
  }

  protected function getCourseShortLabel(array $course_data): string {
    $subject_code = $course_data['subject_code'] ?? '?';
    $language_code = $course_data['language_code'] ?? NULL;

    return self::calculateCourseShortLabel($subject_code, $language_code);
  }

  public static function calculateCourseShortLabel(?string $subject_code, ?string $language_code = NULL): string {
    $subject_code = $subject_code ?? '?';
    $subject_code = mb_strtoupper($subject_code);
    if ($subject_code === 'COA') {
      $subject_code = 'OA';
    }

    $short_name = $subject_code;
    if ($language_code) {
      $short_name .= ':' . mb_strtoupper($language_code);
    }

    return $short_name;
  }

}
