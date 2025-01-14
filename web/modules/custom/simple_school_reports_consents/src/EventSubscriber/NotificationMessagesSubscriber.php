<?php

namespace Drupal\simple_school_reports_consents\EventSubscriber;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\simple_school_reports_consents\Service\ConsentsServiceServiceInterface;
use Drupal\simple_school_reports_extension_proxy\Events\NotificationMessagesEvent;
use Drupal\simple_school_reports_extension_proxy\Events\SsrEvents;
use Drupal\unionen_debug\Events\UserDebugInfoCollectorEvent;
use Drupal\unionen_personalisation\Service\UnionenPersonalisationServiceInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for notification messages.
 */
class NotificationMessagesSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;


  public function __construct(
    protected ConsentsServiceServiceInterface $consentsService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[SsrEvents::NOTIFICATION_MESSAGES][] = 'addNotificationMessage';
    return $events;
  }

  public function addNotificationMessage(NotificationMessagesEvent $event) {
    $event->addInformationMessage('unhandled_consents:' . $event->getCurrentUser()->id(), function () use ($event) {
      $cache = new CacheableMetadata();
      $cache->addCacheTags(['user_list', 'ssr_consent_answer_list', 'node_list:consent']);
      $consents = $this->consentsService->getUnHandledConsentIds($event->getCurrentUser()->id());
      if (empty($consents)) {
        return [NULL, $cache];
      }

      $link = Link::createFromRoute($this->t('here'),'simple_school_reports_consents.user_consents_page', ['user' => $event->getCurrentUser()->id()])->toString();
      $message= $this->t('You have consents to answer. You can handle your consents @link.', ['@link' => $link]);

      return [
        $message,
        $cache,
      ];
    });
  }

}
