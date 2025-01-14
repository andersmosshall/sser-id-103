<?php

namespace Drupal\simple_school_reports_maillog\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the maillog entity edit forms.
 */
class SsrMaillogForm extends ContentEntityForm {

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
        $this->messenger()->addStatus($this->t('New maillog %label has been created.', $message_arguments));
        $this->logger('simple_school_reports_maillog')->notice('Created new maillog %label', $logger_arguments);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The maillog %label has been updated.', $message_arguments));
        $this->logger('simple_school_reports_maillog')->notice('Updated maillog %label.', $logger_arguments);
        break;
    }

    $form_state->setRedirect('entity.ssr_maillog.canonical', ['ssr_maillog' => $entity->id()]);

    return $result;
  }

}
