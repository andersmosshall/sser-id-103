<?php

namespace Drupal\simple_school_reports_attendance_period_analyse\EventSubscriber;

use Drupal\autologout\AutologoutManagerInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Theme\ThemeManager;
use Drupal\Core\Url;
use Drupal\simple_school_reports_core\Service\TermServiceInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Defines AbsencePeriodAnalyseEventSubscriber Subscriber.
 */
class AbsencePeriodAnalyseEventSubscriber implements EventSubscriberInterface {

  /**
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   */
  public function __construct(
    protected RouteMatchInterface $routeMatch,
    protected TermServiceInterface $termService,
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
    if ($this->routeMatch->getRouteName() !== 'view.absence_period_analyse.statistics') {
      return;
    }

    $request = $event->getRequest();

    if ($request->getMethod() !== 'GET') {
      return;
    }

    $from = $request->query->get('from');
    $to = $request->query->get('to');

    if (!$from && !$to) {

      $from = $this->termService->getCurrentTermStart(TRUE);
      $to = $this->termService->getCurrentTermEnd(TRUE);

      if (!$from || !$to) {
        return;
      }

      $from = $from->format('Y-m-d');
      $to = $to->format('Y-m-d');

      $url = Url::fromUserInput($request->getRequestUri());
      $query = $url->getOption('query');
      $query['from'] = $from;
      $query['to'] = $to;
      $url->setOption('query', $query);

      // Redirect user to user page.
      $response = new RedirectResponse($url->toString());
      $event->setResponse($response);
    }
  }

}
