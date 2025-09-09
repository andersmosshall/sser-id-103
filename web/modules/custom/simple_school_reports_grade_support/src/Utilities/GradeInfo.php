<?php

namespace Drupal\simple_school_reports_grade_support\Utilities;

class GradeInfo {
  public function __construct(
    public int $id,
    public int $revisionId,
    public int $student,
    public int $syllabusId,
    public ?int $gradeTid,
    public ?int $mainGrader,
    public array $jointGraders,
    public ?\DateTime $date,
    public ?bool $trial,
    public ?string $excludeReason,
    public ?string $remark,
    public bool $replaced,
  ) {}
}
