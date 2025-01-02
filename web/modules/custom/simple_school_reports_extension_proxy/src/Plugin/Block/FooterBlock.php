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
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\simple_school_reports_core\Service\TermServiceInterface;
use Drupal\simple_school_reports_grade_stats\Plugin\Block\StudentGradeStatisticsBlock;
use Drupal\taxonomy\TermInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Provides a GradeStatisticsProxyBlock block.
 *
 * @Block(
 *  id = "ssr_footer_block",
 *  admin_label = @Translation("SSR footer block"),
 * )
 */
class FooterBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * @var RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  public function __construct(
    array $configuration,
          $plugin_id,
          $plugin_definition,
    ModuleHandlerInterface $module_handler,
    AccountProxyInterface $current_user,
    CurrentPathStack $current_path,
    RouteMatchInterface $route_match
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
    $this->currentUser = $current_user;
    $this->currentPath = $current_path;
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
      $container->get('path.current'),
      $container->get('current_route_match'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $cache = new CacheableMetadata();
    $cache->addCacheContexts(['route', 'user.permissions']);

    $bug_report_link = NULL;
    $help_link = NULL;

    $destination = $this->currentPath->getPath();
    if ($this->moduleHandler->moduleExists('simple_school_reports_help') && $this->routeMatch->getRouteName() !== 'simple_school_reports_help.help_page') {
      if ($this->currentUser->hasPermission('send bug report')) {
        $bug_report_link = Url::fromRoute('simple_school_reports_help.bug_report', [] ,['query' => ['destination' => $destination]])->toString();
      }

      $contexts = [];

      $contexts[] = $destination;
      if (strpos($destination, '/en') === 0) {
        $contexts[] = substr($destination, 3, -1);
      }
      $contexts[] = $this->routeMatch->getRouteName();

      if ($node = $this->routeMatch->getParameter('node')) {
        if ($node instanceof NodeInterface) {
          $contexts[] = 'node:' . $node->bundle();
        }
      }

      if ($term = $this->routeMatch->getParameter('term')) {
        if ($term instanceof TermInterface) {
          $contexts[] = 'term:' . $term->bundle();
        }
      }

      if ($term = $this->routeMatch->getParameter('taxonomy_term')) {
        if ($term instanceof TermInterface) {
          $contexts[] = 'term:' . $term->bundle();
        }
      }

      if ($user = $this->routeMatch->getParameter('user')) {
        if ($user instanceof UserInterface) {
          $contexts[] = 'user';
          foreach ($user->getRoles() as $role) {
            $contexts[] = 'user:' . $role;
          }
        }
      }

      $contexts = urlencode(json_encode($contexts));

      $help_link = Url::fromRoute('simple_school_reports_help.help_page', [] ,['query' => ['destination' => $destination, 'contexts' => $contexts]])->toString();
    }

    $build = [
      '#theme' => 'ssr_footer',
      '#bug_report_link' => $bug_report_link,
      '#help_link' => $help_link,
    ];

    $cache->applyTo($build);
    return $build;
  }

  public function blockAccess(AccountInterface $account) {
    if ($account->isAnonymous()) {
      return AccessResult::forbidden()->cachePerPermissions();
    }
    return AccessResult::allowed()->cachePerPermissions();
  }

}
