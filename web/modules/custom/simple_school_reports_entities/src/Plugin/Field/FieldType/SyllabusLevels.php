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


    $syllabus_identifier = $syllabus->get('identifier')->value ?? '?';
    $syllabus_language_code = $syllabus->get('language_code')->value ?? NULL;

    $levels_json = $syllabus->get('levels')->value ?? '[]';
    try {
      $level_course_codes = Json::decode($levels_json);
    }
    catch (\Exception $e) {
      $level_course_codes = [];
    }

    if (empty($level_course_codes)) {
      return;
    }

    $level_identifiers = [];
    $level_identifiers[$syllabus_identifier] = $syllabus_identifier;
    foreach ($level_course_codes as $level_course_code) {
      $level_identifier = ActivateSyllabusFormBase::calculateSyllabusIdentifier($level_course_code, $syllabus_language_code);
      $level_identifiers[$level_identifier] = $level_identifier;
    }

    // Skip if only own identifier is present.
    if (count($level_identifiers) === 1) {
      return;
    }

    $syllabuses = \Drupal::entityTypeManager()
      ->getStorage('ssr_syllabus')
      ->loadByProperties(['identifier' => array_values($level_identifiers)]);

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
