<?php

namespace Drupal\simple_school_reports_class_support\Plugin\Field\FieldType;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * Computed field for the students in this class.
 */
class SchoolClassMentors extends EntityReferenceFieldItemList {
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
    $mentor_uids = $class_service->getMentorIdsByClassId($class->id());

    foreach ($mentor_uids as $delta => $mentor_uid) {
      $this->list[$delta] = $this->createItem(0, [
        'target_id' => $mentor_uid,
      ]);
    }
  }

}
