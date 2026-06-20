<?php

namespace Drupal\simple_school_reports_child_care_support\Service;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides an interface defining ChildCareCheckInService.
 */
interface ChildCareCheckInServiceInterface {

  /**
   * @param string|int $student_id
   * @param \DateTimeInterface $date
   * @param array|null $restricted_child_care_ids
   *
   * @return array
   *   Array (student may have multiple check ins) of assosiative array with the following keys:
   *    - from: timestamp
   *    - to: timestamp
   *    - child_care_id: int|string
   *    - check_in_id: int|string
   */
  public function getChildCareCheckIns(string|int $student_id, \DateTimeInterface $date = new \DateTime(), ?array $restricted_child_care_ids = NULL): array;

  /**
   * @param string|int $student_id
   * @param string|int $child_care_id
   * @param \DateTimeInterface $date
   *
   * @return array|null
   *   Assosiative array with the following keys:
   *     - from: timestamp
   *     - to: timestamp
   *     - child_care_id: int|string
   *     - check_in_id: int|string
   */
  public function getLatestCheckIn(string|int $student_id, string|int $child_care_id, \DateTimeInterface $date = new \DateTime()): ?array;

  /**
   * @param string|int $student_id
   * @param string|int $child_care_id
   * @param \DateTimeInterface $date
   *
   * @return array|null
   *   Assosiative array with the following keys:
   *     - from: timestamp
   *     - to: NULL (as user is not checked in yet)
   *     - child_care_id: int|string
   *     - check_in_id: int|string
   */
  public function getActiveCheckIn(string|int $student_id, string|int $child_care_id, \DateTimeInterface $date = new \DateTime()): ?array;


  /**
   * @param string|int $student_id
   * @param \DateTimeInterface $date
   * @param array|null $restricted_child_care_ids
   *
   * @return array
   *   A render array
   */
  public function buildStudentCheckIns(string|int $student_id, \DateTimeInterface $date = new \DateTime(), ?array $restricted_child_care_ids = NULL): array;

  public function allowChildCareCheckIn(string|int $child_care_id, \DateTimeInterface $date = new \DateTime(), ?AccountInterface $account = NULL): bool;

  public function allowChildCareCheckOut(string|int $child_care_id, \DateTimeInterface $date = new \DateTime(), ?AccountInterface $account = NULL): bool;

  public function allowStudentCheckIn(string|int $student_id, \DateTimeInterface $date = new \DateTime(), ?array $restricted_child_care_ids = NULL, ?AccountInterface $account = NULL): bool;

  public function allowStudentCheckOut(string|int $student_id, \DateTimeInterface $date = new \DateTime(), ?array $restricted_child_care_ids = NULL, ?AccountInterface $account = NULL): bool;

  /**
   * Get cacheable metadata to apply to child care schema related controllers, etc
   * with check-in data.
   *
   * @param \DateTimeInterface|null $date
   * @param array $options
   *
   * @return \Drupal\Core\Cache\CacheableMetadata
   */
  public function getCacheableMetadata(?\DateTimeInterface $date, array $options = []): CacheableMetadata;

  /**
   * @return Array<string|int>
   */
  public function getStudentIdsFromRequest(): array;

  /**
   * @return array|null
   *   Assosiative array with the following keys:
   *     - time: timestamp
   *     - type: check_in|check_out
   */
  public function getPointOfInterest(string|int $student_id, \DateTimeInterface $date = new \DateTime(), ?array $restricted_child_care_ids = NULL): ?array;
}
