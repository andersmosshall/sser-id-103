<?php

use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Implements HOOK_deploy_NAME().
 */
function simple_school_reports_entities_deploy_10001() {
  $vid = 'school_week_deviation_type';
  /** @var \Drupal\taxonomy\TermStorageInterface $termStorage */
  $termStorage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');


  $deviation_types = $termStorage->loadTree($vid, 0, NULL, TRUE);
  $deviation_type_exists = [];
  foreach ($deviation_types as $deviation_type) {
    $deviation_type_exists[$deviation_type->label()] = TRUE;
  }

  $to_import = [
    'Höstlov',
    'Jullov',
    'Sportlov',
    'Påsklov',
    'Sommarlov',
    'Studiedag',
  ];

  foreach ($to_import as $label) {
    if (empty($deviation_type_exists[$label])) {
      $term = $termStorage->create([
        'name' => $label,
        'vid' => $vid,
        'langcode' => 'sv',
      ]);
      $term->save();
    }
  }
}

/**
 * Implements HOOK_deploy_NAME().
 */
function simple_school_reports_entities_deploy_10002() {
  // Install deviation type field.
  $storage_definition = \Drupal\Core\Field\BaseFieldDefinition::create('entity_reference')
    ->setLabel(t('Specific deviation'))
    ->setDescription(t('Specific deviation for this school week only.'))
    ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
    ->setSetting('target_type', 'school_week_deviation')
    ->setDisplayConfigurable('form', TRUE)
    ->setDisplayConfigurable('view', TRUE);

  \Drupal::entityDefinitionUpdateManager()
    ->installFieldStorageDefinition('deviation', 'school_week', 'simple_school_reports_entities', $storage_definition);
}

