<?php

namespace Drupal\simple_school_reports_grade_support\Utilities;

class GradeReference {
  public function __construct(
    public int $id,
    public int $revisionId,
  ) {}
}
