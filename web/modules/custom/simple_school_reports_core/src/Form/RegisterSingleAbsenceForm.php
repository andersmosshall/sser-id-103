<?php

namespace Drupal\simple_school_reports_core\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\simple_school_reports_core\AbsenceDayHandler;
use Drupal\simple_school_reports_core\Service\EmailService;
use Drupal\simple_school_reports_core\Service\UserMetaDataServiceInterface;
use Drupal\simple_school_reports_maillog\SsrMaillogInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a confirmation form for cancelling multiple user accounts.
 */
class RegisterSingleAbsenceForm extends RegisterMultipleAbsenceForm {

  /**
   * @var \Drupal\simple_school_reports_core\Service\EmailServiceInterface
   */
  protected $emailService;

  protected UserMetaDataServiceInterface $userMetaDataService;

  protected TimeInterface $time;

  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->emailService = $container->get('simple_school_reports_core.email_service');
    $instance->userMetaDataService = $container->get('simple_school_reports_core.user_meta_data');
    $instance->time = $container->get('datetime.time');
    return $instance;
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'register_single_absence_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?UserInterface $user = NULL) {
    if (!$user || !$user->hasRole('student')) {
      $this->logger('register_absence_form')->error('No student found.');
      throw new NotFoundHttpException('no student user');
    }
    $form = parent::buildForm($form, $form_state, $user);

    $mentors = [];
    $recipients = [];

    /** @var UserInterface $mentor */
    foreach ($user->get('field_mentor')->referencedEntities() as $mentor) {
      if  ($mentor->id() !== $this->currentUser()->id()) {
        if ($mail = $this->emailService->getUserEmail($mentor)) {
          $mentors[] = $mentor->getDisplayName();
          $recipients[] = $mail;
        }
      }
    }

    $form['student_uid'] = [
      '#type' => 'value',
      '#value' => $user->id(),
    ];

    if (!empty($mentors)) {
      $form['recipients'] = [
        '#type' => 'value',
        '#value' => $recipients,
      ];

      $form['mentor_mail'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Message') . ' (' . $this->t('Not obligatory') . ')',
        '#default_value' => '',
        '#description' => $this->t('Mail will be sent to mentor; @mentors', ['@mentors' => implode(', ', $mentors)]),
      ];
    }

    $caregivers_mail = [];
    $caregivers = [];
    foreach ($this->userMetaDataService->getCaregivers($user, TRUE) as $caregiver) {
      if ($mail = $this->emailService->getUserEmail($caregiver)) {
        $caregivers[] = $caregiver->getDisplayName();
        $caregivers_mail[] = $mail;
      }
    }

    if (!empty($caregivers)) {
      if ($this->currentUser()->hasPermission('school staff permissions')) {
        $form['send_to_caregivers'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Notify caregivers'),
          '#default_value' => TRUE,
          '#description' => $this->t('Mail with information about this registration will be sent to @caregivers', ['@caregivers' => implode(', ', $caregivers)]),
        ];
      }
      else {
        $form['send_to_caregivers'] = [
          '#type' => 'value',
          '#value' => TRUE,
        ];
      }
    }
    else {
      $form['send_to_caregivers'] = [
        '#type' => 'value',
        '#value' => FALSE,
      ];
    }

    $form['caregivers_mail'] = [
      '#type' => 'value',
      '#value' => $caregivers_mail,
    ];

