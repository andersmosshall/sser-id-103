<?php

namespace Drupal\simple_school_reports_core\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Site\Settings;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates combination of roles.
 */
class RolesConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Constructs a RolesConstraintValidator object.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
    );
  }

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

    // There can only be a defined number of super admin user.
    if (isset($roles['super_admin'])) {
      $allowed_super_admins = (int) Settings::get('ssr_allowed_super_admins', 0);
      if ($allowed_super_admins <= 0) {
        $this->context->addViolation($constraint->tooManySuparAdminUsersMessage, ['@number' => $allowed_super_admins]);
      }
      else {
        $super_admins_ids = $this->entityTypeManager->getStorage('user')->getQuery()
          ->accessCheck(FALSE)
          ->condition('roles', 'super_admin')
          ->condition('uid', $entity->id(), '<>')
          ->execute();

        if (count($super_admins_ids) + 1 > $allowed_super_admins) {
          $this->context->addViolation($constraint->tooManySuparAdminUsersMessage, ['@number' => $allowed_super_admins]);
        }
      }
    }
  }
}
