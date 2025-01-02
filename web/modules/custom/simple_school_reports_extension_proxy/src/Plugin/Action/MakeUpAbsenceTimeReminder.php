<?php

namespace Drupal\simple_school_reports_extension_proxy\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\simple_school_reports_core\Plugin\Action\MailCaregivers;
use Drupal\simple_school_reports_core\Service\EmailServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Mail caregivers.
 *
 * @Action(
 *   id = "extension_proxy_make_up_absence_time_reminder",
 *   label = @Translation("Reminder of make up absence time"),
 *   type = "user",
 *   confirm_form_route_name = "simple_school_reports_extension_proxy.make_up_time_reminder"
 * )
 */
class MakeUpAbsenceTimeReminder extends MailCaregivers implements ContainerFactoryPluginInterface {
  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->moduleHandler = $container->get('module_handler');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if (!$this->moduleHandler->moduleExists('simple_school_reports_absence_make_up')) {
      return AccessResult::forbidden();
    }
    return parent::access($object, $account, $return_as_object);
  }

}
