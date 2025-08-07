<?php

namespace Drupal\simple_school_reports_core\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Update old ssr courses
 *
 * @QueueWorker(
 *   id = "ssr_update_course_attendance_report",
 *   title = @Translation("Update ols ssr course attendance reports"),
 *   cron = {"time" = 60}
 * )
 */
class UpdateOldCourseAttendanceReportsQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface  {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * ModifyEntityQueue constructor.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param array $plugin_definition
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entityTypeManager;
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
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if (!is_array($data) || empty($data['course_attendance_report_nid'])) {
      return;
    }
    try {
      /** @var \Drupal\node\NodeInterface|null $attendance_report */
      $attendance_report = $this->entityTypeManager->getStorage('node')->load($data['course_attendance_report_nid']);
      if (!$attendance_report || $attendance_report->bundle() !== 'course_attendance_report') {
        // Course does not exist or is not of type 'course'.
        return;
      }

      // Skip if course already has connection to a syllabus.
      /** @var \Drupal\paragraphs\ParagraphInterface[] $reports */
      $reports = $attendance_report->get('field_student_course_attendance')->referencedEntities();

      $updated_reports = [];

      $syllabus_map = [];

      foreach ($reports as $report) {
        if ($report->bundle() !== 'student_course_attendance') {
          continue;
        }

        $syllabus = $report->get('field_syllabus')->entity;
        if (!$syllabus) {
          $school_subject = $report->get('field_subject')->entity;
          if ($school_subject) {
            $syllabus = $syllabus_map[$school_subject->id()] ?? NULL;
            if (!$syllabus) {
              // Get syllabus connected to the school subject.
              $syllabus = current($this->entityTypeManager->getStorage('ssr_syllabus')->loadByProperties([
                'school_subject' => $school_subject->id(),
              ]));
              $syllabus_map[$school_subject->id()] = $syllabus;
            }

            if ($syllabus) {
              $report->set('field_syllabus', $syllabus);
              $report->save();
            }
          }
        }

        $updated_reports[] = $report;
      }


      $attendance_report->set('field_student_course_attendance', $updated_reports);
      $attendance_report->save();
    }
    catch (\Exception $e) {
      \Drupal::logger('simple_school_reports_core')->error('Error processing item @id in UpdateOldCourseAttendanceReportsQueue: @message', ['@id' => $data['course_attendance_report_nid'], '@message' => $e->getMessage()]);
    }
  }
}
