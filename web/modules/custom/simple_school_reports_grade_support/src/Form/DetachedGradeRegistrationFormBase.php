<?php

namespace Drupal\simple_school_reports_grade_support\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Form\ConfirmFormHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\simple_school_reports_core\SchoolGradeHelper;
use Drupal\simple_school_reports_core\SchoolTypeHelper;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a form base for course grade registration.
 */
abstract class DetachedGradeRegistrationFormBase extends GradeRegistrationFormBase {

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
  ) {
    $step = $form_state->get('step') ?? NULL;
    if (!$step) {
      $step = 1;
      $form_state->set('step', 1);
    }
    if ($step === 1) {
      return $this->buildStepOne($form, $form_state);
    }

    $syllabus_ids = $form_state->get('grade_reg_syllabus_ids') ?? [];
    $student_ids = $form_state->get('grade_reg_student_ids') ?? [];
    $grading_teacher_ids = $form_state->get('grade_reg_grading_teacher_ids') ?? [];

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

  public function buildStepOne(array $form, FormStateInterface $form_state,): array {
    $school_types = $this->getSchoolTypeVersions();

    $school_types_unversioned = [];
    foreach ($school_types as $school_type_versioned) {
      $school_type_unversioned = SchoolTypeHelper::getSchoolTypeFromSchoolTypeVersioned($school_type_versioned);
      if ($school_type_unversioned) {
        $school_types_unversioned[$school_type_unversioned] = $school_type_unversioned;
      }
    }

    $school_types_unversioned = array_values($school_types_unversioned);
    $school_grades = SchoolGradeHelper::getSchoolGradeValues($school_types_unversioned);
    if (empty($school_grades)) {
      throw new NotFoundHttpException();
    }
    $student_ids = $this->entityTypeManager->getStorage('user')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 1)
      ->condition('roles', 'student')
      ->condition('field_grade', $school_grades, 'IN')
      ->execute();
    $student_options = [];
    if (!empty($student_ids)) {
      $users = $this->entityTypeManager->getStorage('user')->loadMultiple($student_ids);
      foreach ($users as $user) {
        $student_options[$user->id()] = $user->label();
      }
    }
    if (empty($student_options)) {
      throw new AccessDeniedHttpException();
    }
    $form['student_ids'] = [
      '#type' => 'ssr_multi_select',
      '#title' => $this->t('Students'),
      '#description' => $this->t('Select students to register grades for.'),
      '#options' => $student_options,
      '#filter_placeholder' => $this->t('Enter name or grade/class to filter'),
      '#required' => TRUE,
    ];

    $syllabus_ids = $this->gradableCourseService->getGradableSyllabusIds($school_types);
    $syllabus_options = $this->syllabusService->getSyllabusLabelsInOrder($syllabus_ids);
    if (empty($syllabus_options)) {
      throw new AccessDeniedHttpException();
    }
    $form['syllabus_ids'] = [
      '#type' => 'ssr_multi_select',
      '#title' => $this->t('Courses'),
      '#description' => $this->t('Select courses to register grades for.'),
      '#options' => $syllabus_options,
      '#required' => TRUE,
    ];

    $grading_teacher_ids = $this->entityTypeManager->getStorage('user')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 1)
      ->condition('roles', ['teacher', 'principle'], 'IN')
      ->execute();
    $grading_teacher_options = [];
    if (!empty($grading_teacher_ids)) {
      $users = $this->entityTypeManager->getStorage('user')->loadMultiple($grading_teacher_ids);
      foreach ($users as $user) {
        $grading_teacher_options[$user->id()] = $user->label();
      }
    }
    if (empty($grading_teacher_options)) {
      throw new AccessDeniedHttpException();
    }
    $default_value = [];
    if (isset($grading_teacher_options[$this->currentUser()->id()])) {
      $default_value = [$this->currentUser()->id()];
    }
    $form['grading_teacher_ids'] = [
      '#type' => 'ssr_multi_select',
      '#title' => $this->t('Grading teachers'),
      '#description' => $this->t('Select grading teachers to choose from in the next step.'),
      '#options' => $grading_teacher_options,
      '#default_value' => $default_value,
      '#required' => TRUE,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Next'),
      '#button_type' => 'primary',
      '#submit' => ['::submitFormStepOne'],
    ];

    $form['actions']['cancel'] = ConfirmFormHelper::buildCancelLink($this, $this->getRequest());
    $form['#validate'][] = '::validateFormStepOne';

    return $form;
  }

  public function validateFormStepOne(array $form, FormStateInterface $form_state) {
    // Noop.
  }

  public function submitFormStepOne(array $form, FormStateInterface $form_state) {
    $form_state->set('grade_reg_student_ids', $form_state->getValue('student_ids'));
    $form_state->set('grade_reg_grading_teacher_ids', $form_state->getValue('grading_teacher_ids'));
    $form_state->set('grade_reg_syllabus_ids', $form_state->getValue('syllabus_ids'));
    $form_state->set('step', 2);
    $form_state->setRebuild();
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
