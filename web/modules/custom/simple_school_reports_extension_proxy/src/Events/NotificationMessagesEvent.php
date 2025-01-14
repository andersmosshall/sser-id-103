<?php

namespace Drupal\simple_school_reports_extension_proxy\Events;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use project\Controller\TodoController;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * A code based personalisation option alter event.
 */
class NotificationMessagesEvent extends Event {

  public function __construct(
    protected AccountInterface $currentUser,
    protected CacheBackendInterface $cache,
    protected array $informationMessages = [],
    protected array $importantMessages = [],
  ) {}

  public function getCurrentUser(): AccountInterface {
    return $this->currentUser;
  }

  public function addInformationMessage(string $cid, callable $resolver) {
    $message = $this->resolveMessage($cid, $resolver);
    if ($message === NULL) {
      return;
    }
    if (is_array($message)) {
      foreach ($message as $item) {
        $this->informationMessages[] = $item;
      }
      return;
    }

    $this->informationMessages[] = $message;
  }

  public function addImportantMessage(string $cid, callable $resolver) {
    $message = $this->resolveMessage($cid, $resolver);
    if ($message === NULL) {
      return;
    }
    if (is_array($message)) {
      foreach ($message as $item) {
        $this->importantMessages[] = $item;
      }
      return;
    }

    $this->importantMessages[] = $message;
  }

  public function getInformationMessages(): array {
    return $this->informationMessages;
  }

  public function getImportantMessages(): array {
    return $this->importantMessages;
  }

  protected function resolveMessage(string $cid, callable $resolver): array|string|TranslatableMarkup|null {
    $cached = $this->cache->get($cid);
    if ($cached) {
      return $cached->data;
    }

    /**
     * @var string|\Drupal\Core\StringTranslation\TranslatableMarkup|null $message
     * @var \Drupal\Core\Cache\CacheableMetadata $cache_metadata
     */
    [$message, $cache_metadata] = $resolver();
    $expires = $cache_metadata->getCacheMaxAge() !== Cache::PERMANENT
      ? time() + $cache_metadata->getCacheMaxAge()
      : Cache::PERMANENT;
    $this->cache->set($cid, $message, $expires, $cache_metadata->getCacheTags());

    return $message;
  }





}
