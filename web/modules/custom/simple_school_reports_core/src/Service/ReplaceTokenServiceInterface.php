<?php

namespace Drupal\simple_school_reports_core\Service;

/**
 * Provides an interface defining ReplaceTokenService.
 */
interface ReplaceTokenServiceInterface {

  const STUDENT_REPLACE_TOKENS = 'student_replace_tokens';
  const RECIPIENT_REPLACE_TOKENS = 'recipient_replace_tokens';
  const CURRENT_USER_REPLACE_TOKENS = 'current_user_replace_tokens';
  const ATTENDANCE_REPORT_TOKENS = 'attendance_report_tokens';
  const INVALID_ABSENCE_TOKENS = 'invalid_absence_tokens';

  /**
   * @param array $categories
   * @param bool $flat
   *
   * @return array
   */
  public function getReplaceTokenDescriptions(array $categories = ['ALL'], bool $flat = FALSE) : array;

  /**
   * @param string $text
   * @param array $replace_context
   *
   * @return string
   */
  public function handleText(string $text, array $replace_context) : string;

}
