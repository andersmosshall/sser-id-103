<?php

namespace Drupal\simple_school_reports_grade_registration\Service;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\simple_school_reports_core\AbsenceDayHandler;

/**
 * Class AbsenceStatisticsService
 *
 * @package Drupal\simple_school_reports_grade_registration\Service
 */
class GradeRoundProgressService implements GradeRoundProgressServiceInterface {

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
  public function getProgress(string $grade_round_nid) : string {
    $cid = 'grade_round_progress:' . $grade_round_nid;
    $cache = $this->cache->get($cid);
    if ($cache && is_string($cache->data)) {
      return $cache->data;
    }

    $calculated_value = 0;

    $grade_round_node = $this->entityTypeManager->getStorage('node')->load($grade_round_nid);
    if ($grade_round_node && $grade_round_node->bundle() === 'grade_round') {
      $student_groups_nids = array_column($grade_round_node->get('field_student_groups')->getValue(), 'target_id');

      if (!empty($student_groups_nids)) {
        $grade_subject_nids = [];
        $query = $this->connection->select('node__field_grade_subject', 's');
        $query->condition('s.entity_id', $student_groups_nids, 'IN');
        $query->fields('s', ['field_grade_subject_target_id']);
        $results = $query->execute();

        foreach ($results as $result) {
          if ($result->field_grade_subject_target_id) {
            $grade_subject_nids[] = $result->field_grade_subject_target_id;
          }
        }

        if (!empty($grade_subject_nids)) {
          $school_subject_list = $this->entityTypeManager->getStorage('node')->getQuery()
            ->condition('type', 'grade_subject')
            ->exists('field_teacher')
            ->exists('field_school_subject')
            ->condition('nid', $grade_subject_nids, 'IN')
            ->accessCheck(FALSE)
            ->execute();

          $total = count($school_subject_list);

          if ($total) {
            $query = $this->connection->select('node__field_state', 's');
            $query->fields('s', ['entity_id']);
            $query->condition('s.field_state_value', 'done');
            $query->condition('s.entity_id', $school_subject_list, 'IN');
            $count_done = $query->countQuery()->execute()->fetchField();

            if ($count_done) {
              $calculated_value = round(($count_done / $total) * 100, 1);
            }
          }
          else {
            $calculated_value = 100;
          }
        }
      }
    }

    $this->cache->set($cid, (string) $calculated_value, Cache::PERMANENT, ['node:' . $grade_round_nid]);
    return $calculated_value;
  }

}
