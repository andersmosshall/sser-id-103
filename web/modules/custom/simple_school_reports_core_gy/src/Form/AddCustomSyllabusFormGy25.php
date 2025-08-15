<?php

namespace Drupal\simple_school_reports_core_gy\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\simple_school_reports_core\Form\AddCustomSyllabusFormBase;
use Drupal\simple_school_reports_core_gy\Service\CourseDataServiceGyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a confirmation form for adding custom syllabus in gy 25.
 */
class AddCustomSyllabusFormGy25 extends AddCustomSyllabusFormBase {

  protected CourseDataServiceGyInterface $courseDataService;

  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->courseDataService = $container->get('simple_school_reports_core_gy25.course_data');
    return $instance;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $course_data = $this->courseDataService->getCourseData();
    $course_code = $form_state->getValue('course_code');
    if (isset($course_data[$course_code])) {
      $form_state->setErrorByName('course_code', $this->t('The course code %code already exists.', ['%code' => $course_code]));
    }
  }

  public function getCancelRoute(): string {
    return 'view.syllabus.gy25';
  }

  protected function getSchoolTypeVersioned(): string {
    return 'GY:2025';
  }

  public function getFormId() {
    return 'add_custom_syllabus_form_gy25';
  }

  protected function getSubjectsData(): array {
    return $this->courseDataService->getSubjectsData();
  }
}
