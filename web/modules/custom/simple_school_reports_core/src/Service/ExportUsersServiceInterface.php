<?php

namespace Drupal\simple_school_reports_core\Service;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\UserInterface;

/**
 * Provides an interface defining Export user services.
 */
interface ExportUsersServiceInterface {

  public function access(AccountInterface $account): bool;

  public function getServiceId(): string;

  public function getFileSuffix(): ?string;

  public function getFileExtension(): string;

  public static function getPriority(): int;

  public function getShortDescription(): TranslatableMarkup;

  public function getDescription(): TranslatableMarkup;

  public function getOptionsForm(): array;

  public static function getOptionsWithDefaults(array $options): array;

  public function modifyUidsList(array $uids, array $options): array;

  /**
   * @param array $uids
   * @param array $options
   *
   * @return TranslatableMarkup[]
   */
  public function getErrors(array $uids, array $options): array;

  public function getUserRow(UserInterface $user, array $options): ?array;

  public function makeFileContent(array $user_rows, array $options): ?string;

}
