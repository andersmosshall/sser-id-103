<?php

namespace Drupal\simple_school_reports_examinations\Controller;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\node\NodeStorageInterface;
use Drupal\simple_school_reports_core\Form\RangeToUrlForm;
use Drupal\simple_school_reports_core\Plugin\Block\InvalidAbsenceStudentStatisticsBlock;
use Drupal\simple_school_reports_core\Service\TermServiceInterface;
use Drupal\simple_school_reports_grade_stats\Plugin\Block\StudentGradeStatisticsBlock;
use Drupal\simple_school_reports_reviews\Service\WrittenReviewsRoundProgressServiceInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxy;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Route;
use function Symfony\Component\String\s;

/**
 * Controller for ExaminationsController.
 */
class ExaminationsController extends ControllerBase {

  /**
   * The current request.
   */
  protected Request $currentRequest;

  /**
   * The current route match.
   */
  protected RouteMatchInterface $currentRouteMatch;

  /**
   * The block manager.
   */
  protected BlockManagerInterface $blockManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    RequestStack $request_stack,
    RouteMatchInterface $route_match,
    BlockManagerInterface $block_manager
  ) {
    $this->currentRequest = $request_stack->getCurrentRequest();
    $this->currentRouteMatch = $route_match;
    $this->blockManager = $block_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('current_route_match'),
      $container->get('plugin.manager.block'),
    );
  }

  public function studentTab() {
    return [];
  }

  public function getStudentTabTitle() {
    $user = $this->currentRouteMatch->getParameter('user');
    if ($user && $user instanceof UserInterface) {
      return $user->getDisplayName() . ' - ' . $this->t('Examinations');
    }

    return $this->t('Examinations');
  }
}
