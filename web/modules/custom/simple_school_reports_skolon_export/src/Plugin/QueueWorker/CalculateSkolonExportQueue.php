<?php

namespace Drupal\simple_school_reports_skolon_export\Plugin\QueueWorker;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Site\Settings;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\simple_school_reports_core\Service\EmailService;
use Drupal\simple_school_reports_skolon_export\Service\SkolonExportUsersService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Delete entity queue
 *
 * @QueueWorker(
 *   id = "calculate_skolon_export",
 *   title = @Translation("Calculate skolon export"),
 *   cron = {"time" = 60}
 * )
 */
class CalculateSkolonExportQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface  {

  use StringTranslationTrait;

  /**
   * ModifyEntityQueue constructor.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param array $plugin_definition
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    array $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected StateInterface $state,
    protected SkolonExportUsersService $skolonExportUsersService,
    protected EmailService $emailService,
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
      $container->get('entity_type.manager'),
      $container->get('state'),
      $container->get('simple_school_reports_skolon_export.export_users_skolon'),
      $container->get('simple_school_reports_core.email_service'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    try {
      if (!is_array($data) || !isset($data['queue_id'])) {
        return;
      }

      $active_skolon_queue = $this->state->get('active_skolon_queue', []);
      if (empty($active_skolon_queue) || $active_skolon_queue['queue_id'] !== $data['queue_id']) {
        // If the queue is not active, skip processing.
        return;
      }

      $do_notify = !empty($data['notify_to']);
      $uid = !empty($data['uid']) ? $data['uid'] : NULL;

      $lookup_storage = $this->entityTypeManager->getStorage('ssr_lookup');

      if ($uid) {
        /** @var \Drupal\user\UserInterface|null $user */
        $user = $this->entityTypeManager->getStorage('user')->load($uid);
        if (!$user) {
          // User does not exist, skip processing.
          return;
        }
        $lookup_item = current($lookup_storage->loadByProperties(['identifier' => $uid, 'type' => 'skolon_export_item']));
        if (!$lookup_item) {
          $lookup_item = $lookup_storage->create([
            'identifier' => $uid,
            'type' => 'skolon_export_item',
            'label' => 'Skolon row item ' . $uid,
            'meta' => '',
          ]);
        }

        /** @var \Drupal\simple_school_reports_entities\SSRLookupInterface $lookup_item */
        $lookup_item->set('expires', strtotime('+5 years'));
        $lookup_item->set('dependency_entity_type', 'user');
        $lookup_item->set('dependency_entity_target_id', $uid);

        $skolon_export_item = $this->skolonExportUsersService->getUserRow($user, ['skip_message' => TRUE]);
        if (!$skolon_export_item) {
          // No data to export, skip processing.
          return;
        }

        $skolon_export_item_hash = hash('sha256', Json::encode($skolon_export_item) . Settings::getHashSalt());
        if ($lookup_item->get('meta')->value === $skolon_export_item_hash) {
          // No changes, skip processing.
          return;
        }
        $active_skolon_queue['uids'][$uid] = $uid;
        $this->state->set('active_skolon_queue', $active_skolon_queue);
        $lookup_item->set('meta', $skolon_export_item_hash);
        $lookup_item->save();
        return;
      }

      if ($do_notify) {
        if (!empty($active_skolon_queue['uids'])) {
          $lookup_item = $lookup_storage->create([
            'identifier' => $active_skolon_queue['queue_id'],
            'type' => 'skolon_export_list',
            'label' => 'Skolon export list ' . $active_skolon_queue['queue_id'],
            'meta' => Json::encode($active_skolon_queue['uids']),
          ]);
          $lookup_item->save();

          $subject = t('Skolon export for @school_name', ['@school_name' => Settings::get('ssr_school_name', '-')]);
          $message = t('Data has changed in @school_name affecting @count users, a new skolon export is needed.', [
            '@school_name' => Settings::get('ssr_school_name', '-'),
            '@count' => count($active_skolon_queue['uids']),
          ]);
          $message .= PHP_EOL;
          $message .= PHP_EOL;

          $message .= t('To export only affected users, please use the following link:');
          $message .= PHP_EOL;
          $link = Url::fromRoute('simple_school_reports_skolon_export.export_multiple_users', ['ssr_lookup' => $lookup_item->id()], ['absolute' => TRUE])->toString(TRUE)->getGeneratedUrl();
          $message .= $link;

          $this->emailService->sendMail($data['notify_to'], $subject, $message, [
            'maillog_mail_type' => \Drupal\simple_school_reports_maillog\SsrMaillogInterface::MAILLOG_TYPE_INFRASTRUCTURE,
            'no_reply_to' => TRUE,
          ]);
        }

        $this->state->set('active_skolon_queue', []);
        return;
      }
    }
    catch (\Exception $e) {
      return;
    }
  }
}
