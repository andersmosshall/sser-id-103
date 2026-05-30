<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_child_care_support\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\simple_school_reports_child_care_support\Service\ChildCareService;
use Drupal\simple_school_reports_child_care_support\Service\ChildCareServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the child care entity edit forms.
 */
final class ChildCareForm extends ContentEntityForm {

  protected Connection $connection;

  protected ChildCareServiceInterface $childCareService;

  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->connection = $container->get('database');
    $instance->childCareService = $container->get('simple_school_reports_child_care_support.child_care');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\simple_school_reports_child_care_support\ChildCareInterface $entity */
    $entity = $this->getEntity();

    $form = parent::buildForm($form, $form_state);

    $form['path']['#access'] = FALSE;

    $student_options = [];

    $user_storage = $this->entityTypeManager->getStorage('user');
    $uids = $user_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 1)
      ->condition('roles', 'student')
      ->sort('field_grade')
      ->sort('field_first_name')
      ->sort('field_last_name')
      ->execute();
    if (!empty($uids)) {
      /** @var \Drupal\user\UserInterface[] $users */
      $users = $user_storage->loadMultiple($uids);
      foreach ($users as $user) {
        $student_options[$user->id()] = $user->getDisplayName();
      }
    }


    if (!$entity->isNew() && $form_state->get('selected_students') === NULL) {
      $selected = $this->childCareService->getChildCareStudentIds($entity->id());
      $form_state->set('selected_students', $selected);
    }

    $selected = $form_state->get('selected_students') ?? [];
    $form['student_ids'] = [
      '#type' => 'ssr_multi_select',
      '#title' => $this->t('Students'),
      '#options' => $student_options,
      '#default_value' => $selected,
      '#filter_placeholder' => $this->t('Enter name or grade/class to filter'),
      '#weight' => 100,
    ];

    $form['actions']['#weight'] = 900;

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
        $this->messenger()->addStatus($this->t('New child care %label has been created.', $message_args));
        $this->logger('simple_school_reports_child_care_support')->notice('New child care %label has been created.', $logger_args);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The child care %label has been updated.', $message_args));
        $this->logger('simple_school_reports_child_care_support')->notice('The child care %label has been updated.', $logger_args);
        break;

      default:
        throw new \LogicException('Could not save the entity.');
    }

    $form_state->setRedirectUrl($this->entity->toUrl());

    $student_ids = $form_state->getValue('student_ids');
    $placements = $this->childCareService->getChildCarePlacementIds($this->entity->id());
    $selected = $this->childCareService->getChildCareStudentIds($this->entity->id());

    $to_add = array_diff($student_ids, $selected);
    $to_end = array_diff($selected, $student_ids);

    foreach ($to_add as $student_id) {
      $this->childCareService->addChildCarePlacement($student_id, $this->entity->id());
    }

    // Pre load child care placements.
    if (!empty($to_end)) {
      $this->entityTypeManager->getStorage('ssr_child_care_placement')->loadMultiple(array_values($placements));
    }
    foreach ($to_end as $student_id) {
      if (empty($placements[$student_id])) {
        continue;
      }
      /** @var \Drupal\simple_school_reports_child_care_support\ChildCarePlacementInterface|null $placement */
      $placement = $this->entityTypeManager->getStorage('ssr_child_care_placement')->load($placements[$student_id]);
      if ($placement) {
        $this->childCareService->endChildCarePlacement($placement);
      }
    }

    return $result;
  }

}
