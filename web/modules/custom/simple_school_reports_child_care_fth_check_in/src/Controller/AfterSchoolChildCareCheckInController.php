<?php

namespace Drupal\simple_school_reports_child_care_fth_check_in\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\simple_school_reports_child_care_support\Service\ChildCareCheckInServiceInterface;
use Drupal\simple_school_reports_child_care_support\Service\ChildCareSchemaServiceInterface;
use Drupal\simple_school_reports_child_care_support\Service\ChildCareServiceInterface;
use Drupal\simple_school_reports_core\Controller\SsrCachedPageControllerBase;
use Drupal\simple_school_reports_core_fth\Form\AfterSchoolChildCareDayCheckInOverviewFilterForm;
use Drupal\simple_school_reports_core_fth\Form\AfterSchoolChildCareDayOverviewFilterForm;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for AfterSchoolChildCareCheckInController.
 */
class AfterSchoolChildCareCheckInController extends SsrCachedPageControllerBase {

  use RedirectDestinationTrait;

  protected ChildCareServiceInterface $childCareService;

  protected ChildCareSchemaServiceInterface $childCareSchemaService;

  protected ChildCareCheckInServiceInterface $childCareCheckInService;

  protected Request $currentRequest;

  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->childCareService = $container->get('simple_school_reports_child_care_support.child_care');
    $instance->childCareSchemaService = $container->get('simple_school_reports_child_care_support.child_care_schema');
    $instance->childCareCheckInService = $container->get('simple_school_reports_child_care_support.child_care_check_in');
    $instance->currentRequest = $container->get('request_stack')->getCurrentRequest();
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  public function buildPageContent(): array {
    $build = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['child-care-day-overview-container'],
      ],
    ];

    $build['heading'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => '',
    ];

    $build['graph_target'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['child-care-overview-graph-container']
      ],
    ];

    $build['overview_filter'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Filter'),
    ];
    $build['overview_filter']['form'] = $this->formBuilder()->getForm(AfterSchoolChildCareDayCheckInOverviewFilterForm::class, TRUE);

    $date = $this->childCareService->getDateFromRequest();

    if (!$date) {
      unset($build['heading']);
      unset($build['graph_target']);
      return $build;
    }

    $build['#attached']['library'][] = 'simple_school_reports_child_care_support/child_care_overview';
    $build['#attached']['library'][] = 'simple_school_reports_child_care_support/child_care_check_in_overview';

    $day_map = [
      1 => t('Monday'),
      2 => t('Tuesday'),
      3 => t('Wednesday'),
      4 => t('Thursday'),
      5 => t('Friday'),
      6 => t('Saturday'),
      7 => t('Sunday'),
    ];
    $day = $date->format('N');

    $build['heading']['#value'] = mb_ucfirst($day_map[$day]) . ' ' . $date->format('j/n - Y');

    $groups = $this->childCareService->getChildCareIdsFromRequest();
    if (empty($groups)) {
      return $build;
    }

    $uids = $this->childCareService->getChildCareStudentIdsMultiple($groups, $date);

    $check_ins = [];
    foreach ($uids as $uid) {
      $check_ins[$uid] = $this->childCareCheckInService->getChildCareCheckIns($uid, $date, $groups);
    }

    $data = $this->childCareSchemaService->getDayOverview($uids, $date, $groups, $check_ins);

    foreach ($data as $key => $segment) {
      $timestamp = $segment['from'];
      $date = new \DateTime();
      $date->setTimestamp($timestamp);
      $data[$key]['label'] = $date->format('H:i');
    }

    $build['graph_target']['#attached']['library'][] = 'simple_school_reports_child_care_support/child_care_graph';
    $build['graph_target']['#attributes']['data-graph-data'] = Json::encode($data);
    $build['graph_target']['#attributes']['data-include-check-in'] = 'true';
    $build['graph_target']['graph'] = [
      '#type' => 'html_tag',
      '#tag' => 'canvas',
      '#attributes' => [
        'id' => 'child-care-overview-graph',
      ],
    ];

    $check_in_warning_threshold = NULL;
    $check_out_warning_threshold = NULL;

    $today = (new \DateTime())->format('Y-m-d');
    if ($date->format('Y-m-d') === $today) {
      foreach ($uids as $uid) {
        $poi = $this->childCareCheckInService->getPointOfInterest($uid, $date, $groups);
        if (!$poi) {
          continue;
        }
        if ($poi['type'] === 'check_in') {
          $check_in_warning_threshold = min(($check_in_warning_threshold ?? PHP_INT_MAX), $poi['time']);
        }
        if ($poi['type'] === 'check_out') {
          $check_out_warning_threshold = min(($check_out_warning_threshold ?? PHP_INT_MAX), $poi['time']);
        }
      }
    }

    if ($check_in_warning_threshold !== NULL) {
      $build['message_check_in'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['child-care-global-check-in-message'],
          'style' => 'display: none;',
          'data-display-threshold' => $check_in_warning_threshold + 600 + 1,
        ],
      ];
      $build['message_check_in']['message'] = [
        '#theme' => 'status_messages',
        '#message_list' => [
          'error' => [$this->t('At least one check in is overdue!')],
        ],
      ];
    }

    if ($check_out_warning_threshold !== NULL) {
      $build['message_check_out'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['child-care-global-check-in-message'],
          'style' => 'display: none;',
          'data-display-threshold' => $check_out_warning_threshold + 600 + 1,
        ],
      ];
      $build['message_check_out']['message'] = [
        '#theme' => 'status_messages',
        '#message_list' => [
          'error' => [$this->t('At least one check out is overdue!')],
        ],
      ];
    }

    return $build;
  }

  public function rootContent(): array {
    $build = [];
    $students_view = Views::getView('child_care_check_in');
    $students_view->setDisplay('list');
    $students_view->preExecute();
    $students_view->execute();
    $students_view_build = $students_view->buildRenderable('list');
    $build['students_view'] = $students_view_build;

    return $build;
  }

  public function getCacheableMetadata(): CacheableMetadata {
    return $this->childCareCheckInService->getCacheableMetadata(new \DateTime());
  }

  public function pageId(): string {
    return 'child_care_fth_check_in_overview';
  }

  public function accessCheckInOverview(AccountInterface $account = NULL): AccessResultInterface {
    if (!ssr_use_child_care()) {
      return AccessResult::forbidden()->addCacheableDependency(parent::access());
    }
    return parent::access()->andIf(AccessResult::allowedIfHasPermission($account, 'school staff permissions'));
  }

  public function getTitle(): string|TranslatableMarkup {
    return $this->t('After school child care - check in');
  }

}
