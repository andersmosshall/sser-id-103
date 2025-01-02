<?php

namespace Drupal\simple_school_reports_leave_application\Service;

/**
 * Provides an interface defining UnionenFeedbackService.
 */
interface LeaveApplicationServiceInterface {

  /**
   * @param ?string $uid
   *
   * @return string[]
   */
  public function getStudentLeaveApplicationIdsToHandle(?string $uid = NULL): array;

  /**
   * @return array
   */
  public function getSettings(): array;

  /**
   * @param string $key
   * @param int $default
   *
   * @return int
   */
  public function getSetting(string $key, int $default = 0): int;

  /**
   * @param array $settings
   *
   * @return void
   */
  public function setSettings(array $settings): void;

}
