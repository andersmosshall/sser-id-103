<?php

namespace Drupal\simple_school_reports_grade_stats\Plugin\Block;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
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
 *  id = "student_grade_statistics_block",
 *  admin_label = @Translation("Student grade statistics"),
 * )
 */
class StudentGradeStatisticsBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;


  /**
   * @var AccountInterface
   */
  protected $currentUser;

  /**
   * @var bool
   */
  protected $skipAccessCheck = FALSE;

  protected AccountInterface|null $fallbackStudent = NULL;

  /**
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\simple_school_reports_core\Service\TermServiceInterface $term_service
   */
  public function __construct(
    array $configuration,
          $plugin_id,
          $plugin_definition,
    RequestStack $request_stack,
    RouteMatchInterface $route_match,
    UuidInterface $uuid,
    EntityTypeManagerInterface $entity_type_manager,
    Connection $connection,
    GradeStatisticsServiceInterface $grade_statistics,
    AccountInterface $current_user,
    CurrentPathStack $current_path
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentRequest = $request_stack->getCurrentRequest();
    $this->currentRouteMatch = $route_match;
    $this->uuidService = $uuid;
    $this->entityTypeManager = $entity_type_manager;
    $this->connection = $connection;
    $this->gradeStatistics = $grade_statistics;
    $this->currentUser = $current_user;
    $this->currentPath = $current_path;
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
      $container->get('current_user'),
      $container->get('path.current'),
    );
  }

  public function setSkipAccessCheck(bool $value): self {
    $this->skipAccessCheck = $value;
    return $this;
  }

  public function setFallbackStudent(UserInterface|null $student): self {
    $this->fallbackStudent = $student;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [
      '#type' => 'container',
    ];
    $cache = new CacheableMetadata();
    $cache->addCacheContexts(['route', 'user.permissions']);
    $cache->addCacheTags(['node_list:grade_student_group', 'node_list:grade_round']);

    /** @var \Drupal\user\UserInterface $student */
    $student = $this->currentRouteMatch->getParameter('user') ?? $this->fallbackStudent;

    if (!$student instanceof UserInterface) {
      throw new AccessDeniedHttpException();
    }

    $student_uid = $student->id();

    $cache->addCacheTags(['user:' . $student_uid]);

    // Find all relevant grade rounds.
    $query = $this->connection->select('node__field_student', 's');
    $query->innerJoin('node__field_student_groups', 'sg', 'sg.field_student_groups_target_id = s.entity_id');
    $query->innerJoin('node__field_locked', 'l', 'sg.entity_id = l.entity_id');

    $results = $query->condition('s.bundle', 'grade_student_group')
      ->condition('s.field_student_target_id', $student_uid)
      ->condition('l.field_locked_value', 1)
      ->fields('sg', ['entity_id'])
      ->execute();

    $grade_round_nids = [];

    foreach ($results as $result) {
      $grade_round_nids[$result->entity_id] = $result->entity_id;
    }

    $data = [];

    $graded_subjects = [];
    $grades = [];

    foreach ($grade_round_nids as $grade_round_nid) {
      $grade_stats = $this->gradeStatistics->getStudentGradeStatistics($grade_round_nid, $student_uid);
      if (!empty($grade_stats['grades'])) {
        foreach ($grade_stats['grades'] as $subject_id => $grade_id) {
          $graded_subjects[$subject_id] = TRUE;
          $grades[$grade_id] = TRUE;
        }
        $data[$grade_round_nid] = $grade_stats;
        $cache->addCacheTags(['node:' . $grade_round_nid]);
      }
    }

    if (empty($data)) {
      return $this->returnEmpty($cache);
    }

    $sorted_grade_rounds = [];
    $grade_rounds = $this->entityTypeManager->getStorage('node')->loadMultiple(array_keys($data));
    /** @var \Drupal\node\NodeInterface $grade_round */
    foreach ($grade_rounds as $grade_round) {
      $sort_key = $grade_round->get('field_document_date')->value . '_' . (1000000000 - $grade_round->id());
      $sorted_grade_rounds[$sort_key] = [
        'id' => $grade_round->id(),
        'label' => $grade_round->label(),
      ];
    }
    ksort($sorted_grade_rounds);

    /** @var \Drupal\taxonomy\TermStorageInterface $term_storage */
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $subjects = $term_storage->loadMultiple(array_keys($graded_subjects));
    $code_map = [];
    $subject_options = [];
    /** @var \Drupal\taxonomy\TermInterface $subject */
    foreach ($subjects as $subject)  {
      $code = $subject->get('field_subject_code_new')->value;
      if ($code) {
        $code_map[$subject->id()] = $code;
        $subject_options[$code] = $subject->label() . ' (' . $code . ')';
      }
    }

    if (empty($subject_options)) {
      return $this->returnEmpty($cache);
    }
    asort($subject_options);

    $grades = $term_storage->loadMultiple(array_keys($grades));
    $use_merit = FALSE;
    $grades_map = [];

    /** @var \Drupal\taxonomy\TermInterface $grade */
    foreach ($grades as $grade) {
      $grades_map[$grade->id()] = $grade->label();

      if ($grade->bundle() === 'af_grade_system') {
        $use_merit = TRUE;
      }
    }

    $has_school_staff_permissions = $this->skipAccessCheck || $this->currentUser->hasPermission('school staff permissions');

    if ($use_merit) {
      $subject_options['merit'] = $this->t('Merit');
    }

    $build['grades_select'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Select subjects'),
      '#attributes' => [
        'class' => ['visible-columns-select'],
      ],
    ];

    foreach ($subject_options as $key => $label) {
      $build['grades_select'][$key] = [
        '#type' => 'checkbox',
        '#title' => $label,
        '#return_value' => mb_strtolower($key),
      ];
    }

    $build['table'] = [
      '#type' => 'table',
      '#ssr_sorted_row_keys' => [],
    ];

    $build['table']['#header']['round'] = $this->t('Grade round');

    foreach ($subject_options as $key => $label) {
      if ($key !== 'merit') {
        $label = $key;
      }
      $build['table']['#header'][$key] = Markup::create('<div class="col--' . mb_strtolower($key) .'">' . $label . '</div>');
    }

    $show_grade_doc_file_gen = $has_school_staff_permissions;

    $has_document_setting_nids = [];
    $has_gen_links = FALSE;

    if ($show_grade_doc_file_gen) {
      $build['generate_doc_wrapper'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Generate grade document'),
        '#attributes' => [
          'class' => ['generate-grade-document-fieldset'],
        ],
      ];

      $student_group_nids = $this->entityTypeManager->getStorage('node')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('type', 'grade_student_group')
        ->condition('field_student', $student_uid)
        ->condition('field_document_type', ['term', 'final'], 'IN')
        ->execute();

      if (!empty($student_group_nids)) {
        $has_document_setting_nids = $this->entityTypeManager->getStorage('node')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('type', 'grade_round')
          ->condition('field_student_groups', $student_group_nids, 'IN')
          ->execute();
      }
    }

    foreach ($sorted_grade_rounds as $key => $item) {
      $grade_round_nid = $item['id'];
      $grade_round_label = $item['label'];

      $build['table']['#ssr_sorted_row_keys'][] = $key;
      $build['table'][$key]['round'] = [
        '#markup' => $grade_round_label,
      ];

      $code_mapped_grades = [];
      foreach ($data[$grade_round_nid]['grades'] as $subject_id => $grade_id) {
        if (!empty($code_map[$subject_id]) && !empty($grades_map[$grade_id])) {
          $code_mapped_grades[$code_map[$subject_id]] = $grades_map[$grade_id];
        }
      }

      $code_mapped_grades['merit'] = $data[$grade_round_nid]['grade_system'] === 'af_grade_system'
        ? $data[$grade_round_nid]['merit']
        : '';

      foreach ($subject_options as $code => $label) {
        $value = $code_mapped_grades[$code] ?? '';
        $build['table'][$key][$code] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => [
              'col--' . mb_strtolower($code),
            ],
          ],
          'value' => [
            '#markup' => $value,
          ],
        ];
      }

      if ($show_grade_doc_file_gen && in_array($grade_round_nid, $has_document_setting_nids)) {
        $has_gen_links = TRUE;
        $build['generate_doc_wrapper'][$grade_round_nid] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => 'generate-grade-doc-wrapper',
          ],
        ];

        $build['generate_doc_wrapper'][$grade_round_nid]['link'] = [
          '#type' => 'link',
          '#attributes' => [
            'class' => ['button', 'button--action', 'button--primary'],
          ],
          '#title' => $grade_round_label,
          '#description' => $this->t('Generate grade document'),
          '#url' => Url::fromRoute('simple_school_reports_grade_registration.generate_grade_single_doc', ['node' => $grade_round_nid, 'user' => $student_uid], ['query' => ['destination' => $this->currentPath->getPath(),]]),
        ];
      }
    }

    if (!$has_gen_links && isset($build['generate_doc_wrapper'])) {
      unset($build['generate_doc_wrapper']);
    }

    $build['#attached']['library'][] = 'simple_school_reports_grade_stats/student_grade_statistics';
    $build['#attributes']['class'][] = 'student-grade-statistics';

    $cache->applyTo($build);
    return $build;
  }

  protected function returnEmpty(CacheableMetadata $cache) {
    $build['message'] = [
      '#type' => 'html_tag',
      '#tag' => 'em',
      '#value' => $this->t('There is no grade statistics to be shown yet.'),
    ];
    $cache->applyTo($build);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function blockAccess(AccountInterface $account) {
    /** @var \Drupal\user\UserInterface $user */
    $user = $this->currentRouteMatch->getParameter('user');
    return AccessResult::allowedIf($user && $user->hasRole('student') && $user->access('update', $account))->cachePerUser()->addCacheContexts(['route']);
  }

}
