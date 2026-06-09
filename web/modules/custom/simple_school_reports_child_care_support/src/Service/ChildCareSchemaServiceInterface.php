<?php

namespace Drupal\simple_school_reports_child_care_support\Service;

use Drupal\simple_school_reports_child_care_support\ChildCarePlacementInterface;

/**
 * Provides an interface defining ChildCareSchemaService.
 */
interface ChildCareSchemaServiceInterface {

  const SCHEMA_SEGMENT_TYPE_SCHOOL_NO_NEEDS = 1;
  const SCHEMA_SEGMENT_TYPE_LEAVE_ABSENCE = 2;
  const SCHEMA_SEGMENT_TYPE_REPORTED_ABSENCE = 3;
  const SCHEMA_SEGMENT_TYPE_SCHOOL_DAY = 4;
  const SCHEMA_SEGMENT_TYPE_SCHOOL_NO_OFFER = 5;
  const SCHEMA_SEGMENT_TYPE_SCHOOL_CHILD_CARE = 6;



  /**
   * Get child care schema ids in order.
   *
   * @return array
   *  Sorted array of schema ids, first is the latest (may be future).
   */
  public function getChildCareSchemaIdsInOrder(string|int $child_care_id): array;

  /**
   * @param string|int $child_care_id
   * @param \DateTimeInterface $date
   *
   * @return int|string|null
   */
  public function getActiveChildCareSchema(string|int $child_care_id, \DateTimeInterface $date = new \DateTime()): null|int|string;

  /**
   * @param string|int $student_id
   * @param \DateTimeInterface $date
   *
   * @return array
   *   Assosiative array with the following keys:
   *    - from: timestamp
   *    - to: timestamp
   *    - schema_id: int|string|null
   *    - deviation_id: int|string|null if it is a deviation from schema.
   *    - child_care_ids: array<int|string>
   *
   */
  public function getChildCareStudentNeed(string|int $student_id, \DateTimeInterface $date = new \DateTime()): ?array;

  /**
   * @param string|int $student_id
   * @param \DateTimeInterface $date
   *
   * @return array
   *   Assosiative array with the following keys:
   *    - from: timestamp
   *    - to: timestamp
   *    - deviation_id: int|string|null if it is a deviation from schema.
 */
  public function getChildCareOffer(string|int $child_care_id, \DateTimeInterface $date = new \DateTime()): ?array;

  /**
   * @param string|int $student_id
   * @param \DateTimeInterface $date
   *
   * @return array
   *   Array of assosiative arrays in order with segment data with the following keys:
   *    - from: timestamp
   *    - to: timestamp
   *    - type: int (see consts in ChildCareSchemaServiceInterface)
   *    - child_care_ids: array<int|string> placements when type is SCHEMA_SEGMENT_TYPE_SCHOOL_CHILD_CARE.
   */
  public function getStudentChildCareSchemaSegments(string|int $student_id, \DateTimeInterface $date = new \DateTime(), ?array $restricted_child_care_ids = NULL): array;

  /**
   * @param string|int $student_id
   * @param \DateTimeInterface $date
   *
   * @return array
   *   An render array
   */
  public function buildStudentDayOverview(string|int $student_id, \DateTimeInterface $date = new \DateTime(), ?array $restricted_child_care_ids = NULL): array;

  /**
   * @param array $student_ids
   * @param \DateTimeInterface $date
   * @param array|null $restricted_child_care_ids
   *
   * @return array
   *   Array of segements with the following keys:
   *    - from: timestamp
   *    - to: timestamp
   *    - needs: int
   *    - offers: int
   *
   * NOTE: The segements are equally distributed. (15 minutues).
   */
  public function getDayOverview(array $student_ids, \DateTimeInterface $date = new \DateTime(), ?array $restricted_child_care_ids = NULL): array;

}
