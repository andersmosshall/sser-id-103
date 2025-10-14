<?php

namespace Drupal\simple_school_reports_grade_support\Utilities;

class GradeInfoMinimal {
  public function __construct(
    public int $id,
    public int $revisionId,
    public int $student,
    public int $syllabusId,
    public ?int $gradeTid,
    public ?int $points,
    public ?GradeInfoMinimal $previousLevel = null,
  ) {}
}
