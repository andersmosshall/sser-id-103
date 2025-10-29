<?php

namespace Drupal\simple_school_reports_entities\Plugin\Validation\Constraint;

use Drupal\simple_school_reports_core\SchoolTypeHelper;
use Drupal\simple_school_reports_entities\SchoolWeekDeviationInterface;
use Drupal\simple_school_reports_entities\SSROrganizationInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates ssr organization.
 */
class SsrOrganizationConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    if (!$entity instanceof SSROrganizationInterface) {
      return;
    }

    if (!$constraint instanceof SsrOrganizationConstraint) {
      return;
    }

    $organization_type = $entity->get('organization_type')->value;
    if (!$organization_type) {
      $this->context->addViolation($constraint->missingType);
    }

    $parent = $entity->get('parent')->entity;
    $parent_type = $parent?->get('organization_type')->value ?? NULL;

    if ($organization_type ===  'school_organiser' && $parent && $parent_type !== 'school_organiser') {
      $this->context->addViolation($constraint->missingOrganizer);
    }

    // School must have a parent of type school organiser.
    if ($organization_type === 'school' && $parent_type !== 'school_organiser') {
      $this->context->addViolation($constraint->missingOrganizer);
    }

    // School unit must have a parent of type school.
    if ($organization_type === 'school_unit' && $parent_type !== 'school') {
      $this->context->addViolation($constraint->missingSchool);
    }

    // There can only be one school unit per school type in a school.
    if ($organization_type === 'school_unit') {
      $school_types = array_column($entity->get('school_types')->getValue(), 'value');
      $school_types_to_check = SchoolTypeHelper::getSupportedSchoolTypes(FALSE);
      if (!empty($school_types) && !empty($school_types_to_check)) {
        $database = \Drupal::database();
        $query = $database->select('ssr_organization__school_types', 'st');
        $query->condition('st.school_types_value', $school_types, 'IN');
        $query->fields('st', ['entity_id', 'school_types_value']);
        $results = $query->execute();

        $map = [];
        foreach ($results as $result) {
          $map[$result->school_types_value ?? '?'] = $result->entity_id;
        }

        foreach ($school_types as $school_type) {
          if (isset($map[$school_type]) && $map[$school_type] != $entity->id()) {
            $this->context->addViolation($constraint->schoolTypeOccupied, ['%type' => $school_type]);
          }
        }
      }
    }

    // Short name validation.
    if ($organization_type === 'school' || $organization_type === 'school_unit') {
      if ($entity->get('short_name')->isEmpty()) {
        $this->context->addViolation($constraint->missingShortname);
      }
    }
  }
}
