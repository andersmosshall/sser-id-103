<?php

namespace Drupal\simple_school_reports_core\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;
use Drupal\simple_school_reports_core\AbsenceDayHandler;
use Drupal\simple_school_reports_core\Service\EmailService;
use Drupal\simple_school_reports_maillog\SsrMaillogInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a confirmation form for register single day absence.
 */
class RegisterSingleSpecificAbsenceForm extends RegisterSingleAbsenceForm {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'register_single_specific_absence_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?UserInterface $user = NULL, ?string $date = NULL, ?string $type = NULL) {
    if (!$date) {
      throw new NotFoundHttpException('no date set');
    }

    $options = [
      'reported' => $this->t('Reported absence'),
      'leave' => $this->t('Leave absence'),
    ];

    if (!$type || !isset($options[$type])) {
      throw new NotFoundHttpException('invalid type');
    }

    $form = parent::buildForm($form, $form_state, $user);

    $form['#title'] = $this->t('Register @absence_type @date for @name', [
      '@absence_type' => $options[$type],
      '@date' => $date,
      '@name' => $user->getDisplayName(),
    ]);

    $form['absence_type'] = [
      '#type' => 'value',
      '#value' => $type,
    ];

    $form['interval_type'] = [
      '#type' => 'value',
      '#value' => 'custom',
    ];

    unset($form['custom_interval']['#states']);

    $from_date = new DrupalDateTime($date . ' 00:00:00');

    $form['custom_interval']['from_date'] = [
      '#type' => 'datetime',
      '#date_date_element' => 'date',
      '#title' => $this->t('Absence from'),
      '#default_value' => $from_date,
      '#required' => TRUE,
      '#date_increment' => 60,
    ];

    $from_date = new DrupalDateTime($date . ' 00:00:00');
    $from_date->setTime(23,59);

    $form['custom_interval']['field_absence_to'] = [
      '#type' => 'datetime',
      '#date_date_element' => 'date',
      '#title' => $this->t('Absence to'),
      '#default_value' => $from_date,
      '#required' => TRUE,
      '#date_increment' => 60,
    ];

    return $form;
  }
}
