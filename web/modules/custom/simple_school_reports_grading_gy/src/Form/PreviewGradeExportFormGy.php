<?php

namespace Drupal\simple_school_reports_grading_gy\Form;

use Drupal\simple_school_reports_core\SchoolTypeHelper;
use Drupal\simple_school_reports_grade_support\Form\PreviewGradeExportFormBase;

/**
 * Provides a form to preview grades for GY.
 */
class PreviewGradeExportFormGy extends PreviewGradeExportFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'preview_grades_form';
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

  protected function getPreviewBlockId(): string {
    return 'student_grade_preview_block_gy';
  }
}
