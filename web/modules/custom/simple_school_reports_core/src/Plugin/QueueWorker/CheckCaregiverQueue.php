<?php

namespace Drupal\simple_school_reports_core\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Check if caregiver should be deleted.
 *
 * @QueueWorker(
 *   id = "check_caregiver_queue",
 *   title = @Translation("Check if caregiver should be deleted"),
 *   cron = {"time" = 60}
 * )
 */
class CheckCaregiverQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface  {

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
    // Modify entity.
    if (!empty($data)) {
      /** @var \Drupal\user\UserInterface|null $caregiver */
      $caregiver = $this->entityTypeManager->getStorage('user')->load($data);
      if (!$caregiver || $caregiver->hasPermission('super user permissions')) {
        return;
      }

      $roles = $caregiver->getRoles(TRUE);
      // Get roles that is not caregiver.
      $filtered_roles = array_filter($roles, function ($role) {
        return $role !== 'caregiver';
      });

      if (empty($filtered_roles)) {
        // Check if caregiver has any students.
        $students_id = $this->entityTypeManager->getStorage('user')->getQuery()
          ->accessCheck(FALSE)
          ->condition('field_caregivers', $caregiver->id())
          ->execute();

        if (empty($students_id)) {
          // Delete caregiver.
          $caregiver->delete();
        }
      }
    }
  }
}
