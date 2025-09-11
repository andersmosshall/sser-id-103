<?php

namespace Drupal\simple_school_reports_grading_gy\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\simple_school_reports_core\SchoolTypeHelper;
use Drupal\simple_school_reports_grade_support\Controller\ViewCourseGradesControllerBase;

/**
 * Controller for view course grades page GY.
 */
class ViewCourseGradesGyController extends ViewCourseGradesControllerBase {

  /**
   * {@inheritdoc}
   */
  public function pageId(): string {
    return 'view_course_grades_gy';
  }

  /**
   * {@inheritdoc}
   */
  public function getSchoolTypeVersions(): array {
    return SchoolTypeHelper::getSchoolTypeVersions('GY');
  }

  /**
   * {@inheritdoc}
   */
  public function access(?AccountInterface $account = NULL, NodeInterface $node = NULL): AccessResultInterface {
    $course = $node;
    if (!$course || $course->bundle() !== 'course') {
      return AccessResult::forbidden()->addCacheContexts(['route']);
    }

    $base_access = parent::access($account, $node);
    if (!$base_access->isAllowed()) {
      return $base_access;
    }

    $school_types = SchoolTypeHelper::getSchoolTypeVersions('GY');
    $allowed_syllabuses = $this->gradableCourseService->getGradableSyllabusIds($school_types);
    $syllabus_access = AccessResult::allowedIf(in_array($course->get('field_syllabus')->target_id, $allowed_syllabuses))->addCacheableDependency($course);

    return $base_access->andIf($syllabus_access);
  }

}
