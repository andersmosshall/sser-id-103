<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_entities\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the student leave application entity edit forms.
 */
final class StudentLeaveApplicationForm extends ContentEntityForm {

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $entity = $this->getEntity();
    if (!$entity->isNew()) {
      $form['ssr_student_leave_application_last_changed'] = [
        '#type' => 'hidden',
        '#default_value' => $entity->getChangedTime(),
      ];
    }

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    /** @var \Drupal\simple_school_reports_entities\StudentLeaveApplicationInterface $entity */
    $entity = $this->buildEntity($form, $form_state);
    if (!$entity->isNew()) {
      $ssr_meeting_last_changed = $form_state->getValue('ssr_student_leave_application_last_changed');
      if ($entity->getChangedTime() != $ssr_meeting_last_changed) {
        $form_state->setError($form, $this->t('This content has been modified by another user, please reload the page and try again.'));
        return;
      }
    }

    $errors = $entity->validateApplication();
    if (!empty($errors)) {
      foreach ($errors as $field => $error) {
        if ($entity->hasField($field)) {
          $form_state->setErrorByName($field, $error);
          continue;
        }
        $form_state->setError($form, $error);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $message_args = ['%label' => $this->entity->toLink()->toString()];
    $logger_args = [
      '%label' => $this->entity->label(),
      'link' => $this->entity->toLink($this->t('View'))->toString(),
    ];

    switch ($result) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('New leave application %label has been created.', $message_args));
        $this->logger('simple_school_reports_entities')->notice('New student leave application %label has been created.', $logger_args);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The leave application %label has been updated.', $message_args));
        $this->logger('simple_school_reports_entities')->notice('The student leave application %label has been updated.', $logger_args);
        break;

      default:
        throw new \LogicException('Could not save the entity.');
    }

    $form_state->setRedirectUrl($this->entity->toUrl());

    return $result;
  }

}
