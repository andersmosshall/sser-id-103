<?php

namespace Drupal\simple_school_reports_core;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\node\NodeInterface;

class SchoolTypeHelper {

  public static function getSupportedSchoolTypes(): array {
    $school_types = [
      'FS' => 'Förskola',
      'FKLASS' => 'Förskoleklass',
      'FTH' => 'Fritidshem',
      'OPPFTH' => 'Öppen fritidsverksamhet',
      'GR' => 'Grundskola',
      'GRS' => 'Grundsärskola',
      'TR' => 'Träningsskolan',
      'SP' => 'Specialskola',
      'SAM' => 'Sameskola',
      'GY' => 'Gymnasieskola',
      'GYS' => 'Gymnasiesärskola',
      'VUX' => 'Kommunal vuxenutbildning',
      'VUXSFI' => 'Kommunal vuxenutbildning i svenska för invandrare',
      'VUXGR' => 'Kommunal vuxenutbildning på grundläggande nivå',
      'VUXGY' => 'Kommunal vuxenutbildning på gymnasial nivå',
      'VUXSARGR' => 'Kommunal vuxenutbildning som särskild utbildning på grundläggande nivå',
      'VUXSARTR' => 'Kommunal vuxenutbildning som särskild utbildning som motsvarar träningsskolan',
      'VUXSARGY' => 'Kommunal vuxenutbildning som särskild utbildning på gymnasial nivå',
      'SFI' => 'Utbildning i svenska för invandrare',
      'SARVUX' => 'Särskild utbildning för vuxna',
      'SARVUXGR' => 'Särskild utbildning för vuxna på grundläggande nivå',
      'SARVUXGY' => 'Särskild utbildning för vuxna på gymnasial nivå',
      'SFI' => 'Kommunal vuxenutbildning i svenska för invandrare',
      'KU' => 'Kulturskola',
      'YH' => 'Yrkeshögskola',
      'FHS' => 'Folkhögskola',
      'STF' => 'Studieförbund',
      'KKU' => 'Konst- och kulturutbildning',
      'HS' => 'Högskola',
      'ABU' => 'Arbetsmarknadsutbildning',
      'AU' => 'Annan undervisning',
    ];

    return $school_types;
  }

  public static function getSchoolTypes(): array {
    $grades = SchoolGradeHelper::getSchoolGradeValues();
    $school_types = [];

    foreach ($grades as $grade) {
      if ($grade < 0) {
        $school_types['FS'] = TRUE;
      }
      elseif ($grade === 0) {
        $school_types['FKLASS'] = TRUE;
      }
      elseif ($grade >= 1 && $grade <= 9) {
        $school_types['GR'] = TRUE;
      }
      elseif ($grade > 10000 && $grade < 20000) {
        $school_types['GY'] = TRUE;
      }
    }

    return array_keys($school_types);
  }
}
