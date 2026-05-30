<?php

namespace Drupal\simple_school_reports_child_care_support\Service;

use Drupal\simple_school_reports_child_care_support\ChildCarePlacementInterface;

/**
 * Provides an interface defining ChildCareSchemaService.
 */
interface ChildCareSchemaServiceInterface {

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

}
