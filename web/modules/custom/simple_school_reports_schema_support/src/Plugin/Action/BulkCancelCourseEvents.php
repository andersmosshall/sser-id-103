<?php

namespace Drupal\simple_school_reports_schema_support\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\simple_school_reports_entities\CalendarEventInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Mail caregivers.
 *
 * @Action(
 *   id = "ssr_multiple_cancel_course_event",
 *   label = @Translation("Cancel lessons"),
 *   type = "ssr_calendar_event",
 *   confirm_form_route_name = "simple_school_reports_schema.multiple_cancel_event"
 * )
 */
class BulkCancelCourseEvents extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * The tempstore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a CancelUser object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   */
  public function __construct(array
      $configuration,
      $plugin_id,
      $plugin_definition,
      PrivateTempStoreFactory $temp_store_factory,
      AccountInterface $current_user
  ) {
    $this->currentUser = $current_user;
    $this->tempStoreFactory = $temp_store_factory;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('tempstore.private'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities) {
    $this->tempStoreFactory->get('cancel_multiple_course_events')->set($this->currentUser->id(), $entities);
  }

  /**
   * {@inheritdoc}
   */
  public function execute($object = NULL) {
    $this->executeMultiple([$object]);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $calendar_event = $object instanceof CalendarEventInterface ? $object : NULL;
    if (!$calendar_event || $calendar_event->bundle() !== 'course') {
      return $return_as_object ? AccessResult::forbidden() : FALSE;
    }

    $course = $calendar_event->get('field_course')->entity;
    $access_to_course = !$course || $course->access('update', $account, FALSE);

    /** @var \Drupal\user\UserInterface $object */
    $access = AccessResult::allowedIf(!$calendar_event->get('completed')->value && $access_to_course);
    if ($course) {
      $access->addCacheableDependency($course);
    }

    return $return_as_object ? $access->addCacheableDependency($object)->cachePerUser() : $access->isAllowed();
  }

}
