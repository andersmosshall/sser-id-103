<?php

namespace Drupal\simple_school_reports_core\Plugin\Action;

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
 *   id = "ssr_mail_students",
 *   label = @Translation("Mail students"),
 *   type = "user",
 *   confirm_form_route_name = "simple_school_reports_core.user_send_mail_multiple"
 * )
 */
class MailStudents extends ActionBase implements ContainerFactoryPluginInterface {

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
   * @var \Drupal\simple_school_reports_core\Service\EmailServiceInterface
   */
  protected $emailService;

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
                              AccountInterface $current_user,
                              EmailServiceInterface $email_service
  ) {
    $this->currentUser = $current_user;
    $this->tempStoreFactory = $temp_store_factory;
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
      $container->get('simple_school_reports_core.email_service')
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
    if (!$account->hasPermission('school staff permissions')) {
      $access = AccessResult::forbidden();
    }
    else {
      /** @var \Drupal\user\UserInterface $object */
      if (!$object->hasRole('student')) {
        $access = AccessResult::forbidden();
        $this->messenger()->addWarning($this->t('@name is not a student.', ['@name' => $object->getDisplayName()]));
      }
      elseif (!$this->emailService->getUserEmail($object)) {
        $access = AccessResult::forbidden();
        $this->messenger()->addWarning($this->t('Could not send mail to @mail since valid mail is missing.', ['@mail' => $object->getDisplayName()]));
      }
      else {
        $access = AccessResult::allowed();
      }
    }

    $access->addCacheableDependency($object);
    $access->cachePerPermissions();
    return $return_as_object ? $access : $access->isAllowed();
  }

}
