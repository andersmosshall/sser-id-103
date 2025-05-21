<?php

namespace Drupal\simple_school_reports_core\Controller;

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
 * Controller for UserPageController.
 */
class UserPageController extends ControllerBase {

  /**
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * @var \Drupal\simple_school_reports_core\Service\TermServiceInterface
   */
  protected $termService;

  /**
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    AccountProxy $current_user,
    TermServiceInterface $term_service,
    RequestStack $request_stack,
    RouteMatchInterface $route_match,
    FormBuilderInterface $form_builder,
    BlockManagerInterface $block_manager
  ) {
    $this->currentUser = $current_user;
    $this->termService = $term_service;
    $this->currentRequest = $request_stack->getCurrentRequest();
    $this->currentRouteMatch = $route_match;
    $this->formBuilder = $form_builder;
    $this->blockManager = $block_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('simple_school_reports_core.term_service'),
      $container->get('request_stack'),
      $container->get('current_route_match'),
      $container->get('form_builder'),
      $container->get('plugin.manager.block'),
    );
  }

  public function latestReports() {
    $build = [];

    $user = $this->currentRouteMatch->getParameter('user');
    if (!($user && $user instanceof UserInterface)) {
      throw new AccessDeniedHttpException();
    }
    return $build;
  }

  public function getLatestReportsTitle() {
    $user = $this->currentRouteMatch->getParameter('user');
    if ($user && $user instanceof UserInterface) {
      return $user->getDisplayName() . ' - ' . $this->t('Latest reports');
    }

    return $this->t('Latest report');
  }

  public function getStudentAbsenceStatisticsTitle() {
    $user = $this->currentRouteMatch->getParameter('user');
    if ($user && $user instanceof UserInterface) {
      return $user->getDisplayName() . ' - ' . $this->t('Statistics');
    }

    return $this->t('Statistics');
  }

  public function getStudentSchemaTitle() {
    $user = $this->currentRouteMatch->getParameter('user');
    if ($user && $user instanceof UserInterface) {
      return $user->getDisplayName() . ' - ' . $this->t('Schema');
    }

    return $this->t('Schema');
  }

  public function accessIfUserIsStudent(UserInterface $user, AccountInterface $account): AccessResult {
    if ($user->hasRole('student')) {
      return AccessResult::allowed()->addCacheableDependency($user);
    }
    return AccessResult::forbidden()->addCacheableDependency($user);
  }

}
