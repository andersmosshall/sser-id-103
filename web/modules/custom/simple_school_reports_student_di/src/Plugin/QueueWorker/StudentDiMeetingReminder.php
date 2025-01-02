<?php

namespace Drupal\simple_school_reports_student_di\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\simple_school_reports_core\Service\EmailServiceInterface;
use Drupal\simple_school_reports_maillog\SsrMaillogInterface;
use Drupal\simple_school_reports_student_di\Service\StudentDiMeetingsServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Remind attendees of meeting.
 *
 * @QueueWorker(
 *   id = "student_di_meeting_reminder",
 *   title = @Translation("Remind attendees of meeting"),
 *   cron = {"time" = 60}
 * )
 */
class StudentDiMeetingReminder extends QueueWorkerBase implements ContainerFactoryPluginInterface  {

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
      $meeting_data = $this->meetingsService->getMeetingData($data);
      if (!$meeting_data['round_id']) {
        return;
      }

      /** @var \Drupal\node\NodeInterface|null $round */
      $round = $this->entityTypeManager->getStorage('node')->load($meeting_data['round_id']);
      if (!$round) {
        return;
      }

      // Reminder limits in hours.
      $remind_limits = [];
      foreach ($round->get('field_caregiver_reminder_setting')->getValue() as $value) {
        if (is_array($value)) {
          $value = $value['value'];
        }
        if (is_numeric($value) && $value > 0) {
          $remind_limits[] = $value;
        }
      }
      if (empty($remind_limits)) {
        return;
      }
      sort($remind_limits);

      /** @var \Drupal\simple_school_reports_entities\SsrMeetingInterface|null $meeting */
      $meeting = $this->entityTypeManager->getStorage('ssr_meeting')->load($data);
      if (!$meeting) {
        return;
      }

      $now = time() + 15 * 60;
      $meeting_start = $meeting->get('from')->value;

      $meta_keys = [];
      $meta_values = $meeting->get('meta')->getValue();

      foreach ($meta_values as $meta) {
        if (is_array($meta)) {
          $meta = $meta['value'];
        }
        $meta_keys[$meta] = $meta;
      }

      $student = $meeting->get('field_student')->target_id;

      $send_reminder = FALSE;
      $meta_key = '';
      foreach ($remind_limits as $limit) {
        $meta_key = 'reminder:' . $student . ':' . $meeting_start . ':' . $limit;
        if (isset($meta_keys[$meta_key])) {
          return;
        }

        $diff = $limit * 60 * 60;
        if ($now + $diff > $meeting_start) {
          $send_reminder = TRUE;
          break;
        }
      }

      if (!$send_reminder) {
        return;
      }

      /** @var \Drupal\node\NodeInterface|null $round */
      $group = $this->entityTypeManager->getStorage('node')->load($meeting_data['group_id']);
      if (!$group) {
        return;
      }

      $teachers = array_column($meeting->get('field_teachers')->getValue(), 'target_id');
      $student = $meeting->get('field_student')->target_id;

      if (!$student) {
        return;
      }

      /** @var \Drupal\user\UserInterface|null $student_object */
      $student_object = $this->entityTypeManager->getStorage('user')->load($student);
      if (!$student_object) {
        return;
      }

      $attending = array_column($meeting->get('attending')->getValue(), 'target_id');
      $send_to = [];

      if ($group->get('field_remind_student')->value) {
        $send_to[] = $student;
      }

      foreach ($attending as $uid) {
        if ($uid == $student) {
          continue;
        }

        if (in_array($uid, $teachers)) {
          continue;
        }

        $send_to[] = $uid;
      }

      if (!empty($send_to)) {
        $message = $this->meetingsService->getMeetingReminderMessage($meeting);
        $subject = 'Påminnelse om utvecklingssamtal - ' . $student_object->getDisplayName();

        foreach ($send_to as $recipient_uid) {
          /** @var \Drupal\user\UserInterface|null $recipient */
          $recipient = $this->entityTypeManager->getStorage('user')->load($recipient_uid);
          if (!$recipient) {
            continue;
          }
          $email = $this->emailService->getUserEmail($recipient);
          if (!$email) {
            continue;
          }

          $options = [
            'maillog_mail_type' => SsrMaillogInterface::MAILLOG_TYPE_MEETING_REMINDER,
            'no_reply_to' => TRUE,
          ];

          try {
            $this->emailService->sendMail($email, $subject, $message, $options);
          }
          catch (\Exception $e) {
            $this->logger->error('Failed to send meeting reminder to @email: @message', ['@email' => $email, '@message' => $e->getMessage()]);
          }
        }
      }



      // Send to caregivers and/or student.
      $meta_values[] = $meta_key;
      $meeting->set('meta', $meta_values);
      $meeting->setKeepListCache(TRUE);
      $meeting->save();
    }
    catch (\Exception $e) {
      // Skip the meeting.
      $this->logger->error('Failed to remind of meeting @id: @message', ['@id' => $data, '@message' => $e->getMessage()]);
    }
  }
}
