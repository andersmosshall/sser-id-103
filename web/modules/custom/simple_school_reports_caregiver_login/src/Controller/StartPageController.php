<?php

namespace Drupal\simple_school_reports_caregiver_login\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
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

  public function caregiverStartPage() {
    return [];
  }

  public function routeAccess(AccountInterface $account) {
    return AccessResult::allowedIf($account->id() == 1 || in_array('caregiver', $account->getRoles()));
  }

}
