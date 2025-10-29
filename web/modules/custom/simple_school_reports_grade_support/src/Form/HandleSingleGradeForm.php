<?php

namespace Drupal\simple_school_reports_grade_support\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\simple_school_reports_grade_support\GradeInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a form base for course grade registration.
 */
class HandleSingleGradeForm extends GradeRegistrationFormBase {

  protected ?GradeInterface $grade = NULL;

  public function getFormId() {
    return 'ssr_handle_single_grade_form';
  }

  public function getSchoolTypeVersions(): array {

    $school_type = $this->grade?->get('syllabus')->entity?->get('school_type_versioned')->value ?? NULL;
    if ($school_type) {
      return [$school_type];
    }
    return [];
  }

  public function getCancelRoute(): string {
    return 'simple_school_reports_grade_support.grade_registration_types';
  }

  protected function getGradeRegistrationCourses(NodeInterface $course, ?FormStateInterface $form_state = NULL): array {
    return [];
  }

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
    string|int|null $ssr_grade_revision_id = NULL,
  ) {
    if (!$ssr_grade_revision_id) {
      throw new NotFoundHttpException();
    }

    /** @var \Drupal\simple_school_reports_grade_support\GradeInterface|null $ssr_grade */
    $ssr_grade = $this->entityTypeManager->getStorage('ssr_grade')->loadRevision($ssr_grade_revision_id);
    if (!$ssr_grade) {
      throw new NotFoundHttpException();
    }
    $this->grade = $ssr_grade;

    $syllabus_id = $ssr_grade->get('syllabus')->target_id;
    $student_id = $ssr_grade->get('student')->target_id;

    $grading_teacher_id = $ssr_grade->get('main_grader')->target_id;
    $joint_grading_teacher_ids = array_column($ssr_grade->get('joint_grading_by')->getValue(), 'target_id');

    $syllabus_ids = [];
    if ($syllabus_id) {
      $syllabus_ids[] = $syllabus_id;
    }
    $student_ids = [];
    if ($student_id) {
      $student_ids[] = $student_id;
    }

    $grading_teacher_ids = [];
    if ($grading_teacher_id) {
      $grading_teacher_ids[] = $grading_teacher_id;
    }
    if (!empty($joint_grading_teacher_ids)) {
      $grading_teacher_ids = array_merge($grading_teacher_ids, $joint_grading_teacher_ids);
    }
    if ($this->currentUser()->hasRole('teacher')) {
      $grading_teacher_ids[] = $this->currentUser()->id();
    }
    $grading_teacher_ids = array_unique($grading_teacher_ids);


    $course = $ssr_grade->get('course')->entity ?? NULL;

    $syllabuses = !empty($syllabus_ids)
      ? $this->entityTypeManager->getStorage('ssr_syllabus')->loadMultiple($syllabus_ids)
      : [];

    $students = !empty($student_ids)
      ? $this->entityTypeManager->getStorage('user')->loadMultiple($student_ids)
      : [];

    $grading_teachers = !empty($grading_teacher_ids)
      ? $this->entityTypeManager->getStorage('user')->loadMultiple($grading_teacher_ids)
      : [];

    return parent::buildForm($form, $form_state, $syllabuses, $students, $grading_teachers, $course);
  }

  /**
   * Access check for the form.
   *
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   */
  public function access(?AccountInterface $account = NULL): AccessResultInterface {
    return AccessResult::allowedIfHasPermission($account, 'administer simple school reports settings');
  }

}
