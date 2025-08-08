<?php

namespace Drupal\simple_school_reports_core_gr\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\simple_school_reports_core\Form\AddCustomSyllabusFormBase;
use Drupal\simple_school_reports_core_gr\Service\CourseDataServiceGrInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a confirmation form for adding custom syllabus.
 */
class AddCustomSyllabusFormGr extends AddCustomSyllabusFormBase {

  protected CourseDataServiceGrInterface $courseDataService;

  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->courseDataService = $container->get('simple_school_reports_core_gr.course_data');
    return $instance;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $course_data = $this->getCourseData();
    $course_code = $form_state->getValue('course_code');
    if (isset($course_data[$course_code])) {
      $form_state->setErrorByName('course_code', $this->t('The course code %code already exists.', ['%code' => $course_code]));
    }
  }

  public function getCancelRoute(): string {
    return 'view.syllabus.gr';
  }

  protected function getSchoolTypeVersioned(): string {
    return 'GR:22';
  }

  public function getFormId() {
    return 'add_custom_syllabus_form_gr';
  }

  protected function getSubjectsData(): array {
    return $this->courseDataService->getSubjectsData();
  }
}
