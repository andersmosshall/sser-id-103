<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_grade_support;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a grade entity type.
 */
interface GradeInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {
  public const CORRECTION_TYPE_CORRECTED = 'corrected';
  public const CORRECTION_TYPE_CHANGED = 'changed';

  public const SEMESTER_HT = 'ht';
  public const SEMESTER_VT = 'vt';

  public const EXCLUDE_REASON_ADAPTED_STUDIES = 'adapted_studies';

  /**
   * @param \Drupal\simple_school_reports_grade_support\GradeInterface|null $original
   *
   * @return bool
   */
  public function hasChanges(?GradeInterface $original = NULL): bool;

  /**
   * @return self
   */
  public function setIdentifier(): self;

  /**
   * @return self
   */
  public function sanitizeFields(): self;

}
