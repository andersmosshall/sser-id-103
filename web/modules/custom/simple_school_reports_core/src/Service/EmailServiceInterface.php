<?php

namespace Drupal\simple_school_reports_core\Service;

use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;

/**
 * Provides an interface defining EmailService.
 */
interface EmailServiceInterface {

  /**
   * @param string|null $mail
   *
   * @return bool
   */
  public function skipMail(?string $mail) : bool;

  /**
   * @param int $studentId
   *
   * @return array|null
   */
  public function getCaregiverRecipients(int $studentId) : ?array;

  /**
   * @param bool $use_sleep
   *
   * @return int
   */
  public function getMailCount(): int;

  /**
   *
   */
  public function mailCountIncrement();

  /**
   *
   */
  public function resetMailCount();

  /**
   * @param string $recipient
   * @param string $subject
   * @param string $message
   * @param array $options
   *
   * @return bool
   */
  public function sendMail(string $recipient, string $subject, string $message, array $options = []) : bool;

  /**
   * @param \Drupal\Core\Session\AccountInterface $user
   *
   * @return string|null
   */
  public function getUserEmail(AccountInterface $user) : ?string;

  public function getUserByEmail(string $email): ?AccountInterface;

}
