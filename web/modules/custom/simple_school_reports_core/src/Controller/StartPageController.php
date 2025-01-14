<?php

namespace Drupal\simple_school_reports_core\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\system\Controller\SystemController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxy;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller for StartPageController.
 */
class StartPageController extends SystemController {

  /**
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->currentUser = $container->get('current_user');
    return $instance;
  }

  protected function makeResponse(Url $url): TrustedRedirectResponse {
    $cache = new CacheableMetadata();
    $cache->setCacheMaxAge(0);
    $response = new TrustedRedirectResponse($url->toString());
    $response->addCacheableDependency($cache);
    $response->setMaxAge(0);
    return $response;
  }

  public function startPageResolver() {
    if ($this->currentUser->isAnonymous()) {
      return $this->makeResponse(Url::fromRoute('user.login'));
    }

    $start_page_routes = [];

    $results = $this->moduleHandler()->invokeAll('ssr_start_page_route', [$this->currentUser]);
    foreach ($results as $result) {
      if ($result) {
        $start_page_routes[] = $result;
      }
    }

    if (count($start_page_routes) === 0) {
      return $this->makeResponse(Url::fromRoute('entity.user.canonical', ['user' => $this->currentUser->id()]));
    }

    if (count($start_page_routes) === 1) {
      $route = current($start_page_routes);
      return $this->makeResponse(Url::fromRoute($route));
    }

    return parent::systemAdminMenuBlockPage();
  }

  public function defaultStartPage() {
    return [];
  }

}
