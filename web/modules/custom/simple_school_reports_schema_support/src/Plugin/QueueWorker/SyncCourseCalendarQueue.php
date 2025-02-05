<?php

namespace Drupal\simple_school_reports_schema_support\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\simple_school_reports_schema_support\Service\CalendarEventsSyncServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sync course calendar.
 *
 * @QueueWorker(
 *   id = "ssr_sync_course_calendar",
 *   title = @Translation("Sync course calendar"),
 *   cron = {"time" = 60}
 * )
 */
class SyncCourseCalendarQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface  {

  protected CalendarEventsSyncServiceInterface $calendarEventsSyncService;

  /**
   * ModifyEntityQueue constructor.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param array $plugin_definition
   * @param \Drupal\simple_school_reports_schema_support\Service\CalendarEventsSyncServiceInterface $calendar_events_sync_service
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition, CalendarEventsSyncServiceInterface $calendar_events_sync_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->calendarEventsSyncService = $calendar_events_sync_service;
  }

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('simple_school_reports_schema_support.calendar_events_sync')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    try {
      if (!empty($data)) {
        $course_id = $data['course_id'] ?? NULL;
        $from = $data['from'] ?? NULL;
        $to = $data['to'] ?? NULL;

        if (!$course_id || !$from || !$to) {
          return;
        }

        $this->calendarEventsSyncService->syncCourseCalendarEvents($course_id, $from, $to, TRUE);
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('ssr_sync_course_calendar')->error($e->getMessage());
    }
  }
}
