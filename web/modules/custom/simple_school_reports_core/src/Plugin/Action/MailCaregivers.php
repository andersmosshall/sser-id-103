<?php

namespace Drupal\simple_school_reports_core\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\simple_school_reports_core\Service\EmailServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Mail caregivers.
 *
 * @Action(
 *   id = "simple_school_reports_core_mail_caregivers_action",
 *   label = @Translation("Mail caregivers"),
 *   type = "user",
 *   confirm_form_route_name = "simple_school_reports_core.multiple_mail_caregivers"
 * )
 */
class MailCaregivers extends ActionBase implements ContainerFactoryPluginInterface {

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
  public function executeMultiple(array $entities) {
    $recipient_data = [];
    /** @var \Drupal\user\UserInterface $entity */
    foreach ($entities as $entity) {
      $recipient_data[$entity->id()] = [
        'student' => $entity->getDisplayName(),
        'student_email' => $this->emailService->getUserEmail($entity),
        'recipients' => $this->emailService->getCaregiverRecipients($entity->id()),
      ];
    }
    $this->tempStoreFactory->get('mail_caregivers')->set($this->currentUser->id(), $recipient_data);
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
  public function execute($object = NULL) {
    $this->executeMultiple([$object]);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {

    /** @var \Drupal\user\UserInterface $object */
    $access = AccessResult::allowedIf($object->access('update', $account, FALSE) && $object->hasRole('student') && $account->hasPermission('mail caregivers'));
    if ($access->isAllowed()) {
      $recipient_data = $this->emailService->getCaregiverRecipients($object->id());
      if (empty($recipient_data)) {
        $access = AccessResult::forbidden();
        $this->messenger()->addWarning($this->t('@student misses caregiver(s) with email address set.', ['@student' => $object->getDisplayName()]));
      }
      else if ($return_as_object) {
        $uids = array_keys($recipient_data);
        $tags = [];
        foreach ($uids as $uid) {
          $tags[] = 'user:' .  $uid;
        }
        $access->addCacheTags($tags);
      }
    }

    return $return_as_object ? $access->addCacheableDependency($object)->cachePerUser() : $access->isAllowed();
  }

}
