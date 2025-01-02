<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_dnp_support\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Form controller for the dnp provisioning entity edit forms.
 */
final class DnpProvisioningForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    if ($entity->isNew()) {
      throw new AccessDeniedHttpException('You are not supposed to create new dnp provisioning entities this way.');
    }

    return parent::buildForm($form, $form_state);
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
        $this->messenger()->addStatus($this->t('New dnp provisioning %label has been created.', $message_args));
        $this->logger('simple_school_reports_dnp_support')->notice('New dnp provisioning %label has been created.', $logger_args);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The dnp provisioning %label has been updated.', $message_args));
        $this->logger('simple_school_reports_dnp_support')->notice('The dnp provisioning %label has been updated.', $logger_args);
        break;

      default:
        throw new \LogicException('Could not save the entity.');
    }

    $form_state->setRedirectUrl($this->entity->toUrl());

    return $result;
  }

}
