<?php

/**
 * @file
 * Definition of Drupal\d8views\Plugin\views\field\DaysLeft
 */

namespace Drupal\simple_school_reports_extension_proxy\Plugin\views\field;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\Core\Url;
use Drupal\simple_school_reports_core\Service\AbsenceStatisticsServiceInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to show progress in consent.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("absence_matrix_info")
 */
class AbsenceMatrixInfo extends FieldPluginBase {

  use RedirectDestinationTrait;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\simple_school_reports_core\Service\AbsenceStatisticsServiceInterface
   */
  protected $absenceStatisticsService;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->moduleHandler = $container->get('module_handler');
    $instance->absenceStatisticsService = $container->get('simple_school_reports_core.absence_statistics');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['weekday'] = ['default' => 0];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $options = [
      0 => $this->t('Monday'),
      1 => $this->t('Tuesday'),
      2 => $this->t('Wednesday'),
      3 => $this->t('Thursday'),
      4 => $this->t('Friday'),
      5 => $this->t('Saturday'),
      6 => $this->t('Sunday'),
    ];

    $form['weekday'] = [
      '#type' => 'select',
      '#title' => $this->t('Select weekday'),
      '#options' => $options,
      '#default_value' => $this->options['weekday'],
    ];
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * @{inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * @{inheritdoc}
   */
  public function render(ResultRow $values) {
    if (!$this->moduleHandler->moduleExists('simple_school_reports_absence_matrix') || !$this->view instanceof ViewExecutable || empty($this->view->element['#monday_timestamp'])) {
      return '';
    }
    $build = [];
    $cache = new CacheableMetadata();
    $uid = $values->uid ?? 0;
    $monday_timestamp = $this->view->element['#monday_timestamp'];
    $date_from = new \DateTime();
    $date_from->setTimestamp($monday_timestamp);

    $days_to_add = $this->options['weekday'] ?? 0;

    if ($days_to_add > 0) {
      $date_from->add(new \DateInterval('P' . $days_to_add . 'D'));
    }


    $operations = [];

    $query = $this->getDestinationArray();
    $query['skip_date_validation'] = TRUE;

    $operations[] = [
      'title' => $this->t('Set reported'),
      'weight' => 0,
      'url' => Url::fromRoute('simple_school_reports_core.single_absence_day_specific', ['user' => $uid, 'date' => $date_from->format('Y-m-d'), 'type' => 'reported']),
      'query' => $query,
      'attributes' => [
        'class' => ['use-ajax'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => json_encode([
          'width' => 880,
        ]),
      ],
    ];

    $operations[] = [
      'title' => $this->t('Set leave'),
      'weight' => 0,
      'url' => Url::fromRoute('simple_school_reports_core.single_absence_day_specific', ['user' => $uid, 'date' => $date_from->format('Y-m-d'), 'type' => 'leave']),
      'query' => $query,
      'attributes' => [
        'class' => ['use-ajax'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => json_encode([
          'width' => 880,
        ]),
      ],
    ];

    $options = [
      'reported' => (string) $this->t('Reported absence'),
      'leave' => (string) $this->t('Leave absence'),
    ];

    $items = [];
    $date_from->setTime(0,0,0);
    $absence_list = $this->absenceStatisticsService->getUserDayAbsenceItems($date_from->format('Y-m-d'), $uid);
    foreach ($absence_list as $nid => $absence_data) {
      $type_value = $options[$absence_data['type']] ?? $absence_data['type'];
      $display_full = explode(' ', $type_value)[0];

      $from_object = new \DateTime();
      $from_object->setTimestamp($absence_data['from']);

      $to_object = new \DateTime();
      $to_object->setTimestamp($absence_data['to']);

      $time_spec = $from_object->format('H:i') . ' - ' . $to_object->format('H:i');

      if ($time_spec !== '00:00 - 23:59') {
        $display_full .= ' ' . $time_spec;
      }

      $items[] = [
        '#markup' => $display_full,
      ];
      $operations[] = [
        'title' => $this->t('Delete @label', ['@label' => $display_full ]),
        'weight' => 0,
        'url' => Url::fromRoute('entity.node.delete_form', ['node' => $nid]),
        'query' => $query,
        'attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => json_encode([
            'width' => 880,
          ]),
        ],
      ];

    }

    if (!empty($items)) {
      $build['absence_list'] = [
        '#theme' => 'item_list',
        '#items' => $items,
        '#title' => NULL,
        '#list_type' => 'ul',
        '#attributes' => [
          'class' => ['absence-matrix-info--absence-list'],
        ],
      ];
    }

    $operations[] = [
      'title' => $this->t('Add period'),
      'weight' => 0,
      'url' => Url::fromRoute('simple_school_reports_core.single_absence_day', ['user' => $uid]),
      'query' => $query,
      'attributes' => [
        'class' => ['use-ajax'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => json_encode([
          'width' => 880,
        ]),
      ],
    ];

    if (!empty($operations)) {
      $build['operations'] = [
        '#type' => 'operations',
        '#links' => $operations,
      ];
    }

    $cache->addCacheTags(['node_list:day_absence:' . $date_from->format('Y-m-d') . ':' . $uid]);
    $cache->addCacheContexts(['route', 'url.query_args']);
    $cache->applyTo($build);

    $build['#attached']['library'][] = 'core/drupal.dialog.ajax';

    return $build;
  }

}
