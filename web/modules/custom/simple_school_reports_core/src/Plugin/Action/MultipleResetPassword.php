<?php

namespace Drupal\simple_school_reports_core\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\simple_school_reports_core\Service\EmailServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * MultipleResetPassword.
 *
 * @Action(
 *   id = "simple_school_reports_core_multiple_password_reset",
 *   label = @Translation("Send login instructions"),
 *   type = "user",
 *   confirm_form_route_name = "simple_school_reports_core.multiple_password_reset"
 * )
 */
class MultipleResetPassword extends ActionBase implements ContainerFactoryPluginInterface {

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
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\simple_school_reports_core\Service\EmailServiceInterface
   */
  protected $emailService;


  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    PrivateTempStoreFactory
    $temp_store_factory,
    AccountInterface $current_user,
    ModuleHandlerInterface $module_handler,
    EmailServiceInterface $email_service
  ) {
    $this->currentUser = $current_user;
    $this->tempStoreFactory = $temp_store_factory;
    $this->moduleHandler = $module_handler;
    $this->emailService = $email_service;

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
      $container->get('module_handler'),
      $container->get('simple_school_reports_core.email_service'),
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getPluginDefinition() {
    $definition = parent::getPluginDefinition();
    if (is_array($definition)) {
      $definition['skip_access_denied_message'] = TRUE;
    }
    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities) {
    $this->tempStoreFactory->get('multiple_password_reset')->set($this->currentUser->id(), $entities);
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

    $is_school_staff = $account->hasPermission('school staff permissions');
    $has_admin_permission = $account->hasPermission('administer simple school reports settings');

    $access = AccessResult::forbidden();

    if ($is_school_staff) {
      if ($object->hasRole('student') || $object->hasRole('caregiver')) {
        // Not supported for now.
        if ($object->hasRole('student') && !$this->moduleHandler->moduleExists('simple_school_reports_student_login')) {
          \Drupal::messenger()->addError('The student login module needs to be active to allow login');
        }
        else if ($object->hasRole('caregiver') && !$this->moduleHandler->moduleExists('simple_school_reports_caregiver_login')) {
          \Drupal::messenger()->addError('The caregiver login module needs to be active to allow login');
        }
        else {
          $access = AccessResult::allowed();
        }
      }
      else {
        if ($has_admin_permission) {
          $access = AccessResult::allowed();
        }
      }
      $access->addCacheableDependency($object);
    }

    if ($access->isAllowed()) {
      if (!$this->emailService->getUserEmail($object)) {
        \Drupal::messenger()->addError($this->t('@name does not have an valid email address', ['@name' => $object->getDisplayName()]));
        $access = AccessResult::forbidden();
      }

      if ($object->isBlocked()) {
        \Drupal::messenger()->addError('Login instructions cannot be sent to blocked users');
        $access = AccessResult::forbidden();
      }
    }

    $access->cachePerUser();
    return $return_as_object ? $access : $access->isAllowed();
  }

}
