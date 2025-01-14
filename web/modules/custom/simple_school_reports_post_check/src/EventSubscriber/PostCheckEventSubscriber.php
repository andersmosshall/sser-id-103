<?php

namespace Drupal\simple_school_reports_post_check\EventSubscriber;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TempStore\PrivateTempStore;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\simple_school_reports_core\Service\EmailServiceInterface;
use Drupal\simple_school_reports_maillog\SsrMaillogInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class PostCheckService
 */
class PostCheckEventSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The temp store service.
   */
  protected PrivateTempStore $tempStore;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * TermService constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(
    PrivateTempStoreFactory $private_temp_store_factory,
    protected TimeInterface $time,
    LoggerChannelFactoryInterface $logger_channel_factory,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected MessengerInterface $messenger,
    protected EmailServiceInterface $emailService,
    protected AccountInterface $currentUser,
  ) {
    $this->tempStore = $private_temp_store_factory->get('ssr_post_check');
    $this->logger = $logger_channel_factory->get('ssr_post_check');
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
    try {
      $request = $event->getRequest();

      if ($request->getMethod() === 'POST') {
        if ($this->isAbsenceDayRequest($request)) {
          if ($uid = $this->getUserParameter($request)) {
            $this->tempStore->set('ssr_check_absence_day_user', $uid);
            $this->tempStore->delete('ssr_callstack');
            \Drupal\simple_school_reports_post_check\CallstackHelper::ssrCollectActive(TRUE);
            \Drupal\simple_school_reports_post_check\CallstackHelper::ssrCallstackAddEntry(__FILE__, __FUNCTION__, __LINE__, [
              'init' => TRUE,
              'uid' => \Drupal::currentUser()->id(),
              'uname' => \Drupal::currentUser()->getDisplayName(),
            ]);
          }
        }
        return;
      }

      if ($request->getMethod() === 'GET') {
        if ($uid = $this->tempStore->get('ssr_check_absence_day_user')) {
          $this->tempStore->delete('ssr_check_absence_day_user');
          $this->checkAbsenceDay($uid, $request->server->get('HTTP_USER_AGENT', 'No user agent'));
          $this->tempStore->delete('ssr_callstack');
        }
      }
    }
    catch ( \Exception $e) {
      $this->logger->error('Error while the request in post check: @message', [
        '@message' => $e->getMessage(),
      ]);
    }
  }

  protected function isAbsenceDayRequest(Request $request): bool {
    $is_ajax = $request->isXmlHttpRequest();
    if ($is_ajax) {
      return FALSE;
    }
    return preg_match('/\/student\/\d+\/register-absence/', $request->getRequestUri());
  }

  protected function getUserParameter(Request $request): ?string {
    $pattern = '/\/student\/(\d+)\/register-absence/';
    if (preg_match($pattern, $request->getRequestUri(), $matches)) {
      // $matches[1] contains the {user} number
      return $matches[1];
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAbsenceDay(string $uid, string $user_agent) {
    try {
      $nid = $this->entityTypeManager->getStorage('node')->getQuery()
        ->accessCheck(FALSE)
        ->condition('type', 'day_absence')
        ->condition('field_student', $uid)
        ->condition('changed', $this->time->getRequestTime() - 5, '>')
        ->range(0, 1)
        ->execute();

      if (empty($nid)) {
        $link = Link::createFromRoute($this->t('Latest report'),'simple_school_reports_core.latest_reports', ['user' => $uid])->toString();
        $this->messenger->addWarning($this->t('It looks like an error may have occurred when creating the absence registration. Please check in @link that everything is in order. If not, please try again or contact the school.', ['@link' => $link]));
        $this->logger->error('Failed to check absence day registration. Request log will be logged.');
        if (function_exists('do_ssr_request_log')) {
          do_ssr_request_log(TRUE);
          $ssr_bug_report_email = Settings::get('ssr_bug_report_email', NULL);
          if ($ssr_bug_report_email) {
            $options = [
              'maillog_mail_type' => SsrMaillogInterface::MAILLOG_TYPE_OTHER,
              'no_reply_to' => TRUE,
            ];
            $message = 'Error occurred ' . date('Y-m-d H:i:s', $this->time->getRequestTime()) .' with absence post check at ' . Settings::get('ssr_school_name', '?') . ' check the log! User: ' . ($this->currentUser?->getDisplayName() ?? '?') . ' with roles ' . implode(', ', $this->currentUser?->getRoles(TRUE) ?? '?');
            $message .= PHP_EOL . PHP_EOL . 'User agent: ' . $user_agent;

            $callstack = array_reverse($this->tempStore->get('ssr_callstack') ?? []);
            if (!empty($callstack)) {
              $message .= PHP_EOL . PHP_EOL . 'Callstack:' . PHP_EOL . implode(PHP_EOL, $callstack);
            }

            $this->emailService->sendMail($ssr_bug_report_email, 'Absence error check has occurred', $message, $options);
          }
        }
      }
    }
    catch (\Exception $e) {
      // Do nothing.
      $this->logger->error('Error while checking absence day: @message', [
        '@message' => $e->getMessage(),
      ]);
    }
  }

}
