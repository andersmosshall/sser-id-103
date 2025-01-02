<?php

namespace Drupal\simple_school_reports_extension_proxy\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Mail caregivers.
 *
 * @Action(
 *   id = "extension_proxy_toggle_allow_login",
 *   label = @Translation("Activate/Deactivate allow login"),
 *   type = "user",
 *   confirm_form_route_name = "simple_school_reports_extension_proxy.toggle_allow_login"
 * )
 */
class ToggleAllowLogin extends ActionBase implements ContainerFactoryPluginInterface {

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
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   * @param \Drupal\Core\Session\AccountInterface $current_user
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
      $container->get('current_user'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities) {
    $this->tempStoreFactory->get('toggle_allow_login')->set($this->currentUser->id(), $entities);
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
    /** @var \Drupal\user\UserInterface $object */
    $access = AccessResult::allowedIf($object->access('update', $account, FALSE) && $object->hasRole('caregiver'));
    $access->addCacheableDependency($object);
    $access->cachePerUser();
    return $return_as_object ? $access : $access->isAllowed();
  }

}
