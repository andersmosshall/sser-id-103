<?php

/**
 * @file
 * Definition of Drupal\d8views\Plugin\views\field\DaysLeft
 */

namespace Drupal\simple_school_reports_core\Plugin\views\field;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to summarize absence days for student
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("absence_day_student")
 */
class AbsenceDayStudent extends FieldPluginBase {

  /**
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * @var \Drupal\simple_school_reports_core\Service\AbsenceStatisticsServiceInterface
   */
  protected $absenceStatisticsService;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->currentRequest = $container->get('request_stack')->getCurrentRequest();
    $instance->absenceStatisticsService = $container->get('simple_school_reports_core.absence_statistics');
    return $instance;
  }

  /**
   * @{inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();

    $from = $this->currentRequest->get('from', 0);
    $to = $this->currentRequest->get('to', 0);
    $calculated_value = $this->absenceStatisticsService->getAllAbsenceDayData($from, $to);
    $cases = 'CASE ';
    foreach ($calculated_value as $value => $uids) {
      $cases .= 'WHEN uid IN (' . implode(', ', $uids) . ') THEN ' . $value . ' ';
    }
    $cases .= 'ELSE 0 END';
    $this->query->addField(NULL, $cases, 'cad');
  }

  /**
   * @{inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $values->cad ?? 0;
    $build = [];
    $build['value'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => [],
      ],
      '#value' => $value === 1 ? $value . ' ' . $this->t('day') : $value . ' ' . $this->t('days'),
    ];

    $cache = new CacheableMetadata();
    $cache->addCacheContexts(['url.query_args', 'route']);
    $cache->applyTo($build);
    return $build;
  }

  public function clickSort($order) {
    $this->query->addOrderBy(NULL, NULL, $order, 'cad');
    $this->query->addOrderBy(NULL, NULL, $order, 'cia');
  }

}
