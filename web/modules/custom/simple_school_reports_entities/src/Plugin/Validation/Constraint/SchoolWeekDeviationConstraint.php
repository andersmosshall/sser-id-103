<?php

namespace Drupal\simple_school_reports_entities\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Validation constraint for school week deviation.
 *
 * Only apply to entity type school_week_deviation.
 */
#[Constraint(
  id: 'SsrSchoolWeekDeviationConstraint',
  label: new TranslatableMarkup('School week deviation constraint'),
  type: ['entity']
)]
class SchoolWeekDeviationConstraint extends SymfonyConstraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $missingStartOrEndDate = 'If the school week deviation has teaching hours, it must have a start and end date for the school day.';

}
