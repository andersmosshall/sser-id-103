<?php

namespace Drupal\simple_school_reports_entities\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\simple_school_reports_core\Service\UserMetaDataServiceInterface;
use Drupal\simple_school_reports_entities\SchoolWeekInterface;
use Drupal\simple_school_reports_extension_proxy\LessonHandlingTrait;

/**
 *
 */
class SchoolWeekService implements SchoolWeekServiceInterface {

  use LessonHandlingTrait;

  protected array $lookup = [];

  protected int $displayCount = -1;

  /**
   * @param \Drupal\Core\Database\Connection $connection
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   * @param \Drupal\Core\State\StateInterface $state
   */
  public function __construct(
    protected Connection $connection,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected CacheBackendInterface $cache,
    protected StateInterface $state,
    protected UserMetaDataServiceInterface $userMetaDataService,
    protected ModuleHandlerInterface $moduleHandler,
  ) {}

  /**
   * @return \Drupal\simple_school_reports_entities\SchoolWeekInterface[]
   */
  protected function getSchoolWeekMap(): array {
    $cid = 'school_week_map';
    if (isset($this->lookup[$cid])) {
      return $this->lookup[$cid];
    }

    $map = [];
    $reverse_map = [];
    // Map school weeks to users.
    $results = $this->connection->select('user__field_adapted_studies', 'as')
      ->fields('as', ['entity_id', 'field_adapted_studies_target_id'])
      ->execute();
    foreach ($results as $result) {
      $map['u:' . $result->entity_id] = $result->field_adapted_studies_target_id;
      $reverse_map[$result->field_adapted_studies_target_id] = [
        'type' => 'user',
        'id' => $result->entity_id,
      ];
    }

    // Map school weeks to classes.
    $results = $this->connection->select('ssr_school_class_field_data', 'cd')
      ->fields('cd', ['id', 'school_week'])
      ->execute();
    foreach ($results as $result) {
      if (!$result->school_week) {
        continue;
      }
      $map['c:' . $result->id] = $result->school_week;
      $reverse_map[$result->school_week] = [
        'type' => 'class',
        'id' => $result->id,
      ];
    }

    // Map school weeks to grades.
    $state = $this->state->get('ssr_school_week_per_grade', []);
    foreach ($state as $grade => $school_week_id) {
      $map['g:' . $grade] = $school_week_id;
      $reverse_map[$school_week_id] = [
        'type' => 'grade',
        'id' => $grade,
      ];
    }

    if (empty($map)) {
      return [];
    }

    foreach ($map as $key => $id) {
      $map[$key] = $this->entityTypeManager->getStorage('school_week')->load($id);
      if (!$map[$key]) {
        unset($map[$key]);
      }
    }

    $this->lookup[$cid] = $map;
    $this->lookup[$cid . ':reverse'] = $reverse_map;
    return $map;
  }

  /**
   * {@inheritdoc}
   */
  public function getSchoolWeek(string $uid, ?\DateTime $date = NULL): ?SchoolWeekInterface {
    $parent_school_week = NULL;

    $school_week_map = $this->getSchoolWeekMap();


    $use_classes = $this->moduleHandler->moduleExists('simple_school_reports_class_support');
    if ($use_classes) {
      /** @var \Drupal\simple_school_reports_class_support\Service\SsrClassServiceInterface $class_service */
      $class_service = \Drupal::service('simple_school_reports_class_support.class_service');
      $class_id = $class_service->getStudentClassId($uid);
      if ($class_id && isset($school_week_map['c:' . $class_id])) {
        $parent_school_week = $school_week_map['c:' . $class_id];
      }
    }

    $grade = !$parent_school_week ? $this->userMetaDataService->getUserGrade($uid, $date) : NULL;
    if ($grade !== NULL && isset($school_week_map['g:' . $grade])) {
      $parent_school_week = $school_week_map['g:' . $grade];
    }

    if (isset($school_week_map['u:' . $uid])) {
      $school_week = $school_week_map['u:' . $uid];
      if ($parent_school_week) {
        $school_week->setParentSchoolWeek($parent_school_week);
      }
    }
    else {
      $school_week = $parent_school_week;
    }

    return $school_week;
  }

  public function getSchoolWeekReference(string $school_week_id): array {
    $default = [
      'type' => 'none',
      'id' => 'none',
    ];
    // Warm up cache.
    $this->getSchoolWeekMap();

    $cid = 'school_week_map:reverse';

    return $this->lookup[$cid][$school_week_id] ?? $default;
  }


