<?php

namespace Drupal\simple_school_reports_core_gr\Form;

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
