<?php

namespace Drupal\simple_school_reports_core\Utilities;

use Drupal\Core\StringTranslation\TranslatableMarkup;

class TimeToStringUtils {

  public static function formatTimeLength(int $length): string {
    if ($length === 0) {
      return '-';
    }

    $hours = floor($length / 3600);
    $min = round(($length % 3600) / 60);

    if ($hours < 10) {
      $hours = '0' . $hours;
    }
    else {
      $hours = number_format($hours, 0, ',', ' ');
    }
    if ($min < 10) {
      $min = '0' . $min;
    }

    return $hours . ':' . $min;
  }
}

