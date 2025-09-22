<?php

namespace Drupal\simple_school_reports_core_gy;

use Drupal\simple_school_reports_core\Service\FileTemplateService;

class ProgrammesSyncHelper {

  public static function syncProgrammes(string $school_type) {
    $file_name = NULL;
    if ($school_type === 'GY:2011') {
      $file_name = 'programmes_gy11.csv';
    } elseif ($school_type === 'GY:2025') {
      $file_name = 'programmes_gy25.csv';
    }
    if (!$file_name) {
      return;
    }
    $path = \Drupal::moduleHandler()->getModuleDirectories()['simple_school_reports_core_gy'] . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . $file_name;
    if (!file_exists($path)) {
      return [];
    }

    $first_row = TRUE;
    $programme_data = [];

    $handle = fopen($path, 'r');
    while (($row = fgetcsv($handle)) !== FALSE) {
      $row = FileTemplateService::trimCsvRow($row);

      // Validate the first row.
      if ($first_row) {
        $first_row = FALSE;

        // Item,Item code,Type,Parent code,Link
        if (count($row) < 5 || $row[0] !== 'Item' || $row[1] !== 'Item code' || $row[2] !== 'Type' || $row[3] !== 'Parent code' || $row[4] !== 'Link') {
          break;
        }
        continue;
      }

      $programme_name = $row[0];
      $programme_code = $row[1];
      $type = $row[2];
      $parent_code = $row[3];
      $link = $row[4];
      $priority = 1;

      $type = mb_strtolower($type);
      if ($type === 'focus') {
        $type = 'programme_focus';
        $priority = 100;
      } else {
        $type = 'programme';
      }

      $programme_data[$programme_code] = [
        'priority' => $priority,
        'label' => $programme_name,
        'code' => mb_strtoupper($programme_code),
        'parent_code' => $parent_code,
        'link' => $link,
        'type' => $type,
      ];
    }
    fclose($handle);

    // Sort by priority.
    uasort($programme_data, function ($a, $b) {
      return $a['priority'] <=> $b['priority'];
    });

    $programmes_map = [];
    $programmes = \Drupal::entityTypeManager()->getStorage('ssr_programme')->loadByProperties(['school_type_versioned' => $school_type]);
    /** @var \Drupal\simple_school_reports_entities\ProgrammeInterface $programme */
    foreach ($programmes as $programme) {
      $code = $programme->get('code')->value;
      $programmes_map[$code] = $programme;
    }

    foreach ($programme_data as $code => $data) {
      if (isset($programmes_map[$code])) {
        // Update existing programme.
        $programme = $programmes_map[$code];
      } else {
        // Create new programme, unpublished by default.
        $programme = \Drupal::entityTypeManager()->getStorage('ssr_programme')->create([
          'type' => $data['type'],
          'school_type_versioned' => $school_type,
          'langcode' => 'sv',
          'status' => 0,
        ]);
      }

      $parent_code = $data['parent_code'] ?? NULL;
      if ($parent_code) {
        // Set parent programme if exists otherwise skip this programme/focus.
        if (!isset($programmes_map[$parent_code])) {
          \Drupal::logger('simple_school_reports_core_gy')->error('Parent programme with code @code not found for programme @programme.', [
            '@code' => $parent_code,
            '@programme' => $data['code'],
          ]);
          continue;
        }
        $programme->set('parent', $programmes_map[$parent_code]->id());

        $data['label'] = $programmes_map[$parent_code]->label() . ' - ' . $data['label'];
      }
      $programme->set('label', $data['label']);
      $programme->set('code', mb_strtoupper($data['code']));
      $programme->set('link', $data['link']);
      $programme->save();
      $programmes_map[$code] = $programme;
    }
  }
}
