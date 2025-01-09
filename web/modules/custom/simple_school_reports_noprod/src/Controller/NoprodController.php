<?php

namespace Drupal\simple_school_reports_noprod\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for NoprodController.
 */
class NoprodController extends ControllerBase {

  public function replaceTableHelper() {
    $build = [];

    $build['settings_check_label'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t('Replace table helper'),
    ];

    $headers = [];

    $rows = [];
    $max_rows = 32;
    $max_cols = 23;

    for ($row_id = 1; $row_id <= $max_rows; $row_id++) {
      $cells = [];
      for ($col_id = 1; $col_id <= $max_cols; $col_id++) {
        $cells[] = [
          'data' => '!' . $row_id . '!' . $col_id . '!',
        ];
      }
      $rows[] = $cells;
    }

    $build['replace_table_helper'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
    ];

    return $build;
  }

}
