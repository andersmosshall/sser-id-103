<?php

namespace Drupal\simple_school_reports_leave_application\Controller;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\unionen_quiz\Form\AddQuizResultGroupBasedResultPageForm;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Controller for LeaveApplicationController.
 */
class LeaveApplicationController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    protected RouteMatchInterface $currentRouteMatch,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
    );
  }

  public function studentTab(UserInterface $user) {
    return [];
  }

  public function getStudentTabTitle() {
    $user = $this->currentRouteMatch->getParameter('user');
    if ($user && $user instanceof UserInterface) {
      return $user->getDisplayName() . ' - ' . $this->t('Leave applications');
    }

    return $this->t('Leave applications');
  }

  public static function accessStudentTab(UserInterface $user, AccountInterface $account) {
    if (!$user->hasRole('student')) {
      return AccessResult::forbidden()->addCacheableDependency($user);
    }
    return $user->access('edit', $account, TRUE);
  }

}
