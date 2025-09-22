<?php

namespace Drupal\simple_school_reports_grading_gy\Form;

use Drupal\simple_school_reports_core\SchoolTypeHelper;
use Drupal\simple_school_reports_grade_support\Form\ExportsGradesFormBase;

/**
 * Provides a form for grades export.
 */
class ExportsGradesFormGy extends ExportsGradesFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'register_detached_grades_gy';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute(): string {
    return 'view.ssr_grade_reg_rounds.gy';
  }

  /**
   * {@inheritdoc}
   */
  public function getSchoolTypeVersions(): array {
    return SchoolTypeHelper::getSchoolTypeVersions('GY');
  }

  public function batchTest(string $student_id, &$context) {
    parent::batchTest($student_id, $context);
  }

}
