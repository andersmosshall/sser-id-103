<?php

namespace Drupal\simple_school_reports_child_care_support\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\simple_school_reports_child_care_support\Service\ChildCareServiceInterface;
use Drupal\simple_school_reports_core\Controller\SsrCachedPageControllerBase;
use Drupal\simple_school_reports_core\Form\RangeToUrlForm;
use Drupal\simple_school_reports_core\Plugin\Block\InvalidAbsenceStudentStatisticsBlock;
use Drupal\simple_school_reports_core\Service\TermServiceInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxy;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Route;

/**
 * Controller for ChildCareStudentController.
 */
class ChildCareStudentController extends SsrCachedPageControllerBase {

  protected ChildCareServiceInterface $childCareService;

  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->childCareService = $container->get('simple_school_reports_child_care_support.child_care');
    return $instance;
  }

  public function buildPageContent(?UserInterface $user = NULL): array {
    $build = [];

    if ($user) {
      $build['#markup'] = 'HELLO WORLD [' . $user->getDisplayName() . ']';
    }
    else {
      $build['#markup'] = 'HELLO WORLD [NO USER]';
    }

    return $build;
  }

  public function getCacheableMetadata(): CacheableMetadata {
    $cache = parent::getCacheableMetadata();

    $cache->addCacheTags(['ssr_child_care_placement_list']);

    // TEMP!!
    $cache->setCacheMaxAge(0);

    return $cache;
  }

  public function pageId(): string {
    return 'child_care_student';
  }

  public function getTitle(): string|TranslatableMarkup {
    $user = $this->routeMatch->getParameter('user');
    if ($user && $user instanceof UserInterface) {
      return $user->getDisplayName() . ' - ' . $this->t('Child care');
    }
    return $this->t('Child care');
  }

  public function accessStudentChildCare(UserInterface $user = NULL, AccountInterface $account = NULL): AccessResult {
    if (!ssr_use_child_care() || !$user) {
      return AccessResult::forbidden()->addCacheableDependency(parent::access());
    }

    if (!$user->hasRole('student')) {
      return AccessResult::forbidden()->addCacheableDependency(parent::access())->addCacheableDependency($user);
    }

    $group_ids = $this->childCareService->getChildCareGroups($user->id());
    return AccessResult::allowedIf(!empty($group_ids))->addCacheableDependency(parent::access());
  }

}
