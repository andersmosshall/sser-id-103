<?php

namespace Drupal\simple_school_reports_core\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that a string is a valid and unique SSN.
 *
 * Uniqueness is verified by checking the field_ssn table.
 *
 * @Constraint(
 *   id = "SsrSsnConstraint",
 *   label = @Translation("SSN valid and unique constraint"),
 *   type = {"string"}
 * )
 */
class SsnConstraint extends Constraint {

  /**
   * The default invalid violation message.
   *
   * @var string
   */
  public $invalidMessage = 'The entered personal number @value is not valid.';

  /**
   * The default not unique violation message.
   *
   * @var string
   */
  public $notUniqueMessage = 'The entered personal number @value is already used by @user.';

}
