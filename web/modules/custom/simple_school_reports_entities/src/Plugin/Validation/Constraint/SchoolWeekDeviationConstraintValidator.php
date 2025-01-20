<?php

namespace Drupal\simple_school_reports_entities\Plugin\Validation\Constraint;

use Drupal\simple_school_reports_entities\SchoolWeekDeviationInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates school week deviation.
 */
class SchoolWeekDeviationConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    if (!$entity instanceof SchoolWeekDeviationInterface) {
      return;
    }

    if (!$constraint instanceof SchoolWeekDeviationConstraint) {
      return;
    }

    $no_teaching = !!($entity->get('no_teaching')->value ?? FALSE);
    if (!$no_teaching) {
      $from = $entity->get('from')->value;
      $to = $entity->get('to')->value;
      if (!$from || !$to) {
        $this->context->addViolation($constraint->missingStartOrEndDate);
      }
    }
  }

}
