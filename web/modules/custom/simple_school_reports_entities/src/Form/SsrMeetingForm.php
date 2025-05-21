<?php

namespace Drupal\simple_school_reports_entities\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the meeting entity edit forms.
 */
class SsrMeetingForm extends ContentEntityForm {

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $entity = $this->getEntity();
    if (!$entity->isNew()) {
      $form['ssr_meeting_last_changed'] = [
        '#type' => 'hidden',
        '#default_value' => $entity->getChangedTime(),
      ];
    }

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $entity = $this->getEntity();
    if (!$entity->isNew()) {
      $ssr_meeting_last_changed = $form_state->getValue('ssr_meeting_last_changed');
      if ($entity->getChangedTime() != $ssr_meeting_last_changed) {
        $form_state->setError($form, $this->t('This content has been modified by another user, please reload the page and try again.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->getEntity();

    $ui = $form_state->getUserInput();
    if (!empty($ui['attending'])) {
      $attending_values = [];
      foreach ($ui['attending'] as $key => $value) {
        if ($value) {
          $attending_values[] = ['target_id' => $value];
        }
      }

      $form_state->setValue('attending', $attending_values);
      $entity->set('attending', $attending_values);
    }

    $result = parent::save($form, $form_state);



    $message_arguments = ['%label' => $entity->toLink()->toString()];
    $logger_arguments = [
      '%label' => $entity->label(),
      'link' => $entity->toLink($this->t('View'))->toString(),
    ];

    switch ($result) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('New meeting %label has been created.', $message_arguments));
        $this->logger('simple_school_reports_entities')->notice('Created new meeting %label', $logger_arguments);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The meeting %label has been updated.', $message_arguments));
        $this->logger('simple_school_reports_entities')->notice('Updated meeting %label.', $logger_arguments);
        break;
    }

    $form_state->setRedirect('entity.ssr_meeting.canonical', ['ssr_meeting' => $entity->id()]);

    return $result;
  }

}
