<?php

namespace Drupal\simple_school_reports_grade_stats\Controller;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
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
class GradeStatisticsController extends ControllerBase {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;


  /**
   * {@inheritdoc}
   */
  public function __construct(
    RouteMatchInterface $route_match
  ) {
    $this->currentRouteMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
    );
  }

  /**
   * @param \Drupal\user\UserInterface $student
   *
   * @return array
   */
  public function studentGradeStatistics() {
    return [];
  }

  public function getStudentGradeStatisticsTitle() {
    $user = $this->currentRouteMatch->getParameter('user');
    if ($user && $user instanceof UserInterface) {
      return $user->getDisplayName() . ' - ' . $this->t('Grade statistics');
    }

    return $this->t('Grade statistics');
  }

}
