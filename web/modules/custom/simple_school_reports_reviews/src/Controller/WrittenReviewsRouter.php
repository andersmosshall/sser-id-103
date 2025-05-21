<?php

namespace Drupal\simple_school_reports_reviews\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\simple_school_reports_core\Form\RangeToUrlForm;
use Drupal\simple_school_reports_core\Plugin\Block\InvalidAbsenceStudentStatisticsBlock;
use Drupal\simple_school_reports_core\Service\TermServiceInterface;
use Drupal\simple_school_reports_reviews\Service\WrittenReviewsRoundProgressServiceInterface;
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
class WrittenReviewsRouter extends ControllerBase {

  /**
   * @var \Drupal\simple_school_reports_reviews\Service\WrittenReviewsRoundProgressServiceInterface
   */
  protected $progressService;

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
   * {@inheritdoc}
   */
  public function __construct(
    WrittenReviewsRoundProgressServiceInterface $progress_service,
    RequestStack $request_stack,
    RouteMatchInterface $route_match
  ) {
    $this->progressService = $progress_service;
    $this->currentRequest = $request_stack->getCurrentRequest();
    $this->currentRouteMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('simple_school_reports_reviews.written_reviews_round_progress_service'),
      $container->get('request_stack'),
      $container->get('current_route_match'),
    );
  }

  public function studentTab() {
    return [];
  }

  public function getStudentTabTitle() {
    $user = $this->currentRouteMatch->getParameter('user');
    if ($user && $user instanceof UserInterface) {
      return $user->getDisplayName() . ' - ' . $this->t('Written reviews');
    }

    return $this->t('Written reviews');
  }

  public function getStudentPreviewTitle() {
    $user = $this->currentRouteMatch->getParameter('user');
    $node = $this->currentRouteMatch->getParameter('node');
    if ($user && $user instanceof UserInterface && $node && $node instanceof NodeInterface) {
      return $user->getDisplayName() . ' - ' . $this->t('Written reviews') . ' (' . $node->label() . ')';
    }

    return $this->t('Written reviews');
  }

  public function accessStudentPreview(NodeInterface $node, UserInterface $user, AccountInterface $account) {
    if ($node->bundle() === 'written_reviews_round' && $user->hasRole('student') && $user->access('update', $account)) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

  public function router(string $round_nid, string $student_uid) {
    $query = $this->currentRequest->query->all();
    if (!empty($query['post_save_destination'])) {
      $query['destination'] = $query['post_save_destination'];
      unset($query['post_save_destination']);
    }
    $written_reviews_nid = $this->progressService->getWrittenReviewsNid($round_nid, $student_uid);

    if (!$written_reviews_nid) {
      /** @var UserInterface $student */
      $student = $this->entityTypeManager()->getStorage('user')->load($student_uid);
      if ($student->hasRole('student')) {
        $round_node = $this->entityTypeManager()->getStorage('node')->load($round_nid);

        if ($round_node && $round_node->bundle() === 'written_reviews_round') {
          $name = '';
          _simple_school_reports_core_resolve_name($name, $student, TRUE);

          $written_reviews_node = $this->entityTypeManager()->getStorage('node')->create([
            'type' => 'written_reviews',
            'title' => 'Skriftligt omdöme för ' . $name,
            'langcode' => 'sv',
          ]);

          $written_reviews_node->set('field_student', $student);
          $student_grade = $student->get('field_grade')->value;
          if ($student_grade) {
            $written_reviews_node->set('field_grade', $student_grade);
          }

          $student_class_id = $student->get('field_class')->target_id;
          if ($student_class_id) {
            $written_reviews_node->set('field_class', ['target_id' => $student_class_id]);
          };

          $written_reviews_node->set('field_written_reviews_round', $round_node);
          $written_reviews_node->save();
          $written_reviews_nid = $written_reviews_node->id();
        }
      }
    }

    if ($written_reviews_nid) {
      return new RedirectResponse(Url::fromRoute('entity.node.edit_form', ['node' => $written_reviews_nid], ['query' => $query])->toString());
    }

    throw new AccessDeniedHttpException();
  }

}
