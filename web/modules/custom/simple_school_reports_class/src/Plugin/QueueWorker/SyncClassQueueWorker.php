<?php

namespace Drupal\simple_school_reports_class\Plugin\QueueWorker;


use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\State\StateInterface;
use Drupal\simple_school_reports_class_support\Service\SsrClassServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Sync students for enteties that uses a specifc class.
 *
 * @QueueWorker(
 *   id = "ssr_sync_class",
 *   title = @Translation("Sync class students lists"),
 *   cron = {"time" = 60}
 * )
 */
class SyncClassQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface  {

  /**
   * Construct a new SyncClassQueueWorker.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param array $plugin_definition
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition,
    protected SsrClassServiceInterface $classService,
    protected StateInterface $state,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
      $container->get('simple_school_reports_class_support.class_service'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if (empty($data) || empty($data['class_id'])) {
      return;
    }

    try {
      $class_id = $data['class_id'];
      $do_sync = $this->state->get('sync_class_' . $class_id, FALSE);
      if (!$do_sync) {
        return;
      }
      $this->classService->syncClass($class_id);
      $this->state->delete('sync_class_' . $class_id);
    }
    catch (\Exception $e) {
    }
  }
}
