<?php

namespace Drupal\simple_school_reports_extension_proxy\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\simple_school_reports_consents\Service\ConsentsServiceServiceInterface;
use Drupal\simple_school_reports_core\Plugin\Action\MailCaregivers;
use Drupal\simple_school_reports_core\Service\EmailServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Mail users of consents.
 *
 * @Action(
 *   id = "extension_proxy_consent_reminders",
 *   label = @Translation("Remind of consents"),
 *   type = "user",
 *   confirm_form_route_name = "simple_school_reports_extension_proxy.consent_reminder"
 * )
 */
class ConsentReminder extends ActionBase implements ContainerFactoryPluginInterface {

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
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\simple_school_reports_consents\Service\ConsentsServiceServiceInterface
   */
  protected $consentService;

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
                              AccountInterface $current_user,
    ModuleHandlerInterface $module_handler
  ) {
    $this->currentUser = $current_user;
    $this->tempStoreFactory = $temp_store_factory;
    $this->moduleHandler = $module_handler;
    if ($this->moduleHandler->moduleExists('simple_school_reports_consents')) {
      $this->consentService = \Drupal::service('simple_school_reports_consents.consent_service');
    }



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
    );
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities) {
    $this->tempStoreFactory->get('mail_multiple_users')->set($this->currentUser->id(), $entities);
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
    if (!$this->moduleHandler->moduleExists('simple_school_reports_consents')) {
      return AccessResult::forbidden();
    }

    if (!$account->hasPermission('school staff permissions')) {
      $access = AccessResult::forbidden();
    }
    else {
      $remindible_uids = $this->consentService->getExpectedUids(ConsentsServiceServiceInterface::VIEWS_FILTER_TO_REMIND);

      if (!in_array($object->id(), $remindible_uids)) {
        $access = AccessResult::forbidden();
        $this->messenger()->addWarning($this->t('@user has no consent to answer or can not login with a valid email.', ['@user' => $object->getDisplayName()]));
      }
      else {
        $access = AccessResult::allowed();
      }
    }

    $access->addCacheableDependency($object);
    $access->addCacheTags(['user_list', 'ssr_consent_answer_list', 'node_list:consent']);
    $access->cachePerPermissions();
    return $return_as_object ? $access : $access->isAllowed();
  }

}
