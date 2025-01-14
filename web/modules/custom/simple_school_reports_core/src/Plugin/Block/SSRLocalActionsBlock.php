<?php

namespace Drupal\simple_school_reports_core\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Menu\Plugin\Block\LocalActionsBlock;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block to display the local actions.
 *
 * @Block(
 *   id = "ssr_local_actions_block",
 *   admin_label = @Translation("SSR - Primary admin actions")
 * )
 */
class SSRLocalActionsBlock extends LocalActionsBlock implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->moduleHandler = $container->get('module_handler');
    $instance->currentRequest = $container->get('request_stack')->getCurrentRequest();
    $instance->currentPath = $container->get('path.current');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $cache = new CacheableMetadata();
    $cache->addCacheContexts(['route', 'url.query_args']);
    $local_actions = parent::build();

    $route_name = $this->routeMatch->getRouteName();

    $black_list_routes = ['view.grade_registration_rounds.active'];
    $destination_to_add = NULL;
    if (!in_array($route_name, $black_list_routes)) {
      $destination_to_add = $this->currentPath->getPath();
    }

    foreach (Element::children($local_actions) as $key) {
      $local_action = &$local_actions[$key];
      if (!empty($local_action['#link']['url']) && $local_action['#link']['url'] instanceof Url) {
        /** @var \Drupal\Core\Url $url */
        $url = $local_action['#link']['url'];
        $query = $url->getOption('query');
        if (!isset($query['destination'])) {
          $query['destination'] = $destination_to_add;
          $url->setOption('query', $query);
          $local_action['#link']['url'] = $url;
        }
        $local_action['#link']['localized_options']['options']['query']['destination'] = $destination_to_add;
      }

      if (!empty($local_action['#link']['title'])) {
        if (strpos(mb_strtolower($local_action['#link']['title']), 'add') === 0) {
          $local_action['#link']['localized_options']['attributes']['class'][] = 'button--add';
        }
      }
    }

    $black_list_routes = ['simple_school_reports_iup.generate_iup_single_doc', 'system.403', 'system.404'];
    $destination = $this->currentRequest->get('back_destination');
    $destination = $destination ?? $this->currentRequest->get('destination');
    if ($destination && !in_array($route_name, $black_list_routes)) {

      if (substr($destination, 0, 1) !== '/') {
        $destination = '/' . $destination;
      }
      $back_query = [];

      $back_query_routes = [
        'simple_school_reports_core.multiple_adbsence_day',
        'simple_school_reports_core.single_absence_day',
        'simple_school_reports_core.single_absence_day_specific',
      ];

      if (in_array($route_name, $back_query_routes)) {
        $back_query = ['back' => '1'];
      }

      $local_actions['ssr_back_action'] = [
        '#theme' => 'menu_local_action',
        '#link' => [
          'title' => $this->t('Back'),
          'url' => Url::fromUserInput($destination, ['query' => $back_query]),
          'localized_options' => [
            '#no_primary' => TRUE,
            'attributes' => [
              'class' => [
                'button--back',
              ],
            ],
          ],
        ],
        '#access' => AccessResult::allowed(),
        '#weight' => -999,
      ];
    };

    $context = [
      'current_request' => $this->currentRequest,
      'path.current' => $this->currentPath,
      'route_match' => $this->routeMatch,
      'route_name' => $route_name,
    ];

    $this->moduleHandler->alter('ssr_local_actions', $local_actions, $cache, $context);


    $cache->applyTo($local_actions);
    return $local_actions;
  }

}
