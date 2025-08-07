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
 *   id = "ssr_update_course",
 *   title = @Translation("Update ols ssr courses"),
 *   cron = {"time" = 60}
 * )
 */
class UpdateOldCoursesQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface  {

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
    if (!is_array($data) || empty($data['course_nid'])) {
      return;
    }

    try {
      /** @var \Drupal\node\NodeInterface|null $course */
      $course = $this->entityTypeManager->getStorage('node')->load($data['course_nid']);
      if (!$course || $course->bundle() !== 'course') {
        // Course does not exist or is not of type 'course'.
        return;
      }

      // Skip if course already has connection to a syllabus.
      $syllabus = $course->get('field_syllabus')->entity;
      if ($syllabus) {
        return;
      }

      $school_subject = $course->get('field_school_subject')->entity;
      if (!$school_subject) {
        return;
      }

      // Get syllabus connected to the school subject.
      $syllabus = current($this->entityTypeManager->getStorage('ssr_syllabus')->loadByProperties([
        'school_subject' => $school_subject->id(),
      ]));

      if (!$syllabus) {
        // No syllabus found for the school subject.
        return;
      }

      $course->set('field_syllabus', $syllabus);
      $course->save();
    }
    catch (\Exception $e) {
      \Drupal::logger('simple_school_reports_core')->error('Error processing item @id in UpdateOldCoursesQueue: @message', ['@id' => $data['course_nid'], '@message' => $e->getMessage()]);
    }
  }
}
