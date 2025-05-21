<?php

namespace Drupal\simple_school_reports_class_support\Plugin\Field\FieldType;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * Computed field for the students in this class.
 */
class SchoolClassStudents extends EntityReferenceFieldItemList {
  use ComputedItemListTrait;

  /**
  * {@inheritdoc}
  */
  public function computeValue() {
    /** @var \Drupal\simple_school_reports_class_support\SchoolClassInterface $class */
    $class = $this->getEntity();
    if (!$class->id()) {
      return;
    }

    /** @var \Drupal\simple_school_reports_class_support\Service\SsrClassServiceInterface $class_service */
    $class_service = \Drupal::service('simple_school_reports_class_support.class_service');
    $student_uids = $class_service->getStudentIdsByClassId($class->id());

    foreach ($student_uids as $delta => $student_uid) {
      $this->list[$delta] = $this->createItem(0, [
        'target_id' => $student_uid,
      ]);
    }
  }

}
