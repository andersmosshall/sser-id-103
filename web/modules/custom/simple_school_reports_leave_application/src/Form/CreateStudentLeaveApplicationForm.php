<?php

namespace Drupal\simple_school_reports_leave_application\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\simple_school_reports_core\Service\EmailServiceInterface;
use Drupal\simple_school_reports_entities\StudentLeaveApplicationInterface;
use Drupal\simple_school_reports_leave_application\Service\LeaveApplicationServiceInterface;
use Drupal\simple_school_reports_maillog\SsrMaillogInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a confirmation form to handle student leave application.
 */
class CreateStudentLeaveApplicationForm extends ConfirmFormBase {

  protected ?AccountInterface $student = NULL;

  public function __construct(
    protected EmailServiceInterface $emailService,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LeaveApplicationServiceInterface $leaveApplicationService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('simple_school_reports_core.email_service'),
      $container->get('entity_type.manager'),
      $container->get('simple_school_reports_leave_application.leave_application_service'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'create_student_leave_application_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    if ($this->student) {
      return  $this->t('Create leave application for @student', ['@student' => $this->student->getDisplayName()]);
    }
    return $this->t('Create leave application');
  }

  public function getCancelRoute() {
    if ($this->student) {
      return Url::fromRoute('simple_school_reports_leave_application.leave_application_student_tab', ['user' => $this->student->id()]);
    }
    return '<front>';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url($this->getCancelRoute());
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Create application');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?UserInterface $user = NULL) {
    $student = $user;
    if (!$student instanceof UserInterface) {
      throw new NotFoundHttpException();
    }

    if (!$student->hasRole('student')) {
      throw new NotFoundHttpException();
    }

    $this->student = $student;
    $form['student_id'] = [
      '#type' => 'value',
      '#value' => $student->id(),
    ];

    $form['period_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Enter date as'),
      '#options' => [
        'date' => $this->t('Date'),
        'date_time' => $this->t('Date and time'),
      ],
      '#default_value' => 'date',
      '#required' => TRUE,
    ];

