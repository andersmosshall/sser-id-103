<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_entities\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the ssr organization entity edit forms.
 */
final class SSROrganizationForm extends ContentEntityForm {

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $organization = $this->getEntity();
    if (!$organization->isNew()) {
      // Disable a set of fields if they are set.
      $fields_to_disable = [
        'label',
        'municipality_code',
        'school_types',
        'school_unit_code',
        'organization_type',
        'parent',
      ];

      foreach ($fields_to_disable as $field_name) {
        if ($organization->get($field_name)->value || $organization->get($field_name)->target_id) {
          $form[$field_name]['#disabled'] = TRUE;
        }
      }
    }

    return $form;
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
        $this->messenger()->addStatus($this->t('New ssr organization %label has been created.', $message_args));
        $this->logger('simple_school_reports_entities')->notice('New ssr organization %label has been created.', $logger_args);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The ssr organization %label has been updated.', $message_args));
        $this->logger('simple_school_reports_entities')->notice('The ssr organization %label has been updated.', $logger_args);
        break;

      default:
        throw new \LogicException('Could not save the entity.');
    }

    $form_state->setRedirectUrl($this->entity->toUrl());

    return $result;
  }

}
