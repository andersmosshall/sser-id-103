<?php

namespace Drupal\simple_school_reports_extra_adaptations\Service;

/**
 * Interface describing the Extra Adaptations service.
 */
interface ExtraAdaptationsServiceInterface {

  /**
   * @return array
   *   Map to school subject ids for extra adaptations, keyed by extra
   *   adaptation term id.
   */
  public function getExtraAdaptationSubjectMap(): array;

}
