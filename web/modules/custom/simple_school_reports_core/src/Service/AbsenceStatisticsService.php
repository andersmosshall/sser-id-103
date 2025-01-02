<?php

namespace Drupal\simple_school_reports_core\Service;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\simple_school_reports_core\AbsenceDayHandler;

/**
 * Class AbsenceStatisticsService
 *
 * @package Drupal\simple_school_reports_core\Service
 */
class AbsenceStatisticsService implements AbsenceStatisticsServiceInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * A cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;


  /**
   * TermService constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(
    Connection $connection,
    EntityTypeManagerInterface $entity_type_manager,
    CacheBackendInterface $cache
  ) {
    $this->connection = $connection;
    $this->entityTypeManager = $entity_type_manager;
    $this->cache = $cache;
  }

  /**
   * @inheritDoc
   */
  public function getAllInvalidAbsenceData(int $from, int $to) : array {
    $cid = 'all_invalid_absence_data:' . $from . '-' . $to;
    $cache = $this->cache->get($cid);
    if ($cache && is_array($cache->data)) {
      return $cache->data;
    }

    $calculated_value = [
      0 => [0]
    ];
    $attendance_report_nids = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'course_attendance_report')
      ->condition('field_class_start', $to + 1, '<')
      ->condition('field_class_end', $from - 1, '>')
      ->accessCheck(FALSE)
      ->execute();

    if (!empty($attendance_report_nids)) {
      $absence_per_uid = [];
      $query = $this->connection->select('paragraph__field_invalid_absence', 'ia');
      $query->innerJoin('paragraph__field_student', 's', 's.entity_id = ia.entity_id');
      $query->innerJoin('paragraphs_item_field_data', 'd', 'd.id = ia.entity_id');
      $query->condition('ia.bundle', 'student_course_attendance')
        ->condition('ia.field_invalid_absence_value', 0, '<>')
        ->condition('d.parent_id', $attendance_report_nids, 'IN')
        ->fields('ia',['field_invalid_absence_value'])
        ->fields('s',['field_student_target_id']);
      $results = $query->execute();

      foreach ($results as $result) {
        if (!isset($absence_per_uid[$result->field_student_target_id])) {
          $absence_per_uid[$result->field_student_target_id] = 0;
        }
        $absence_per_uid[$result->field_student_target_id] += (int) $result->field_invalid_absence_value;
      }

      foreach ($absence_per_uid as $uid => $value) {
        if ($value !== 0) {
          $calculated_value[$value][] = $uid;
        }
      }
    }
    $this->cache->set($cid, $calculated_value, Cache::PERMANENT, ['node_list:course_attendance_report']);
    return $calculated_value;
  }

  public function getAllAbsenceDayData(int $from, int $to) : array {
    $cid = 'all_absence_day_data:' . $from . '-' . $to;
    $cache = $this->cache->get($cid);
    if ($cache && is_array($cache->data)) {
      return $cache->data;
    }

    $calculated_value = [
      0 => [0]
    ];

    $absence_nids = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'day_absence')
      ->condition('field_absence_from', $to - 1, '<')
      ->condition('field_absence_to', $from + 1, '>')
      ->accessCheck(FALSE)
      ->execute();

    if (!empty($absence_nids)) {
      $absence_day_per_uid = [];
      $query = $this->connection->select('node__field_student', 's');
      $query->innerJoin('node__field_absence_from', 'af', 'af.entity_id = s.entity_id');
      $query->innerJoin('node__field_absence_to', 'at', 'at.entity_id = s.entity_id');
      $query->condition('s.entity_id', $absence_nids, 'IN')
        ->fields('s',['field_student_target_id'])
        ->fields('af',['field_absence_from_value'])
        ->fields('at',['field_absence_to_value']);
      $results = $query->execute();

      foreach ($results as $result) {
        $value = $result->field_absence_to_value - $result->field_absence_from_value;
        if ($value > 0) {
          if (!isset($absence_day_per_uid[$result->field_student_target_id])) {
            $absence_day_per_uid[$result->field_student_target_id] = 0;
          }
          $absence_day_per_uid[$result->field_student_target_id] += $value;
        }
      }

      foreach ($absence_day_per_uid as $uid => $value) {
        $value = (string) round($value / 86400, 2);
        if ($value > 0) {
          $calculated_value[$value][] = $uid;
        }
      }
    }

    $this->cache->set($cid, $calculated_value, Cache::PERMANENT, ['node_list:day_absence',]);
    return $calculated_value;
  }

  public function getUserDayAbsenceItems(string $date_string, int $uid): array {
    $cid = 'user_day_absence_items:' . $date_string;
    $cache = $this->cache->get($cid);
    if ($cache && is_array($cache->data)) {
      $absence_list = $cache->data;
    }
    else {
      $absence_list = [];

      $from = new \DateTime($date_string . ' 00:00:00');
      $to = clone $from;
      $to->setTime(23, 59, 59);

      $absence_nids = $this->entityTypeManager->getStorage('node')->getQuery()
        ->condition('type', 'day_absence')
        ->condition('field_absence_from', $to->getTimestamp() - 1, '<')
        ->condition('field_absence_to', $from->getTimestamp() + 1, '>')
        ->accessCheck(FALSE)
        ->execute();

      if (!empty($absence_nids)) {
        $query = $this->connection->select('node__field_student', 's');
        $query->innerJoin('node__field_absence_from', 'af', 'af.entity_id = s.entity_id');
        $query->innerJoin('node__field_absence_to', 'at', 'at.entity_id = s.entity_id');
        $query->innerJoin('node__field_absence_type', 't', 't.entity_id = s.entity_id');
        $query->condition('s.entity_id', $absence_nids, 'IN')
          ->fields('s',['field_student_target_id', 'entity_id'])
          ->fields('af',['field_absence_from_value'])
          ->fields('at',['field_absence_to_value'])
          ->fields('t', ['field_absence_type_value']);
        $results = $query->execute();

        foreach ($results as $result) {
          $list_uid = $result->field_student_target_id;
          $list_nid = $result->entity_id;
          $absence_list[$list_uid][$list_nid] = [
            'nid' => $list_nid,
            'from' => $result->field_absence_from_value,
            'to' => $result->field_absence_to_value,
            'type' => $result->field_absence_type_value,
          ];
        }
      }

      $cache_tags = ['node_list:day_absence:' . $date_string];
      $this->cache->set($cid, $absence_list, Cache::PERMANENT, $cache_tags);
    }

    return $absence_list[$uid] ?? [];

  }


}
