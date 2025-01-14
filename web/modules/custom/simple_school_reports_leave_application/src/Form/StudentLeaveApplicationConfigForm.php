<?php

namespace Drupal\simple_school_reports_leave_application\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\simple_school_reports_attendance_period_analyse\Service\AttendancePeriodAnalyseServiceInterface;
use Drupal\simple_school_reports_leave_application\Service\LeaveApplicationServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a confirmation form for student leave applications.
 */
class StudentLeaveApplicationConfigForm extends FormBase {

  public function __construct(
    protected LeaveApplicationServiceInterface $leaveApplicationService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('simple_school_reports_leave_application.leave_application_service'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'student_leave_application_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['long_leave'] = [
      '#type' => 'number',
      '#title' => $this->t('Long leave'),
      '#description' => $this->t('Number of days for which a leave application must be handled by a principle.'),
      '#default_value' => $this->leaveApplicationService->getSetting('long_leave'),
      '#min' => 1,
      '#max' => 365,
    ];

    $form['max_application_days'] = [
      '#type' => 'number',
      '#title' => $this->t('Max application days'),
      '#description' => $this->t('Maximum number of days a leave application can have.'),
      '#default_value' => $this->leaveApplicationService->getSetting('max_application_days'),
      '#min' => 1,
      '#max' => 365,
    ];

    $form['max_application_days_ago'] = [
      '#type' => 'number',
      '#title' => $this->t('Max application days ago'),
      '#description' => $this->t('Maximum number of days a leave application can be in the past.'),
      '#default_value' => $this->leaveApplicationService->getSetting('max_application_days_ago',),
      '#min' => 1,
      '#max' => 365,
    ];

    $form['max_application_days_future'] = [
      '#type' => 'number',
      '#title' => $this->t('Max application days future'),
      '#description' => $this->t('Maximum number of days a leave application can be in the future.'),
      '#default_value' => $this->leaveApplicationService->getSetting('max_application_days_future'),
      '#min' => 1,
      '#max' => 365,
    ];

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
    $settings = [
      'long_leave' => $form_state->getValue('long_leave'),
      'max_application_days' => $form_state->getValue('max_application_days'),
      'max_application_days_ago' => $form_state->getValue('max_application_days_ago'),
      'max_application_days_future' => $form_state->getValue('max_application_days_future'),
    ];
    $this->leaveApplicationService->setSettings($settings);
    $this->messenger()->addStatus($this->t('Configuration has been saved'));
  }

}
