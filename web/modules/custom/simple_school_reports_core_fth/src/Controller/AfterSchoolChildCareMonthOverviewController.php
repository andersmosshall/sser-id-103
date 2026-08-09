<?php

namespace Drupal\simple_school_reports_core_fth\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Link;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\simple_school_reports_child_care_support\Service\ChildCareSchemaServiceInterface;
use Drupal\simple_school_reports_child_care_support\Service\ChildCareServiceInterface;
use Drupal\simple_school_reports_core\Controller\SsrCachedPageControllerBase;
use Drupal\simple_school_reports_core_fth\Form\AfterSchoolChildCareMonthOverviewFilterForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for AfterSchoolChildCareMonthOverviewController.
 */
class AfterSchoolChildCareMonthOverviewController extends SsrCachedPageControllerBase {

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
        'class' => ['child-care-month-overview-container'],
      ],
    ];

    $build['#attached']['library'][] = 'simple_school_reports_child_care_support/child_care_overview';

    $build['overview_filter'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Filter'),
    ];
    $build['overview_filter']['form'] = $this->formBuilder()->getForm(AfterSchoolChildCareMonthOverviewFilterForm::class, TRUE);

    $groups = $this->childCareService->getChildCareIdsFromRequest();
    if (empty($groups)) {
      return $build;
    }

    $from = $this->currentRequest->query->get('from');
    $to = $this->currentRequest->query->get('to');

    if (!$from || !$to || $from > $to) {
      return $build;
    }

    if ($to - $from > 63 * 24 * 60 * 60) {
      $messages = [];
      $messages['warning'][] = $this->t('A too long date range was selected. Please choose a shorter span.');
      $build['message'] = [
        '#theme' => 'status_messages',
        '#message_list' => $messages,
        '#status_headings' => [
          'status' => t('Status message'),
          'error' => t('Error message'),
          'warning' => t('Warning message'),
        ],
      ];
      return $build;
    }

    $date = new \DateTime();
    $date->setTimestamp($from);
    $date->setTime(0, 0, 0);

    $to_date = new \DateTime();
    $to_date->setTimestamp($to);
    $to_date->setTime(23, 59, 59);

    $headers = [
      'week' => $this->t('Week'),
      1 => $this->t('Monday'),
      2 => $this->t('Tuesday'),
      3 => $this->t('Wednesday'),
      4 => $this->t('Thursday'),
      5 => $this->t('Friday'),
      6 => $this->t('Saturday'),
      7 => $this->t('Sunday'),
    ];
    $use_weekend = FALSE;
    $rows = [];

    while ($date < $to_date) {
      $day = (int) $date->format('N');
      $year = $date->format('Y');
      $week_number = $date->format('W');
      $row_key = $year . '-' . $week_number;

      if (empty($rows[$row_key])) {
        $rows[$row_key] = [
          'week' => $week_number,
          1 => [],
          2 => [],
          3 => [],
          4 => [],
          5 => [],
          6 => [],
          7 => [],
        ];
      }

      $uids = $this->childCareService->getChildCareStudentIdsMultiple($groups, $date);
      $data = $this->childCareSchemaService->getDayOverview($uids, $date, $groups);

      $missing_offer_sections = 0;
      $perfect_sections = 0;
      $overtrace_sections = 0;

      foreach ($data as $key => $segment) {
        if ($segment['needs'] === 0 && $segment['offers'] === 0) {
          continue;
        }

        if ($segment['needs'] > $segment['offers']) {
          $missing_offer_sections += $segment['needs'] - $segment['offers'];
          continue;
        }

        if ($segment['offers'] > $segment['needs']) {
          $overtrace_sections += $segment['offers'] - $segment['needs'];
          continue;
        }

        $perfect_sections += $segment['needs'];
      }
      $total_sections = $missing_offer_sections + $perfect_sections + $overtrace_sections;

      $coverage_percent = NULL;
      $day_stat_value = '-';
      if ($total_sections > 0) {
        if ($missing_offer_sections > 0) {
          $coverage_percent = (($total_sections - $missing_offer_sections) / $total_sections) * 100;
          $day_stat_value = number_format($coverage_percent, 1) . '%';
        }
        elseif ($overtrace_sections > 0) {
          $coverage_percent = (($total_sections + $overtrace_sections) / $total_sections) * 100;
          $day_stat_value = number_format($coverage_percent, 1) . '%';
        }
        else {
          $coverage_percent = 100;
          $day_stat_value = number_format($coverage_percent, 1) . '%';
        }
      }

      $day_stat_classes = 'child-care-coverage-stats';

      if ($coverage_percent !== NULL) {
        $day_stat_class = 'child-care-coverage-stats--ok';
        if ($coverage_percent < 100) {
          $day_stat_class = 'child-care-coverage-stats--danger';
        }
        if ($coverage_percent > 120) {
          $day_stat_class = 'child-care-coverage-stats--warning';
        }
        $day_stat_classes .= ' ' . $day_stat_class;

        if ($day === 6 || $day === 7) {
          $use_weekend = TRUE;
        }
      }

      $rows[$row_key][$day]['data']['day_label'] = [
        '#markup' => '<div><strong>' . $date->format('j/n') . '</strong></div>',
      ];
      $rows[$row_key][$day]['data']['day_stats'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [$day_stat_classes],
        ]
      ];

      $rows[$row_key][$day]['data']['day_stats']['status'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $day_stat_value,
      ];

      $rows[$row_key][$day]['data']['day_stats']['link'] = [
        '#type' => 'link',
        '#attributes' => [
          'class' => ['button', 'button--extrasmall', 'button--primary', 'button--overview-day'],
        ],
        '#title' => $this->t('Details'),
        '#url' => Url::fromRoute('simple_school_reports_core_fth.overview_day', ['date' => $date->format('Y-m-d'), 'groups' => implode(',', $groups)  ?: '-1'], ['query' => $this->getDestinationArray()]),
      ];


      $date->modify('+1 day');
    }

    ksort($rows);

    if (!$use_weekend) {
      unset($headers[6]);
      unset($headers[7]);
      foreach ($rows as &$row) {
        unset($row[6]);
        unset($row[7]);
      }
    }

    $build['stat_wrapper']['table'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#attributes' => [
        'class' => ['child-care-coverage-stats-table'],
      ],
    ];

    return $build;
  }

  public function getCacheableMetadata(): CacheableMetadata {
    return $this->childCareSchemaService->getCacheableMetadata(new \DateTime());
  }

  public function pageId(): string {
    return 'child_care_month_overview';
  }

  public function accessMonthOverview(AccountInterface $account = NULL): AccessResultInterface {
    if (!ssr_use_child_care()) {
      return AccessResult::forbidden()->addCacheableDependency(parent::access());
    }
    return parent::access()->andIf(AccessResult::allowedIfHasPermission($account, 'school staff permissions'));
  }

  public function getTitle(): string|TranslatableMarkup {
    return $this->t('Overview - month');
  }

}
