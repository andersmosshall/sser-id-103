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
      ->fields('s', ['id', 'identifier', 'label', 'language_code', 'levels__value', 'course_code', 'points'])
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
      $previous_level_syllabus_ids = [];
      $previous_level_identifiers = [];
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
      foreach ($level_course_codes as $level_course_code) {
        $level_identifier = ActivateSyllabusFormBase::calculateSyllabusIdentifier($level_course_code, $syllabus_language_code);
        $level_identifiers[$level_identifier] = $level_identifier;
      }
      if (!isset($level_identifiers[$syllabus_identifier])) {
        $level_identifiers[$syllabus_identifier] = $syllabus_identifier;
      }

      // Only resolve levels if there is more than one level (e.g. more than self syllabus).
      if (count($level_identifiers) > 1) {
        $prevent_previous_levels = FALSE;
        foreach ($level_identifiers as $level_identifier) {
          if ($level_identifier === $syllabus_identifier) {
            $prevent_previous_levels = TRUE;
          }

          if (!$prevent_previous_levels) {
            $previous_level_identifiers[] = $level_identifier;
          }

          if (isset($syllabus_identifier_map[$level_identifier])) {
            $level_syllabus_ids[] = $syllabus_identifier_map[$level_identifier];
            if (!$prevent_previous_levels) {
              $previous_level_syllabus_ids[] = $syllabus_identifier_map[$level_identifier];
            }
          }
        }
      }

      $group_for_ids = $group_for_map[$result->id] ?? [];

      $use_diploma_project = FALSE;
      if (str_starts_with($result->course_code ?? '', 'GYAR')) {
        $use_diploma_project = TRUE;
      }

      $map_by_syllabus_id[$result->id] = [
        'id' => $result->id,
        'identifier' => $syllabus_identifier ?? '',
        'label' => $result->label ?? '',
        'course_code' => $result->course_code ?? '',
        'group_for' => $group_for_ids,
        'levels' => $level_syllabus_ids,
        'previous_levels' => $previous_level_syllabus_ids,
        'previous_levels_identifiers' => $previous_level_identifiers,
        'points' => is_numeric($result->points) ? (int) $result->points : NULL,
        'aggregated_points' => NULL,
        'language_code' => $result->language_code,
        'associated_syllabuses' => array_unique(array_merge([$result->id], $level_syllabus_ids, $group_for_ids)),
        'use_diploma_project' => $use_diploma_project,
      ];
    }

    // Calculate aggregate points.
    foreach ($map_by_syllabus_id as $syllabus_id => $syllabus) {
      if (empty($syllabus['previous_levels_identifiers'])) {
        continue;
      }
      if ($syllabus['points'] === NULL) {
        continue;
      }
      $aggregated_points = $syllabus['points'];
      foreach ($syllabus['previous_levels_identifiers'] as $previous_level_identifier) {
        $previous_level_points = NULL;
        if (isset($syllabus_identifier_map[$previous_level_identifier])) {
          $previous_level_id = $syllabus_identifier_map[$previous_level_identifier];
          if (isset($map_by_syllabus_id[$previous_level_identifier])) {
            $previous_level_points = $map_by_syllabus_id[$previous_level_identifier]['points'] ?? 0;
          }
        }

        if ($previous_level_points === NULL) {
          // Look for previous level points in syllabus import services that
          // may have level support.
          $services = [
            'simple_school_reports_core_gr.course_data',
            'simple_school_reports_core_gy11.course_data',
            'simple_school_reports_core_gy25.course_data',
          ];

          $course_code = ActivateSyllabusFormBase::parseSyllabusIdentifier($previous_level_identifier)['course_code'] ?? NULL;

          foreach ($services as $service_name) {
            if (!$course_code || !\Drupal::hasService($service_name)) {
              continue;
            }
            $service = \Drupal::service($service_name);
            $course_data = $service->getCourseData()[$course_code] ?? NULL;
            if (!$course_data) {
              continue;
            }
            $previous_level_points = $course_data['points'] ?? 0;
            break;
          }
        }

        if (is_numeric($previous_level_points)) {
          $aggregated_points += $previous_level_points;
        }
      }
      $map_by_syllabus_id[$syllabus_id]['aggregated_points'] = $aggregated_points;
    }

    $this->lookup[$cid_by_syllabus_id] = $map_by_syllabus_id;
    $this->lookup[$cid_syllabus_identifier] = $syllabus_identifier_map;
  }

  /**
   * {@inheritdoc}
   */
  public function getSyllabusAssociations(array $syllabus_ids): array {
    if (empty($syllabus_ids)) {
      return [];
    }

    $this->warmUpMap();
    $map = $this->lookup['syllabus_id_map'] ?? [];

    $final_list = $syllabus_ids;
    foreach ($syllabus_ids as $syllabus_id) {
      if (isset($map[$syllabus_id])) {
        $final_list = array_merge($final_list, $map[$syllabus_id]['associated_syllabuses']);
      }
    }
    return array_unique(array_values($final_list));
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
  public function getSyllabusPreviousLevelIds(int $syllabus_id): array {
    $this->warmUpMap();
    $map = $this->lookup['syllabus_id_map'] ?? [];
    if (empty($map[$syllabus_id]['previous_levels'])) {
      return [];
    }
    return $map[$syllabus_id]['previous_levels'];
  }

  /**
   * {@inheritdoc}
   */
  public function getSyllabusLabelsInOrder(?array $syllabus_ids = NULL): array {
    $this->warmUpMap();
    $map = $this->lookup['syllabus_id_map'] ?? [];

    $names = [];

    if (is_array($syllabus_ids) && count($syllabus_ids) === 1) {
      $syllabus_id = $syllabus_ids[0];
      if (isset($map[$syllabus_id])) {
        $names[$syllabus_id] = $map[$syllabus_id]['label'];
      }
      return $names;
    }

    foreach ($map as $syllabus_id => $data) {
      if (!is_array($syllabus_ids)) {
        $names[$syllabus_id] = $data['label'];
        continue;
      }
      if (!in_array($syllabus_id, $syllabus_ids)) {
        continue;
      }
      $names[$syllabus_id] = $data['label'];
    }

    return $names;
  }

  /**
   * {@inheritdoc}
   */
  public function getSyllabusCourseCodesInOrder(?array $syllabus_ids = NULL): array {
    $this->warmUpMap();
    $map = $this->lookup['syllabus_id_map'] ?? [];

    $course_codes = [];

    if (is_array($syllabus_ids)) {
      foreach ($syllabus_ids as $syllabus_id) {
        if (isset($map[$syllabus_id])) {
          $course_codes[$syllabus_id] = $map[$syllabus_id]['course_code'];
        }
      }
    }
    else {
      foreach ($map as $syllabus_id => $data) {
        $course_codes[$syllabus_id] = $data['course_code'];
      }
    }

    return $course_codes;
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

  public function getSyllabusPreviousPoints(int $syllabus_id): array {
    $this->warmUpMap();
    $map = $this->lookup['syllabus_id_map'] ?? [];

    $points = NULL;
    $aggregated_points = NULL;

    if (!empty($map[$syllabus_id])) {
      $points = $map[$syllabus_id]['points'];
      $aggregated_points = $map[$syllabus_id]['aggregated_points'];
    }
    return [
      'points' => $points,
      'aggregated_points' => $aggregated_points,
    ];
  }

  public function useDiplomaProject(int $syllabus_id): bool {
    $this->warmUpMap();
    $map = $this->lookup['syllabus_id_map'] ?? [];

    return !empty($map[$syllabus_id]['use_diploma_project']);
  }
}
