<?php

namespace Drupal\simple_school_reports_child_care_support\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\simple_school_reports_child_care_support\ChildCarePlacementInterface;

/**
 * Class ChildCareService
 */
class ChildCareService implements ChildCareServiceInterface {

  protected array $lookup = [];

  public function __construct(
    protected Connection $connection,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected CacheBackendInterface $cache,
    protected TimeInterface $time,
    protected StateInterface $state,
  ) {}

  protected function getPlacements(\DateTimeInterface $date = new \DateTime()): array {
    $cid = 'placements:' . $date->format('Y-m-d');
    if (array_key_exists($cid, $this->lookup)) {
      return $this->lookup[$cid];
    }

    $timestamp = $date->getTimestamp();

    $query = $this->connection->select('ssr_child_care_placement', 'p');
    $query->condition('p.from', $timestamp, '<=');
    $or_condition = $query->orConditionGroup();
    $or_condition->condition('p.to', $timestamp, '>=');
    $or_condition->isNull('p.to');
    $query->condition($or_condition);
    $query->orderBy('p.created', 'ASC');
    $query->fields('p', ['child_care', 'student', 'id']);
    $results = $query->execute();

    $placements = [];
    foreach ($results as $result) {
      $placements[$result->child_care][$result->student] = $result->id;
    }

    $this->lookup[$cid] = $placements;
    return $placements;
  }

  /**
   * {@inheritdoc}
   */
  public function getChildCarePlacementIds(string|int $child_care_id, \DateTimeInterface $date = new \DateTime()): array {
    $placements = $this->getPlacements($date);
    return $placements[$child_care_id] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getChildCareStudentIds(string|int $child_care_id, \DateTimeInterface $date = new \DateTime()): array {
    return array_keys($this->getChildCarePlacementIds($child_care_id, $date));
  }

  /**
   * {@inheritdoc}
   */
  public function getChildCareStudentIdsMultiple(array $child_care_ids, \DateTimeInterface $date = new \DateTime()): array {
    $ids = [];
    foreach ($child_care_ids as $child_care_id) {
      $ids = array_merge($ids, $this->getChildCareStudentIds($child_care_id, $date));
    }
    return array_unique($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function getChildCareGroups(string|int $student_id, \DateTimeInterface $date = new \DateTime()): array {
    $cid = 'student_groups' . ':' . $date->format('Y-m-d');
    if (array_key_exists($cid, $this->lookup)) {
      return $this->lookup[$cid][$student_id] ?? [];
    }

    $data = [];

    $timestamp = $date->getTimestamp();

    $query = $this->connection->select('ssr_child_care_placement', 'p');
    $query->innerJoin('ssr_child_care', 'c', 'c.id = p.child_care');
    $query->condition('p.from', $timestamp, '<=');
    $or_condition = $query->orConditionGroup();
    $or_condition->condition('p.to', $timestamp, '>=');
    $or_condition->isNull('p.to');
    $query->condition($or_condition);
    $query->orderBy('p.created', 'ASC');
    $query->fields('p', ['student', 'child_care']);
    $results = $query->execute();

    foreach ($results as $result) {
      $student = $result->student;
      $child_care = $result->child_care;

      if (!$student || !$child_care) {
        continue;
      }
      if (!isset($data[$student])) {
        $data[$student] = [];
      }
      $data[$student][] = $child_care;
    }

    $this->lookup[$cid] = $data;
    return $this->lookup[$cid][$student_id] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function addChildCarePlacement(int|string $student_id, int|string $child_care_id) {
    /** @var \Drupal\user\UserInterface|null $student */
    $student = $this->entityTypeManager->getStorage('user')->load($student_id);
    if (!$student) {
      return;
    }
    $from = new \DateTime();
    $from->setTime(0,0, 0);

    $placement = $this->entityTypeManager->getStorage('ssr_child_care_placement')->create([
      'label' => $student->getDisplayName(),
      'student' => ['target_id' => $student_id],
      'child_care' => ['target_id' => $child_care_id],
      'from' => $from->getTimestamp(),
      'to' => NULL,
      'langcode' => 'sv',
    ]);
    $placement->save();
  }

  /**
   * {@inheritdoc}
   */
  public function endChildCarePlacement(ChildCarePlacementInterface $placement) {
    $placement_from = $placement->get('from')->value;
    $age = $this->time->getRequestTime() - $placement_from;

    // Delete if placement is less than 24 hours.
    if ($age < 24 * 60 * 60) {
      $placement->delete();
      return;
    }

    // Set to end date.
    $to = new \DateTime();
    $to->setTime(0,0, 0);
    $to->setTimestamp($to->getTimestamp() - 1);

    $placement->set('to', $to->getTimestamp())->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings(): array {
    $cid = 'settings';
    if (array_key_exists($cid, $this->lookup)) {
      return $this->lookup[$cid];
    }

    $state = $this->state->get('simple_school_reports_child_care_support.settings', []);

    // Add defaults.
    $state += [
      'future_min_limit' => 3,
      'future_max_limit' => 365,
    ];
    $this->lookup[$cid] = $state;
    return $state;

  }

  /**
   * {@inheritdoc}
   */
  public function setSettings(array $settings) {
    $default = $this->getSettings();
    $settings += $default;

    if (is_numeric($settings['future_min_limit'])) {
      $settings['future_min_limit'] = abs((int) $settings['future_min_limit']);
    }
    else {
      unset($settings['future_min_limit']);
    }

    if (is_numeric($settings['future_max_limit'])) {
      $settings['future_max_limit'] = abs((int) $settings['future_max_limit']);
    }
    else {
      unset($settings['future_max_limit']);
    }


    $this->state->set('simple_school_reports_child_care_support.settings', $settings);
    unset($this->lookup['settings']);
  }

}
