<?php

namespace Drupal\simple_school_reports_student_di\Controller;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\simple_school_reports_core\Service\TermServiceInterface;
use Drupal\simple_school_reports_entities\SsrMeetingInterface;
use Drupal\simple_school_reports_student_di\Form\StudentDiBookForm;
use Drupal\simple_school_reports_student_di\Form\StudentDiUnbookForm;
use Drupal\unionen_quiz\Form\AddQuizResultGroupBasedResultPageForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxy;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;

/**
 * Controller for StudentDIController.
 */
class StudentDIController extends ControllerBase {
  /**
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

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
    RequestStack $request_stack,
    RouteMatchInterface $route_match,
    FormBuilderInterface $form_builder,
    BlockManagerInterface $block_manager
  ) {
    $this->currentUser = $current_user;
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
      $container->get('request_stack'),
      $container->get('current_route_match'),
      $container->get('form_builder'),
      $container->get('plugin.manager.block'),
    );
  }

  public function studentTab(UserInterface $user) {
    $build = [];

    return $build;
  }

  public function meetingBook(SsrMeetingInterface $meeting, UserInterface $user) {
    $build = [];

    if (!$user->hasRole('student')) {
      throw new AccessDeniedHttpException();
    }

    $set_student = $meeting->get('field_student')->target_id;
    if ($set_student && $set_student != $user->id()) {
      $access = FALSE;
    }
    else {
      $meeting->set('field_student', $user);
      $access = $meeting->access('book');
    }

    if (!$access) {
      $this->messenger()->addWarning('Something went wrong. The meeting might have been changed or already been booked.');
      return new RedirectResponse(Url::fromRoute('simple_school_reports_student_di.di_user_tab', ['user' => $user->id()])->toString());
    }

    $build['book_form'] = $this->formBuilder()->getForm(StudentDiBookForm::class, $meeting, $user);

    return $build;
  }

  public function meetingUnbook(SsrMeetingInterface $meeting, UserInterface $user) {
    $build = [];

    if (!$user->hasRole('student')) {
      throw new AccessDeniedHttpException();
    }


    $set_student = $meeting->get('field_student')->target_id;
    if (!$set_student || $set_student != $user->id()) {
      $access = FALSE;
    }
    else {
      $meeting->set('field_student', $user);
      $access = $meeting->access('unbook');
    }

    if (!$access) {
      $this->messenger()->addWarning('Something went wrong. The meeting might have been changed or already been booked.');
      return new RedirectResponse(Url::fromRoute('simple_school_reports_student_di.di_user_tab', ['user' => $user->id()])->toString());
    }

    $build['unbook_form'] = $this->formBuilder()->getForm(StudentDiUnbookForm::class, $meeting, $user);

    return $build;
  }

  public function getStudentTabTitle() {
    $user = $this->currentRouteMatch->getParameter('user');
    if ($user && $user instanceof UserInterface) {
      return $user->getDisplayName() . ' - ' . $this->t('Development interview');
    }

    return $this->t('Development interview');
  }

  public static function accessStudentDITab(UserInterface $user, AccountInterface $account) {
    if (!$user->hasRole('student')) {
      return AccessResult::forbidden()->addCacheableDependency($user);
    }

    $access = $user->access('edit', $account, TRUE);
    if ($access->isAllowed()) {

      $meeting_exists = FALSE;
      /** @var \Drupal\simple_school_reports_student_di\Service\StudentDiMeetingsServiceInterface $meeting_service */
      $meeting_service = \Drupal::service('simple_school_reports_student_di.meetings_service');

      $group_ids = $meeting_service->getStudentGroupIds($user->id());
      foreach ($group_ids as $group_id) {
        if (!empty($meeting_service->getBookedMeetingIds($user->id(), $group_id))) {
          $meeting_exists = TRUE;
          break;
        }
        if (!empty($meeting_service->getAvailableMeetingIds($user->id(), $group_id))) {
          $meeting_exists = TRUE;
          break;
        }
      }

      $cache = new CacheableMetadata();
      $cache->addCacheableDependency($user);
      $cache->addCacheTags([
        'ssr_meeting_list:student_di',
        'node_list:student_development_interview',
        'node_list:di_student_group',
      ]);
      return AccessResult::allowedIf($meeting_exists)->addCacheableDependency($cache);

    }

    return $access;
  }

}
