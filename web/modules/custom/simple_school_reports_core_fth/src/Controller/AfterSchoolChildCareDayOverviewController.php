<?php

namespace Drupal\simple_school_reports_core_fth\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\simple_school_reports_child_care_support\Service\ChildCareSchemaServiceInterface;
use Drupal\simple_school_reports_child_care_support\Service\ChildCareServiceInterface;
use Drupal\simple_school_reports_core\Controller\SsrCachedPageControllerBase;
use Drupal\simple_school_reports_core_fth\Form\AfterSchoolChildCareDayOverviewFilterForm;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for AfterSchoolChildCareDayOverviewController.
 */
class AfterSchoolChildCareDayOverviewController extends SsrCachedPageControllerBase {

  use RedirectDestinationTrait;

  protected ChildCareServiceInterface $childCareService;

  protected ChildCareSchemaServiceInterface $childCareSchemaService;

  protected Request $currentRequest;

  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->childCareService = $container->get('simple_school_reports_child_care_support.child_care');
    $instance->childCareSchemaService = $container->get('simple_school_reports_child_care_support.child_care_schema');
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
    $build['overview_filter']['week_form'] = $this->formBuilder()->getForm(AfterSchoolChildCareDayOverviewFilterForm::class, TRUE);

    $date_string = $this->currentRequest->query->get('date');
    $date = $date_string ? new \DateTime($date_string) : NULL;

    if (!$date) {
      unset($build['heading']);
      unset($build['graph_target']);
      return $build;
    }

    $build['#attached']['library'][] = 'simple_school_reports_child_care_support/child_care_overview';

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

    $groups = $this->currentRequest->query->get('groups');
    if (is_string($groups) && $groups !== '') {
      $groups = explode(',', $groups);
    }
    if (empty($groups)) {
      return $build;
    }

    $uids = $this->childCareService->getChildCareStudentIdsMultiple($groups, $date);
    $data = $this->childCareSchemaService->getDayOverview($uids, $date, $groups);

    foreach ($data as $key => $segment) {
      $timestamp = $segment['from'];
      $date = new \DateTime();
      $date->setTimestamp($timestamp);
      $data[$key]['label'] = $date->format('H:i');
    }

    $build['graph_target']['#attached']['library'][] = 'simple_school_reports_child_care_support/child_care_graph';
    $build['graph_target']['#attributes']['data-graph-data'] = Json::encode($data);
    $build['graph_target']['graph'] = [
      '#type' => 'html_tag',
      '#tag' => 'canvas',
      '#attributes' => [
        'id' => 'child-care-overview-graph',
      ],
    ];

    $students_view = Views::getView('child_care_day_overview_students');
    $students_view->setDisplay('list');
    $students_view->preExecute();
    $students_view->execute();
    $students_view_build = $students_view->buildRenderable('list');
    $build['students_view'] = $students_view_build;

    return $build;
  }

  public function getCacheableMetadata(): CacheableMetadata {
    $cache = parent::getCacheableMetadata();

    $cache->addCacheTags([
      'school_week_list',
      'node_list:day_absence',
      'school_week_deviation_list',
      'ssr_school_week_per_grade',
      'ssr_child_care_list',
      'ssr_child_care_placement_list',
      'ssr_child_care_schema_list',
      'ssr_cc_deviation_list',
      'ssr_cc_deviation_student_list',
    ]);
    $cache->addCacheContexts(['url.query_args:date', 'url.query_args:groups', 'user']);

    // TEMP!!
    $cache->setCacheMaxAge(0);

    return $cache;
  }

  public function pageId(): string {
    return 'child_care_day_overview';
  }

  public function accessDayOverview(AccountInterface $account = NULL): AccessResultInterface {
    if (!ssr_use_child_care()) {
      return AccessResult::forbidden()->addCacheableDependency(parent::access());
    }
    return parent::access()->andIf(AccessResult::allowedIfHasPermission($account, 'school staff permissions'));
  }

  public function getTitle(): string|TranslatableMarkup {
    return $this->t('Overview - day');
  }

}
