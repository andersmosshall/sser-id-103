<?php

namespace Drupal\simple_school_reports_maillog;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a maillog entity type.
 */
interface SsrMaillogInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  const MAILLOG_TYPE_COURSE_ATTENDANCE = 'course_attendance';
  const MAILLOG_TYPE_LEAVE_APPLICATION = 'leave_application';
  const MAILLOG_TYPE_CAREGIVER = 'caregiver';
  const MAILLOG_TYPE_TEST = 'test';
  const MAILLOG_TYPE_INFRASTRUCTURE = 'infra_structure';
  const MAILLOG_TYPE_MAIL_MENTOR = 'mail_mentor';

  const MAILLOG_TYPE_MEETING_REMINDER = 'meeting_reminder';
  const MAILLOG_TYPE_MAIL_USER = 'mail_user';
  const MAILLOG_TYPE_OTHER = 'other';

  const MAILLOG_SEND_STATUS_SENT = 'sent';
  const MAILLOG_SEND_STATUS_FAILED = 'failed';
  const MAILLOG_SEND_STATUS_SIMULATED = 'simulated';

}
