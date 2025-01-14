<?php

namespace Drupal\simple_school_reports_student_di\EventSubscriber;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\simple_school_reports_consents\Service\ConsentsServiceServiceInterface;
use Drupal\simple_school_reports_core\Service\UserMetaDataServiceInterface;
use Drupal\simple_school_reports_extension_proxy\Events\NotificationMessagesEvent;
use Drupal\simple_school_reports_extension_proxy\Events\SsrEvents;
use Drupal\simple_school_reports_student_di\Service\StudentDiMeetingsServiceInterface;
use Drupal\unionen_debug\Events\UserDebugInfoCollectorEvent;
use Drupal\unionen_personalisation\Service\UnionenPersonalisationServiceInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for notification messages.
 */
class NotificationMessagesSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;


  public function __construct(
    protected StudentDiMeetingsServiceInterface $studentDiMeetingsService,
    protected UserMetaDataServiceInterface $userMetaDataService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[SsrEvents::NOTIFICATION_MESSAGES][] = 'addNotificationMessage';
    return $events;
  }

  protected function returnNoMessage(CacheableMetadata $cache): array {
    return [NULL, $cache];
  }

  public function addNotificationMessage(NotificationMessagesEvent $event) {
    $event->addInformationMessage('student_di_meetings_to_book:' . $event->getCurrentUser()->id(), function () use ($event) {
      $uid = $event->getCurrentUser()->id();
      $cache = new CacheableMetadata();
      $cache->addCacheTags(['user:' . $uid, 'ssr_meeting_list']);

      if (!in_array('caregiver', $event->getCurrentUser()->getRoles())) {
        return $this->returnNoMessage($cache);
      }
      $caregiving_students = $this->userMetaDataService->getCaregiverStudentsData($uid);
      if (empty($caregiving_students)) {
        return $this->returnNoMessage($cache);
      }

      $messages = [];
      // Todo use connection to check for meetings.

      foreach ($caregiving_students as $student_id => $data) {
        foreach ($this->studentDiMeetingsService->getStudentGroupIds($student_id) as $group_id) {
          if (!empty($this->studentDiMeetingsService->getBookedMeetingIds($student_id, $group_id))) {
            continue;
          }
          if (!empty($this->studentDiMeetingsService->getAvailableMeetingIds($student_id, $group_id, TRUE, TRUE))) {
            $link = Link::createFromRoute($this->t('here'),'simple_school_reports_student_di.di_user_tab', ['user' => $student_id])->toString();
            $messages[] = $this->t('There are development meetings available for @name to book. You may book meeting @link.', ['@name' => $data['name'], '@link' => $link]);
            break 2;
          }
        }
      }

      if (!empty($messages)) {
        return [
          $messages,
          $cache,
        ];
      }

      return $this->returnNoMessage($cache);
    });
  }

}
