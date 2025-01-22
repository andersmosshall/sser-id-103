<?php

namespace Drupal\simple_school_reports_core\Service;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 *
 */
class CourseService implements CourseServiceInterface {

  protected array $lookup = [];

  public function __construct(
    protected Connection $connection,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected CacheBackendInterface $cache,
  ) {}

  protected function getStudentIdsInCourseMap(): array {
    $cid = 'students_ids_course_map';
    $cached = $this->cache->get($cid);
    if (!empty($cached)) {
      return $cached->data;
    }

    $map = [];

    $ordered_student_uids = $this->entityTypeManager->getStorage('user')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('roles', 'student')
      ->condition('status', 1)
      ->condition('field_grade', 99, '<>')
      ->sort('field_first_name')
      ->sort('field_last_name')
      ->execute();
    $ordered_student_uids = array_values($ordered_student_uids);

    $active_course_ids = $this->entityTypeManager->getStorage('node')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'course')
      ->condition('status', 1)
      ->execute();

    if (!empty($active_course_ids)) {
      $course_query = $this->connection->select('node__field_student', 'cs');
      $course_query->condition('cs.deleted', 0);
      $course_query->condition('bundle', 'course');
      $course_query->condition('entity_id', $active_course_ids, 'IN');
      $course_query->fields('cs', ['entity_id', 'field_student_target_id']);
      $results = $course_query->execute();

      foreach ($results as $result) {
        $course_id = $result->entity_id;
        $student_id = $result->field_student_target_id;

        if (!in_array($student_id, $ordered_student_uids)) {
          continue;
        }

        $map[$course_id]['default'][] = $student_id;
      }

      $sub_group_max_index = 5;
      for ($group_index = 1; $group_index <= $sub_group_max_index; $group_index++) {
        // Todo complete this.
//      $course_query = $this->connection->select('ssr_schema_entry__students_'. $group_index, 'se');
//      // Innerjoin the course.
//      $course_query->condition('se.deleted', 0);
//      $course_query->fields('se', ['entity_id', 'students_' . $group_index . '_target_id']);
//      $results = $course_query->execute();
//
//      foreach ($results as $result) {
//
//
//        $course_id = $result->entity_id;
//        $student_id = $result->field_student_target_id;
//        $map[$course_id]['default'][] = $student_id;
//      }
      }

      foreach ($map as $course_id => $sub_groups) {
        foreach ($sub_groups as $sub_group => $student_ids) {
          $map[$course_id][$sub_group] = array_values(array_unique($student_ids));
          $map[$course_id][$sub_group] = array_values(array_intersect($ordered_student_uids, $map[$course_id][$sub_group]));

          if ($sub_group === 'default' && empty($map[$course_id][$sub_group])) {
            unset($map[$course_id]);
            break;
          }
        }
      }
    }

    $tags = [
      'user_list:grade',
      'node_list:course',
      'ssr_schema_entry',
    ];
    $this->cache->set($cid, $map, Cache::PERMANENT, $tags);
    return $map;
  }

  /**
   * {@inheritdoc}
   */
  public function getStudentIdsInCourse(int|string $course_id, string $sub_group = 'default'): array {
    $map = $this->getStudentIdsInCourseMap();
    if (empty($map[$course_id][$sub_group])) {
      return [];
    }
    return $map[$course_id][$sub_group];
  }

  /**
   * {@inheritdoc}
   */
  public function getCourseName(int|string $course_id, string $sub_group = 'default'): string {
    $course = $this->entityTypeManager->getStorage('node')->load($course_id);
    if (!$course || $course->bundle() !== 'course') {
      return '';
    }

    $sub_group_name = $this->getSubGroupName($course_id, $sub_group);
    if (!empty($sub_group_name)) {
      return $course->label() . ' (' . $sub_group_name . ')';
    }
    return $course->label();
  }

  /**
   * {@inheritdoc}
   */
  public function getSubGroupName(int|string $course_id, string $sub_group): string {
    if (!str_contains($sub_group, ':')) {
      return '';
    }

    [$ssr_schema_entry_id, $group_index] = explode(':', $sub_group);
    if (!is_numeric($ssr_schema_entry_id) || !is_numeric($group_index)) {
      return '';
    }

    $course = $this->entityTypeManager->getStorage('node')->load($course_id);
    if (!$course || $course->bundle() !== 'course') {
      return '';
    }

    $ssr_schema_entry_ids = array_column($course->get('field_ssr_schema')->getValue(), 'target_id');

    if (!in_array($ssr_schema_entry_id, $ssr_schema_entry_ids)) {
      return '';
    }

    $ssr_schema_entry = $this->entityTypeManager->getStorage('ssr_schema_entry')->load($ssr_schema_entry_id);
    if (!$ssr_schema_entry) {
      return '';
    }

    if ($ssr_schema_entry->hasField('display_name_' . $group_index)) {
      return $ssr_schema_entry->get('display_name_' . $group_index)->value ?? 'Grupp ' . $group_index;
    }

    return '';
  }

}
