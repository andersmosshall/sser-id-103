<?php

namespace Drupal\simple_school_reports_student_di\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\State\StateInterface;
use Drupal\simple_school_reports_core\Service\EmailServiceInterface;
use Drupal\simple_school_reports_maillog\SsrMaillogInterface;
use Drupal\simple_school_reports_student_di\Service\StudentDiMeetingsServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Remind attendees of meeting teacher.
 *
 * @QueueWorker(
 *   id = "student_di_meeting_reminder_teacher",
 *   title = @Translation("Remind teacher of meetings this day"),
 *   cron = {"time" = 60}
 * )
 */
class StudentDiMeetingReminderTeacher extends QueueWorkerBase implements ContainerFactoryPluginInterface  {

  /**
   * Construct a new StudentDiMeetingReminder.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param array $plugin_definition
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\simple_school_reports_student_di\Service\StudentDiMeetingsServiceInterface $meetingsService
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected StudentDiMeetingsServiceInterface $meetingsService,
    protected EmailServiceInterface $emailService,
    protected LoggerInterface $logger,
    protected StateInterface $state,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('simple_school_reports_student_di.meetings_service'),
      $container->get('simple_school_reports_core.email_service'),
      $container->get('logger.factory')->get('meeting_reminder'),
      $container->get('state'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if (empty($data)) {
      return;
    }

    try {
      if (empty($data['teacher_id']) || empty($data['meetings'])) {
        return;
      }

      $today = (new \DateTime())->format('Y-m-d');
      $state_value = $today . ':' . $data['teacher_id'];
      $current_state = $this->state->get('ssr_meeting_reminder_teacher', '');
      if ($current_state === $state_value) {
        return;
      }
      $this->state->set('ssr_meeting_reminder_teacher', $state_value);

      /** @var \Drupal\user\UserInterface|null $teacher */
      $teacher = $this->entityTypeManager->getStorage('user')->load($data['teacher_id']);
      if (!$teacher) {
        return;
      }

      $email = $this->emailService->getUserEmail($teacher);
      if (!$email) {
        return;
      }

      $meetings = $this->entityTypeManager->getStorage('ssr_meeting')->loadMultiple($data['meetings']);
      if (empty($meetings)) {
        return;
      }

      $now = new \DateTime();
      $subject = 'Utvecklingssamtal - ' . $now->format('Y-m-d');
      $message = 'Utvecklingssamtal idag:' . PHP_EOL . PHP_EOL;

      $meeting_messages = [];
      /** @var \Drupal\simple_school_reports_entities\SsrMeetingInterface $meeting */
      foreach ($meetings as $meeting) {
        $meeting_data = $this->meetingsService->getMeetingData($meeting->id());
        $round = $meeting_data['round_id']
          ? $this->entityTypeManager->getStorage('node')->load($meeting_data['round_id'])
          : NULL;

        if (!$round || !$round->get('field_remind_teacher')->value) {
          continue;
        }

        $meeting_messages[] = $this->meetingsService->getMeetingReminderMessage($meeting);
      }

      if (empty($meeting_messages)) {
        return;
      }

      $separator = PHP_EOL . PHP_EOL . '*** *** *** *** ***' . PHP_EOL . PHP_EOL;
      $message .= implode($separator, $meeting_messages);

      $options = [
        'maillog_mail_type' => SsrMaillogInterface::MAILLOG_TYPE_MEETING_REMINDER,
        'no_reply_to' => TRUE,
      ];
      $this->emailService->sendMail($email, $subject, $message, $options);
    }
    catch (\Exception $e) {
      // Skip the meeting.
      $this->logger->error('Failed to send teacher remind message @message', ['@message' => $e->getMessage()]);
    }
  }
}
