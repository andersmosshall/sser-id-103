<?php

/**
 * @file
 * Definition of Drupal\d8views\Plugin\views\field\DaysLeft
 */

namespace Drupal\simple_school_reports_core\Plugin\views\field;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\simple_school_reports_core\Pnum;
use Drupal\user\UserInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to display user ssn.
 *
 * For user without school staff permission, the last four digits will be
 * hidden.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("ssr_uid_ssn")
 */
class UserSsn extends FieldPluginBase {

  /**
   * The current user service.
   */
  protected AccountInterface $currentUser;

  /**
   * The pnum service.
   */
  protected Pnum $pnumService;

  /**
   * @{inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->currentUser = $container->get('current_user');
    $instance->pnumService = $container->get('simple_school_reports_core.pnum');
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
    $build = [];

    $value = '-';
    $user = $values->_entity;
    if ($user instanceof UserInterface) {
      if ($user->get('field_birth_date_source')->value === 'birth_date') {
        $birth_date = $user->get('field_birth_date')->value;
        if ($birth_date) {
          $date = new \DateTime('@' . $birth_date);
          $value = $date->format('ymd') . '-****';
        }
      } elseif ($user->get('field_birth_date_source')->value === 'ssn') {
        $filter = !$this->currentUser->hasPermission('school staff permissions');
        $ssn = $user->get('field_ssn')->value;
        if ($ssn) {
          if ($normalised_ssn = $this->pnumService->normalizeIfValid($ssn)) {
            $value = $filter
              ? substr($normalised_ssn, 0, -4) . '****'
              : $normalised_ssn;
          }
        }
      }
    }

    $build['value'] = [
      '#plain_text' => $value,
    ];

    $cache = new CacheableMetadata();
    $cache->addCacheContexts(['user.permissions']);
    $cache->applyTo($build);
    return $build;
  }

}
