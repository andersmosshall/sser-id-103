<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_entities\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the school week deviation entity edit forms.
 */
final class SchoolWeekDeviationForm extends ContentEntityForm {

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    if (!empty($form['no_teaching'])) {
      if (!empty($form['from'])) {
        $form['from']['#states'] = [
          'visible' => [
            ':input[name="no_teaching[value]"]' => ['checked' => FALSE],
          ],
        ];
      }
      if (!empty($form['to'])) {
        $form['to']['#states'] = [
          'visible' => [
            ':input[name="no_teaching[value]"]' => ['checked' => FALSE],
          ],
        ];
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
        $this->messenger()->addStatus($this->t('New school week deviation %label has been created.', $message_args));
        $this->logger('simple_school_reports_entities')->notice('New school week deviation %label has been created.', $logger_args);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The school week deviation %label has been updated.', $message_args));
        $this->logger('simple_school_reports_entities')->notice('The school week deviation %label has been updated.', $logger_args);
        break;

      default:
        throw new \LogicException('Could not save the entity.');
    }

    $form_state->setRedirectUrl($this->entity->toUrl());

    return $result;
  }

}
