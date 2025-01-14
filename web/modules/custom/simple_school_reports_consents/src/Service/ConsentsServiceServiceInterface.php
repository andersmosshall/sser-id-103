<?php

namespace Drupal\simple_school_reports_consents\Service;

use Drupal\Core\Session\AccountInterface;
use Drupal\simple_school_reports_entities\SsrConsentAnswerInterface;

/**
 * Provides an interface defining AbsenceStatisticsService.
 */
interface ConsentsServiceServiceInterface {

  const CONSENT_ACCEPTED = SsrConsentAnswerInterface::CONSENT_ANSWER_ACCEPTED;
  const CONSENT_REJECTED = SsrConsentAnswerInterface::CONSENT_ANSWER_REJECTED;
  const CONSENT_NOT_ANSWERED = -1;

  const VIEWS_FILTER_NONE = 0;
  const VIEWS_FILTER_FULLY_ACCEPTED = 1;
  const VIEWS_FILTER_SOME_ACCEPTED = 2;
  const VIEWS_FILTER_SOME_REJECTED = 3;
  const VIEWS_FILTER_FULL_REJECTED = 4;
  const VIEWS_FILTER_MISSING_ANSWERS = 5;
  const VIEWS_FILTER_HANDLE_BY_STAFF = 6;
  const VIEWS_FILTER_TO_REMIND = 7;

  public function getUnHandledConsentIds(int $uid): array;

  public function getHandledConsentsIds(int $uid): array;

  public function getConsentCompletion(int $consent_id): string;

  public function getExpectedUids(int $filter = self::VIEWS_FILTER_NONE): array;

  public function getTargetUids(int $consent_id, int $filter = self::VIEWS_FILTER_NONE): array;

  public function getTargetUidsByUidWithData(int $uid): array;

  public function getConsentStatus(int $consent_id, int $target_uid): array;

  public function allowConsentHandling(int $consent_id, int $target_uid, ?int $consent_uid = NULL): bool;

  public function getConsentNames(): array;

}
