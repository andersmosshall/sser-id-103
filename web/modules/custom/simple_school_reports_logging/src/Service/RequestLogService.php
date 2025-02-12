<?php

namespace Drupal\simple_school_reports_logging\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStore;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class AbsenceStatisticsService
 *
 * @package Drupal\simple_school_reports_core\Service
 */
class RequestLogService implements RequestLogServiceInterface, EventSubscriberInterface {

  /**
   * The temp store service.
   */
  protected PrivateTempStore $tempStore;

  /**
   * TermService constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(
    PrivateTempStoreFactory $private_temp_store_factory,
    protected TimeInterface $time,
    protected AccountInterface $currentUser,
  ) {
    $this->tempStore = $private_temp_store_factory->get('simple_school_reports_logging');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['onRequest', 200];
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  public function onRequest(RequestEvent $event) {
    $request = $event->getRequest();

    $is_ajax = $request->isXmlHttpRequest();
    if ($is_ajax) {
      return;
    }

    if ($this->currentUser->isAnonymous()) {
      return;
    }

    $this->addRequestLogItem($request);
  }

  /**
   * {@inheritdoc}
   */
  public function addRequestLogItem(Request $request) {
    try {
      $uri = $request->getRequestUri();
      if (str_starts_with($uri, '/sites')) {
        return;
      }

      if (str_starts_with($uri, '/api/autologout_alterable')) {
        return;
      }

      $entries = $this->tempStore->get('ssr_request_entries') ?? [];

      if (empty($entries)) {
        $entries[] = $request->server->get('HTTP_USER_AGENT', 'No user agent');
      }

      $method = $request->getMethod();

      $entry_parts = [];
      $entry_parts[] = date('Y-m-d H:i:s', $this->time->getRequestTime());
      $entry_parts[] = $method;
      $entry_parts[] = $uri;
      $entries[] = implode(' ', $entry_parts);

      if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
        $post_data = $request->request->all();
        unset($post_data['pass']);
        unset($post_data['password']);
        unset($post_data['field_ssn']);
        $entries[] = json_encode($post_data);
      }

      $this->tempStore->set('ssr_request_entries', $entries);

      if ($request->query->get('cancel') || $request->query->get('back')) {
        do_ssr_request_log(TRUE);
      }
    }
    catch (\Exception $e) {
      // Do nothing.
    }

  }

  /**
   * {@inheritdoc}
   */
  public function clearRequestLogItems() {
    try {
      $this->tempStore->delete('ssr_request_entries');
    }
    catch (\Exception $e) {
      // Do nothing.
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getRequestLogMessage(bool $compressed = TRUE, bool $keep_local_log = FALSE): ?string {
    try {
      if ($this->currentUser->isAnonymous()) {
        return NULL;
      }

      $entries = $this->tempStore->get('ssr_request_entries');

      if (empty($entries)) {
        return NULL;
      }

      /** @var \Drupal\simple_school_reports_core\Service\SSRVersionServiceInterface $ssr_version_service */
      $ssr_version_service = \Drupal::service('simple_school_reports_core.ssr_version');

      $message = 'SSR Version: ' . $ssr_version_service->getSsrVersion() . PHP_EOL;
      $message .= implode(PHP_EOL, $entries);

      if (!$keep_local_log) {
        $this->clearRequestLogItems();
      }

      if ($compressed) {
        $message = gzcompress($message, 9);
        $message = base64_encode($message);
      }

      return $message;
    }
    catch (\Exception $e) {
      // Do nothing.
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function decompressRequestLogMessage(string $compressed_message): string {
    $compressed_message = base64_decode($compressed_message);
    $message = gzuncompress($compressed_message);
    return $message;
  }

}
