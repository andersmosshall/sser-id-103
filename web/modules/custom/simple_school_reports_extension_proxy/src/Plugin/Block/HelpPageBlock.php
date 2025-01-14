<?php

namespace Drupal\simple_school_reports_extension_proxy\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Link;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\simple_school_reports_core\Service\TermServiceInterface;
use Drupal\simple_school_reports_grade_stats\Plugin\Block\StudentGradeStatisticsBlock;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a GradeStatisticsProxyBlock block.
 *
 * @Block(
 *  id = "ssr_help_page_block",
 *  admin_label = @Translation("SSR help page block"),
 * )
 */
class HelpPageBlock extends BlockBase implements ContainerFactoryPluginInterface, TrustedCallbackInterface {

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  public function __construct(
    array $configuration,
          $plugin_id,
          $plugin_definition,
    ModuleHandlerInterface $module_handler,
    AccountProxyInterface $current_user,
    RequestStack $request_stack,
    EntityTypeManagerInterface $entity_type_manager,
    RouteMatchInterface $route_match,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
    $this->currentUser = $current_user;
    $this->currentRequest = $request_stack->getCurrentRequest();
    $this->entityTypeManager = $entity_type_manager;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('current_user'),
      $container->get('request_stack'),
      $container->get('entity_type.manager'),
      $container->get('current_route_match'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $cache = new CacheableMetadata();
    $cache->addCacheContexts(['url.query_args', 'user.permissions']);
    $build = [];

    $contexts_json = urldecode($this->currentRequest->get('contexts', '[]'));
    $contexts = json_decode($contexts_json);

    // Prepare module info.
    $build['module_info'] = [
      '#type' => 'container',
      '#access' => FALSE,
    ];
    $build['module_info']['label'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t('Module info'),
    ];

    if (!empty($contexts)) {
      $build['current_help_pages_wrapper'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['current-help-pages--wrapper'],
        ],
      ];
      $build['current_help_pages_wrapper']['current_help_pages'] = [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $this->t('Help clips for current page'),
      ];

      // Get view and render it.
      $list_template_view = Views::getView('help_pages');
      $list_template_view->setDisplay('context');
      $list_template_view->preExecute();
      $list_template_view->execute();
      $list_template_view->element['#cache']['contexts'][] = 'user.permissions';
      $list_template_view->element['#cache']['contexts'][] = 'url.query_args';
      $list_template_view_build = $list_template_view->buildRenderable('context');
      $build['current_help_pages_wrapper']['current_help_pages_view'] = $list_template_view_build;
    }

    $build['help_pages_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['help-pages--wrapper'],
      ],
    ];

    $build['help_pages_wrapper']['help_pages'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t('All help clips'),
    ];

    // Get view and render it.
    $list_template_view = Views::getView('help_pages');
    $list_template_view->setDisplay('all');
    $list_template_view->preExecute();
    $list_template_view->execute();
    $list_template_view->element['#cache']['contexts'][] = 'user.permissions';
    $list_template_view_build = $list_template_view->buildRenderable('all');
    $build['help_pages_wrapper']['help_pages_view'] = $list_template_view_build;

    if ($this->moduleHandler->moduleExists('simple_school_reports_module_info')) {
      $build['#pre_render'][] = [$this, 'handleModuleInfo'];
    }

    $cache->applyTo($build);
    return $build;
  }

  public function handleModuleInfo(array $build) {
    $skip_contexts = [
      'view.students.students',
    ];

    $contexts_json = urldecode($this->currentRequest->get('contexts', '[]'));
    $contexts = json_decode($contexts_json);

    if (empty($contexts)) {
      return $build;
    }

    foreach ($contexts as $context) {
      if (in_array($context, $skip_contexts)) {
        return $build;
      }
    }

    // Hardcoded context match.
    $context_match = [
      'view.list_template.list' => 'simple_school_reports_list_templates',
    ];

    // Resolve module info candidates.
    $modules = [];

    foreach ($context_match as $context => $module_name) {
      if (in_array($context, $contexts)) {
        $modules[$module_name] = 10;
      }
    }

    if (!empty($build['current_help_pages_wrapper']['current_help_pages_view']['#view'])) {
      /** @var \Drupal\views\ViewExecutable $view */
      $view = $build['current_help_pages_wrapper']['current_help_pages_view']['#view'];

      if (!empty($view->result)) {
        foreach ($view->result as $row) {
          $help_page = $row->_entity;
          if ($help_page instanceof NodeInterface) {
            $help_page_modules = array_column($help_page->get('field_module')->getValue(), 'value');
            foreach ($help_page_modules as $module) {
              // Skip simple_school_reports_help and simple_school_reports_core
              // modules.
              if ($module === 'simple_school_reports_help' || $module === 'simple_school_reports_core') {
                continue;
              }
              if (!isset($modules[$module])) {
                $modules[$module] = 0;
              }
              $modules[$module]++;
            }
          }
        }
      }
    }

    if (empty($modules)) {
      // Check if contexts define a module as suffix.
      foreach ($contexts as $context) {
        $context_parts = explode('.', $context);
        $module = $context_parts[0];

        if (!$this->moduleHandler->moduleExists($module)) {
          continue;
        }


        if (!isset($modules[$module])) {
          $modules[$module] = 0;
        }
        $modules[$module]++;
      }
    }

    if (empty($modules)) {
      return $build;
    }

    asort($modules);
    // Get the last element.
    $modules = array_keys($modules);

    $module_infos = [];

    foreach ($modules as $module) {
      if (!$this->moduleHandler->moduleExists($module)) {
        continue;
      }

      $module_info = current($this->entityTypeManager->getStorage('ssr_module_info')->loadByProperties(['module' => $module]));
      if ($module_info) {
        $module_infos[] = $module_info;
      }
    }

    if (!empty($module_infos)) {
      $build['module_info']['#access'] = TRUE;

      foreach ($module_infos as $module_info) {
        $build['module_info'][$module_info->id()] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['module-info--wrapper'],
          ],
        ];
        $build['module_info'][$module_info->id()]['module_info'] = [
          '#markup' => $module_info->get('description')->value,
        ];
      }
    }

    return $build;
  }

  public function blockAccess(AccountInterface $account) {
    if ($account->isAnonymous() || !$this->moduleHandler->moduleExists('simple_school_reports_help')) {
      return AccessResult::forbidden()->cachePerPermissions();
    }
    return AccessResult::allowed()->cachePerPermissions();
  }

  public static function trustedCallbacks() {
    return ['handleModuleInfo'];
  }

}
