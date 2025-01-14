<?php

namespace Drupal\simple_school_reports_core;

use Personnummer\Personnummer;

  /**
   * @file
   * Example profile file.
   */

class Pnum {

  /**
   * Get personal number in forma YYMMDD-NNNN if valid. Otherwise, return NULL.
   *
   * @param string $ssn
   * @param bool $long_format
   *
   * @return string|null
   */
  public function normalizeIfValid(string $ssn, bool $long_format = FALSE): ?string {
    try {
      $pnum = Personnummer::parse($ssn, [
        'allowCoordinationNumber' => TRUE,
        'allowInterimNumber' => TRUE,
      ]);

      $d = (new \DateTime())->setTimestamp($this->getBirthDateTimestamp($ssn));
      $is_male = $pnum->isMale();

      return $pnum->format($long_format);
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

  public function getBirthDateTimestamp(string $ssn): ?string {
    try {
      $pnum = Personnummer::parse($ssn, [
        'allowCoordinationNumber' => TRUE,
        'allowInterimNumber' => TRUE,
      ]);
      $date = $pnum->getDate();
      $date->setTime(0, 0, 0);

      return $date->getTimestamp();
    }
    catch (\Exception $e) {
      return NULL;
    }
  }
}
