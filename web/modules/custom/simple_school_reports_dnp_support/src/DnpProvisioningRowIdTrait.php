<?php

namespace Drupal\simple_school_reports_dnp_support;

trait DnpProvisioningRowIdTrait {

  protected ?int $calculatedSsrIndex = NULL;

  protected ?string $calculatedSchoolYearRepresentation = NULL;

  protected function getSsrIndex(): int {
    if ($this->calculatedSsrIndex !== NULL) {
      return $this->calculatedSsrIndex;
    }

    $table_prefix = '';
    $database_options = \Drupal::database()->getConnectionOptions();
    if (!empty($database_options['database']['prefix'])) {
      $table_prefix = $database_options['database']['prefix'];
    }

    // Ssr index is the number in the table prefix, e.g. filter out numbers
    // from table prefix.
    $ssr_index = preg_replace('/[^0-9]/', '', $table_prefix);
    if ($ssr_index === '') {
      $ssr_index = 0;
    }

    $this->calculatedSsrIndex = (int) $ssr_index;
    return $this->calculatedSsrIndex;
  }

  protected function generateRowId(string $type, int|string $id): string {
    $prefix = match ($type) {
      'class', DnpProvisioningConstantsInterface::DNP_CLASSES_SHEET => 'k',
      'subject_group', DnpProvisioningConstantsInterface::DNP_SUBJECT_GROUPS_SHEET => 'g',
      'user', DnpProvisioningConstantsInterface::DNP_STUDENTS_SHEET, DnpProvisioningConstantsInterface::DNP_STAFF_SHEET => 'u',
      default => 'n',
    };

    $use_term_prefix = in_array($type, ['class', DnpProvisioningConstantsInterface::DNP_CLASSES_SHEET, 'subject_group', DnpProvisioningConstantsInterface::DNP_SUBJECT_GROUPS_SHEET]);
    if ($use_term_prefix) {
      if (!$this->calculatedSchoolYearRepresentation) {
        $term_service = \Drupal::service('simple_school_reports_core.term_service');
        $this->calculatedSchoolYearRepresentation = $term_service->getDefaultSchoolYearStart()->format('y') . $term_service->getDefaultSchoolYearEnd()->format('y');
      }
      $prefix .= '.' . $this->calculatedSchoolYearRepresentation;
    }

    return 'ssr' . $this->getSsrIndex() . '.' . $prefix . '.' . $id;
  }

  protected function getItemIdFromGeneratedRowId(string $generated_id): string {
    $parts = explode('.', $generated_id);
    return end($parts);
  }

}

