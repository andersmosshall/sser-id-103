<?php

namespace Drupal\simple_school_reports_grade_support\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\node\NodeInterface;
use Drupal\simple_school_reports_core\Controller\SsrCachedPageControllerBase;
use Drupal\simple_school_reports_grade_support\Service\GradableCourseServiceInterface;
use Drupal\simple_school_reports_grade_support\Service\GradeServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for view course grades page.
 */
abstract class ViewCourseGradesControllerBase extends SsrCachedPageControllerBase {

  protected GradableCourseServiceInterface $gradableCourseService;
  protected GradeServiceInterface $gradeService;

  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->gradableCourseService = $container->get('simple_school_reports_grade_support.gradable_course');
    $instance->gradeService = $container->get('simple_school_reports_grade_support.grade_service');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle(): string|TranslatableMarkup {
    $course = $this->routeMatch->getParameter('node');
    if ($course instanceof NodeInterface) {
      return $course->label() . ' - ' . $this->t('Grades');
    }
    return $this->t('Grades');
  }

  /**
   * {@inheritdoc}
   */
  public function buildPageContent(?NodeInterface $node = NULL): array {
    if (!$node || $node->bundle() !== 'course') {
      throw new NotFoundHttpException();
    }
    $course = $node;

    /** @var \Drupal\user\UserInterface[] $students */
    $students = $course->get('field_student')->referencedEntities();

    $student_ids = [];
    foreach ($students as $student) {
      $student_ids[] = $student->id();
    }

    /** @var \Drupal\simple_school_reports_entities\SyllabusInterface $syllabus */
    $syllabus = $course->get('field_syllabus')->entity;

    /** @var \Drupal\simple_school_reports_entities\SyllabusInterface[] $group_for */
    $group_for = $syllabus->get('group_for')->referencedEntities();

    $syllabuses = array_merge([$syllabus], $group_for);

    $syllabus_ids = [];
    foreach ($syllabuses as $syllabus) {
      $syllabus_ids[] = $syllabus->id();
    }

    $grades = $this->gradeService->parseGradesFromFilter($student_ids, $syllabus_ids);

    $build = [];
    $cache = new CacheableMetadata();

    $build['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Student'),
        $this->t('Subject'),
        $this->t('Course code'),
        $this->t('Date'),
        $this->t('Grade'),
      ],
      '#rows' => [],
    ];

    foreach ($students as $student) {
      foreach ($syllabuses as $syllabus) {
        $date = '';
        $grade = $this->t('Not graded');

        if (!empty($grades[$student->id()][$syllabus->id()])) {
          /** @var \Drupal\simple_school_reports_grade_support\Utilities\GradeInfo $grade_info */
          $grade_info = $grades[$student->id()][$syllabus->id()];
          if (!$grade_info->removed) {
            $date = $grade_info->date?->format('Y-m-d');
            $grade = $this->gradeService->getGradeLabel($grade_info->gradeTid) ?? $this->t('Unknown');
          }
        }

        $build['table']['#rows'][] = [
          $student->label(),
          $syllabus->label(),
          $syllabus->get('course_code')->value ?? '',
          $date,
          $grade,
        ];
      }
    }

    $cache->applyTo($build);
    return $build;
  }

  public function getCacheableMetadata(): CacheableMetadata {
    $cache = parent::getCacheableMetadata();
    $cache->addCacheTags(['ssr_grade_list']);
    return $cache;
  }

  /**
   * {@inheritdoc}
   */
  public function access(?AccountInterface $account = NULL, NodeInterface $node = NULL): AccessResultInterface {
    if (!$account) {
      $account = $this->currentUser();
    }
    $course = $node;
    if (!$course || $course->bundle() !== 'course') {
      return AccessResult::forbidden()->addCacheContexts(['route']);
    }
    $base_access = parent::access($account, $node);

    return $course->access('view_course_grades', $account, TRUE)->andIf($base_access);
  }

}
