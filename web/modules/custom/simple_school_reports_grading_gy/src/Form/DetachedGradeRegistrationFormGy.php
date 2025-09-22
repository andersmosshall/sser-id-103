<?php

namespace Drupal\simple_school_reports_grading_gy\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\simple_school_reports_core\SchoolTypeHelper;
use Drupal\simple_school_reports_grade_support\Form\DetachedGradeRegistrationFormBase;

/**
 * Provides a form base for grade registration.
 */
class DetachedGradeRegistrationFormGy extends DetachedGradeRegistrationFormBase {

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
}