  protected function calculateDeviationData($src, string $reference, string|null $reference_id = NULL) {
    $data = [];

    $from = $src->from_date;
    $to = $src->to_date;

    if (!$from || !$to) {
      return $data;
    }

    if ($from > $to) {
      $tmp = $from;
      $from = $to;
      $to = $tmp;
    }

    $comment_id = NULL;

    if ($reference === 'specific' && $reference_id) {
      $school_week_reference = $this->getSchoolWeekReference($reference_id);
      if ($school_week_reference['type'] === 'user') {
        $comment_id = self::DEVIATION_COMMENT_ADAPTED_STUDIES;
      }
      if ($school_week_reference['type'] === 'class') {
        $comment_id = self::DEVIATION_COMMENT_SCHOOL_WEEK;
      }
      if ($school_week_reference['type'] === 'grade') {
        $comment_id = self::DEVIATION_COMMENT_SCHOOL_WEEK;
      }
    }

    if ($reference === 'class') {
      $comment_id = self::DEVIATION_COMMENT_SCHOOL_CLASS;
    }

    if ($reference === 'grade') {
      $comment_id = self::DEVIATION_COMMENT_GRADE;
    }

    $day_data = [
      'from' => $src->from,
      'to' => $src->to,
      'length' => $src->length,
      'deviation_tid' => $src->deviation_type,
      'reference' => $reference,
      'reference_id' => $reference_id,
      'comment_id' => $comment_id,
    ];

    $date = (new \DateTime())->setTimestamp($from);
    $date_string = $date->format('Y-m-d');
    $to_date = (new \DateTime())->setTimestamp($to)->format('Y-m-d');

    while ($date_string !== $to_date) {
      $data[$date_string] = $day_data;
      // Add one day.
      $date->modify('+1 day');
      $date_string = $date->format('Y-m-d');
    }

    $data[$to_date] = $day_data;

    return $data;
  }

  protected function warmUpDeviationsCache(): void {
    $cid = 'school_week_deviations';

    // Deviations id map per school week id.
    $cid_dev_id_map = 'dev_id_map';
    $cid_dev_data = 'dev_data';

    if (array_key_exists($cid_dev_id_map, $this->lookup)) {
      return;
    }

    $cache = $this->cache->get($cid);
    if ($cache) {
      $data = $cache->data;

      $this->lookup[$cid_dev_id_map] = $data[$cid_dev_id_map];
      $this->lookup[$cid_dev_data] = $data[$cid_dev_data];
      return;
    }

    // Warm up cache.
    $this->getSchoolWeekMap();

    $dev_id_map = [];
    $dev_data = [];

    // Prio 1 - Specific deviations per school week.
    $specific_deviations_query = $this->connection->select('school_week__deviation', 'sw_d');
    $specific_deviations_query->innerJoin('school_week_deviation', 'swd', 'sw_d.deviation_target_id = swd.id');
    $specific_deviations_query->fields('sw_d', ['entity_id', 'deviation_target_id']);
    $specific_deviations_query->fields('swd', ['from_date', 'to_date', 'deviation_type', 'length', 'from', 'to']);
    $specific_deviations_query->orderBy('swd.from_date', 'DESC');

    foreach ($specific_deviations_query->execute() as $result) {
      $school_week_id = $result->entity_id;
      $dev_id = $result->deviation_target_id;
      $dev_id_map[$school_week_id][] = $dev_id;

      if (!isset($dev_data[$dev_id])) {
        $dev_data[$dev_id] = $this->calculateDeviationData($result, 'specific', $school_week_id);
      }
    }

    // Prio 2 - Deviations from class.
    $class_ids = [];
    $use_classes = $this->moduleHandler->moduleExists('simple_school_reports_class_support');
    if ($use_classes) {
      /** @var \Drupal\simple_school_reports_class_support\Service\SsrClassServiceInterface $class_service */
      $class_service = \Drupal::service('simple_school_reports_class_support.class_service');
      $class_ids = $class_service->getClassIds();
    };

    if (!empty($class_ids)) {
      $grade_deviations_query = $this->connection->select('school_week_deviation__field_classes', 'swd_c');
      $grade_deviations_query->innerJoin('school_week_deviation', 'swd', 'swd_c.entity_id = swd.id');
      $grade_deviations_query->innerJoin('ssr_school_class_field_data', 'c', 'swd_c.field_classes_target_id = c.id');
      $grade_deviations_query->condition('field_classes_target_id', array_keys($class_ids), 'IN');
      $grade_deviations_query->fields('c', ['school_week']);
      $grade_deviations_query->fields('swd', ['id', 'from_date', 'to_date', 'deviation_type', 'length', 'from', 'to']);
      $grade_deviations_query->orderBy('swd.from_date', 'DESC');
      foreach ($grade_deviations_query->execute() as $result) {
        $school_week_id = $result->school_week ?? 'unknown';

        $dev_id = $result->id;
        $dev_id_map[$school_week_id][] = $dev_id;

        if (!isset($dev_data[$dev_id])) {
          $dev_data[$dev_id] = $this->calculateDeviationData($result, 'class');
        }
      }
    }

    // Prio 3 - Deviations from grade.
    $grade_school_week_map = $this->state->get('ssr_school_week_per_grade', []);

    if (!empty($grade_school_week_map)) {
      $grade_deviations_query = $this->connection->select('school_week_deviation__grade', 'swd_g');
      $grade_deviations_query->innerJoin('school_week_deviation', 'swd', 'swd_g.entity_id = swd.id');
      $grade_deviations_query->condition('grade_value', array_keys($grade_school_week_map), 'IN');
      $grade_deviations_query->fields('swd_g', ['grade_value']);
      $grade_deviations_query->fields('swd', ['id', 'from_date', 'to_date', 'deviation_type', 'length', 'from', 'to']);
      $grade_deviations_query->orderBy('swd.from_date', 'DESC');
      foreach ($grade_deviations_query->execute() as $result) {
        $school_week_id = $grade_school_week_map[$result->grade_value] ?? 'unknown';
        $dev_id = $result->id;
        $dev_id_map[$school_week_id][] = $dev_id;

        if (!isset($dev_data[$dev_id])) {
          $dev_data[$dev_id] = $this->calculateDeviationData($result, 'grade');
        }
      }
    }

    foreach ($dev_id_map as $school_week_id => $dev_ids) {
      $dev_id_map[$school_week_id] = array_unique(array_values($dev_ids));
    }

    $cache_tags = [
      'school_week_list',
      'school_week_deviation_list',
      'ssr_school_week_per_grade',
      'ssr_school_week_per_class',
    ];

    $data = [
      $cid_dev_id_map => $dev_id_map,
      $cid_dev_data => $dev_data,
    ];

    $this->cache->set($cid, $data, CacheBackendInterface::CACHE_PERMANENT, $cache_tags);

    $this->lookup[$cid_dev_id_map] = $data[$cid_dev_id_map];
    $this->lookup[$cid_dev_data] = $data[$cid_dev_data];
  }

