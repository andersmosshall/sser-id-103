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
class StudentDiUnbookForm extends StudentDiBookForm {

  public function getQuestion(UserInterface $student = NULL) {
    if ($student) {
      return $this->t('Are you sure you want to change the meeting for @name?', ['@name' => $student->getDisplayName()]);
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
    return $this->t('Save');
  }

  public function getFormId() {
    return 'student_di_unbook';
  }

  public function buildForm(array $form, FormStateInterface $form_state, ?SsrMeetingInterface $meeting = NULL, ?UserInterface $student = NULL) {
    $form = parent::buildForm($form, $form_state, $meeting, $student);

    unset($form['info_wrapper']['student']);
    unset($form['info_wrapper']['teachers']);

    $attending = $meeting->get('attending')->referencedEntities();
    $form['info_wrapper']['attending'] = [
      '#type' => 'container',
    ];

    if (!empty($attending)) {
      $form['info_wrapper']['attending']['title'] = [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#value' => $this->t('Attending') . ':',
        '#attributes' => [
          'class' => ['label'],
        ],
      ];
      $form['info_wrapper']['attending']['list'] = [
        '#theme' => 'item_list',
        '#items' => [],
        '#title' => NULL,
        '#list_type' => 'ul',
      ];
    }

    foreach ($attending as $user) {
      $form['info_wrapper']['attending']['list']['#items'][] = [
        '#markup' => $user->getDisplayName(),
      ];
    }

    $options = [
      'unbook' => $this->t('Unbook meeting completely'),
      'change_attending' => $this->t('Change attending people'),
    ];

    $form['change_select'] = [
      '#type' => 'radios',
      '#title' => $this->t('What do you want to do?'),
      '#weight' => 300,
      '#options' => $options,
      '#default_value' => NULL,
      '#required' => TRUE,
    ];

    $form['book_wrapper']['attending']['#required'] = FALSE;


    $form['book_wrapper']['#states'] = [
      'visible' => [
        ':input[name="change_select"]' => ['value' => 'change_attending'],
      ],
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Skip if errors already exists.
    if (!empty($form_state->getErrors())) {
      return;
    }

    $unbook = $form_state->getValue('change_select') === 'unbook';

    $student = $this->getStudent($form_state);
    $meeting = $this->getMeeting($form_state);
    if (!$student || !$meeting) {
      $form_state->setError($form, $this->t('Something went wrong. Please try again.'));
      return;
    }

    if ($unbook) {
      $ssr_meeting_last_changed = $form_state->getValue('ssr_meeting_last_changed');
      if ($meeting->getChangedTime() != $ssr_meeting_last_changed) {
        $form_state->setError($form, $this->t('This content has been modified by another user, please reload the page and try again.'));
      }
      return;
    }

    $attending = [];
    foreach ($form_state->getValue('attending', []) as $caregiver_id) {
      if  ($caregiver_id > 0) {
        $attending[] = ['target_id' => $caregiver_id];
      }
    }
    if (empty($attending)) {
      $form_state->setErrorByName('attending', $this->t('@name field is required.', ['@name' => $form['book_wrapper']['attending']['#title']]));
    }

    parent::validateForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $student = $this->getStudent($form_state);
    $meeting = $this->getMeeting($form_state);

    if (!$student || !$meeting) {
      return;
    }

    $unbook = $form_state->getValue('change_select') === 'unbook';
    if ($unbook) {
      $meeting->set('field_student', NULL);
      $meeting->set('attending', []);
      $meeting->save();
      $this->messenger()->addStatus($this->t('Meeting is unbooked'));
      $form_state->setRedirect('simple_school_reports_student_di.di_user_tab', ['user' => $student->id()]);
      return;
    }

    parent::submitForm($form, $form_state);
  }


}
