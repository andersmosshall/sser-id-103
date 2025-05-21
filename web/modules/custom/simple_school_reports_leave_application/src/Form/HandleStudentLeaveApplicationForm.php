<?php

namespace Drupal\simple_school_reports_leave_application\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\simple_school_reports_core\AbsenceDayHandler;
use Drupal\simple_school_reports_core\Service\AbsenceStatisticsService;
use Drupal\simple_school_reports_core\Service\AbsenceStatisticsServiceInterface;
use Drupal\simple_school_reports_core\Service\EmailService;
use Drupal\simple_school_reports_core\Service\EmailServiceInterface;
use Drupal\simple_school_reports_core\Service\MessageTemplateServiceInterface;
use Drupal\simple_school_reports_core\Service\ReplaceTokenServiceInterface;
use Drupal\simple_school_reports_core\Service\TermServiceInterface;
use Drupal\simple_school_reports_entities\StudentLeaveApplicationInterface;
use Drupal\simple_school_reports_leave_application\Service\LeaveApplicationServiceInterface;
use Drupal\simple_school_reports_maillog\SsrMaillogInterface;
use Drupal\user\UserInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a confirmation form to handle student leave application.
 */
class HandleStudentLeaveApplicationForm extends ConfirmFormBase {



  public function __construct(
    protected EmailServiceInterface $emailService,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected TermServiceInterface $termService,
    protected AbsenceStatisticsServiceInterface $absenceStatisticsService,
    protected LeaveApplicationServiceInterface $leaveApplicationService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('simple_school_reports_core.email_service'),
      $container->get('entity_type.manager'),
      $container->get('simple_school_reports_core.term_service'),
      $container->get('simple_school_reports_core.absence_statistics'),
      $container->get('simple_school_reports_leave_application.leave_application_service'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'handle_student_leave_application';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Handle leave application');
  }

  public function getCancelRoute() {
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
    return $this->t('Save');
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
  public function buildForm(array $form, FormStateInterface $form_state, ?StudentLeaveApplicationInterface $ssr_student_leave_application = NULL) {
    $application = $ssr_student_leave_application;
    if (!$application instanceof StudentLeaveApplicationInterface) {
      throw new NotFoundHttpException();
    }

    $student = $application->get('student')->entity;
    if (!$student instanceof UserInterface) {
      throw new NotFoundHttpException();
    }

    $form['ssr_student_leave_application_last_changed'] = [
      '#type' => 'hidden',
      '#default_value' => $application->getChangedTime(),
    ];

    $form['application_id'] = [
      '#type' => 'value',
      '#value' => $application->id(),
    ];

    $form['application_info'] = $this->entityTypeManager->getViewBuilder('ssr_student_leave_application')->view($application);

    $absence_from = $this->termService->getDefaultSchoolYearStart();
    $absence_to = $this->termService->getDefaultSchoolYearEnd();

    $absence_statistics = $this->absenceStatisticsService->getAllAbsenceDayData($absence_from->getTimestamp(), $absence_to->getTimestamp());
    $current_absence_days = 0;

    $form['absence_stats'] = [
      '#type' => 'details',
      '#title' => $this->t('Absence statistics for @student from @from - @to', [
        '@student' => $student->getDisplayName(),
        '@from' => $absence_from->format('Y-m-d'),
        '@to' => $absence_to->format('Y-m-d'),
      ]),
      '#open' => TRUE,
    ];

    foreach ($absence_statistics as $value => $uids) {
      if (in_array($student->id(), $uids)) {
        $current_absence_days = $value;
        break;
      }
    }

    $formatted_absence_day =  number_format($current_absence_days, 2, ',', ' ');

    $statistics_link = Link::createFromRoute($this->t('@student statistics', ['@student' => $student->getDisplayName()]), 'simple_school_reports_core.student_statistics', ['user' => $student->id()])->toString();
    $form['absence_stats']['info'] = [
      '#markup' => $this->t('For more detailed statistics, see @link', ['@link' => $statistics_link]),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];

    $form['absence_stats']['current_absence'] = [
      '#markup' => '<div class="field field--label-above"><div class="field__label">' . $this->t('Absence days') . '</div><div class="field__item">' . $formatted_absence_day . ' ' . $this->t('days') . '</div></div>',
    ];

    $total_leave_days = $current_absence_days + $application->get('leave_days')->value;
    if ($total_leave_days > 14) {
      $form['absence_stats']['warning'] = [
        '#markup' => $this->t('The student has relative high absence when summarize previous absence with days from this application. Depending on the school policy the application may need to be handled by a principle.'),
        '#prefix' => '<p></p><p>',
        '#suffix' => '</p>',
      ];
    }

    $form['application_state'] = [
      '#type' => 'radios',
      '#title' => $this->t('Handle application'),
      '#options' => [
        'approved' => $this->t('I approve the leave application'),
        'rejected' => $this->t('I reject the leave application'),
      ],
      '#required' => TRUE,
    ];

    $form['handler_notes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Handler notes'),
      '#description' => $this->t('Note that these handler notes is included in the email sent to the caregivers if set to do so.'),
    ];

    $form['notify_caregivers'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Notify caregivers'),
      '#default_value' => TRUE,
    ];

    if ($this->currentUser()->id() == $application->getOwnerId()) {
      $form['confirm_same_user'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('I confirm that I want to handle this application even though I am the creator of the application.'),
        '#required' => TRUE,
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $application_id = $form_state->getValue('application_id');
    if (empty($application_id)) {
      $form_state->setError($form, $this->t('Something went wrong. Try again.'));
      return;
    }

    $application = $this->entityTypeManager->getStorage('ssr_student_leave_application')->load($application_id);
    if (!$application instanceof StudentLeaveApplicationInterface) {
      $form_state->setError($form, $this->t('Something went wrong. Try again.'));
      return;
    }

    $ssr_meeting_last_changed = $form_state->getValue('ssr_student_leave_application_last_changed');
    if ($application->getChangedTime() != $ssr_meeting_last_changed) {
      $form_state->setError($form, $this->t('This content has been modified by another user, please reload the page and try again.'));
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
      // Initialize batch (to set title).
      $batch = [
        'title' => $this->t('Handle leave application mails'),
        'init_message' => $this->t('Handle leave application mails'),
        'progress_message' => $this->t('Processed @current out of @total.'),
        'finished' => [self::class, 'finished'],
        'operations' => [],
      ];

      $application_id = $form_state->getValue('application_id');
      $state = $form_state->getValue('application_state');
      $handler_notes = $form_state->getValue('handler_notes');
      $handler = $this->currentUser()->id();
      $notify_caregivers = $form_state->getValue('notify_caregivers');

      if ($state === 'approved') {
        $application = $this->entityTypeManager->getStorage('ssr_student_leave_application')->load($application_id);

        $from_date = NULL;
        $to_date = NULL;

        $from = $application->get('from')->value;
        $to = $application->get('to')->value;

        if ($from && $to && $from > $to) {
          $from_date = (new \DateTime())->setTimestamp((int) $to);
          $to_date = (new \DateTime())->setTimestamp((int) $from);
        }
        elseif ($from && $to) {
          $from_date = (new \DateTime())->setTimestamp((int) $from);
          $to_date = (new \DateTime())->setTimestamp((int) $to);
        }

        /** @var \Drupal\user\UserInterface | null $student */
        $student = $application->get('student')->entity;

        if ($student && $from_date && $to_date) {
          $absence_type = 'leave';
          $values = [];

          $values[] = [
            'field_absence_from' => $from_date->getTimestamp(),
            'date_string' => $from_date->format('Y-m-d'),
            'field_absence_type' => $absence_type,
          ];

          $date_walk_from = clone $from_date;
          $date_walk_to = clone $from_date;

          $date_walk_from->setTime(0,0);
          $date_walk_to->setTime(23, 59, 59);

          $i = 1;

          while ($date_walk_to < $to_date) {
            $values[$i - 1]['field_absence_to'] = $date_walk_to->getTimestamp();
            $values[$i - 1]['field_absence_to_debug'] = $date_walk_to->format('Y-m-d H:i:s');

            $date_walk_from->add(new \DateInterval('P1D'));
            $date_walk_to->add(new \DateInterval('P1D'));
            $values[] = [
              'field_absence_from' => $date_walk_from->getTimestamp(),
              'date_string' => $date_walk_from->format('Y-m-d'),
              'field_absence_type' => $absence_type,
            ];
            $i++;
          }

          unset($values[$i]);
          $values[$i - 1]['field_absence_to'] = $to_date->getTimestamp();

          foreach ($values as $field_values) {
            $field_values['field_student'] = ['target_id' => $student->id()];
            $field_values['title'] = 'Dagsfrånvaro ' . $student->getDisplayName() . ' ' . $field_values['date_string'];
            $field_values['uid'] = $handler;

            $batch['operations'][] = [[AbsenceDayHandler::class, 'createAbsenceDayNode'], [$field_values]];
          }
          $this->messenger()->addStatus($this->t('Absence registered'));
        }
      }

      $batch['operations'][] = [[$this::class, 'setHandled'], [$application_id, $state, $handler_notes, $handler]];
      if ($notify_caregivers) {
        $batch['operations'][] = [[$this::class, 'notifyCaregivers'], [$application_id, $handler_notes]];
      }

      if (!empty($batch['operations'])) {
        if (count($batch['operations']) < 10) {
          $batch['progressive'] = FALSE;
        }
        batch_set($batch);
      }
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Something went wrong. Try again.'));
      $form_state->setRebuild(TRUE);
      return;
    }
  }

  public static function setHandled(string $application_id, string $state, ?string $handler_notes, string $handler, array &$context) {
    $application = \Drupal::entityTypeManager()->getStorage('ssr_student_leave_application')->load($application_id);
    if (!$application instanceof StudentLeaveApplicationInterface) {
      return;
    }
    $application->set('state', $state);
    $application->set('handled_by', ['target_id' => $handler]);
    $application->set('field_handler_notes', $handler_notes);
    $application->save();
  }

  public static function notifyCaregivers(string $application_id, ?string $handler_notes, array &$context) {
    $application = \Drupal::entityTypeManager()->getStorage('ssr_student_leave_application')->load($application_id);
    if (!$application instanceof StudentLeaveApplicationInterface) {
      return;
    }

    $student = $application->get('student')->entity;
    if (!$student instanceof UserInterface) {
      return;
    }

    $state = $application->get('state')->value;
    if ($state === 'pending') {
      return;
    }

    $handler = $application->get('handled_by')->entity ?? \Drupal::currentUser()->getAccount();

    $message = t('@label has been @state by @handler.', [
      '@label' => $application->label(),
      '@state' => $state === 'approved' ? t('approved') : t('rejected'),
      '@handler' => $handler->getDisplayName(),
    ]);

    if (!empty($handler_notes)) {
      $message .= PHP_EOL . PHP_EOL . t('Handler notes') . ':' . PHP_EOL . $handler_notes;
    }

    /** @var \Drupal\simple_school_reports_core\Service\EmailServiceInterface $email_service */
    $email_service = \Drupal::service('simple_school_reports_core.email_service');

    $recipient_data = $email_service->getCaregiverRecipients($student->id());

    if (empty($recipient_data)) {
      return;
    }

    foreach ($recipient_data as $caregiver_uid => $caregiver_mail_data) {
      $replace_context = [];

      $options = [
        'maillog_student_context' => $student->id(),
        'maillog_mail_type' => SsrMaillogInterface::MAILLOG_TYPE_LEAVE_APPLICATION,
      ];

      $subject = t('Leave application for @student', [
        '@student' => $student->getDisplayName(),
      ]);

      EmailService::batchSendMail($caregiver_mail_data['mail'], $subject, $message, $replace_context, [], $options, $context);
    }
  }

  public static function finished($success, $results) {
    if (!$success) {
      \Drupal::messenger()->addError(t('Something went wrong'));
      return;
    }

    if (!empty($results['mail_sent'])) {
      \Drupal::messenger()->addStatus(t('@count mails has been sent.', ['@count' => count($results['mail_sent'])]));
    }

    \Drupal::messenger()->addStatus(t('Leave application has been handled.'));
  }

  public function access(AccountInterface $account, ?StudentLeaveApplicationInterface $ssr_student_leave_application = NULL): AccessResult {
    $application = $ssr_student_leave_application;
    if (!$application instanceof StudentLeaveApplicationInterface) {
      return AccessResult::forbidden();
    }

    $cache = new CacheableMetadata();
    $cache->addCacheableDependency($application);
    $cache->addCacheContexts(['user']);

    $access = $application->access('handle', $account, TRUE);
    return $access->addCacheableDependency($cache);
  }
}
