<?php

/**
 * @file
 * Definition of Drupal\d8views\Plugin\views\field\DaysLeft
 */

namespace Drupal\simple_school_reports_extension_proxy\Plugin\views\field;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to show a list of consents for uid.
 *
 * Consents grouped by target user.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("user_consents_list")
 */
class UserConsentsList extends FieldPluginBase {

  use RedirectDestinationTrait;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\simple_school_reports_consents\Service\ConsentsServiceServiceInterface
   */
  protected $consentService;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->moduleHandler = $container->get('module_handler');

    if ($instance->moduleHandler->moduleExists('simple_school_reports_consents')) {
      $instance->consentService = $container->get('simple_school_reports_consents.consent_service');
    }

    return $instance;
  }

  /**
   * @{inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * @{inheritdoc}
   */
  public function render(ResultRow $values) {
    $uid = $values->uid ?? 0;
    if (!$uid || !$this->moduleHandler->moduleExists('simple_school_reports_consents')) {
      return '';
    }

    $build = [];
    $cache = new CacheableMetadata();

    $target_uid_map = $this->consentService->getTargetUidsByUidWithData($uid);
    $consent_name_map = $this->consentService->getConsentNames();
    $ordered_target_uid_map = [];

    // Show user own first.
    if (isset($target_uid_map[$uid])) {
      $ordered_target_uid_map[$uid] = $target_uid_map[$uid];
      unset($target_uid_map[$uid]);
    }

    foreach ($target_uid_map as $target_uid => $data) {
      $ordered_target_uid_map[$target_uid] = $data;
    }

    foreach ($ordered_target_uid_map as $target_uid => $consent_data) {
      $items = [];

      $show_label = TRUE;
      if ($target_uid == $uid) {
        $show_label = FALSE;
      }

      foreach ($consent_data as $consent_id => $item) {
        $status_data = $this->consentService->getConsentStatus($consent_id, $target_uid)[$uid] ?? NULL;
        if (!$status_data) {
          continue;
        }

        if ($show_label) {
          $show_label = FALSE;
          $build['label_' . $target_uid] = [
            '#type' => 'html_tag',
            '#tag' => 'strong',
            '#value' => $this->t('Regarding @name', ['@name' => $status_data['target_name']]) . ': ',
          ];
        }

        $item = [];

        $item['wrapper'] = [
          '#type' => 'container',
        ];

        $item['wrapper']['consent'] = [
          '#markup' => '<span>' . $consent_name_map[$consent_id] . ': ' . $status_data['status'] . ' </span>',
        ];

        if ($this->consentService->allowConsentHandling($consent_id, $target_uid, $uid)) {
          $item['wrapper']['manage'] = [
            '#type' => 'link',
            '#title' => $this->t('Manage'),
            '#url' => Url::fromRoute('simple_school_reports_consents.handle_consent', ['node' => $consent_id, 'user' => $target_uid], ['query' => $this->getDestinationArray()]),
            '#prefix' => '[',
            '#suffix' => ']',
          ];
        }
        $items[] = $item;
      }

      if (!empty($items)) {
        $build['list_' . $target_uid] = [
          '#theme' => 'item_list',
          '#items' => $items,
          '#title' => NULL,
          '#list_type' => 'ul',
        ];
      }
      else {
        unset($build['label_' . $target_uid]);
      }
    }

    $cache->addCacheTags(['node_list:consent', 'ssr_consent_answer_list', 'user_list:roles', 'user_list:new']);
    $cache->addCacheContexts(['route', 'user']);
    $cache->applyTo($build);
    return $build;
  }

}