    return $form;
  }

  protected function resetPostCheckFlag(): void {
    $this->tempStoreFactory->get('ssr_post_check')->delete('ssr_check_absence_day_user');

  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($this->skipValidation($form_state)) {
      return;
    }
    parent::validateForm($form, $form_state);

    $form_state->set('has_absence_node', FALSE);
    $accounts = $form_state->getValue('accounts', []);

    if (count($accounts) !== 1) {
      $form_state->setError($form, $this->t('Something went wrong.'));
      $this->resetPostCheckFlag();
      return;
    }

    $uid = current(array_keys($accounts));

    $skip_date_validation = $this->getRequest()->query->get('skip_date_validation');
    if ($skip_date_validation) {
      return;
    }

    if (empty($form_state->getErrors())) {
      /** @var $from_date \DateTime */
      /** @var $to_date \DateTime */
      $this->resolveFromToDate($from_date, $to_date, $form_state);

      $limit_from = $from_date->getTimestamp();
      $limit_to = $to_date->getTimestamp();

      if ($from_date->format('Y-m-d') === $to_date->format('Y-m-d')) {
        /** @var \Drupal\node\NodeInterface[] $absence_nodes */
        $absence_nodes = AbsenceDayHandler::getAbsenceNodesFromPeriod([$uid], $limit_from, $limit_to, FALSE);
        $has_absence_node = FALSE;
        $changed_limit = $this->time->getRequestTime() - 300;
        foreach ($absence_nodes as $absence_node) {
          if (
            $absence_node->get('field_absence_from')->value === (string) $limit_from &&
            $absence_node->get('field_absence_to')->value === (string) $limit_to &&
            $absence_node->getChangedTime() > $changed_limit &&
            $absence_node->getOwnerId() === $this->currentUser()->id()
          ) {
            $has_absence_node = TRUE;
            $form_state->set('has_absence_node', TRUE);
          }
        }

        if (!$has_absence_node && !empty($absence_nodes)) {
          $form_state->setError($form, $this->t('There are already absence registrations that partly or completely includes selected absence period.'));
        }
      }
      else {
        $absence_nids = AbsenceDayHandler::getAbsenceNodesFromPeriod([$uid], $limit_from, $limit_to, TRUE);
        if (!empty($absence_nids)) {
          $form_state->setError($form, $this->t('There are already absence registrations that partly or completely includes selected absence period.'));
        }
      }
    }

    if (!empty($form_state->getErrors())) {
      $this->resetPostCheckFlag();
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($this->earlyReturnSubmit($form_state)) {
      $this->resetPostCheckFlag();
      return;
    }

    if ($form_state->get('has_absence_node')) {
      $this->messenger()->addStatus($this->t('Absence registered'));
      $this->resetPostCheckFlag();
      return;
    }

    parent::submitForm($form, $form_state);

    if (!$form_state->getValue('confirm')) {
      $this->logger('confirm_form')->error('Confirm issue!');
      $this->messenger()->addError($this->t('Something went wrong. Try again.'));
      $form_state->setRebuild(TRUE);
      return;
    }

    $recipients = $form_state->getValue('recipients', []);
    $message = $form_state->getValue('mentor_mail', '');
    $student_name = current($form_state->getValue('accounts', ['']));

    // @todo add absence info.
    $this->resolveFromToDate($from_date, $to_date, $form_state);
    $absence_type = $form_state->getValue('absence_type');
    $absence_options = [
      'reported' => $this->t('Reported absence'),
      'leave' => $this->t('Leave absence'),
    ];

    if (isset($absence_options[$absence_type])) {
      $absence_type = $absence_options[$absence_type];
    }

    $absence_info = $this->t('Absence has been registered to @student_name by @current_user', ['@student_name' => $student_name, '@current_user' => $this->currentUser()->getDisplayName()]) . ':' . PHP_EOL . $this->t('@from - @to (@type)', [
      '@from' => $from_date->format('Y-m-d H:i'),
      '@to' => $to_date->format('Y-m-d H:i'),
      '@type' => $absence_type,
    ]);


    // Initialize batch (to set title).
    $batch = [
      'title' => $this->t('Sending mails'),
      'init_message' => $this->t('Sending mails'),
      'progress_message' => $this->t('Processed @current out of @total.'),
      'operations' => [],
    ];

    if (!empty($recipients)) {
      $mentor_message = $absence_info;
      if (!empty($message)) {
        $mentor_message .= PHP_EOL . PHP_EOL . $message;
      }

      $subject = 'Registrering av frånvaro för ' . $student_name;

      foreach ($recipients as $recipient) {
        $options = [
          'maillog_student_context' => $form_state->getValue('student_uid'),
          'maillog_mail_type' => SsrMaillogInterface::MAILLOG_TYPE_MAIL_MENTOR,
        ];

        $batch['operations'][] = [[EmailService::class, 'batchSendMail'], [$recipient, $subject, $mentor_message, [], [], $options]];
      }
    }

    if ($form_state->getValue('send_to_caregivers')) {
      $caregivers_mail = $form_state->getValue('caregivers_mail', []);
      $caregiver_message = $absence_info;
      $subject = 'Registrering av frånvaro för ' . $student_name;

      foreach ($caregivers_mail as $recipient) {
        $options = [
          'maillog_student_context' => $form_state->getValue('student_uid'),
          'maillog_mail_type' => SsrMaillogInterface::MAILLOG_TYPE_CAREGIVER,
          'no_reply_to' => TRUE,
        ];

        $batch['operations'][] = [[EmailService::class, 'batchSendMail'], [$recipient, $subject, $caregiver_message, [], [], $options]];
      }
    }


    if (!empty($batch['operations'])) {
      if (count($batch['operations']) < 10) {
        $batch['progressive'] = FALSE;
      }
      else {
        $batch['op_delay'] = 500;
      }
      batch_set($batch);
    }
  }
}
