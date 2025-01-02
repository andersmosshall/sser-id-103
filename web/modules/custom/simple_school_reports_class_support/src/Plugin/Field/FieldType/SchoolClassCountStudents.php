<?php

namespace Drupal\simple_school_reports_class_support\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\simple_school_reports_dnp_support\DnpProvisioningInterface;

/**
 * Computed field for the DNP Provisioning File Link.
 */
class SchoolClassCountStudents extends FieldItemList {
  use ComputedItemListTrait;

  /**
  * {@inheritdoc}
  */
  public function computeValue() {
    /** @var \Drupal\simple_school_reports_dnp_support\DnpProvisioningInterface $entity */
    $class = $this->getEntity();
    if (!$class->id()) {
      return;
    }

    /** @var \Drupal\simple_school_reports_class_support\Service\SsrClassServiceInterface $class_service */
    $class_service = \Drupal::service('simple_school_reports_class_support.class_service');

    $this->list[0] = $this->createItem(0, [
      'value' => count($class_service->getStudentIdsByClassId($class->id())),
    ]);
  }

}
