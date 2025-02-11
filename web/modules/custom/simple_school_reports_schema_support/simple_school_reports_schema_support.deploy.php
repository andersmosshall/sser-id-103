<?php

use Drupal\time_field\Time;

/**
 * Migrate base_schema to ssr_schema.
 */
function simple_school_reports_schema_support_deploy_10001() {
  $database = \Drupal::database();

  // No need to run this migration on new systems that don't have the old
  // schema.
  if(!$database->schema()->tableExists('node__field_schema')) {
    return;
  }

  // Only migrate courses that have a schema and has been changed the last
  // 18 months.

  $updated_limit = new \DateTime('now');
  $updated_limit->sub(new \DateInterval('P18M'));

  $course_ids = \Drupal::entityTypeManager()->getStorage('node')->getQuery()
    ->accessCheck(FALSE)
    ->condition('type', 'course')
    ->condition('field_schema', NULL, 'IS NOT NULL')
    ->condition('changed', $updated_limit->getTimestamp(), '>')
    ->execute();

  $ssr_schema_entry_storage = \Drupal::entityTypeManager()->getStorage('ssr_schema_entry');

  foreach ($course_ids as $course_id) {
    /** @var \Drupal\node\NodeInterface $course */
    $course = \Drupal::entityTypeManager()->getStorage('node')->load($course_id);

    if (!$course->hasField('field_schema') || !$course->hasField('field_ssr_schema')) {
      continue;
    }

    $ssr_schema_entries = [];
    if ($course->get('field_schema')->isEmpty()) {
      continue;
    }

    foreach ($course->get('field_schema')->referencedEntities() as $schema) {
      $day = $schema->get('field_day')->value;
      $duration = $schema->get('field_duration')->value;
      $start = $schema->get('field_class_start')->value;

      if (!$day || !$duration || !$start) {
        continue;
      }

      $start_date = new \DateTime('now');
      $start_date->setTimestamp($start);

      $from_time = Time::createFromHtml5Format($start_date->format('H:i:00'));

      $schema_entry = $ssr_schema_entry_storage->create([
        'langcode' => 'sv',
        'source' => 'ssr',
        'week_day' => $day,
        'length' => $duration,
        'from' => $from_time->getTimestamp(),
      ]);
      $schema_entry->save();
      $ssr_schema_entries[] = $schema_entry->id();
    }

    $course->set('field_schema', []);
    $course->set('field_ssr_schema', $ssr_schema_entries);
    $course->setSyncing(TRUE);
    $course->save();
    $course->setSyncing(FALSE);
  }
}
