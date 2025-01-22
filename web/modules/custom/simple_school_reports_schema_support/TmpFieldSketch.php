<?php

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

$fields['source'] = BaseFieldDefinition::create('list_string')
  ->setLabel(t('Source'))
  ->setSetting('allowed_values_function', 'simple_school_reports_schema_support_schema_source_options')
  ->setRequired(TRUE)
  ->setDefaultValue('ssr')
  ->setDisplayConfigurable('form', TRUE)
  ->setDisplayConfigurable('view', TRUE);

$fields['lesson_id'] = BaseFieldDefinition::create('uuid')
  ->setLabel(new TranslatableMarkup('UUID'))
  ->setReadOnly(TRUE);

$fields['week_day'] =  BaseFieldDefinition::create('list_integer')
  ->setLabel(t('Day'))
  ->setRequired(TRUE)
  ->setSetting('allowed_values_function', 'ssr_day_map')
  ->setDisplayOptions('form', [
    'type' => 'options_select',
  ])
  ->setDisplayConfigurable('form', TRUE)
  ->setDisplayConfigurable('view', TRUE);

$fields['from'] = BaseFieldDefinition::create('time')
  ->setLabel(t('Lesson start time'))
  ->setRequired(TRUE)
  ->setDisplayConfigurable('form', TRUE)
  ->setDisplayConfigurable('view', TRUE);

$fields['length'] = BaseFieldDefinition::create('integer')
  ->setLabel(t('Lesson length'))
  ->setLabel(t('Set lesson length in minutes'))
  ->setSetting('unsigned', TRUE)
  ->setSetting('min', 1)
  ->setSetting('max', 1200)
  ->setRequired(TRUE)
  ->setDisplayConfigurable('form', TRUE)
  ->setDisplayConfigurable('view', TRUE);

$fields['deviated'] = BaseFieldDefinition::create('boolean')
  ->setLabel(t('Deviated group or periodicity'))
  ->setDefaultValue(TRUE)
  ->setSetting('on_label', t('Yes'))
  ->setSetting('on_label', t('No'))
  ->setDisplayConfigurable('form', TRUE)
  ->setDisplayConfigurable('view', TRUE);

$number_of_groups = 5;

$fields['relevant_groups'] = BaseFieldDefinition::create('list_integer')
  ->setLabel(t('Number of groups'))
  ->setRequired(TRUE)
  ->setDefaultValue(2)
  ->setSetting('allowed_values_function', 'simple_school_reports_schema_support_schema_subgroups')
  ->setDisplayConfigurable('form', TRUE)
  ->setDisplayConfigurable('view', TRUE);

for ($i = 1; $i <= $number_of_groups; $i++) {
  $fields['display_name_' . $i] = BaseFieldDefinition::create('entity_reference')
    ->setLabel(t('Group name @number', ['@number' => $i]))
    ->setRequired(TRUE)
    ->setDefaultValue('Grupp ' . $i)
    ->setDisplayConfigurable('form', TRUE)
    ->setDisplayConfigurable('view', TRUE);

  $fields['periodicity_' . $i] = BaseFieldDefinition::create('list_string')
    ->setLabel(t('Periodicity for group @number', ['@number' => $i]))
    ->setSetting('allowed_values_function', 'simple_school_reports_schema_support_schema_periodicity_options')
    ->setRequired(TRUE)
    ->setDefaultValue('weekly')
    ->setDisplayConfigurable('form', TRUE)
    ->setDisplayConfigurable('view', TRUE);

  $fields['custom_periodicity_' . $i] = BaseFieldDefinition::create('list_integer')
    ->setLabel(t('Specific periodicity for group @number', ['@number' => $i]))
    ->setRequired(TRUE)
    ->setDefaultValue(2)
    ->setSetting('allowed_values_function', 'simple_school_reports_schema_support_schema_custom_periodicity_options')
    ->setDisplayConfigurable('form', TRUE)
    ->setDisplayConfigurable('view', TRUE);

  $fields['custom_periodicity_start_' . $i] = BaseFieldDefinition::create('timestamp')
    ->setLabel(t('With start at'))
    ->setRequired(TRUE)
    ->setDefaultValue((new DateTime())->setTime(12, 0, 0)->getTimestamp())
    ->setDisplayConfigurable('form', TRUE)
    ->setDisplayConfigurable('view', TRUE);

  $fields['students_' . $i] = BaseFieldDefinition::create('entity_reference')
    ->setTranslatable(TRUE)
    ->setLabel(t('Students'))
    ->setDescription(t('Students not relevant for the student list for the corresponding course will be ignored.'))
    ->setSetting('target_type', 'user')
    ->setSetting('handler', 'views')
    ->setSetting('handler_settings', [
      'view' => [
        'arguments' => [],
        'display_name' => 'active_students',
        'view_name' => 'student_reference',
      ],
    ])
    ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
    ->setDisplayConfigurable('form', TRUE)
    ->setDisplayConfigurable('view', TRUE);
}
