<?php

namespace Drupal\simple_school_reports_core\Plugin\EntityReferenceSelection;

use Drupal\user\Plugin\EntityReferenceSelection\UserSelection;

/**
 * Provides specific access control for the user entity type.
 *
 * @EntityReferenceSelection(
 *   id = "simple_school_reports_core_user_selection",
 *   label = @Translation("User first/last name selection"),
 *   entity_types = {"user"},
 *   group = "default",
 *   weight = 1
 * )
 */
class SSRUserSelection extends UserSelection {

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery(NULL, $match_operator);

    if ($match) {
      $group = $query->orConditionGroup()
        ->condition('field_first_name', $match, $match_operator)
        ->condition('field_last_name', $match, $match_operator);
      $query->condition($group);
    }

    return $query;
  }
}
