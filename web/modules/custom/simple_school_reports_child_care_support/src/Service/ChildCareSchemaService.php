<?php

namespace Drupal\simple_school_reports_child_care_support\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\State\StateInterface;
use Drupal\simple_school_reports_child_care_support\ChildCarePlacementInterface;

/**
 * Class ChildCareSchemaService
 */
class ChildCareSchemaService implements ChildCareSchemaServiceInterface {

  protected array $lookup = [];

  public function __construct(
    protected Connection $connection,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected CacheBackendInterface $cache,
    protected TimeInterface $time,
    protected StateInterface $state,
  ) {}

  /**
   * @param int|string $child_care_id.
   */
  protected function getChildCareSchemaDetails(int|string $child_care_id): array {
    $cid = 'ccsd:' . $child_care_id;
    if (array_key_exists($cid, $this->lookup)) {
      return $this->lookup[$cid];
    }

    $query = $this->connection->select('ssr_child_care_schema', 'c');
    $query->condition('c.child_care', $child_care_id);
    $query->orderBy('c.from', 'ASC');
    $query->fields('c', ['id', 'from', 'to']);
    $results = $query->execute();

    $schema_details = [];
    $last_to = NULL;

    $i = 0;
    foreach ($results as $result) {
      $from = $result->from;
      $to = $result->to;
      if (!$from) {
        continue;
      }

      // Adjust previous to value to a correct list of schema details.
      if ($i > 0) {
        if (!$last_to || $last_to > $from) {
          $schema_details[$i - 1]['to'] = $from - 1;
        }
      }

      $schema_details[$i] = [
        'id' => $result->id,
        'from' => $from,
        'to' => $to,
      ];
      $last_to = $to;
      $i++;
    }

    $this->lookup[$cid] = array_reverse($schema_details);
    return $this->lookup[$cid];
  }

  /**
   * {@inheritdoc}
   */
  public function getChildCareSchemaIdsInOrder(int|string $child_care_id): array {
    $details = $this->getChildCareSchemaDetails($child_care_id);
    return array_column($details, 'id');
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveChildCareSchema(int|string $child_care_id, \DateTimeInterface $date = new \DateTime()): int|string|null {
    $cid = 'accs:' . $child_care_id . ':' . $date->format('Y-m-d');
    if (array_key_exists($cid, $this->lookup)) {
      return $this->lookup[$cid];
    }

    $details = $this->getChildCareSchemaDetails($child_care_id);
    $ts = $date->getTimestamp();

    // Find the first schema item that is within the date range.
    foreach ($details as $detail) {
      $from = $detail['from'];
      $to = $detail['to'] ?? PHP_INT_MAX;
      if ($from <= $ts && $ts <= $to) {
        return $detail['id'];
      }
    }

    return NULL;
  }

}
