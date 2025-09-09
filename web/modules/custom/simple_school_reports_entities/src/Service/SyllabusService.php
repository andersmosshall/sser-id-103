<?php

namespace Drupal\simple_school_reports_entities\Service;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\simple_school_reports_core\Form\ActivateSyllabusFormBase;

/**
 * Support methods for syllabus stuff.
 */
class SyllabusService implements SyllabusServiceInterface {
  use StringTranslationTrait;

  private array $lookup = [];

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Connection $database,
  ) {}

  protected function warmUpMap(): void {
    $cid_by_syllabus_id = 'syllabus_id_map';
    $cid_syllabus_identifier = 'syllabus_identifier_map';
    if (isset($this->lookup[$cid_by_syllabus_id])) {
      return;
    }

    $map_by_syllabus_id = [];
    $syllabus_identifier_map = [];

    $group_for_query = $this->database->select('ssr_syllabus__group_for', 'gf')
      ->fields('gf', ['entity_id', 'group_for_target_id'])
      ->execute();

    $group_for_map = [];
    foreach ($group_for_query as $result) {
      $group_for_map[$result->entity_id][] = $result->group_for_target_id;
    }

    $results = $this->database->select('ssr_syllabus_field_data', 's')
      ->fields('s', ['id', 'identifier', 'label', 'language_code', 'levels__value'])
      ->orderBy('s.label', 'ASC')
      ->execute();


    $rows = $results->fetchAll();

    foreach ($rows as $result) {
      $syllabus_identifier_map[$result->identifier] = $result->id;
    }

    foreach ($rows as $result) {
      $syllabus_identifier = $result->identifier;

      // Resolve level syllabus identifiers.
      $level_syllabus_ids = [];
      $syllabus_language_code = $result->language_code ?? NULL;
      $levels_json = $result->levels__value;
      $level_course_codes = [];
      try {
        if (!empty($levels_json)) {
          $level_course_codes = Json::decode($levels_json);
        }
      }
      catch (\Exception $e) {
        $level_course_codes = [];
      }

      $level_identifiers = [];
      $level_identifiers[$syllabus_identifier] = $syllabus_identifier;
      foreach ($level_course_codes as $level_course_code) {
        $level_identifier = ActivateSyllabusFormBase::calculateSyllabusIdentifier($level_course_code, $syllabus_language_code);
        $level_identifiers[$level_identifier] = $level_identifier;
      }

      // Only resolve levels if there is more than one level (e.g. more than self syllabus).
      if (count($level_identifiers) > 1) {
        foreach ($level_identifiers as $level_identifier) {
          if (isset($syllabus_identifier_map[$level_identifier])) {
            $level_syllabus_ids[] = $syllabus_identifier_map[$level_identifier];
          }
        }
      }

      $group_for_ids = $group_for_map[$result->id] ?? [];

      $map_by_syllabus_id[$result->id] = [
        'id' => $result->id,
        'identifier' => $syllabus_identifier,
        'label' => $result->label,
        'group_for' => $group_for_ids,
        'levels' => $level_syllabus_ids,
        'language_code' => $result->language_code,
        'associated_syllabuses' => array_unique(array_merge([$result->id], $level_syllabus_ids, $group_for_ids)),
      ];
    }
    $this->lookup[$cid_by_syllabus_id] = $map_by_syllabus_id;
    $this->lookup[$cid_syllabus_identifier] = $syllabus_identifier_map;
  }

  /**
   * {@inheritdoc}
   */
  public function getSyllabusAssociations(array $syllabus_ids): array {
    $this->warmUpMap();
    $map = $this->lookup['syllabus_id_map'] ?? [];

    $final_list = $syllabus_ids;
    foreach ($syllabus_ids as $syllabus_id) {
      if (isset($map[$syllabus_id])) {
        $final_list = array_merge($final_list, $map[$syllabus_id]['associated_syllabuses']);
      }
    }
    return array_unique($final_list);
  }

  /**
   * {@inheritdoc}
   */
  public function getSyllabusLevelIds(int $syllabus_id): array {
    $this->warmUpMap();
    $map = $this->lookup['syllabus_id_map'] ?? [];
    if (empty($map[$syllabus_id]['levels'])) {
      return [];
    }
    return $map[$syllabus_id]['levels'];
  }

  /**
   * {@inheritdoc}
   */
  public function getSyllabusLabelsInOrder(?array $syllabus_ids = NULL): array {
    $this->warmUpMap();
    $map = $this->lookup['syllabus_id_map'] ?? [];

    $names = [];

    if (is_array($syllabus_ids)) {
      foreach ($syllabus_ids as $syllabus_id) {
        if (isset($map[$syllabus_id])) {
          $names[$syllabus_id] = $map[$syllabus_id]['label'];
        }
      }
    }
    else {
      foreach ($map as $syllabus_id => $data) {
        $names[$syllabus_id] = $data['label'];
      }
    }

    return $names;
  }

  /**
   * {@inheritdoc}
   */
  public function getSyllabusWeight(?array $syllabus_ids = NULL): array {
    $weight = 0;
    $weight_list = [];
    $syllabus_ids_in_order = array_keys($this->getSyllabusLabelsInOrder($syllabus_ids));
    foreach ($syllabus_ids_in_order as $syllabus_id) {
      $weight++;
      $weight_list[$syllabus_id] = $weight;
    }
    return $weight_list;
  }
}
