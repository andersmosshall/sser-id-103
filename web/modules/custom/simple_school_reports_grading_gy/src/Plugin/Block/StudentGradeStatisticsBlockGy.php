<?php

namespace Drupal\simple_school_reports_grading_gy\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\simple_school_reports_core\SchoolGradeHelper;
use Drupal\simple_school_reports_core\SchoolTypeHelper;
use Drupal\simple_school_reports_entities\Service\SyllabusServiceInterface;
use Drupal\simple_school_reports_grade_support\GradeSnapshotInterface;
use Drupal\simple_school_reports_grade_support\Service\GradableCourseServiceInterface;
use Drupal\simple_school_reports_grade_support\Service\GradeServiceInterface;
use Drupal\simple_school_reports_grade_support\Service\GradeSnapshotServiceInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\simple_school_reports_grade_support\Utilities\GradeReference;

/**
 * Provides a 'StudentGradeStatisticsBlockGy' block.
 *
 * @Block(
 *  id = "student_grade_statistics_block_gy",
 *  admin_label = @Translation("Student grade statistics GY"),
 * )
 */
class StudentGradeStatisticsBlockGy extends BlockBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected RouteMatchInterface $routeMatch,
    protected SyllabusServiceInterface $syllabusService,
    protected GradableCourseServiceInterface $gradableCourseService,
    protected GradeServiceInterface $gradeService,
    protected GradeSnapshotServiceInterface $gradeSnapshotService,
    protected Connection $connection,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('simple_school_reports_entities.syllabus_service'),
      $container->get('simple_school_reports_grade_support.gradable_course'),
      $container->get('simple_school_reports_grade_support.grade_service'),
      $container->get('simple_school_reports_grade_support.grade_snapshot_service'),
      $container->get('database'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * @return array
   */
  protected function getSyllabusIds(): array {
    $school_types = SchoolTypeHelper::getSchoolTypeVersions('GY');
    return $this->gradableCourseService->getGradableSyllabusIds($school_types);
  }

  protected function getSnapshotLimit(): int {
    return 500;
  }

  /**
   * @param \Drupal\user\UserInterface $user
   *
   * @return \Drupal\simple_school_reports_grade_support\GradeSnapshotInterface[]
   */
  protected function getSnapshots(UserInterface $user): array {
    $syllabus_ids = $this->getSyllabusIds();
    $syllabus_ids = $this->syllabusService->getSyllabusAssociations($syllabus_ids);
    if (empty($syllabus_ids)) {
      return [];
    }

    $school_type_versions = SchoolTypeHelper::getSchoolTypeVersions('GY');

    $query = $this->connection->select('ssr_grade_snapshot__grades', 'gsg');
    $query->innerJoin('ssr_grade_revision', 'gr', 'gsg.grades_target_revision_id = gr.revision_id');
    $query->innerJoin('ssr_grade', 'g', 'gr.revision_id = g.revision_id');
    $query->innerJoin('ssr_grade_snapshot', 'gs', 'gsg.entity_id = gs.id');
    $query->innerJoin('ssr_grade_snapshot_period_field_data', 'p', 'gs.grade_snapshot_period = p.id');
    $query->innerJoin('ssr_grade_snapshot_period__school_type_versioned', 'stv', 'p.id = stv.entity_id');
    $query->condition('g.syllabus', $syllabus_ids, 'IN');
    $query->condition('p.status', 1);
    $query->condition('stv.school_type_versioned_value', $school_type_versions, 'IN');
    $query->condition('gs.student', $user->id());
    $query->orderBy('p.period_index', 'DESC');
    $query->fields('gs', ['id']);
    $results = $query->execute();

    $snapshot_ids = [];
    foreach ($results as $result) {
      if (isset($snapshot_ids[$result->id])) {
        continue;
      }
      $snapshot_ids[$result->id] = $result->id;
      if (count($snapshot_ids) >= $this->getSnapshotLimit()) {
        break;
      }
    }

    if (empty($snapshot_ids)) {
      return [];
    }

    return $this->entityTypeManager->getStorage('ssr_grade_snapshot')->loadMultiple($snapshot_ids);
  }

  protected function buildTable(GradeSnapshotInterface $snapshot): array {
    $student = $this->routeMatch->getParameter('user');

    $build = [];

    /** @var \Drupal\simple_school_reports_grade_support\GradeSnapshotPeriodInterface|null $snapshot_period */
    $snapshot_period = $snapshot?->get('grade_snapshot_period')->entity;

    $build['label'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => $snapshot_period?->label() ?? $this->t('Unknown term'),
    ];

    $build['table'] = [
      '#type' => 'table',
      '#header' => [
        'subject' => $this->t('Subject'),
        'course_code' => $this->t('Course code'),
        'points' => $this->t('Points'),
        'date' => $this->t('Date'),
        'grade' => $this->t('Grade'),
      ],
      '#rows' => [],
      '#empty' => $this->t('There are no grades to be shown yet.'),
      '#attributes' => [
        'class' => ['student-grade-statistics-table'],
      ],
    ];
    $build['#attached']['library'][] = 'simple_school_reports_grade_support/student_grade_statistics';

    $syllabus_ids = $this->getSyllabusIds();
    $syllabus_ids = $this->syllabusService->getSyllabusAssociations($syllabus_ids);

    /** @var \Drupal\simple_school_reports_grade_support\Utilities\GradeReference[] $grade_references */
    $grade_references = [];
    foreach ($snapshot->get('grades')->getValue() as $target) {
      $grade_references[] = new GradeReference(
        $target['target_id'],
        $target['target_revision_id'],
      );
    }

    $data = $this->gradeService->parseGradesFromReferences($grade_references)[$student->id()] ?? [];

    $has_points = FALSE;

    /** @var \Drupal\simple_school_reports_grade_support\Utilities\GradeInfo $grade_info */
    foreach ($data as $syllabus_id => $grade_info) {
      if (!in_array($syllabus_id, $syllabus_ids)) {
        continue;
      }
      $date = $grade_info->date?->format('Y-m-d') ?? '';
      $grade = $this->gradeService->getGradeLabel($grade_info);
      if (!$grade) {
        continue;
      }

      $syllabus_label = $this->gradeService->getSyllabusLabel($grade_info) ?? $this->t('Unknown course');
      $course_code = $this->gradeService->getCourseCode($grade_info) ?? '';

      $points = $grade_info->points ?? '';
      if (!empty($points)) {
        $has_points = TRUE;
      }

      $row = [];
      $row['data'] = [
        $syllabus_label,
        $course_code,
        $points,
        $date,
        $grade,
      ];
      $row['class'] = [
        'student-grade-statistics-table--row',
      ];

      if ($grade_info->replaced) {
        $row['class'][] = 'student-grade-statistics-table--row--replaced';
      }

      $build['table']['#rows'][] = $row;
    }

    if (!$has_points) {
      $build['table']['#rows']['points'] = '';
    }


    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $cache = new CacheableMetadata();
    $cache->addCacheContexts(['route']);

    $user = $this->routeMatch->getParameter('user');
    if (!$user instanceof UserInterface) {
      return $this->returnEmpty($cache);
    }
    $cache->addCacheTags(['ssr_grade_snapshot_list:student:' . $user->id()]);


    $snapshots = $this->getSnapshots($user);
    if (empty($snapshots)) {
      return $this->returnEmpty($cache);
    }

    foreach (array_values($snapshots) as $key => $snapshot) {
      $build[$snapshot->id() . '_table'] = $this->buildTable($snapshot);

      if ($key < count($snapshots) - 1) {
        $build[$snapshot->id() . '_divider'] = [
          '#markup' => '<hr>',
        ];
      }
    }


    $cache->applyTo($build);
    return $build;
  }

  protected function expectingGrades(UserInterface $user): bool {
    $school_grades = SchoolGradeHelper::getSchoolGradeValues(['GY']);
    $student_school_grade = $user->get('field_grade')->value;
    return in_array($student_school_grade, $school_grades);
  }

  protected function returnEmpty(CacheableMetadata $cache) {
    $user = $this->routeMatch->getParameter('user');

    $build = [];

    if ($user && $this->expectingGrades($user)) {
      $build['message'] = [
        '#type' => 'html_tag',
        '#tag' => 'em',
        '#value' => $this->t('There are no grades to be shown yet.'),
      ];
    }

    $cache->applyTo($build);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();
    $contexts[] = 'route';
    return $contexts;
  }

  public function getCacheTags() {
    $tags = parent::getCacheTags();
    $tags[] = 'ssr_grade_snapshot_period_list';

    /** @var \Drupal\user\UserInterface $student */
    $student = $this->routeMatch->getParameter('user');
    if ($student instanceof UserInterface) {
      $tags[] = 'ssr_grade_snapshot_list:student:' . $student->id();
    }

    return $tags;
  }

  /**
   * {@inheritdoc}
   */
  public function blockAccess(AccountInterface $account, ?UserInterface $user = NULL) {
    if (!$user) {
      return AccessResult::forbidden();
    }
    if (!$user->hasRole('student')) {
      return AccessResult::forbidden()->addCacheableDependency($user);
    }

    return AccessResult::allowedIf($user->access('update', $account))
      ->cachePerUser()
      ->addCacheContexts(['route'])
      ->addCacheableDependency($user);
  }

}
