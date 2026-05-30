<?php

namespace Drupal\simple_school_reports_child_care_support\Service;

use Drupal\simple_school_reports_child_care_support\ChildCarePlacementInterface;

/**
 * Provides an interface defining ChildCareService.
 */
interface ChildCareServiceInterface {

  /**
   * Get (active) child care placement ids for a child care.
   *
   * @param string|int $child_care_id
   *
   * @return array
   *   Array of placement ids, keyed by student id.
   */
  public function getChildCarePlacementIds(string|int $child_care_id, \DateTime $date = new \DateTime()): array;

  /**
   * @param string|int $child_care_id
   * @param \DateTime $date
   *
   * @return array
   */
  public function getChildCareStudentIds(string|int $child_care_id, \DateTime $date = new \DateTime()): array;

  /**
   * @param string|int $student_id
   *
   * @return array<string|int>
   *   Array of child care ids.
   */
  public function getChildCareGroups(string|int $student_id, \DateTime $date = new \DateTime()): array;

  /**
   * @param string|int $student_id
   * @param string|int $child_care_id
   *
   * @return mixed
   */
  public function addChildCarePlacement(string|int $student_id, string|int $child_care_id);

  /**
   * @param \Drupal\simple_school_reports_child_care_support\ChildCarePlacementInterface $placement
   *
   * @return mixed
   */
  public function endChildCarePlacement(ChildCarePlacementInterface $placement);

  /**
   * @return array
   */
  public function getSettings(): array;

  /**
   * @param array $settings
   *
   * @return mixed
   */
  public function setSettings(array $settings);
}
