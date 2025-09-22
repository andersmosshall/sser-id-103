<?php

namespace Drupal\simple_school_reports_entities\Plugin\Field\FieldType;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\simple_school_reports_core\Form\ActivateSyllabusFormBase;

/**
 * Computed field for the syllabus levels.
 */
class SyllabusLevels extends FieldItemList {
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

    /** @var \Drupal\simple_school_reports_entities\Service\SyllabusServiceInterface $syllabus_service */
    $syllabus_service = \Drupal::service('simple_school_reports_entities.syllabus_service');

    $level_syllabus_ids = $syllabus_service->getSyllabusLevelIds($syllabus->id());;
    if (empty($level_syllabus_ids)) {
      return;
    }

    $syllabuses = \Drupal::entityTypeManager()
      ->getStorage('ssr_syllabus')
      ->loadMultiple($level_syllabus_ids);

    if (empty($syllabuses) || count($syllabuses) === 1) {
      return;
    }

    $syllabuses = array_values($syllabuses);

    // Sort syllabuses by label.
    usort($syllabuses, function ($a, $b) {
      return strnatcmp($a->label(), $b->label());
    });

    foreach ($syllabuses as $delta => $syllabus) {
      $this->list[$delta] = $this->createItem(0, [
        'value' => $syllabus->label(),
      ]);
    }
  }

}
