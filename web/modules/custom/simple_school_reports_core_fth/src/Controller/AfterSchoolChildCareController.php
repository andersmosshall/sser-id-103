<?php

namespace Drupal\simple_school_reports_core_fth\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
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
 * Controller for AfterSchoolChildCareController.
 */
class AfterSchoolChildCareController extends ControllerBase {

  public function overviewMonth() {
    $build = [];

    return $build;
  }

  public function overviewDay() {
    $build = [];

    return $build;
  }

  public function accessOverview(): AccessResult {
    return AccessResult::allowed();
  }

}
