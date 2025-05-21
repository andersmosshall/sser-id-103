<?php

namespace Drupal\simple_school_reports_attendance_period_analyse\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\simple_school_reports_attendance_period_analyse\Service\AttendancePeriodAnalyseServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a confirmation form for activating school subject.
 */
class AttendancePeriodAnalyseConfigForm extends FormBase {

  /**
   * @param \Drupal\simple_school_reports_attendance_period_analyse\Service\AttendancePeriodAnalyseServiceInterface $attendancePeriodAnalyseService
   */
  public function __construct(
    protected AttendancePeriodAnalyseServiceInterface $attendancePeriodAnalyseService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('simple_school_reports_attendance_period_analyse.attendance_period_analyse_service'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'attendance_period_analyse_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['absence_percentage_limits'] = [
      '#type' => 'details',
      '#title' => $this->t('Absence percentage limits'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    $absence_percentage_limits = $this->attendancePeriodAnalyseService->getAbsencePercentageLimits();

    // Remove the 0 and 100 limits, (first and last item).
    unset($absence_percentage_limits[0]);
    array_pop($absence_percentage_limits);

    $absence_percentage_limits = array_values($absence_percentage_limits);

    for ($i = 1; $i <= 10; $i++) {
      $form['absence_percentage_limits'][$i] = [
        '#type' => 'number',
        '#title' => $this->t('Absence percentage limit @i', ['@i' => $i]),
        '#default_value' => $absence_percentage_limits[$i - 1] ?? NULL,
        '#min' => 1,
        '#max' => 99,
      ];
    }


    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $absence_percentage_limits = [];
    for ($i = 1; $i <= 10; $i++) {
      $absence_percentage_limits[] = $form_state->getValue(['absence_percentage_limits', $i]);
    }
    $this->attendancePeriodAnalyseService->setAbsencePercentageLimits($absence_percentage_limits);
    $this->messenger()->addStatus($this->t('Configuration has been saved'));
  }

}
