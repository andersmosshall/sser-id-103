<?php

namespace Drupal\simple_school_reports_dnp_support\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\simple_school_reports_dnp_support\DnpProvisioningInterface;

/**
 * Computed field for the DNP Provisioning File Link.
 */
class DnpProvisioningNumberStudents extends FieldItemList {
  use ComputedItemListTrait;

  /**
  * {@inheritdoc}
  */
  public function computeValue() {
    /** @var \Drupal\simple_school_reports_dnp_support\DnpProvisioningInterface $entity */
    $entity = $this->getEntity();

    $this->list[0] = $this->createItem(0, [
      'value' => $entity->getRowCount(DnpProvisioningInterface::DNP_STUDENTS_SHEET)
    ]);
  }

}
