<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_child_care_support\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\simple_school_reports_child_care_support\Service\ChildCareServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Form controller for the child care schema entity edit forms.
 */
final class ChildCareSchemaForm extends ContentEntityForm {

  protected ChildCareServiceInterface $childCareService;

  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->childCareService = $container->get('simple_school_reports_child_care_support.child_care');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\simple_school_reports_child_care_support\ChildCareInterface $entity */
    $entity = $this->getEntity();

    $is_student = FALSE;

    if ($entity->isNew()) {
      /** @var \Drupal\user\UserInterface $student */
      $student = $this->getRouteMatch()->getParameter('user');
      if ($student) {
        if (!$student->access('caregiver_access')) {
          throw new AccessDeniedHttpException();
        }
        $entity->set('student', $student);
        $is_student = TRUE;
      }

      /** @var \Drupal\simple_school_reports_child_care_support\ChildCareInterface $child_care */
      $child_care = $this->getRouteMatch()->getParameter('ssr_child_care');
      if ($child_care) {
        if (!$child_care->access('update')) {
          throw new AccessDeniedHttpException();
        }
        $entity->set('child_care', $child_care);
      }
    }

    $form = parent::buildForm($form, $form_state);
    self::alterFieldCopy($form, $form_state);

    if ($entity->isNew()) {
      if ($is_student) {
        $min_limit = $this->childCareService->getSettings()['future_min_limit'];
        $date_max = $this->childCareService->getSettings()['future_max_limit'];

        $min_limit_date = new \DateTime();
        $min_limit_date->add(new \DateInterval('P' . $min_limit . 'D'));

        $date_max_date = new \DateTime();
        $date_max_date->add(new \DateInterval('P' . $date_max . 'D'));

        $form['from']['widget'][0]['value']['#min'] = $min_limit_date->format('Y-m-d');
        $form['from']['widget'][0]['value']['#max'] = $date_max_date->format('Y-m-d');

        $form['to']['widget'][0]['value']['#min'] = $min_limit_date->format('Y-m-d');
        $form['to']['widget'][0]['value']['#max'] = $date_max_date->format('Y-m-d');
      }
    }
    else {
      // Disable from date.
      $form['from']['widget'][0]['value']['#disabled'] = TRUE;
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
        $this->messenger()->addStatus($this->t('New child care schema %label has been created.', $message_args));
        $this->logger('simple_school_reports_child_care_support')->notice('New child care schema %label has been created.', $logger_args);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The child care schema %label has been updated.', $message_args));
        $this->logger('simple_school_reports_child_care_support')->notice('The child care schema %label has been updated.', $logger_args);
        break;

      default:
        throw new \LogicException('Could not save the entity.');
    }

    $form_state->setRedirectUrl($this->entity->toUrl());

    return $result;
  }

  public static function alterFieldCopy(array &$form, FormStateInterface $form_state) {

    for ($day_index = 1; $day_index <= 7; $day_index++) {
      if (isset($form['from_' . $day_index])) {
        $form['from_' . $day_index]['#attributes']['class'][] = 'school-week-from';
      }

      if (isset($form['to_' . $day_index])) {
        $form['to_' . $day_index]['#attributes']['class'][] = 'school-week-to';
      }
    }

    $form['from_copy'] = [
      '#type' => 'msr_input_copy',
      '#target_selectors' => ['.school-week-from input'],
    ];

    $form['to_copy'] = [
      '#type' => 'msr_input_copy',
      '#target_selectors' => ['.school-week-to input'],
    ];

    $form['#attached']['library'][] = 'simple_school_reports_entities/school_week_form';
  }

}
