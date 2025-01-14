<?php

namespace Drupal\simple_school_reports_grade_stats\Plugin\Block;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\simple_school_reports_core\AbsenceDayHandler;
use Drupal\simple_school_reports_grade_stats\Service\GradeStatisticsServiceInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a 'StudentGradeStatisticsBlock' block.
 *
 * @Block(
 *  id = "grade_statistics_block",
 *  admin_label = @Translation("Grade statistics"),
 * )
 */
class GradeStatisticsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  const NO_GRADE_KEY = -10;

  use StringTranslationTrait;

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
   * The UUID service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuidService;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * @var \Drupal\simple_school_reports_grade_stats\Service\GradeStatisticsServiceInterface
   */
  protected $gradeStatistics;


  /**
   * @var AccountInterface
   */
  protected $currentUser;

  /**
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\simple_school_reports_core\Service\TermServiceInterface $term_service
   */
  public function __construct(
    array                           $configuration,
                                    $plugin_id,
                                    $plugin_definition,
    RequestStack                    $request_stack,
    RouteMatchInterface             $route_match,
    UuidInterface                   $uuid,
    EntityTypeManagerInterface      $entity_type_manager,
    Connection                      $connection,
    GradeStatisticsServiceInterface $grade_statistics,
    AccountInterface                $current_user
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentRequest = $request_stack->getCurrentRequest();
    $this->currentRouteMatch = $route_match;
    $this->uuidService = $uuid;
    $this->entityTypeManager = $entity_type_manager;
    $this->connection = $connection;
    $this->gradeStatistics = $grade_statistics;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack'),
      $container->get('current_route_match'),
      $container->get('uuid'),
      $container->get('entity_type.manager'),
      $container->get('database'),
      $container->get('simple_school_reports_grade_stats.grade_statistics'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(?NodeInterface $grade_statistics = NULL) {
    if (!$grade_statistics || $grade_statistics->bundle() !== 'grade_statistics') {
      return [];
    }

    $build = [
      '#type' => 'container',
    ];
    $cache = new CacheableMetadata();
    $cache->addCacheContexts(['user.permissions']);
    $cache->addCacheTags(['user_gender_change']);

    $settings = $this->getPresentationSettings($grade_statistics);

    $data_sources = $grade_statistics->get('field_data_source')
      ->referencedEntities();

    /**
     * @var ParagraphInterface $data_source
     */
    foreach ($data_sources as $key => $data_source) {
      $build[$key] = $this->buildFromDataSource($data_source, $settings, $cache);
    }

    $build['#attached']['library'][] = 'simple_school_reports_grade_stats/grade_statistics';
    $build['#attributes']['class'][] = 'grade-statistics';

    $cache->applyTo($build);
    return $build;
  }

  protected function getPresentationSettings(NodeInterface $grade_statistics): array {
    $settings = [
      'subjects' => [],
      'subject_ids' => [],
      'separate_subjects' => FALSE,
      'subject_merge_types' => [],
      'separate_grades' => $grade_statistics->get('field_grade_sectioning')->value === 'separate',
      'merge_subjects' => FALSE,
      'separate_gender' => FALSE,
      'merge_gender' => FALSE,
      'gender_types' => [],
      'gender_type_labels' => [],
      'use_graphs' => FALSE,
      'use_tables' => FALSE,
      'primary_label' => $grade_statistics->get('field_primary_label')->value ?? '',
      'compare_label' => $grade_statistics->get('field_compare_label')->value ?? '',
      'supported_grade_systems' => array_keys(_simple_school_reports_extension_proxy_supported_grade_systems()),
      'grade_meta' => [],
      'grade_map' => [],
    ];

    $subjects = $grade_statistics->get('field_school_subjects')
      ->referencedEntities();
    foreach ($subjects as $subject) {
      $settings['subjects'][$subject->id()] = $subject;
      $settings['subject_ids'][] = $subject->id();
    }

    $field_merge = array_column($grade_statistics->get('field_merge')
      ->getValue(), 'value');
    foreach ($field_merge as $value) {
      $settings[$value . '_subjects'] = TRUE;
    }

    if ($settings['separate_subjects']) {
      $settings['subject_merge_types'] = $settings['subject_ids'];
    }

    if ($settings['merge_subjects']) {
      $settings['subject_merge_types'][] = 'all';
    }

    $field_gender_options = array_column($grade_statistics->get('field_gender_options')
      ->getValue(), 'value');
    foreach ($field_gender_options as $value) {
      $settings[$value . '_gender'] = TRUE;
    }

    if ($settings['merge_gender']) {
      $settings['gender_types'][] = 'all';
      $settings['gender_type_labels']['all'] = '';
    }

    if ($settings['separate_gender']) {
      $settings['gender_types'][] = 'male';
      $settings['gender_types'][] = 'female';

      $settings['gender_type_labels']['male'] = $this->t('Boys');
      $settings['gender_type_labels']['female'] = $this->t('Girls');
    }

    $field_statistics_presentation = array_column($grade_statistics->get('field_statistics_presentation')
      ->getValue(), 'value');
    foreach ($field_statistics_presentation as $value) {
      $settings['use_' . $value] = TRUE;
    }

    /** @var \Drupal\taxonomy\TermStorageInterface $term_storage */
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');

    $grade_term_ids = $term_storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('vid', $settings['supported_grade_systems'], 'IN')
      ->execute();

    if (!empty($grade_term_ids)) {
      $separate_grades = $settings['separate_grades'];
      $grade_terms = $term_storage->loadMultiple($grade_term_ids);

      /** @var \Drupal\taxonomy\TermInterface $grade_term */
      foreach ($grade_terms as $grade_term) {
        $vid = $grade_term->bundle();
        $merit = $grade_term->get('field_merit')->value ? $grade_term->get('field_merit')->value * 100 : 0;
        $merit = (int) $merit;
        $data_key = $grade_term->label() === '-' ? self::NO_GRADE_KEY : NULL;
        if ($data_key === NULL) {
          if ($separate_grades) {
            $data_key = $merit;
          }
          else {
            $data_key = $merit > 0 ? 10 : 0;
          }
        }
        $settings['grade_map'][$grade_term->id()] = $data_key;
        $settings['grade_meta'][$grade_term->id()] = [
          'label' => $grade_term->label(),
          'data_key' => $data_key,
          'vid' => $vid,
        ];
      }
    }

    return $settings;
  }

  protected function buildFromDataSource(ParagraphInterface $data_source_paragraph, array $settings, CacheableMetadata $cache): array {
    if ($data_source_paragraph->bundle() !== 'grade_statistics_data_source') {
      return [];
    }
    $data_source_label = $data_source_paragraph->get('field_label')->value ?? '?';
    $use_merit = $data_source_paragraph->get('field_include_merit')->value ?? FALSE;

    $primary_data_student_groups = array_column($data_source_paragraph->get('field_grade_stats_primary_source')
      ->getValue(), 'target_id');
    $primary_data_label = $settings['primary_label'];
    if (empty($primary_data_student_groups)) {
      return [];
    }

    $primary_data = $this->buildStatistics($primary_data_student_groups, $settings['subject_ids'], $settings, $cache, $primary_data_label);

    $compare_data_student_groups = array_column($data_source_paragraph->get('field_grade_stats_compare_source')
      ->getValue(), 'target_id');
    $compare_data_label = $settings['compare_label'];
    $compare_data = [];
    if (!empty($compare_data_student_groups)) {
      $compare_data = $this->buildStatistics($compare_data_student_groups, $settings['subject_ids'], $settings, $cache, $compare_data_label);
    }

    $final_stats_data = $this->buildFinalStatistics($primary_data, $compare_data, $primary_data_label, $compare_data_label, $settings);


    $build = [
      '#type' => 'container',
      '#attributes' => [
        'class' => 'grade-statistics--data-source-wrapper',
      ],
    ];

    $build['label'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $data_source_label,
    ];

    if (!empty($final_stats_data['tables']['count_all_table'])) {
      $build['count_all_table'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => 'grade-statistics--count-all',
        ],
      ];
      $build['count_all_table']['table'] = $this->buildTable($final_stats_data['tables']['count_all_table']);
    }

    if ($use_merit) {
      $build['merit'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => 'grade-statistics--merit',
        ],
      ];

      $build['merit']['label'] = [
        '#type' => 'html_tag',
        '#tag' => 'h5',
        '#value' => $data_source_label . ' - ' . $this->t('Mean merit'),
      ];

      $build['merit']['table'] = $this->buildTable($final_stats_data['tables']['mean_merit_table'] ?? []);
    }

    if ($settings['merge_subjects']) {
      $build['merged_subjects'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => 'grade-statistics--merged-subjects',
        ],
      ];

      $build['merged_subjects']['label'] = [
        '#type' => 'html_tag',
        '#tag' => 'h5',
        '#value' => $data_source_label . ' - ' . $this->t('All selected subjects'),
      ];

      if ($settings['use_graphs']) {
        $build['merged_subjects']['graph'] = $this->buildGraph($final_stats_data['graphs']['all'] ?? []);
      }

      if ($settings['use_tables']) {
        $build['merged_subjects']['table'] = $this->buildTable($final_stats_data['tables']['all'] ?? []);
      }
    }

    if ($settings['separate_subjects']) {
      $build['subjects'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => 'grade-statistics--subjects',
        ],
      ];

      /** @var \Drupal\taxonomy\TermInterface $subject */
      foreach ($settings['subjects'] as $key => $subject) {
        if (empty($final_stats_data['graphs'][$subject->id()]) && empty($final_stats_data['tables'][$subject->id()])) {
          continue;
        }

        $build['subjects'][$key] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => 'grade-statistics--subject-item',
          ],
        ];

        $subject_name = $subject->label();
        if ($subject->get('field_language_code')->value) {
          $subject_name .= ' ' . $subject->get('field_language_code')->value;
        }

        $build['subjects'][$key]['label'] = [
          '#type' => 'html_tag',
          '#tag' => 'h5',
          '#value' => $data_source_label . ' - ' . $subject_name,
        ];

        if ($settings['use_graphs']) {
          $build['subjects'][$key]['graph'] = $this->buildGraph($final_stats_data['graphs'][$subject->id()] ?? []);
        }

        if ($settings['use_tables']) {
          $build['subjects'][$key]['table'] = $this->buildTable($final_stats_data['tables'][$subject->id()] ?? []);
        }
      }
    }

    return $build;

  }

  protected function buildTable($data): array {
    if (empty($data['labels']) || empty($data['data_groups'])) {
      return [];
    }

    $build['table'] = [
      '#type' => 'table',
    ];

    $build['table']['#header']['round'] = '';

    foreach ($data['data_groups'] as $data_group_type => $data_group) {
      $build['table'][$data_group_type]['#attributes']['class'][] = 'row--' . $data_group_type;
      $build['table'][$data_group_type]['round'] = [
        '#markup' => $data_group['label'] ?? '',
      ];
    }


    foreach ($data['labels'] as $key => $label) {
      $build['table']['#header'][$key] = $label;
      foreach ($data['data_groups'] as $data_group_type => $data_group) {
        $value = !empty($data_group['data'][$key]) ? $data_group['data'][$key] : 0;
        $build['table'][$data_group_type][$key] = [
          '#markup' => $value,
        ];
      }
    }

    return $build;
  }

  protected function buildGraph($data): array {
    if (empty($data['labels']) || empty($data['data_groups'])) {
      return [];
    }

    $build = [];

    $labels = [];
    foreach ($data['labels'] as $label) {
      $labels[] = (string) $label;
    }

    $graph_data = [
      'labels' => $labels,
      'datasets' => [],
    ];

    // ToDo define these.
    $data_color_map = [
      'primary_all' => 'rgba(44, 192, 54, 0.75)',
      'primary_male' => 'rgba(0, 60, 197, 0.75)',
      'primary_female' => 'rgba(215, 34, 34, 0.75)',
      'compare_all' => 'rgba(44, 192, 54, 0.50)',
      'compare_male' => 'rgba(0, 60, 197, 0.50)',
      'compare_female' => 'rgba(215, 34, 34, 0.50)',
      'default' => 'rgba(0, 0, 0, 0.50)',
    ];

    foreach ($data['data_groups'] as $key => $data_group) {
      $color = $data_color_map[$key] ?? $data_color_map['default'];

      $graph_data['datasets'][] = [
        'label' => $data_group['label'],
        'data' => $data_group['data'],
        'rawData' => $data_group['raw_data'],
        'backgroundColor' => [
          $color,
        ],
      ];
    }


    $uuid = 'id-' . $this->uuidService->generate();

    $build['#attached']['library'][] = 'simple_school_reports_grade_stats/grade_statistics_graph';
    $build['#attached']['drupalSettings']['gradeStatisticsGraphData']['grade_statistics_graph'][$uuid] = $graph_data;


    $build['graph_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['graph-wrapper'],
        'style' => ['height: 400px'],
      ],
    ];
    $build['graph_wrapper']['graph'] = [
      '#type' => 'html_tag',
      '#tag' => 'canvas',
      '#attributes' => [
        'id' => $uuid,
      ],
    ];


    return $build;
  }

  protected function buildStatistics(array $student_groups, array $subject_ids, array $settings, CacheableMetadata $cache, &$label): array {
    $node_storage = $this->entityTypeManager->getStorage('node');

    $grade_rounds = [];
    $grade_round_ids = $node_storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'grade_round')
      ->condition('field_student_groups', $student_groups, 'IN')
      ->execute();

    if (!empty($grade_round_ids)) {
      $grade_rounds = $node_storage->loadMultiple($grade_round_ids);
    }

    $raw_statistics = [];

    /** @var NodeInterface $grade_round */
    foreach ($grade_rounds as $grade_round) {
      $cache->addCacheTags(['node:' . $grade_round->id()]);
      $raw_statistics[$grade_round->id()] = $this->gradeStatistics->getGradeStatistics($grade_round->id());
      if (!$label) {
        $label = $grade_round->label();
      }
    }
    $statistics = [];

    // Include 'all' gender type.
    if (!in_array('all', $settings['gender_types'])) {
      $settings['gender_types'][] = 'all';
    }

    // Merge statistics.

    // Set default values.
    foreach ($settings['gender_types'] as $gender_type) {
      foreach ($settings['subject_merge_types'] as $subject_merge_type) {
        $statistics[$subject_merge_type][$gender_type] = [];
        foreach ($settings['grade_map'] as $data_key) {
          $statistics[$subject_merge_type][$gender_type]['data'][$data_key] = 0;
          $statistics[$subject_merge_type][$gender_type]['vids'] = [];
          $statistics[$subject_merge_type][$gender_type]['count'] = 0;

          // Always use this.
          $statistics['all'][$gender_type]['count'] = 0;
          $statistics['all'][$gender_type]['total_merit'] = 0;
        }
      }
    }

    foreach ($raw_statistics as $grade_round_id => $grade_round_raw_statistic) {
      if (empty($grade_round_raw_statistic['student_groups'])) {
        continue;
      }

      foreach ($grade_round_raw_statistic['student_groups'] as $student_group => $raw_statistic) {
        if (in_array($student_group, $student_groups)) {
          $do_merge_all = isset($statistics['all']);
          foreach ($settings['gender_types'] as $gender_type) {
            if (!empty($raw_statistic[$gender_type . '_count'])) {
              $statistics['all'][$gender_type]['count'] += $raw_statistic[$gender_type . '_count'];
            }
            if (!empty($raw_statistic['total_merit'][$gender_type])) {
              $statistics['all'][$gender_type]['total_merit'] += $raw_statistic['total_merit'][$gender_type];
            }
          }


          foreach ($subject_ids as $subject_id) {
            $subject_in_use = in_array($subject_id, $settings['subject_merge_types']);
            foreach ($settings['gender_types'] as $gender_type) {
              if (!empty($raw_statistic[$gender_type . '_count']) && $subject_in_use) {
                $statistics[$subject_id][$gender_type]['count'] += $raw_statistic[$gender_type . '_count'];
              }
              if (!empty($raw_statistic['subjects'][$subject_id][$gender_type]['grades'])) {
                foreach ($raw_statistic['subjects'][$subject_id][$gender_type]['grades'] as $grade_id => $count) {
                  $data_key = $settings['grade_map'][$grade_id] ?? NULL;
                  $grade_meta = $settings['grade_meta'][$grade_id] ?? NULL;
                  if ($data_key !== NULL && $grade_meta !== NULL) {
                    if ($do_merge_all) {
                      if (empty($statistics['all'][$gender_type]['data'][$data_key])) {
                        $statistics['all'][$gender_type]['data'][$data_key] = 0;
                      }
                      $statistics['all'][$gender_type]['data'][$data_key] += $count;
                      $statistics['all'][$gender_type]['vids'][$grade_meta['vid']] = TRUE;
                    }
                    if ($subject_in_use) {
                      if (empty($statistics[$subject_id][$gender_type]['data'][$data_key])) {
                        $statistics[$subject_id][$gender_type]['data'][$data_key] = 0;
                      }
                      $statistics[$subject_id][$gender_type]['data'][$data_key] += $count;
                      $statistics[$subject_id][$gender_type]['vids'][$grade_meta['vid']] = TRUE;
                    }
                  }
                }
              }
            }
          }
        }
      }
    }

    return $statistics;
  }

  protected function buildFinalStatistics(array $primary_data, array $compare_data, string $primary_label, string $compare_label, array $settings): array {
    $data = [];

    $use_graphs = $settings['use_graphs'];
    $use_tables = $settings['use_tables'];

    $has_compare = !empty($compare_data);

    // Resolve count table.
    $table_data = [
      'labels' => [],
      'data_groups' => [],
    ];

    foreach ($settings['gender_types'] as $gender_type) {
      if ($settings['gender_type_labels'][$gender_type]) {
        $table_data['labels'][$gender_type] = $settings['gender_type_labels'][$gender_type];
      }
    }
    $table_data['labels']['all'] = $this->t('All');

    $table_data['data_groups']['primary'] = [
      'label' => $primary_label,
      'data' => [],
    ];

    if ($has_compare) {
      $table_data['data_groups']['compare'] = [
        'label' => $compare_label,
        'data' => [],
      ];
    }

    $primary_total = [];
    $compare_total = [];

    $primary_total['all'] = !empty($primary_data['all']['all']['count']) ? $primary_data['all']['all']['count'] : 1;
    $compare_total['all'] = $has_compare && !empty($compare_data['all']['all']['count']) ? $compare_data['all']['all']['count'] : 1;

    foreach ($settings['gender_types'] as $gender_type) {
      $primary_total[$gender_type] = !empty($primary_data['all'][$gender_type]['count']) ? $primary_data['all'][$gender_type]['count'] : 1;
      $compare_total[$gender_type] = $has_compare && !empty($compare_data['all'][$gender_type]['count']) ? $compare_data['all'][$gender_type]['count'] : 1;
    }


    foreach ($table_data['labels'] as $gender_type => $label) {
      $value = !empty($primary_data['all'][$gender_type]['count']) ? $primary_data['all'][$gender_type]['count'] : 0;
      if ($gender_type !== 'all') {
        $value = $value . ' ( ' . round(($value / $primary_total['all']) * 100) . '%)';
      }
      $table_data['data_groups']['primary']['data'][] = $value;

      if ($has_compare) {
        $value = !empty($compare_data['all'][$gender_type]['count']) ? $compare_data['all'][$gender_type]['count'] : 0;
        if ($gender_type !== 'all') {
          $value = $value . ' ( ' . round(($value / $compare_total['all']) * 100) . '%)';
        }
        $table_data['data_groups']['compare']['data'][] = $value;
      }
    }

    $table_data['labels'] = array_values($table_data['labels']);
    $data['tables']['count_all_table'] = $table_data;

    // Resolve total merit table.
    $table_data = [
      'labels' => [$this->t('Mean merit')],
      'data_groups' => [],
    ];

    foreach ($settings['gender_types'] as $gender_type) {
      $label_suffix = $settings['gender_type_labels'][$gender_type] ? ' - ' . $settings['gender_type_labels'][$gender_type] : '';

      $value = !empty($primary_data['all'][$gender_type]['total_merit']) ? $primary_data['all'][$gender_type]['total_merit'] : 0;
      $value = round($value / $primary_total[$gender_type], 1) . ' p';

      $table_data['data_groups']['primary_' . $gender_type] = [
        'label' => $primary_label . $label_suffix,
        'data' => [$value],
      ];

      if ($has_compare) {
        $value = !empty($compare_data['all'][$gender_type]['total_merit']) ? $compare_data['all'][$gender_type]['total_merit'] : 0;
        $value = round($value / $compare_total[$gender_type], 1) . ' p';

        $table_data['data_groups']['compare_' . $gender_type] = [
          'label' => $compare_label . $label_suffix,
          'data' => [$value],
        ];
      }
    }
    $data['tables']['mean_merit_table'] = $table_data;

    foreach ($settings['subject_merge_types'] as $subject_merge_type) {

      $table_data = [
        'labels' => [],
        'data_groups' => [],
      ];
      $vids = [];
      foreach ($settings['gender_types'] as $gender_type) {
        if (!empty($primary_data[$subject_merge_type][$gender_type]['vids'])) {
          $vids = array_merge($vids, array_keys($primary_data[$subject_merge_type][$gender_type]['vids']));
        }

        if (!empty($compare_data[$subject_merge_type][$gender_type]['vids'])) {
          $vids = array_merge($vids, array_keys($compare_data[$subject_merge_type][$gender_type]['vids']));
        }
      }

      $vids = array_unique($vids);

      if (empty($vids)) {
        continue;
      }

      foreach ($settings['gender_types'] as $gender_type) {
        $label_suffix = $settings['gender_type_labels'][$gender_type] ? ' - ' . $settings['gender_type_labels'][$gender_type] : '';

        $table_data['data_groups']['primary_' . $gender_type] = [
          'label' => $primary_label . $label_suffix,
          'data' => [],
        ];

        if ($has_compare) {
          $table_data['data_groups']['compare_' . $gender_type] = [
            'label' => $compare_label . $label_suffix,
            'data' => [],
          ];
        }
      }

      $labels = [];

      foreach ($settings['grade_meta'] as $grade_meta) {
        if (in_array($grade_meta['vid'], $vids)) {
          $data_key = $grade_meta['data_key'];
          if ($settings['separate_grades']) {
            $labels[$data_key][] = $grade_meta['label'];
          }
          else {
            if ($data_key > 0) {
              $labels[$data_key] = (string) $this->t('Pass');
            }
            else {
              $labels[$data_key] = (string) $this->t('Not pass');
            }
          }
        }
      }

      ksort($labels);
      foreach ($labels as &$label) {
        if (is_array($label)) {
          $label = array_unique($label);
          $label = implode(', ', $label);
        }
      }

      foreach ($labels as $data_key => $data_label) {
        $has_data = FALSE;
        $local_data = [];

        foreach ($settings['gender_types'] as $gender_type) {
          $local_data['primary_' . $gender_type] = 0;
          $local_data['compare_' . $gender_type] = 0;
          if (!empty($primary_data[$subject_merge_type][$gender_type]['data'][$data_key])) {
            $has_data = TRUE;
            $local_data['primary_' . $gender_type] = $primary_data[$subject_merge_type][$gender_type]['data'][$data_key];
          }

          if ($has_compare && !empty($compare_data[$subject_merge_type][$gender_type]['data'][$data_key])) {
            $has_data = TRUE;
            $local_data['compare_' . $gender_type] = $compare_data[$subject_merge_type][$gender_type]['data'][$data_key];
          }
        }

        if ($has_data || $data_key !== self::NO_GRADE_KEY) {
          $table_data['labels'][] = $data_label;

          foreach ($settings['gender_types'] as $gender_type) {
            $primary_key = 'primary_' . $gender_type;

            $table_data['data_groups'][$primary_key]['data'][] = $local_data[$primary_key];

            if ($has_compare) {
              $compare_key = 'compare_' . $gender_type;
              $table_data['data_groups'][$compare_key]['data'][] = $local_data[$compare_key];
            }
          }
        }
      }


      if ($use_tables) {
        $data['tables'][$subject_merge_type] = $table_data;
      }

      if ($use_graphs) {
        $graph_data = $table_data;
        foreach ($graph_data['data_groups'] as &$data_group) {
          $total = array_sum($data_group['data']);
          if ($total < 1) {
            $total = 1;
          }
          $data_group['raw_data'] = $data_group['data'];
          foreach ($data_group['data'] as &$data_point) {
            $data_point = round(($data_point / $total) * 100);
          }
        }
        $data['graphs'][$subject_merge_type] = $graph_data;
      }
    }


    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIf($account->hasPermission('school staff permissions'))
      ->cachePerPermissions();
  }

}
