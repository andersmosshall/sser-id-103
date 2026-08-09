<?php

namespace Drupal\simple_school_reports_core\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for a cached page.
 */
abstract class SsrCachedPageControllerBase extends ControllerBase implements TrustedCallbackInterface {

  /**
   * Construct a new SsrCachedPageControllerBase.
   */
  public function __construct(
    protected RouteMatchInterface $routeMatch,
    protected RendererInterface $renderer,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('renderer'),
    );
  }

  /**
   * @return array
   *   The page content.
   */
  abstract public function buildPageContent(): array;

  /**
   * @return string
   */
  abstract public function pageId(): string;

  /**
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup
   */
  abstract public function getTitle(): string | TranslatableMarkup;

  /**
   * @return array
   */
  public function build(): array {
    $raw_parameters_json = Json::encode($this->routeMatch->getRawParameters()->getIterator());

    $build = [];

    $build['page_wrapper'] = [
      '#weight' => -100,
      '#cache' => [
        'keys' => [
          'ssr_cached_page',
          $this->pageId(),
          $raw_parameters_json,
        ],
      ],
      '#pre_render' => [[$this, 'prePrender']],
    ];

    $build['root_build_wrapper'] = [
      '#weight' => 100,
    ];
    $build['root_build_wrapper']['root_build'] = $this->rootContent();

    $cache = $this->getCacheableMetadata();
    $cache->applyTo($build['page_wrapper']);
    return $build;
  }

  /**
   * @param array $build
   *
   * @return array
   */
  public function prePrender(array $build): array {
    $params = $this->routeMatch->getParameters();
    $build['page'] = $this->buildPageContent(...$params);
    return $build;
  }

  /**
   * @param array $build
   *
   * @return array
   */
  public function rootContent(): array {
    return [];
  }

  /**
   * @return \Drupal\Core\Cache\CacheableMetadata
   */
  public function getCacheableMetadata(): CacheableMetadata {
    $cache = new CacheableMetadata();
    $cache->setCacheContexts(['route']);
    $params = $this->routeMatch->getParameters();
    foreach ($params as $param) {
      if ($param instanceof CacheableDependencyInterface) {
        $cache->addCacheableDependency($param);
      }
    }
    return $cache;
  }

  /**
   * Resolve access to the page.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   If the user has access to the page.
   */
  public function access(): AccessResultInterface {
    return AccessResult::allowed()->addCacheContexts(['route']);
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['prePrender'];
  }

}
