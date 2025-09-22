<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_grade_support\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the grade snapshot period entity edit forms.
 */
final class GradeSnapshotPeriodForm extends ContentEntityForm {

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
        $this->messenger()->addStatus($this->t('New grade snapshot period %label has been created.', $message_args));
        $this->logger('simple_school_reports_grade_support')->notice('New grade snapshot period %label has been created.', $logger_args);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The grade snapshot period %label has been updated.', $message_args));
        $this->logger('simple_school_reports_grade_support')->notice('The grade snapshot period %label has been updated.', $logger_args);
        break;

      default:
        throw new \LogicException('Could not save the entity.');
    }

    $form_state->setRedirectUrl($this->entity->toUrl());

    return $result;
  }

}
