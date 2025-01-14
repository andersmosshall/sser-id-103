<?php

namespace Drupal\simple_school_reports_student_di\Service;

use Drupal\simple_school_reports_entities\SsrMeetingInterface;

/**
 * Provides an interface defining StudentDiMeetingsService.
 */
interface StudentDiMeetingsServiceInterface {


  public function getStudentGroupIds(string $student_id): array;

  public function getBookedMeetingIds(string $student_id, string $group_id): array;

  public function getAvailableMeetingIds(string $student_id, string $group_id, bool $check_locked = TRUE, bool $check_locked_caregivers = FALSE): array;

  public function getMeetingData(string $meeting_id): array;

  public function getMeetingReminderMessage(SsrMeetingInterface $meeting): string;

  public function handleMeetingChanged(SsrMeetingInterface $meeting, bool $deleted = FALSE): void;

}
