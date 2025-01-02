<?php

namespace Drupal\simple_school_reports_post_check;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\simple_school_reports_core\Service\EmailService;
use Drupal\simple_school_reports_core\Service\EmailServiceInterface;
use Drupal\simple_school_reports_core\Service\ReplaceTokenServiceInterface;
use Drupal\simple_school_reports_maillog\SsrMaillogInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 *  Callstack helper.
 */
class CallstackHelper {

  public static function ssrCallstackEnabled(): bool {
    return \Drupal::state()->get('ssr_callstack_enabled', TRUE);
  }

  public static function ssrSetCallstackEnabled(bool $value): void {
    \Drupal::state()->set('ssr_callstack_enabled', $value);
  }

  public static function ssrCollectActive(bool | null $value = NULL): bool {
    static $callstack_collect;
    if (!isset($callstack_collect)) {
      $callstack_collect = FALSE;
    }

    if ($value !== NULL) {
      try {
        $callstack_collect = $value === TRUE && self::ssrCallstackEnabled();
      }
      catch (\Exception $e) {
        $callstack_collect = FALSE;
      }
    }

    return $callstack_collect;
  }

  public static function ssrCallstackAddEntry(string $file, string $fname, int $line, array $data = []) {
    try {
      if (!self::ssrCollectActive()) {
        return;
      }

      /** @var \Drupal\Core\TempStore\PrivateTempStore $tempstore */
      $tempstore = \Drupal::service('tempstore.private')->get('ssr_post_check');

      $callstack = $tempstore->get('ssr_callstack') ?? [];
      $callstack[] = $file . '->' . $fname . ':' . $line . PHP_EOL . json_encode($data) . PHP_EOL . '-------------------------';
      $tempstore->set('ssr_callstack', $callstack);
    }
    catch (\Exception $e) {
      // Do nothing.
    }
  }

}
