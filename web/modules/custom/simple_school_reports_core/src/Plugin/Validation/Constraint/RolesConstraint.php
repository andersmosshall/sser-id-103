<?php

namespace Drupal\simple_school_reports_core\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Validation constraint for the user roles in SSR.
 *
 * Only apply to entity type user.
 */
#[Constraint(
  id: 'SsrRolesConstraint',
  label: new TranslatableMarkup('Roles constraint'),
  type: ['entity']
)]
class RolesConstraint extends SymfonyConstraint {

  /**
   * The default disallowed roles combination violation message.
   *
   * @var string
   */
  public $invalidCombinatioinMessage = 'This combination of roles is not allowed.';

  public $onlyStudentRoleMessage = 'User with role student cannot have any other role.';

}
