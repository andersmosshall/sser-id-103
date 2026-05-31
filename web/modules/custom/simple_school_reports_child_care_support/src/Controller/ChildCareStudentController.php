<?php

namespace Drupal\simple_school_reports_child_care_support\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\simple_school_reports_child_care_support\Service\ChildCareServiceInterface;
use Drupal\simple_school_reports_core\Controller\SsrCachedPageControllerBase;
use Drupal\simple_school_reports_core\Form\WeekNumberToUrlRangeForm;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for ChildCareStudentController.
 */
class ChildCareStudentController extends SsrCachedPageControllerBase {

  protected ChildCareServiceInterface $childCareService;

  protected Request $currentRequest;

  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->childCareService = $container->get('simple_school_reports_child_care_support.child_care');
    $instance->currentRequest = $container->get('request_stack')->getCurrentRequest();
    return $instance;
  }

  public function buildPageContent(?UserInterface $user = NULL): array {
    $build = [];

    $build['week_form_wrapper'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Week'),
    ];
    $build['week_form_wrapper']['week_form'] = $this->formBuilder()->getForm(WeekNumberToUrlRangeForm::class, TRUE);

    if ($user) {
      $build['info']['#markup'] = 'HELLO WORLD [' . $user->getDisplayName() . ']';
    }
    else {
      $build['info']['#markup'] = 'HELLO WORLD [NO USER]';
    }

    $from = $this->currentRequest->query->get('from');
    $to = $this->currentRequest->query->get('to');

    if ($from && $to) {
      $from_date = new \DateTime();
      $from_date->setTimestamp($from);

      $to_date = new \DateTime();
      $to_date->setTimestamp($to);

      $build['range']['#markup'] = ' [' . $from_date->format('Y-m-d H:i:s') . ' - ' . $to_date->format('Y-m-d H:i:s') . ']';
    }

    return $build;
  }

  public function getCacheableMetadata(): CacheableMetadata {
    $cache = parent::getCacheableMetadata();

    $cache->addCacheTags(['ssr_child_care_placement_list']);
    $cache->addCacheContexts(['url.query_args:from', 'url.query_args:to']);

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

    if (!$user->hasRole('student') || !$user->access('caregiver_access', $account)) {
      return AccessResult::forbidden()->addCacheableDependency(parent::access())->addCacheableDependency($user);
    }

    $group_ids = $this->childCareService->getChildCareGroups($user->id());
    return AccessResult::allowedIf(!empty($group_ids))->addCacheableDependency(parent::access())->addCacheableDependency($user);
  }

}
