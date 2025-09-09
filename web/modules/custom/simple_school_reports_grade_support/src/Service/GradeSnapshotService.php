<?php

namespace Drupal\simple_school_reports_grade_support\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\simple_school_reports_core\Service\TermServiceInterface;

/**
 * Provides a service for managing grade snapshots.
 */
class GradeSnapshotService implements GradeSnapshotServiceInterface {

  protected array $lookup = [];

  public function __construct(
    protected Connection $connection,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected TermServiceInterface $termService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getSnapshotPeriodId(?\DateTime $date = NULL): int|string {
    if (!$date) {
      $date = new \DateTime('now');
    }
    $term_index = $this->termService->getDefaultTermIndex($date);
    $cid = 'snapshot_id:' . $term_index;
    if (array_key_exists($cid, $this->lookup)) {
      return $this->lookup[$cid];
    }

    $snapshot_period_storage = $this->entityTypeManager->getStorage('ssr_grade_snapshot_period');

    $id = current($snapshot_period_storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('period_index', $term_index)
      ->execute());

    if (!$id) {
      $parsed_term = $this->termService->parseDefaultTermIndex($term_index);
      $label = $parsed_term['semester_name_short'];

      $snapshot_period = $snapshot_period_storage->create([
        'label' => $label,
        'period_index' => $term_index,
        'status' => 0,
        'langcode' => 'sv',
      ]);
      $snapshot_period->save();
      $id = $snapshot_period->id();
    }

    $this->lookup[$cid] = $id;
    return $id;
  }

}
