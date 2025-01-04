<?php

namespace Drupal\simple_school_reports_core\Plugin\Validation\Constraint;

use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\user\UserInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates combination of roles.
 */
class RolesConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    if (!$entity instanceof UserInterface) {
      return;
    }

    if (!$constraint instanceof RolesConstraint) {
      return;
    }

    $roles = [];
    foreach ($entity->getRoles() as $role) {
      // Skip authenticated and anonymous roles.
      if ($role === 'authenticated' || $role === 'anonymous') {
        continue;
      }

      $roles[$role] = $role;
    }

    if (empty($roles)) {
      return;
    }

    // Student role is not allowed to be combined with any other role.
    if (count($roles) > 1 && isset($roles['student'])) {
      $this->context->addViolation($constraint->onlyStudentRoleMessage);
    }
  }

}
