<?php

namespace Drupal\simple_school_reports_entities\Plugin\Field\FieldType;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * Computed field for the students in this programme.
 */
class ProgrammeStudents extends EntityReferenceFieldItemList {
  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  public function computeValue() {
    /** @var \Drupal\simple_school_reports_entities\ProgrammeInterface $programme */
    $programme = $this->getEntity();
    if (!$programme->id()) {
      return;
    }

    /** @var \Drupal\simple_school_reports_entities\Service\ProgrammeServiceInterface $programme_service */
    $programme_service = \Drupal::service('simple_school_reports_entities.programme_service');
    $student_uids = $programme_service->getStudentIdsByProgrammeId($programme->id());

    foreach ($student_uids as $delta => $student_uid) {
      $this->list[$delta] = $this->createItem(0, [
        'target_id' => $student_uid,
      ]);
    }
  }

}
