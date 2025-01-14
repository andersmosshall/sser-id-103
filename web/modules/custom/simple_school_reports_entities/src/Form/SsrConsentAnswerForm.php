<?php

namespace Drupal\simple_school_reports_entities\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the consent answer entity edit forms.
 */
class SsrConsentAnswerForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $result = parent::save($form, $form_state);

    $entity = $this->getEntity();

    $message_arguments = ['%label' => $entity->toLink()->toString()];
    $logger_arguments = [
      '%label' => $entity->label(),
      'link' => $entity->toLink($this->t('View'))->toString(),
    ];

    switch ($result) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('New consent answer %label has been created.', $message_arguments));
        $this->logger('simple_school_reports_entities')->notice('Created new consent answer %label', $logger_arguments);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The consent answer %label has been updated.', $message_arguments));
        $this->logger('simple_school_reports_entities')->notice('Updated consent answer %label.', $logger_arguments);
        break;
    }

    $form_state->setRedirect('entity.ssr_consent_answer.canonical', ['ssr_consent_answer' => $entity->id()]);

    return $result;
  }

}
