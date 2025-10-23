<?php

namespace Drupal\simple_school_reports_entities\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Validation constraint for ssr organization.
 *
 * Only apply to entity type ssr_organization.
 */
#[Constraint(
  id: 'SsrOrganizationConstraint',
  label: new TranslatableMarkup('Organization constraint'),
  type: ['entity']
)]
class SsrOrganizationConstraint extends SymfonyConstraint {

  public $missingType = 'Organization type must be set';

  /**
   * @var string
   */
  public $missingSchool = 'Parent organization of type school must be set';

  /**
   * @var string
   */
  public $missingOrganizer = 'Parent organization of type organizer must be set';

  /**
   * @var string
   */
  public $schoolTypeOccupied = 'There is already a school unit set for the school type %type';

  /**
   * @var string
   */
  public $missingShortname = 'A short name must be set';

}
