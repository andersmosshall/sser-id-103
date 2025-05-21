<?php

namespace Drupal\simple_school_reports_leave_application\EventSubscriber;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\simple_school_reports_consents\Service\ConsentsServiceServiceInterface;
use Drupal\simple_school_reports_extension_proxy\Events\NotificationMessagesEvent;
use Drupal\simple_school_reports_extension_proxy\Events\SsrEvents;
use Drupal\simple_school_reports_leave_application\Service\LeaveApplicationServiceInterface;
use Drupal\unionen_debug\Events\UserDebugInfoCollectorEvent;
use Drupal\unionen_personalisation\Service\UnionenPersonalisationServiceInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for notification messages.
 */
class NotificationMessagesSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;


  public function __construct(
    protected LeaveApplicationServiceInterface $leaveApplicationService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[SsrEvents::NOTIFICATION_MESSAGES][] = 'addNotificationMessage';
    return $events;
  }

  public function addNotificationMessage(NotificationMessagesEvent $event) {
    $event->addInformationMessage('unhandled_student_leave_applications:' . $event->getCurrentUser()->id(), function () use ($event) {
      $cache = new CacheableMetadata();
      $cache->addCacheTags(['user_list:student', 'ssr_student_leave_application_list']);
      $application_ids = $this->leaveApplicationService->getStudentLeaveApplicationIdsToHandle($event->getCurrentUser()->id());
      if (empty($application_ids)) {
        return [NULL, $cache];
      }

      $link = Link::createFromRoute($this->t('here'),'view.pending_leave_applications.pending', [], ['query' => ['id' => 'me']])->toString();
      $message= $this->t('You have leave applications to handle. You can handle them @link.', ['@link' => $link]);

      return [
        $message,
        $cache,
      ];
    });
  }

}
