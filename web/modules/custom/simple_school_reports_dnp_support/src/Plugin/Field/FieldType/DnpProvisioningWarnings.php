<?php

namespace Drupal\simple_school_reports_dnp_support\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * Computed field for the DNP Provisioning File Link.
 */
class DnpProvisioningWarnings extends FieldItemList {
  use ComputedItemListTrait;

  /**
  * {@inheritdoc}
  */
  public function computeValue() {
    /** @var \Drupal\simple_school_reports_dnp_support\DnpProvisioningInterface $entity */
    $entity = $this->getEntity();
    $warnings = $entity->getWarnings();
    if (empty($warnings)) {
      return;
    }

    foreach ($warnings as $delta => $warning) {
      $this->list[$delta] = $this->createItem(0, [
        'value' => $warning,
      ]);
    }
  }

}
