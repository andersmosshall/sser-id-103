<?php

namespace Drupal\simple_school_reports_pwa_support\EventSubscriber;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Defines ManifestRequestSubscriber Subscriber.
 */
class ManifestRequestSubscriber implements EventSubscriberInterface {

  /**
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   */
  public function __construct(
    protected RouteMatchInterface $routeMatch,
    protected ModuleHandlerInterface $moduleHandler,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = 'onRequest';
    return $events;
  }

  /**
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The request event.
   */
  public function onRequest(RequestEvent $event) {
    if ($this->routeMatch->getRouteName() !== 'pwa.manifest') {
      return;
    }

    if (!$this->moduleHandler->moduleExists('simple_school_reports_pwa')) {
      throw new NotFoundHttpException();
    }
  }

}
