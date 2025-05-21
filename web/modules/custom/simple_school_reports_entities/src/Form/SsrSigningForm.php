<?php

namespace Drupal\simple_school_reports_entities\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the signing entity edit forms.
 */
class SsrSigningForm extends ContentEntityForm {

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
        $this->messenger()->addStatus($this->t('New signing %label has been created.', $message_arguments));
        $this->logger('simple_school_reports_entities')->notice('Created new signing %label', $logger_arguments);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The signing %label has been updated.', $message_arguments));
        $this->logger('simple_school_reports_entities')->notice('Updated signing %label.', $logger_arguments);
        break;
    }

    $form_state->setRedirect('entity.ssr_signing.canonical', ['ssr_signing' => $entity->id()]);

    return $result;
  }

}
