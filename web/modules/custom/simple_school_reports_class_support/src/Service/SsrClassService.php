<?php

namespace Drupal\simple_school_reports_class_support\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueFactoryInterface;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Support methods for class stuff.
 */
class SsrClassService implements SsrClassServiceInterface {
  use StringTranslationTrait;

  private array $lookup = [];

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Connection $database,
    protected StateInterface $state,
    protected QueueFactory $queueFactory,
    protected AccountInterface $currentUser,
    protected MessengerInterface $messenger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getStudentIdsByClassId(string $class_id): array {
    $cid = 'students_class_' . $class_id;

    if (is_array($this->lookup[$cid] ?? NULL)) {
      return $this->lookup[$cid];
    }
    $uids = $this->entityTypeManager->getStorage('user')->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 1)
      ->condition('roles', 'student')
      ->condition('field_class', $class_id)
      ->sort('field_grade')
      ->sort('field_first_name')
      ->sort('field_last_name')
      ->execute();

    $this->lookup[$cid] = array_values($uids);
    return $uids;
  }

  /**
   * {@inheritdoc}
   */
  public function getMentorIdsByClassId(string $class_id): array {
    $cid = 'mentors_class_' . $class_id;
    if (is_array($this->lookup[$cid] ?? NULL)) {
      return $this->lookup[$cid];
    }

    $student_uids = $this->getStudentIdsByClassId($class_id);
    if (empty($student_uids)) {
      return [];
    }

    $uids = [];
    foreach ($student_uids as $uid) {
      $student = $this->entityTypeManager->getStorage('user')->load($uid);
      $mentor_uids = array_column($student?->get('field_mentor')->getValue() ?? [], 'target_id');
      foreach ($mentor_uids as $mentor_uid) {
        $uids[$mentor_uid] = $mentor_uid;
      }
    }

    $this->lookup[$cid] = array_values($uids);
    return $uids;
  }

  /**
   * {@inheritdoc}
   */
  public function getClassIdsByGrade(string $grade): array {
    $cid = 'classes_grade_' . $grade;
    if (is_array($this->lookup[$cid] ?? NULL)) {
      return $this->lookup[$cid];
    }

    $class_ids = [];

    $uids = $this->entityTypeManager->getStorage('user')->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 1)
      ->condition('roles', 'student')
      ->condition('field_grade', $grade)
      ->execute();

    if (!empty($uids)) {
      $query = $this->database->select('user__field_class', 'uc');
      $query->innerJoin('ssr_school_class_field_data', 'c', 'uc.field_class_target_id = c.id');
      $query->condition('uc.entity_id', array_values($uids), 'IN');
      $query->condition('c.status', 1);
      $query->fields('c', ['id']);
      $results = $query->execute();

      foreach ($results as $result) {
        $class_ids[$result->id] = $result->id;
      }
    }

    $this->lookup[$cid] = array_values($class_ids);
    return $class_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function getStudentClassId(string $student_id): ?string {
    $cid = 'classes_map';
    if (is_array($this->lookup[$cid] ?? NULL)) {
      $map = $this->lookup[$cid];
    }
    else {
      $map = [];

      $results = $this->database->select('user__field_class', 'uc')
        ->fields('uc', ['entity_id', 'field_class_target_id'])
        ->execute();

      foreach ($results as $result) {
        $map[$result->entity_id] = $result->field_class_target_id;
      }

      $this->lookup[$cid] = $map;
    }

    return $map[$student_id] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getSortedClasses(bool $include_inactive = FALSE): array {
    $cid = 'classes_sorted' . ($include_inactive ? '_all' : '_active');
    if (is_array($this->lookup[$cid] ?? NULL)) {
      return $this->lookup[$cid];
    }

    $classes = [];

    $query = $this->entityTypeManager->getStorage('ssr_school_class')->getQuery()
      ->accessCheck(FALSE)
      ->range(0, 300);

    if (!$include_inactive) {
      $query->condition('status', 1);
    }

    $class_ids = $query->execute();

    if (!empty($class_ids)) {
      foreach ($this->entityTypeManager->getStorage('ssr_school_class')->loadMultiple($class_ids) as $class) {
        $classes[$class->id()] = $class;
      }
    }

    $class_weights = [];

    $results = $this->database->select('draggableviews_structure', 'd')
      ->condition('d.view_name', 'classes_list')
      ->condition('d.view_display', 'list')
      ->fields('d', ['entity_id'])
      ->orderBy('d.weight', 'ASC')
      ->execute();

    $weight = 0;
    foreach ($results as $result) {
      $class_weights[$result->entity_id] = $weight;
      $weight++;
    }

    // Make unsorted classes appear at the beginning.
    foreach ($class_ids as $class_id) {
      $class_weights[$class_id] = $class_weights[$class_id] ?? $class_id * -1;
    }

    uasort($classes, function ($a, $b) use ($class_weights) {
      return $class_weights[$a->id()] <=> $class_weights[$b->id()];
    });

    $this->lookup[$cid] = $classes;
    return $classes;
  }

  /**
   * {@inheritdoc}
   */
  public function getClassIds(bool $include_inactive = FALSE): array {
    $query = $this->entityTypeManager->getStorage('ssr_school_class')->getQuery()
      ->accessCheck(FALSE);
    if (!$include_inactive) {
      $query->condition('status', 1);
    }
    return array_values($query->execute());
  }

  public function getSortedClassOptions(bool $include_inactive = FALSE, $include_all_option = TRUE, bool $include_none_option = FALSE): array {
    $class_options = [];
    if ($include_all_option) {
      $class_options[''] = $this->t('All');
    }
    if ($include_none_option) {
      $class_options['none'] = $this->t('No class');
    }
    foreach ($this->getSortedClasses($include_inactive) as $class) {
      $class_options[$class->id()] = $class->label();
    }
    return $class_options;
  }

  public function queueClassSync(string $class_id) {
    $queue = $this->queueFactory->get('ssr_sync_class');
    $queue->createQueue();
    $queue->createItem(['class_id' => $class_id]);

    $this->state->set('sync_class_' . $class_id, TRUE);
    if ($this->currentUser->isAuthenticated()) {
      $this->messenger->addStatus($this->t('Note. It may take several minutes before the student lists are updated in places where class is used.'));
    }
  }

  public function syncClass(string $class_id) {
    // Fetch all courses that uses the class and trigger a save, pre save will
    // handle the student sync.
    $courses = $this->entityTypeManager->getStorage('node')
      ->loadByProperties([
        'type' => 'course',
        'field_class' => $class_id,
      ]);
    foreach ($courses as $course) {
      $course->save();
    }

    // Fetch all assessment_groups that uses the class and trigger a save, pre save will
    // handle the student sync.
    $assessment_groups = $this->entityTypeManager->getStorage('ssr_assessment_group')
      ->loadByProperties([
        'school_class' => $class_id,
      ]);
    foreach ($assessment_groups as $assessment_group) {
      $assessment_group->save();
    }
  }

}
