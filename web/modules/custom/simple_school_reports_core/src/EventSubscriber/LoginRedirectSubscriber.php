<?php

namespace Drupal\simple_school_reports_core\EventSubscriber;

use Drupal\autologout\AutologoutManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Defines LoginRedirectSubscriber Subscriber.
 */
class LoginRedirectSubscriber implements EventSubscriberInterface {

  /**
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   */
  public function __construct(
    protected RouteMatchInterface $routeMatch,
    protected AccountInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = 'onRequest';
    return $events;
  }

  /**
   * Check for autologout JS.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The request event.
   */
  public function onRequest(RequestEvent $event) {
    if (!$this->currentUser->isAnonymous() || $this->routeMatch->getRouteName() !== 'system.403') {
      return;
    }

    $request = $event->getRequest();
    $destination = $request->query->get('destination');
    if ($destination && $destination !== '/user/login') {
      $request->query->remove('destination');
      $redirect_url = Url::fromRoute('user.login', [], ['query' => ['destination' => $destination]]);
      $response = new RedirectResponse($redirect_url->toString());
      $response->setMaxAge(0);
      $event->setResponse($response);
    }
  }

}
