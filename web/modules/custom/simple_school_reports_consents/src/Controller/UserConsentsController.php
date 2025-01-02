<?php

namespace Drupal\simple_school_reports_consents\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\simple_school_reports_consents\Service\ConsentsServiceServiceInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for UserConsentsController.
 */
class UserConsentsController extends ControllerBase {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * @var \Drupal\simple_school_reports_consents\Service\ConsentsServiceServiceInterface
   */
  protected $consentsService;


  /**
   * {@inheritdoc}
   */
  public function __construct(
    RouteMatchInterface $route_match,
    ConsentsServiceServiceInterface $consents_service
  ) {
    $this->currentRouteMatch = $route_match;
    $this->consentsService = $consents_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('simple_school_reports_consents.consent_service'),
    );
  }

  public function build() {
    return [];
  }

  public function title() {
    $user = $this->currentRouteMatch->getParameter('user');
    if ($user && $user instanceof UserInterface) {
      return $user->getDisplayName() . ' - ' . $this->t('Consents');
    }

    return $this->t('Consents');
  }

  public function access(UserInterface $user, AccountInterface $account) {
    $access = $user->access('update', $account, TRUE);

    $consents_access = AccessResult::allowedIf(
      !empty($this->consentsService->getHandledConsentsIds($user->id())) ||
        !empty($this->consentsService->getUnHandledConsentIds($user->id()))
    );
    $consents_access->addCacheTags(['user_list', 'ssr_consent_answer_list', 'node_list:consent']);
    $consents_access->cachePerUser();

    $access->andIf($consents_access);

    return $access;
  }

}
