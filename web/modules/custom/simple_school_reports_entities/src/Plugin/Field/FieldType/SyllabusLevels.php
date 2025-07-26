<?php

namespace Drupal\simple_school_reports_entities\Plugin\Field\FieldType;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * Computed field for the syllabus levels.
 */
class SyllabusLevels extends EntityReferenceFieldItemList {
  use ComputedItemListTrait;

  /**
  * {@inheritdoc}
  */
  public function computeValue() {
    /** @var \Drupal\simple_school_reports_entities\SyllabusInterface $syllabus */
    $syllabus = $this->getEntity();
    if (!$syllabus->id()) {
      return;
    }

    $syllabus_ids = [];

    foreach ($syllabus_ids as $delta => $syllabus_id) {
      $this->list[$delta] = $this->createItem(0, [
        'target_id' => $syllabus_id,
      ]);
    }
  }

}