  /**
   * {@inheritdoc}
   */
  public function getDeviationIdsInUse(): array {
    $this->warmUpDeviationsCache();

    $dev_ids = [];
    foreach ($this->lookup['dev_id_map'] as $ids) {
      $dev_ids = array_merge($dev_ids, $ids);
    }

    return array_values(array_unique($dev_ids));
  }

  /**
   * {@inheritdoc}
   */
  public function getSchoolWeekDeviationIds(SchoolWeekInterface $school_week): array {
    $this->warmUpDeviationsCache();

    $school_week_id = $school_week->id() ?? -1;
    $dev_ids = $this->lookup['dev_id_map'][$school_week_id] ?? [];

    if ($school_week->getParentSchoolWeek()) {
      $dev_ids = array_merge($dev_ids, $this->getSchoolWeekDeviationIds($school_week->getParentSchoolWeek()));
    }

    return array_values(array_unique($dev_ids));
  }

  public function getSchoolWeekDeviationMap(SchoolWeekInterface $school_week): array {
    if (!$school_week->id()) {
      return [];
    }

    $this->warmUpDeviationsCache();

    $cid = 'school_week_deviations_map:' . $school_week->id();

    if (isset($this->lookup[$cid])) {
      return $this->lookup[$cid];
    }

    $dev_ids = $this->getSchoolWeekDeviationIds($school_week);
    $dev_data = $this->lookup['dev_data'];

    $map = [];
    foreach ($dev_ids as $dev_id) {
      $map += $dev_data[$dev_id];
    }

    $this->lookup[$cid] = $map;
    return $map;

  }

  /**
   * {@inheritdoc}
   */
  public function getDeviationData(string $deviation_id): ?array {
    $this->warmUpDeviationsCache();

    return $this->lookup['dev_data'][$deviation_id] ?? NULL;
  }

  public function getDeviationViewsDisplay(): string {
    $this->displayCount++;

    return 'list_' . (($this->displayCount % 15) + 1);
  }

}
