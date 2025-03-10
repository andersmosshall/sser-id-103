<?php

namespace Drupal\simple_school_reports_core\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Delete entity queue
 *
 * @QueueWorker(
 *   id = "modify_entity_queue",
 *   title = @Translation("Modify entity queue"),
 *   cron = {"time" = 60}
 * )
 */
class ModifyEntityQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface  {

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
    if (!empty($data['entity_type']) && !empty($data['entity_id']) && !empty($data['fields'])) {
      try {
        $storage = $this->entityTypeManager->getStorage($data['entity_type']);
      }
      catch (\Exception $e) {
        // Entity type doesnt exists.
        $storage = NULL;
      }

      if (!$storage) {
        return;
      }


      $entity = $storage->load($data['entity_id']);
      if ($entity) {
        foreach ($data['fields'] as $field => $value) {
          if (method_exists($entity, 'hasField') && $entity->hasField($field)) {
            $entity->set($field, $value);
          }
        }
        $entity->save();
      }
    }
  }
}
