<?php

namespace Drupal\simple_school_reports_grade_support\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\simple_school_reports_core\SchoolTypeHelper;
use Drupal\simple_school_reports_grade_support\Service\GradableCourseServiceInterface;
use Drupal\simple_school_reports_grade_support\Service\GradeService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a form base for course grade registration.
 */
abstract class CourseGradeRegistrationFormBase extends GradeRegistrationFormBase {

  /**
   * @var \Drupal\node\NodeInterface|null
   */
  protected ?NodeInterface $course = NULL;

  /**
   * {@inheritdoc}
   */
  public function buildForm(
    array $form,
    FormStateInterface $form_state,
    array $syllabuses = [],
    array $students = [],
    array $grading_teachers = [],
    ?NodeInterface $course = NULL,
    ?NodeInterface $node = NULL,
  ) {
    if (!$node || $node->bundle() !== 'course') {
      throw new NotFoundHttpException();
    }
    $course = $node;
    $this->course = $node;

    if (!$this->gradableCourseService->allowGradeRegistration($course)) {
      throw new AccessDeniedHttpException();
    }

    $syllabuses = [$course->get('field_syllabus')->entity];
    $students = $course->get('field_student')->referencedEntities();
    $grading_teachers = $course->get('field_grading_teacher')->referencedEntities();

    return parent::buildForm($form, $form_state, $syllabuses, $students, $grading_teachers, $course);
  }

  /**
   * Access check for the form.
   *
   * @param \Drupal\Core\Session\AccountInterface|null $account
   * @param \Drupal\node\NodeInterface|NULL $node
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   */
  public function access(?AccountInterface $account = NULL, NodeInterface $node = NULL): AccessResultInterface {
    if (!$account) {
      $account = $this->currentUser();
    }
    $course = $node;
    if (!$course || $course->bundle() !== 'course') {
      return AccessResult::forbidden()->addCacheContexts(['route']);
    }

    $school_type = SchoolTypeHelper::getSchoolTypeFromSchoolTypeVersioned($this->getSchoolTypeVersions()[0] ?? '');
    return $course->access('register_course_grades_' . (mb_strtolower($school_type)), $account, TRUE);
  }

}
