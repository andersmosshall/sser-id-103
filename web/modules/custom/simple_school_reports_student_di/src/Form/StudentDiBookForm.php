<?php

namespace Drupal\simple_school_reports_student_di\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\simple_school_reports_entities\SsrMeetingInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Form controller for book student development interview meeting.
 */
class StudentDiBookForm extends ConfirmFormBase {

  protected EntityTypeManagerInterface $entityTypeManager;

  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  public function getQuestion(UserInterface $student = NULL) {
    if ($student) {
      return $this->t('Are you sure you want to book the meeting for @name?', ['@name' => $student->getDisplayName()]);
    }
    return '';
  }

  public function getCancelUrl() {
    return Url::fromRoute('<front>');
  }

  public function getDescription() {
    return '';
  }

  public function getConfirmText() {
    return $this->t('Book', [], ['context' => 'verb']);
  }

  public function getFormId() {
    return 'student_di_book';
  }

  public function buildForm(array $form, FormStateInterface $form_state, ?SsrMeetingInterface $meeting = NULL, ?UserInterface $student = NULL) {
    if (!$meeting || !$student || !$student->hasRole('student')) {
      throw new NotFoundHttpException();
    }

    $form['meeting_id'] = [
      '#type' => 'value',
      '#value' => $meeting->id(),
    ];

    $form['student_id'] = [
      '#type' => 'value',
      '#value' => $student->id(),
    ];

    $form = parent::buildForm($form, $form_state);
    $form['#title'] = $this->getQuestion($student);


    $form['info_wrapper'] = [
      '#type' => 'container',
      '#weight' => 100,
    ];

    $meeting_from = $meeting->get('from')->value;
    $meeting_to = $meeting->get('to')->value;
    $meeting_label = date('Y-m-d H:i', $meeting_from) . ' - ' . date('H:i', $meeting_to);
    $form['info_wrapper']['meeting'] = [
      '#type' => 'item',
      '#markup' => '<h4 class="label">' . $this->t('Meeting') . ':</h4> ' . $meeting_label,
    ];

    $form['info_wrapper']['student'] = [
      '#type' => 'item',
      '#markup' => '<h4 class="label">' . $this->t('Student') . ':</h4> ' . $student->getDisplayName(),
    ];

    $form['info_wrapper']['teachers'] = [
      '#type' => 'container',
    ];
    /** @var \Drupal\user\UserInterface $teacher */
    foreach ($meeting->get('field_teachers')->referencedEntities() as $teacher) {
      $form['info_wrapper']['teachers'][$teacher->id()] = [
        '#type' => 'item',
        '#markup' => '<h4 class="label">' . $this->t('Teacher') . ':</h4> ' . $teacher->getDisplayName(),
      ];
    }


    $form['ssr_meeting_last_changed'] = [
      '#type' => 'hidden',
      '#default_value' => $meeting->getChangedTime(),
    ];



    $form['book_wrapper'] = [
      '#type' => 'container',
      '#weight' => 400,
    ];
    $caregivers = $student->get('field_caregivers')->referencedEntities();

    if (!empty($caregivers)) {
      $caregiver_options = [];
      foreach ($caregivers as $caregiver) {
        $caregiver_options[$caregiver->id()] = $caregiver->getDisplayName();
      }
      $form['book_wrapper']['attending'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Attending'),
        '#options' => $caregiver_options,
        '#required' => TRUE,
      ];

    } else {
      $form['book_wrapper']['attending'] = [
        '#type' => 'value',
        '#value' => FALSE,
      ];
    }


    $form['actions']['#weight'] = 900;


    return $form;
  }

  protected function getStudent(FormStateInterface $form_state): ?UserInterface {
    $student_id = $form_state->getValue('student_id');
    if (!$student_id) {
      return NULL;
    }
    return $this->entityTypeManager->getStorage('user')->load($student_id);
  }

  protected function getMeeting(FormStateInterface $form_state): ?SsrMeetingInterface {
    $meeting_id = $form_state->getValue('meeting_id');
    if (!$meeting_id) {
      return NULL;
    }
    return $this->entityTypeManager->getStorage('ssr_meeting')->load($meeting_id);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $student = $this->getStudent($form_state);
    $meeting = $this->getMeeting($form_state);

    if (!$student || !$meeting) {
      $form_state->setError($form, $this->t('Something went wrong. Please try again.'));
      return;
    }

    $meeting_student_id = $meeting->get('field_student')->target_id;

    if ($meeting_student_id && $meeting_student_id != $student->id()) {
      $form_state->setError($form, $this->t('This content has been modified by another user, please reload the page and try again.'));
    }

    $ssr_meeting_last_changed = $form_state->getValue('ssr_meeting_last_changed');
    if ($meeting->getChangedTime() != $ssr_meeting_last_changed) {
      $form_state->setError($form, $this->t('This content has been modified by another user, please reload the page and try again.'));
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $student = $this->getStudent($form_state);
    $meeting = $this->getMeeting($form_state);

    if (!$student || !$meeting) {
      return;
    }

    $meeting->set('field_student', $student);

    // Only set the attending field with caregivers. The presave hook will
    // solve the rest.
    $attending = [];
    foreach ($form_state->getValue('attending', []) as $caregiver_id) {
      if  ($caregiver_id > 0) {
        $attending[] = ['target_id' => $caregiver_id];
      }
    }
    $meeting->set('attending', $attending);

    $meeting->save();
    $this->messenger()->addStatus($this->t('Meeting attendance list is saved'));
    $form_state->setRedirect('simple_school_reports_student_di.di_user_tab', ['user' => $student->id()]);
  }


}
