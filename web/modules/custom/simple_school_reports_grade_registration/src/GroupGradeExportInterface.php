<?php

namespace Drupal\simple_school_reports_grade_registration;

/**
 * Provides an interface grade group export services.
 */
interface GroupGradeExportInterface {

  /**
   * @param string $student_group_nid
   * @param array $references
   * @param array $context
   */
  public function handleExport(string $student_group_nid, array $references, array &$context);

  /**
   * @param array $references
   * @param array $context
   */
  public function beforeFinishExport(array $references, array &$context);

}
