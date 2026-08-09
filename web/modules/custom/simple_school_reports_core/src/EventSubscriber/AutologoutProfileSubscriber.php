<?php

namespace Drupal\simple_school_reports_core\EventSubscriber;

use Drupal\autologout_alterable\Events\AutologoutEvents;
use Drupal\autologout_alterable\Events\AutologoutProfileAlterEvent;
use Drupal\Core\Site\Settings;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Defines LoginRedirectSubscriber Subscriber.
 */
class AutologoutProfileSubscriber implements EventSubscriberInterface {

  public function __construct() {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {

    $events[AutologoutEvents::AUTOLOGOUT_PROFILE_ALTER][] = 'onProfileAlter';
    return $events;
  }

  /**
   * @param \Drupal\autologout_alterable\Events\AutologoutProfileAlterEvent $event
   */
  public function onProfileAlter(AutologoutProfileAlterEvent $event) {
    $profile = $event->getAutologoutProfile();
    if (!$profile->isExtendible()) {
      return;
    }

    $is_dev = Settings::get('ssr_env', '') === 'dev';

    $session_candidates = [];

    // Default session time: 1 hour.
    $session_candidates[] = $is_dev ? 86400 : 3600;

    $account = $event->getCurrentUser();
    $roles = $account->getRoles();

    // Caregivers: 3 days.
    if (in_array('caregiver', $roles)) {
      $session_candidates = [
        86400 * 3,
      ];
    }

    if (!$is_dev) {
      // Super user: 15 minutes.
      if ($account->hasPermission('super user permissions')) {
        $session_candidates[] = 900;
      }
      // Administrators: 1 hour.
      if ($account->hasPermission('administer simple school reports settings')) {
        $session_candidates[] = 3600;
      }
      // Staff: 4 hours.
      if ($account->hasPermission('school staff permissions')) {
        $session_candidates[] = 14400;
      }
    }

    $session_time = min($session_candidates);
    $last_activity = $profile->getLastActivity();
    $expiration = new \DateTime();
    $expiration->setTimestamp($last_activity->getTimestamp() + $session_time);
    $profile->setSessionExpiration($expiration);
    $event->setAutologoutProfile($profile);
  }

}
