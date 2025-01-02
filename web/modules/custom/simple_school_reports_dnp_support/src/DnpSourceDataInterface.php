<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_dnp_support;

/**
 * Provides an interface defining a dnp provisioning entity type.
 */
interface DnpSourceDataInterface extends DnpProvisioningConstantsInterface {

  /**
   * @return string[]
   */
  public function getGrades(): array;

  /**
   * @return array[]
   */
  public function getClassesToRemove(): array;

  /**
   * @return string[]
   */
  public function getSubjectCodes(string $grade): array;

  /**
   * @return array[]
   */
  public function getSubjectGroupsToRemove(): array;

  /**
   * @return int[]
   */
  public function getStudentUids(): array;

  /**
   * @return string[]
   */
  public function getSubjectCodesForStudent(int|string $student_uid): array;

  /**
   * @return array[]
   */
  public function getStudentsToRemove(): array;

  /**
   * @param int $uid
   *
   * @return bool
   */
  public function useSecrecyMarking(int|string $uid): bool;

  /**
   * @return bool
   */
  public function useEmailForStudent(): bool;

  /**
   * @return int[]
   */
  public function getStaffUids(): array;

  /**
   * @return string[]
   */
  public function getSubjectCodesForStaff(int|string $staff_uid, string $grade): array;

  /**
   * @return array[]
   */
  public function getStaffToRemove(): array;

  /**
   * @return bool
   */
  public function useEmailForStaff(): bool;

  /**
   * @param array $parsed_data
   *
   * @return self
   */
  public function setLastProvisioningData(array $parsed_data): self;

  /**
   * Gets an array of all property values.
   *
   * @return mixed[]
   *   An array of property values, keyed by property name.
   */
  public function toArray();

}
