<?php

namespace Drupal\simple_school_reports_core\Service;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\simple_school_reports_core\SchoolSubjectHelper;

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
      ->sort('field_grade')
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
      $course_query->condition('cs.bundle', 'course');
      $course_query->condition('cs.entity_id', $active_course_ids, 'IN');
      $course_query->fields('cs', ['entity_id', 'field_student_target_id']);
      $course_query->orderBy('cs.entity_id');
      $results = $course_query->execute();

      foreach ($results as $result) {
        $course_id = $result->entity_id;
        $student_id = $result->field_student_target_id;

        if (!in_array($student_id, $ordered_student_uids)) {
          continue;
        }

        $map[$course_id]['default'][$student_id] = $student_id;
      }

      $sub_group_max_index = 5;
      for ($group_index = 1; $group_index <= $sub_group_max_index; $group_index++) {

        $group_query = $this->connection->select('ssr_schema_entry__students_' . $group_index, 'ses');
        $group_query->innerJoin('node__field_ssr_schema', 'c', 'c.field_ssr_schema_target_id = ses.entity_id');
        $group_query->innerJoin('ssr_schema_entry', 'se', 'se.id = ses.entity_id');
        $group_query->condition('c.deleted', 0);
        $group_query->orderBy('se.id');
        $results = $group_query
          ->fields('ses', ['students_' . $group_index . '_target_id'])
          ->fields('c', ['entity_id'])
          ->fields('se', ['id', 'deviated', 'relevant_groups'])
          ->execute();

        foreach ($results as $result) {
          $deviated = $result->deviated;
          if (!$deviated) {
            continue;
          }

          $relevant_groups = $result->relevant_groups;
          if ($group_index > $relevant_groups) {
            continue;
          }

          $course_id = $result->entity_id;
          $student_id = $result->{'students_' . $group_index . '_target_id'};

          if (empty($map[$course_id]['default'][$student_id])) {
            continue;
          }

          $sub_group_index = $result->id . ':' . $group_index;
          $map[$course_id][$sub_group_index][$student_id] = $student_id;
        }
      }

      foreach ($map as $course_id => $sub_groups) {
        foreach ($sub_groups as $sub_group => $student_ids) {
          $map[$course_id][$sub_group] = array_values(array_intersect($ordered_student_uids, $student_ids));

          if (empty($map[$course_id][$sub_group])) {
            if ($sub_group === 'default') {
              unset($map[$course_id]);
              break;
            }
            unset($map[$course_id][$sub_group]);
          }
        }
      }
    }

    $tags = [
      'user_list:grade',
      'node_list:course',
      'ssr_schema_entry_list',
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
  public function getActiveCourseIdsWithStudents(): array {
    $map = $this->getStudentIdsInCourseMap();
    return array_keys($map);
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


  protected function warmUpSchemaEntryCache(string|int $sync_course_id, bool $warm_up_all = FALSE): string {
    $cid = 'schema_entry_data';
    if ($warm_up_all) {
      $cid .= ':all';
    }
    else {
      $cid .= ':' . $sync_course_id;
    }

    if (array_key_exists($cid, $this->lookup)) {
      if (empty($this->lookup[$cid][$sync_course_id])) {
        $this->lookup[$cid][$sync_course_id] = [];
      }
      return $cid;
    }


    $student_ids_map = $this->getStudentIdsInCourseMap();

    $relevant_course_ids = array_keys($student_ids_map);
    $course_ids = in_array($sync_course_id, $relevant_course_ids) ? [$sync_course_id] : [];
    if ($warm_up_all) {
      $course_ids = $relevant_course_ids;
    }

    $map = [];
    if (!empty($course_ids)) {
      $se_fields = [
        'id',
        'source',
        'week_day',
        'from',
        'length',
        'deviated',
        'relevant_groups',
      ];

      for ($i = 1; $i <= 5; $i++) {
        $se_fields[] = 'periodicity_' . $i;
        $se_fields[] = 'custom_periodicity_' . $i;
        $se_fields[] = 'custom_periodicity_start_' . $i;
      }

      $group_query = $this->connection->select('ssr_schema_entry', 'se');
      $group_query->innerJoin('node__field_ssr_schema', 'c', 'c.field_ssr_schema_target_id = se.id');
      $group_query->leftJoin('node__field_school_subject', 'sub', 'sub.entity_id = c.entity_id');
      $group_query->condition('c.deleted', 0);
      $group_query->condition('c.entity_id', $course_ids, 'IN');
      $results = $group_query
        ->fields('c', ['entity_id'])
        ->fields('sub', ['field_school_subject_target_id'])
        ->fields('se', $se_fields)
        ->execute();

      foreach ($results as $result) {
        try {
          $course_id = $result->entity_id;

          $schema_entry_data_item = [
            'id' => $result->id,
            'source' => $result->source,
            'from' => $result->from,
            'to' => $result->from + $result->length * 60,
            'week_day' => $result->week_day,
            'periodicity' => 'weekly',
            'sub_group_id' => 'default',
            'subject' => SchoolSubjectHelper::getSubjectShortName($result->field_school_subject_target_id),
            'periodicity_week' => 1,
          ];

          if (!$result->deviated) {
            $map[$course_id][] = $schema_entry_data_item;
            continue;
          }

          $sub_group_max_index = $result->relevant_groups ?? 5;

          for ($i = 1; $i <= $sub_group_max_index; $i++) {
            $sub_group_index = $result->id . ':' . $i;
            // Skip if no students in subgroup.
            if (empty($student_ids_map[$course_id][$sub_group_index])) {
              continue;
            }

            $periodicity = $result->{'periodicity_' . $i};
            $schema_entry_data_item['periodicity'] = $periodicity;
            $schema_entry_data_item['sub_group_id'] = $sub_group_index;

            if ($periodicity === 'odd_weeks' || $periodicity === 'even_weeks') {
              $schema_entry_data_item['periodicity_week'] = 2;
            }

            if ($periodicity === 'custom') {
              $periodicity_week = $result->{'custom_periodicity_' . $i} ?? 2;
              $schema_entry_data_item['periodicity_week'] = $periodicity_week;
              $periodicity_start_date = new \DateTime();
              $periodicity_start_date->setTimestamp($result->{'custom_periodicity_start_' . $i});
              $periodicity_start_week = (int) $periodicity_start_date->format('W');

              if ($periodicity_start_week < 2) {
                $periodicity_start_date->modify('+' . ($periodicity_week * 7) . ' days');
              }
              elseif ($periodicity_start_week > 50) {
                $periodicity_start_date->modify('-' . ($periodicity_week * 7) . ' days');
              }

              $periodicity_start_year = (int) $periodicity_start_date->format('Y');
              $periodicity_start_week = (int) $periodicity_start_date->format('W');
              $schema_entry_data_item['periodicity_start_week'] = $periodicity_start_year * 100 + $periodicity_start_week;
            }
            $map[$course_id][] = $schema_entry_data_item;
          }
        }
        catch (\Exception $e) {
          continue;
        }
      }
    }


    $this->lookup[$cid] = $map;
    if (empty($this->lookup[$cid][$sync_course_id])) {
      $this->lookup[$cid][$sync_course_id] = [];
    }
    return $cid;
  }

  /**
   * {@inheritdoc}
   */
  public function getSchemaEntryData(string|int $course_id, bool $warm_up_cache = FALSE): array {
    $cid = $this->warmUpSchemaEntryCache($course_id, $warm_up_cache);
    return $this->lookup[$cid][$course_id];
  }

  public function getStudentSchemaEntryDataIdentifiers(string|int $student_id): array {
    $cid = 'ssedids';
    $cid_hash = 'ssedids:hash';
    $cid_hash_reverse = 'ssedids:hash:reverse';
    if (isset($this->lookup[$cid])) {
      $identifiers_map = $this->lookup[$cid];
    }
    else {
      $identifiers_map = [];

      $map = $this->getStudentIdsInCourseMap();
      foreach ($map as $course_id => $sub_groups) {
        $has_sub_groups = count($sub_groups) > 1;
        foreach ($sub_groups as $sub_group => $student_ids) {
          if ($sub_group === 'default' && $has_sub_groups) {
            continue;
          }
          foreach ($student_ids as $uid) {
            $identifiers_map[$uid][] = $course_id . ';' . $sub_group;
          }
        }
      }

      foreach ($identifiers_map as $uid => $identifiers) {
        $hash = sha1(Json::encode($identifiers));
        $this->lookup[$cid_hash][$uid] = $hash;
        $this->lookup[$cid_hash_reverse][$hash] = $uid;
      }

      $this->lookup[$cid] = $identifiers_map;
    }

    return $identifiers_map[$student_id] ?? [];
  }

  public function getAllSchemaEntryDataIdentifiersHashes(): array {
    $this->getStudentSchemaEntryDataIdentifiers(0);
    return array_keys($this->lookup['ssedids:hash:reverse']);
  }

  public function getStudentSchemaEntryDataIdentifiersHash(string|int $student_id): ?string {
    $this->getStudentSchemaEntryDataIdentifiers($student_id);
    $cid_hash = 'ssedids:hash';
    return $this->lookup[$cid_hash][$student_id] ?? NULL;
  }

  public function getStudentSchemaEntryDataByHash(string $hash): array {
    $cid_hash_reverse = 'ssedids:hash:reverse';
    $uid = $this->lookup[$cid_hash_reverse][$hash] ?? NULL;
    if (!$uid) {
      return [];
    }
    return $this->getStudentSchemaEntryData($uid);
  }

  public function getStudentSchemaEntryData(string|int $student_id): array {
    $identifiers = $this->getStudentSchemaEntryDataIdentifiers($student_id);
    if (empty($identifiers)) {
      return [];
    }
    $data = [];

    $exploded_identifiers = [];
    foreach ($identifiers as $identifier) {
      [$course_id, $sub_group_index] = explode(';', $identifier);
      $exploded_identifiers[$course_id][$sub_group_index] = $sub_group_index;
    }

    foreach ($exploded_identifiers as $course_id => $sub_group_indexes) {
      $course_schema_entry_data = $this->getSchemaEntryData($course_id, TRUE);
      if (!empty($course_schema_entry_data)) {
        foreach ($course_schema_entry_data as $course_schema_entry_data_item) {
          $sub_group_index = $course_schema_entry_data_item['sub_group_id'] ?? 'no-index';
          if (isset($sub_group_indexes[$sub_group_index])) {
            $data[] = $course_schema_entry_data_item;
          }
        }
      }
    }

    return $data;
  }

  public function clearLookup(): void {
    $this->lookup = [];
  }

}
