<?php

namespace Drupal\simple_school_reports_reviews\Service;

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
class WrittenReviewsRoundProgressService implements WrittenReviewsRoundProgressServiceInterface {

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
  public function getProgress(string $written_reviews_round_nid) : string {
    $cid = 'written_reviews_round_progress:' . $written_reviews_round_nid;
    $cache = $this->cache->get($cid);
    if ($cache && is_string($cache->data)) {
      return $cache->data;
    }

    $calculated_value = 0;

    $written_reviews_round_node = $this->entityTypeManager->getStorage('node')->load($written_reviews_round_nid);
    if ($written_reviews_round_node && $written_reviews_round_node->bundle() === 'written_reviews_round') {
      $review_subject_nids = array_column($written_reviews_round_node->get('field_written_reviews_subject')->getValue(), 'target_id');

      if (!empty($review_subject_nids)) {
        $query = $this->connection->select('node__field_state', 's');
        $query->fields('s', ['entity_id']);
        $query->condition('s.field_state_value', 'done');
        $query->condition('s.entity_id', $review_subject_nids, 'IN');
        $count_done = $query->countQuery()->execute()->fetchField();

        $total = count($review_subject_nids);

        if ($count_done) {
          $calculated_value = round(($count_done / $total) * 100, 1);
        }
      }
    }

    $this->cache->set($cid, (string) $calculated_value, Cache::PERMANENT, ['node:' . $written_reviews_round_nid]);
    return $calculated_value;
  }

  /**
   * @inheritDoc
   */
  public function getWrittenReviewsNid(string $written_reviews_round_nid, string $student_uid) : ?string {
    return current($this->entityTypeManager
      ->getStorage('node')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'written_reviews')
      ->condition('field_student', $student_uid)
      ->condition('field_written_reviews_round', $written_reviews_round_nid)
      ->execute()
    );
  }
}
