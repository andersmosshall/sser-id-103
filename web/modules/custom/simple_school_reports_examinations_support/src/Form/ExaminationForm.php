<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_examinations_support\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the examination entity edit forms.
 */
final class ExaminationForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    $entity = parent::buildEntity($form, $form_state);
    if ($entity->isNew() || $entity->get('assessment_group')->isEmpty()) {
      $assessment_group_id = $this->getRequest()->query->get('assessment_group');
      if (!$assessment_group_id) {
        $assessment_group_id = $this->getRouteMatch()->getRawParameter('assessment_group');
      }

      if (!$assessment_group_id) {
        $form_state->setError($form, $this->t('Something went wrong. Try again.'));
        $form_state->setRebuild(TRUE);
        return $entity;
      }

      $entity->set('assessment_group', ['target_id' => $assessment_group_id]);
    }
    return $entity;
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
        $this->messenger()->addStatus($this->t('New examination %label has been created.', $message_args));
        $this->logger('simple_school_reports_examinations_support')->notice('New examination %label has been created.', $logger_args);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The examination %label has been updated.', $message_args));
        $this->logger('simple_school_reports_examinations_support')->notice('The examination %label has been updated.', $logger_args);
        break;

      default:
        throw new \LogicException('Could not save the entity.');
    }

    $form_state->setRedirectUrl($this->entity->toUrl());

    return $result;
  }

}