    $form['date_wrapper'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="period_type"]' => ['value' => 'date'],
        ],
      ],
    ];
    $form['date_wrapper']['date_from'] = [
      '#type' => 'date',
      '#title' => $this->t('From'),
    ];
    $form['date_wrapper']['date_to'] = [
      '#type' => 'date',
      '#title' => $this->t('To'),
    ];

    $form['date_time_wrapper'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="period_type"]' => ['value' => 'date_time'],
        ],
      ],
    ];

    $form['date_time_wrapper']['date_time_from'] = [
      '#type' => 'datetime',
      '#title' => $this->t('From'),
      '#date_increment' => 60,
    ];
    $form['date_time_wrapper']['date_time_to'] = [
      '#type' => 'datetime',
      '#title' => $this->t('To'),
      '#date_increment' => 60,
    ];

    $form['field_reason'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Reason for leave'),
      '#required' => TRUE,
    ];

    $form['field_compensation_plan'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Compensation plan'),
      '#description' => $this->t('Enter a plan for how student may compensate for the lost tuition.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * @param string|int|\DateTimeInterface|null $input
   * @param string $type
   *   'from' or 'to'
   *
   * @return int|null
   */
  protected function convertToTimestamp(null | string | int | array | \DateTimeInterface $input, string $type, bool $adjust_only_seconds): ?int {
    try {
      if ($input === NULL) {
        return NULL;
      }

      $date = NULL;
      if (is_int($input)) {
        $date = new \DateTime();
        $date->setTimestamp($input);
      }

      if ($input instanceof \DateTimeInterface) {
        $date = $input;
      }
      if (is_string($input) && !empty($input)) {
        $date = new \DateTime($input);
      }

      if (is_array($input)) {
        if (!empty($input['object']) && $input['object'] instanceof \DateTimeInterface) {
          $date = $input['object'];
        }
        elseif (!empty($input['date'])) {
          $date_string = $input['date'];
          if (!empty($input['time'])) {
            $date_string .= ' ' . $input['time'];
          }
          else {
            $date_string .= $type === 'from' ? ' 00:00:00' : ' 23:59:59';
          }
          $date = new \DateTime($date_string);
        }
      }

      if ($date === NULL) {
        return NULL;
      }

      if ($adjust_only_seconds) {
        if ($type === 'from') {
          $date->setTime($date->format('H'), $date->format('i'), 0);
        }
        else {
          $date->setTime($date->format('H'), $date->format('i'), 59);
        }
      }
      else {
        if ($type === 'from') {
          $date->setTime(0, 0, 0);
        }
        else {
          $date->setTime(23, 59, 59);
        }
      }

      return $date->getTimestamp();

    } catch (\Exception $e) {
      return NULL;
    }

  }

  protected function createApplication(FormStateInterface $form_state, bool $save = FALSE): StudentLeaveApplicationInterface {
    /** @var \Drupal\simple_school_reports_entities\StudentLeaveApplicationInterface $application */
    $application = $this->entityTypeManager->getStorage('ssr_student_leave_application')->create([
      'student' => ['target_id' => $form_state->getValue('student_id')],
      'state' => 'pending',
      'uid' => $this->currentUser()->id(),
    ]);

    $reason = $form_state->getValue('field_reason');
    $compensation_plan = $form_state->getValue('field_compensation_plan');
    $application->set('field_reason', $reason);
    $application->set('field_compensation_plan', $compensation_plan);

    $period_type = $form_state->getValue('period_type');
    $from = $period_type === 'date'
      ? $form_state->getValue('date_from')
      : $form_state->getValue('date_time_from');
    $from = $this->convertToTimestamp($from, 'from', $period_type === 'date_time');
    if ($from) {
      $application->set('from', $from);
    }

    $to = $period_type === 'date'
      ? $form_state->getValue('date_to')
      : $form_state->getValue('date_time_to');
    $to = $this->convertToTimestamp($to, 'to', $period_type === 'date_time');
    if ($to) {
      $application->set('to', $to);
    }

    if ($save) {
      $application->save();
    }
    return $application;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $application = $this->createApplication($form_state);
    $errors = $application->validateApplication();

    if (!empty($errors)) {
      foreach ($errors as $field => $error) {
        $known_field = FALSE;
        if ($field === 'from') {
          $known_field = TRUE;
          $period_type = $form_state->getValue('period_type');
          if ($period_type === 'date') {
            $field = 'date_from';
          }
          else {
            $field = 'date_time_from';
          }
        }

        if ($field === 'to') {
          $known_field = TRUE;
          $period_type = $form_state->getValue('period_type');
          if ($period_type === 'date') {
            $field = 'date_to';
          }
          else {
            $field = 'date_time_to';
          }
        }

        $form_state->setErrorByName($field, $error);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->getValue('confirm')) {
      $this->messenger()->addError($this->t('Something went wrong. Try again.'));
      $form_state->setRebuild(TRUE);
      return;
    }

    try {
      $application = $this->createApplication($form_state, TRUE);
      $this->messenger()->addStatus($this->t('@label created.', ['@label' => $application->label()]));

      // Notify the mentors or the expected users.
      $mail_map = [];

      $subject = $this->t('New leave application');
      $message = $this->t('@label created by @user.', ['@label' => $application->label(), '@user' => $this->currentUser()->getDisplayName()]);
      $handle_suffix = PHP_EOL . PHP_EOL . $this->t('Login to @url to handle the application.', ['@url' => Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString()]);

      $student = $application->get('student')->entity;

      /** @var UserInterface $mentor */
      foreach ($student->get('field_mentor')->referencedEntities() as $mentor) {
        if  ($mentor->id() !== $this->currentUser()->id()) {
          if ($mail = $this->emailService->getUserEmail($mentor)) {
            $mail_map[$mail] = $message;
            if ($application->access('handle', $mentor)) {
              $mail_map[$mail] .= $handle_suffix;
            }
          }
        }
      }

      if (empty($mail_map)) {
        $principles = $this->entityTypeManager->getStorage('user')->loadByProperties(['status' => 1, 'roles' => 'principle']);
        foreach ($principles as $principle) {
          if ($mail = $this->emailService->getUserEmail($principle)) {
            $mail_map[$mail] = $message . $handle_suffix;
          }
        }
      }

      $options = [
        'maillog_mail_type' => SsrMaillogInterface::MAILLOG_TYPE_LEAVE_APPLICATION,
        'no_reply_to' => TRUE,
      ];
      foreach ($mail_map as $mail => $message) {
        $this->emailService->sendMail($mail, $subject, $message, $options);
      }


      $this->messenger()->addStatus($this->t('The school has been notified.'));
    } catch (\Exception $e) {
      $this->messenger()->addError($this->t('Something went wrong. Try again.'));
      $form_state->setRebuild(TRUE);
      return;
    }

  }

  public function access(AccountInterface $account, ?UserInterface $user = NULL): AccessResultInterface {
    $student = $user;
    if (!$student instanceof UserInterface) {
      return AccessResult::forbidden();
    }
    if (!$student->hasRole('student')) {
      return AccessResult::forbidden()->addCacheableDependency($student);
    }

    return $student->access('caregiver_access', $account, TRUE);
  }

}
