<?php

namespace Drupal\simple_school_reports_child_care_support\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\simple_school_reports_child_care_support\ChildCarePlacementInterface;
use Drupal\simple_school_reports_core\Service\UserMetaDataServiceInterface;
use Drupal\simple_school_reports_entities\Service\SchoolWeekServiceInterface;

/**
 * Class ChildCareSchemaService
 */
class ChildCareSchemaService implements ChildCareSchemaServiceInterface {

  use StringTranslationTrait;

  protected array $lookup = [];

  public function __construct(
    protected Connection $connection,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected CacheBackendInterface $cache,
    protected TimeInterface $time,
    protected StateInterface $state,
    protected ChildCareServiceInterface $childCareService,
    protected UserMetaDataServiceInterface $userMetaDataService,
    protected SchoolWeekServiceInterface $schoolWeekService,
  ) {}

  protected function getFromTo(\DateTimeInterface $date): array {
    $cid = 'from_to:' . $date->format('Y-m-d');
    if (array_key_exists($cid, $this->lookup)) {
      return $this->lookup[$cid];
    }

    $from_data = clone $date;
    $from_data->setTime(0, 0, 0);
    $from = $from_data->getTimestamp();
    $to_data = clone $date;
    $to_data->setTime(23, 59, 59);
    $to = $to_data->getTimestamp();
    $this->lookup[$cid] = [$from, $to];
    return [$from, $to];
  }

  /**
   * @param int|string $child_care_id .
   */
  protected function getChildCareSchemaDetails(int|string $child_care_id): array {
    $cid = 'ccsd:' . $child_care_id;
    if (array_key_exists($cid, $this->lookup)) {
      return $this->lookup[$cid];
    }

    $query = $this->connection->select('ssr_child_care_schema', 's');
    $query->condition('s.child_care', $child_care_id);
    $query->orderBy('s.from', 'ASC');
    $query->fields('s', ['id', 'from', 'to']);
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

  protected function getSchemaSegmentsData(
    \DateTimeInterface $date
  ): array {
    $cid = 'child_care:ssd:' . $date->format('Y-m-d');
    if (array_key_exists($cid, $this->lookup) && is_array($this->lookup[$cid])) {
      return $this->lookup[$cid];
    }
    $cached = $this->cache->get($cid);
    if ($cached && is_array($cached->data)) {
      $this->lookup[$cid] = $cached->data;
      return $this->lookup[$cid];
    }
    $data = [];

    [$from, $to] = $this->getFromTo($date);

    // Look for deviations for students.
    $results = $this->connection->select('ssr_cc_deviation_student', 'd')
      ->condition('d.from_date', $to, '<')
      ->condition('d.to_date', $from, '>')
      ->fields('d', ['student', 'from', 'to', 'id'])
      ->orderBy('d.created', 'DESC')
      ->execute();

    foreach ($results as $result) {
      $student_id = $result->student;
      if (isset($data['student_needs'][$student_id])) {
        continue;
      }

      $data['student_needs'][$student_id] = [
        'from' => $from + $result->from,
        'to' => $from + $result->to,
        'deviation_id' => $result->id,
        'schema_id' => NULL,
      ];
    }

    // Look for deviations for child care offers.
    $results = $this->connection->select('ssr_cc_deviation', 'd')
      ->condition('d.from_date', $to, '<')
      ->condition('d.to_date', $from, '>')
      ->fields('d', ['child_care', 'from', 'to', 'id'])
      ->orderBy('d.created', 'DESC')
      ->execute();

    foreach ($results as $result) {
      $child_care_id = $result->child_care;
      if (isset($data['offer'][$child_care_id])) {
        continue;
      }

      $data['offer'][$child_care_id] = [
        'from' => $from + $result->from,
        'to' => $from + $result->to,
        'deviation_id' => $result->id,
        'schema_id' => NULL,
      ];
    }

    // Look for schema.
    $query = $this->connection->select('ssr_child_care_schema__weeks', 'wf');
    $query->innerJoin('ssr_child_care_schema', 's', 's.id = wf.entity_id');
    $query->innerJoin('ssr_child_care_schema_week', 'w', 'wf.weeks_target_id = w.id');

    // TODO: get schema weeks and resolve correct value.
    $day = $date->format('N');
    $week_from_field = 'from_' . $day;
    $week_to_field = 'to_' . $day;

    $query->condition('s.from', $to, '<=');
    $or_condition = $query->orConditionGroup();
    $or_condition->condition('s.to', $from, '>');
    $or_condition->isNull('s.to');
    $query->condition($or_condition);
    $query->orderBy('s.from', 'DESC');
    $query->orderBy('s.changed', 'DESC');
    $query->fields('s', ['child_care', 'student', 'from', 'to', 'id']);
    $query->fields('wf', ['delta']);
    $query->fields('w', [$week_from_field, $week_to_field]);
    $results = $query->execute();

    $schema_data = [];
    foreach ($results as $result) {
      $child_care_id = $result->child_care;
      $student_id = $result->student;
      $schema_id = $result->id;

      $schema_data_key = NULL;
      if ($student_id) {
        $schema_data_key = 'u:' . $student_id;
      }
      elseif ($child_care_id) {
        $schema_data_key = 'cc:' . $child_care_id;
      }
      if (!$schema_data_key) {
        continue;
      }

      // Skip schema that is not the most recent.
      if (isset($schema_data[$schema_data_key]) && $schema_data[$schema_data_key]['schema_id'] !== $schema_id) {
        continue;
      }

      if (!isset($schema_data[$schema_data_key])) {
        $schema_data[$schema_data_key] = [
          'from' => $result->from,
          'schema_id' => $schema_id,
          'weeks' => [],
        ];
      }

      $schema_from = $result->{$week_from_field};
      $schema_to = $result->{$week_to_field};

      if (!is_numeric($schema_from) || !is_numeric($schema_to)) {
        $schema_data[$schema_data_key]['weeks'][$result->delta] = [
          'from' => NULL,
          'to' => NULL,
          'schema_id' => $schema_id,
        ];
      }
      else {
        $schema_data[$schema_data_key]['weeks'][$result->delta] = [
          'from' => $from + $result->{$week_from_field},
          'to' => $from + $result->{$week_to_field},
          'schema_id' => $schema_id,
        ];
      }
    }

    // Order all schema weeks by delta and reset to 0 indexed array.
    foreach ($schema_data as $schema_data_key => $schema_data_item) {
      ksort($schema_data_item['weeks']);
      $schema_data[$schema_data_key]['weeks'] = array_values($schema_data_item['weeks']);
    }

    // Resolve the correct week for each student and child care.
    foreach ($schema_data as $schema_data_key => $schema_data_item) {
      [$type, $id] = explode(':', $schema_data_key);

      $schema_from = $schema_data_item['from'];
      $schema_from_date = new \DateTime();
      $schema_from_date->setTimestamp($schema_from);
      $schema_from_first_day = $this->getFirstDayOfWeek($schema_from_date);

      $weeks = $schema_data_item['weeks'];
      $week_count = count($weeks);

      $diff_in_weeks = floor(abs($schema_from_first_day - $from) / (7 * 24 * 60 * 60));
      $week_index = $diff_in_weeks % $week_count;

      $schema_from = $weeks[$week_index]['from'];
      $schema_to = $weeks[$week_index]['to'];

      if ($type == 'u') {
        $student_id = $id;
        if (isset($data['student_needs'][$student_id])) {
          continue;
        }
        $data['student_needs'][$student_id] = [
          'from' => $schema_from,
          'to' => $schema_to,
          'deviation_id' => NULL,
          'schema_id' => $result->id,
        ];
      }
      elseif ($type == 'cc') {
        $child_care_id = $id;
        if (isset($data['offer'][$child_care_id])) {
          continue;
        }
        $data['offer'][$child_care_id] = [
          'from' => $schema_from,
          'to' => $schema_to,
          'deviation_id' => NULL,
          'schema_id' => $result->id,
        ];
      }
    }

    foreach ($data['student_needs'] ?? [] as $student_id => $student_need) {
      // Remove needs with null from/to values.
      if (!$student_need['from'] || !$student_need['to']) {
        unset($data['student_needs'][$student_id]);
        continue;
      }

      $data['student_needs'][$student_id]['child_care_ids'] = $this->childCareService->getChildCareGroups($student_id, $date);
    }

    foreach ($data['offer'] ?? [] as $child_care_id => $offer) {
      // Remove needs with null from/to values.
      if (!$offer['from'] || !$offer['to']) {
        unset($data['offer'][$child_care_id]);
      }
    }

    $cache_tags = [
      'ssr_cc_deviation_student_list',
      'ssr_cc_deviation_list',
      'ssr_child_care_schema_list',
      'ssr_child_care_placement_list',
    ];
    $this->cache->set($cid, $data, Cache::PERMANENT, $cache_tags);
    $this->lookup[$cid] = $data;
    return $data;
  }

  protected function getStudentAbsenceData(
    int|string $student_id,
    \DateTimeInterface $date
  ): array {
    $cid = 'absence:' . $date->format('Y-m-d');
    if (array_key_exists($cid, $this->lookup) && is_array($this->lookup[$cid])) {
      return $this->lookup[$cid][$student_id] ?? [];
    }
    $data = [];

    [$from, $to] = $this->getFromTo($date);

    $query = $this->connection->select('node__field_student', 's');
    $query->innerJoin('node__field_absence_from', 'af', 'af.entity_id = s.entity_id');
    $query->innerJoin('node__field_absence_to', 'at', 'at.entity_id = s.entity_id');
    $query->innerJoin('node__field_absence_type', 't', 't.entity_id = s.entity_id');
    $query->condition('af.field_absence_from_value', $to - 1, '<');
    $query->condition('at.field_absence_to_value', $from + 1, '>');
    $query->fields('s', ['field_student_target_id', 'entity_id'])
      ->fields('af', ['field_absence_from_value'])
      ->fields('at', ['field_absence_to_value'])
      ->fields('t', ['field_absence_type_value']);
    $results = $query->execute();

    foreach ($results as $result) {
      $student_id = $result->field_student_target_id;
      $absence_from = $result->field_absence_from_value;
      $absence_to = $result->field_absence_to_value;
      $absence_type = $result->field_absence_type_value;

      $data[$student_id][] = [
        'from' => $absence_from,
        'to' => $absence_to,
        'type' => $absence_type,
      ];
    }

    $this->lookup[$cid] = $data;
    return $data[$student_id] ?? [];
  }

  public function getChildCareStudentNeed(int|string $student_id, \DateTimeInterface $date = new \DateTime()): ?array {
    $data = $this->getSchemaSegmentsData($date);
    return $data['student_needs'][$student_id] ?? NULL;
  }

  public function getChildCareOffer(int|string $child_care_id, \DateTimeInterface $date = new \DateTime()): ?array {
    $data = $this->getSchemaSegmentsData($date);
    return $data['offer'][$child_care_id] ?? NULL;
  }

  public function getStudentChildCareSchemaSegments(int|string $student_id, \DateTimeInterface $date = new \DateTime(), ?array $restricted_child_care_ids = NULL): array {
    [$from, $to] = $this->getFromTo($date);

    $need = $this->getChildCareStudentNeed($student_id, $date);
    if (!$need) {
      return [
        [
          'from' => $from,
          'to' => $to,
          'type' => self::SCHEMA_SEGMENT_TYPE_SCHOOL_NO_NEEDS,
        ],
      ];
    }

    $offers = [];
    $child_care_ids = $need['child_care_ids'];
    foreach ($child_care_ids as $child_care_id) {
      if (is_array($restricted_child_care_ids) && !in_array($child_care_id, $restricted_child_care_ids)) {
        continue;
      }

      $offer = $this->getChildCareOffer($child_care_id, $date);
      if ($offer) {
        $offer['id'] = $child_care_id;
        $offers[] = $offer;
      }
    }

    $absence_data = $this->getStudentAbsenceData($student_id, $date);

    $school_grade = $this->userMetaDataService->getUserGrade($student_id, $date);
    $school_week = $school_grade >= 0
      ? $this->schoolWeekService->getSchoolWeek($student_id, $date)
      : NULL;
    $school_day = NULL;
    if ($school_week) {
      $school_day_info = $school_week->getSchoolDayInfo($date);
      if (($school_day_info['from'] ?? FALSE) && ($school_day_info['to'] ?? FALSE)) {
        $school_day = $school_day_info;
      }
    }

    $segments = [];
    $key_points = [];
    $key_points[] = $from;
    $key_points[] = $to;
    $key_points[] = $need['from'];
    $key_points[] = $need['to'];

    if ($school_day) {
      $key_points[] = $school_day['from'];
      $key_points[] = $school_day['to'];
    }
    foreach ($offers as $offer) {
      $key_points[] = $offer['from'];
      $key_points[] = $offer['to'];
    }
    foreach ($absence_data as $item) {
      $key_points[] = $item['from'];
      $key_points[] = $item['to'];
    }

    sort($key_points);
    $key_points = array_unique($key_points);
    $key_points = array_values($key_points);

    // Now we got all keypoints of interest, lets create the segments.
    for ($i = 0; $i < count($key_points) - 1; $i++) {
      $key_from = $key_points[$i];
      $key_to = $key_points[$i + 1] - 1;
      // Skip keypoints outside of the date range.
      if ($key_from < $from || $key_from > $to) {
        continue;
      }

      // Are we not in need for child care for this segment, ignor it.
      if ($key_from >= $need['to'] || $key_to <= $need['from']) {
        $segments[] = [
          'from' => $key_from,
          'to' => $key_to,
          'type' => self::SCHEMA_SEGMENT_TYPE_SCHOOL_NO_NEEDS,
        ];
        continue;
      }

      $type = NULL;
      $child_care_ids = [];

      // Priority:
      // 1. Leave absence
      // 2. Registered absence
      // 3. School day
      // 4. Offers

      $absences = array_filter($absence_data, function($item) use ($key_from, $key_to) {
        return $item['from'] <= $key_from && $item['to'] >= $key_to;
      });
      if (!empty($absences)) {
        $has_leave = array_filter($absences, function($item) use ($key_from, $key_to) {
          return $item['type'] === 'leave';
        });
        $type = $has_leave
          ? self::SCHEMA_SEGMENT_TYPE_LEAVE_ABSENCE
          : self::SCHEMA_SEGMENT_TYPE_REPORTED_ABSENCE;
      }

      if (!$type && $school_day) {
        if ($school_day['from'] <= $key_from && $school_day['to'] >= $key_to) {
          $type = self::SCHEMA_SEGMENT_TYPE_SCHOOL_DAY;
        }
      }

      if (!$type) {
        $child_care_offers = array_filter($offers, function($offer) use ($key_from, $key_to) {
          return $offer['from'] <= $key_from && $offer['to'] >= $key_to;
        });
        if (!empty($child_care_offers)) {
          $type = self::SCHEMA_SEGMENT_TYPE_SCHOOL_CHILD_CARE;
          $child_care_ids = array_column($child_care_offers, 'id');
        }
      }

      $segment = [
        'from' => $key_from,
        'to' => $key_to,
        'type' => $type ?? self::SCHEMA_SEGMENT_TYPE_SCHOOL_NO_OFFER,
      ];
      if ($type === self::SCHEMA_SEGMENT_TYPE_SCHOOL_CHILD_CARE) {
        $segment['child_care_ids'] = $child_care_ids;
      }
      $segments[] = $segment;
    }

    return $this->optimizeSegments($segments);
  }

  protected function optimizeSegments(array $segments): array {
    if (empty($segments)) {
      return [];
    }

    $segments = array_values($segments);

    $new_segments = [];
    $current_segment = $segments[0];

    foreach ($segments as $segment) {
      $type = $segment['type'];
      if ($type === $current_segment['type']) {
        continue;
      }

      $current_segment['to'] = $segment['from'] - 1;
      $new_segments[] = $current_segment;

      // Prepare next segment.
      $current_segment = $segment;
    }
    $new_segments[] = $current_segment;

    return $new_segments;
  }

  protected function getFirstDayOfWeek(\DateTimeInterface $date): int {
    $cid = 'first_day_of_week:' . $date->format('Y-m-d');
    if (array_key_exists($cid, $this->lookup) && is_int($this->lookup[$cid])) {
      return $this->lookup[$cid];
    }

    $return = clone $date;
    $return->setTime(0, 0, 0);
    $day_number = $return->format('N');

    $day_diff = $day_number - 1;

    if ($day_diff >= 1) {
      $return->sub(new \DateInterval('P' . $day_diff . 'D'));
    }
    $this->lookup[$cid] = $return->getTimestamp();
    return $return->getTimestamp();
  }

  public function buildStudentDayOverview(int|string $student_id, \DateTimeInterface $date = new \DateTime(), ?array $restricted_child_care_ids = NULL): array {
    $build = [];
    $build['#attached']['library'][] = 'simple_school_reports_child_care_support/child_care_overview';

    $needs = $this->getChildCareStudentNeed($student_id, $date) ?? [];
    $deviation_id = $needs['deviation_id'] ?? NULL;

    $build['segments'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['child-care-segments'],
      ],
    ];

    $segments = $this->getStudentChildCareSchemaSegments($student_id, $date, $restricted_child_care_ids);
    $segments = array_filter($segments, fn($segment) => $segment['type'] !== ChildCareSchemaServiceInterface::SCHEMA_SEGMENT_TYPE_SCHOOL_NO_NEEDS);

    if (empty($segments)) {
      $build['#empty'] = TRUE;
      $build['segments'][0] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['child-care-segment'],
        ],
      ];
      $build['segments'][0]['time'] = [
        '#markup' => '-',
      ];
      $this->addDeviationComment($build, $deviation_id);
      return $build;
    }

    // Preload child care entities.
    if (!empty($restricted_child_care_ids)) {
      $this->entityTypeManager->getStorage('ssr_child_care')
        ->loadMultiple($restricted_child_care_ids);
    }
    elseif (!empty($needs['child_care_ids'])) {
      $this->entityTypeManager->getStorage('ssr_child_care')
        ->loadMultiple($needs['child_care_ids']);
    }

    foreach ($segments as $key => $segment) {
      $build['segments'][$key] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['child-care-segment'],
        ],
      ];

      $suffix = NULL;
      if ($segment['type'] === ChildCareSchemaServiceInterface::SCHEMA_SEGMENT_TYPE_SCHOOL_CHILD_CARE) {
        $child_care_ids = $segment['child_care_ids'] ?? [];
        /** @var \Drupal\simple_school_reports_child_care_support\ChildCareInterface[] $child_care_entities */
        $child_care_entities = !empty($child_care_ids) ? $this->entityTypeManager->getStorage('ssr_child_care')
          ->loadMultiple($child_care_ids) : [];
        $offer_names = [];
        foreach ($child_care_entities as $child_care_entity) {
          $offer_names[] = $child_care_entity->getShortLabel();
        }
        sort($offer_names);
        $suffix = '(' . implode(', ', $offer_names) . ')';
      }
      else {
        $suffix_map = [
          ChildCareSchemaServiceInterface::SCHEMA_SEGMENT_TYPE_LEAVE_ABSENCE => $this->t('Leave'),
          ChildCareSchemaServiceInterface::SCHEMA_SEGMENT_TYPE_REPORTED_ABSENCE => $this->t('Absence'),
          ChildCareSchemaServiceInterface::SCHEMA_SEGMENT_TYPE_SCHOOL_DAY => $this->t('School day'),
          ChildCareSchemaServiceInterface::SCHEMA_SEGMENT_TYPE_SCHOOL_NO_OFFER => $this->t('No available child care'),
        ];

        $suffix = isset($suffix_map[$segment['type']])
          ? ' - ' . $suffix_map[$segment['type']]
          : NULL;
      }

      // Format time.
      $from = new \DateTime();
      $from->setTimestamp($segment['from']);
      $to = new \DateTime();
      $to->setTimestamp($segment['to'] + 1);

      $build['segments'][$key]['time'] = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => $from->format('H:i') . ' - ' . $to->format('H:i'),
        '#attributes' => [
          'data-from' => $segment['from'],
          'data-to' => $segment['to'],
          'data-child-care-id' => Json::encode($child_care_ids ?? []),
        ]
      ];
      if ($segment['type'] !== ChildCareSchemaServiceInterface::SCHEMA_SEGMENT_TYPE_SCHOOL_CHILD_CARE) {
        $build['segments'][$key]['time']['#attributes']['class'][] = 'child-care-segment--strike-through';
      }

      if ($suffix) {
        $build['segments'][$key]['suffix'] = [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#value' => $suffix,
        ];
      }
    }

    $this->addDeviationComment($build, $deviation_id);
    return $build;
  }

  protected function addDeviationComment(array &$build, string|int|null $deviation_id): void {
    if (!$deviation_id) {
      return;
    }
    $build['comment_group'] = [];

    $deviation = $this->entityTypeManager->getStorage('ssr_cc_deviation_student')
      ->load($deviation_id);

    $comment = $deviation->get('field_comment')->value;
    if ($comment) {
      $comment = nl2br($comment);
      $format = 'plain_text_ck';
      $comment = check_markup($comment, $format);

      $build['comment_group'] = [
        '#type' => 'details',
        '#open' => FALSE,
        '#title' => t('Comment'),
      ];
      $build['comment_group']['comment'] = [
        '#markup' => $comment,
      ];
    }
  }

  public function getDayOverview(array $student_ids, \DateTimeInterface $date = new \DateTime(), ?array $restricted_child_care_ids = NULL, array $check_ins = []): array {
    [$from, $to] = $this->getFromTo($date);

    $timestamp = $from;
    // Step 15 minutes.
    $step = 15 * 60;

    // [] => [ needs: int, offers: int, from: int, to: int ];
    $data = [];

    $min_limit = clone $date;
    $min_limit->setTime(6, 0, 0);

    $max_limit = clone $date;
    $max_limit->setTime(18, 0, 0);

    $student_segments = [];
    foreach ($student_ids as $student_id) {
      $segments = $this->getStudentChildCareSchemaSegments($student_id, $date);
      $segments = array_filter($segments, function($segment) use ($min_limit, $max_limit) {
        return $segment['type'] !== ChildCareSchemaServiceInterface::SCHEMA_SEGMENT_TYPE_SCHOOL_NO_NEEDS;
      });
      if (empty($segments)) {
        continue;
      }
      $student_segments[$student_id] = $segments;
    }

    $offer_segments = [];

    $groups = is_array($restricted_child_care_ids)
      ? $restricted_child_care_ids
      : NULL;

    // All groups are currently not supported.
    if ($groups === NULL) {
      throw new \RuntimeException('Not implemented yet.');
    }

    foreach ($groups as $child_care_id) {
      $offer = $this->getChildCareOffer($child_care_id, $date);
      if (empty($offer)) {
        continue;
      }
      $offer_segments[$child_care_id] = $offer;
    }

    $include_check_in = !empty($check_ins);
    $student_checked_in = $check_ins;

    $lowest_ts = $min_limit->getTimestamp();
    $highest_ts = $max_limit->getTimestamp();

    while ($timestamp <= $to) {
      $needs = 0;
      $offers = 0;
      $checked_in = 0;

      foreach ($student_segments as $segments) {
        $segment = array_find($segments, function($segment) use ($timestamp) {
          return $segment['from'] <= $timestamp && $segment['to'] > $timestamp;
        });
        $type = $segment ? $segment['type'] : ChildCareSchemaServiceInterface::SCHEMA_SEGMENT_TYPE_SCHOOL_NO_NEEDS;
        if (
          $type === ChildCareSchemaServiceInterface::SCHEMA_SEGMENT_TYPE_SCHOOL_CHILD_CARE ||
          $type === ChildCareSchemaServiceInterface::SCHEMA_SEGMENT_TYPE_SCHOOL_NO_OFFER
        ) {
          $needs++;
        }
        if ($type === ChildCareSchemaServiceInterface::SCHEMA_SEGMENT_TYPE_SCHOOL_NO_OFFER) {
          $offers--;
        }
      }

      $offers += $needs;
      if ($offers < 0) {
        $offers = 0;
      }

      if ($needs === 0 || $offers === $needs) {
        $offers_alt = 0;
        foreach ($offer_segments as $child_care_id => $segment) {
          if ($segment['from'] > $timestamp || $segment['to'] <= $timestamp) {
            continue;
          }
          if (!$segment) {
            continue;
          }
          $offers_alt++;
        }
        $offers = max($offers_alt, $offers);
      }

      if ($include_check_in) {
        foreach ($student_checked_in as $check_in_data) {
          $has_checked_in = array_find($check_in_data, function($item) use ($timestamp) {
            return $item['from'] <= $timestamp && ($item['to'] ?? PHP_INT_MAX) > $timestamp;
          });
          if ($has_checked_in) {
            $checked_in++;
          }
        }
      }

      if ($needs > 0 || $offers > 0 || $checked_in > 0) {
        if ($timestamp < $lowest_ts) {
          $lowest_ts = $timestamp;
        }
        if ($timestamp > $highest_ts) {
          $highest_ts = $timestamp;
        }
      }

      $data_item = [
        'needs' => $needs,
        'offers' => $offers,
        'from' => $timestamp,
        'to' => $timestamp + $step - 1,
      ];
      if ($include_check_in) {
        $data_item['checkedIn'] = $checked_in;
      }
      $data[] = $data_item;
      $timestamp += $step;
    }

    $data = array_filter($data, function($item) use ($lowest_ts, $highest_ts) {
      return $item['from'] >= $lowest_ts && $item['from'] <= $highest_ts;
    });
    return array_values($data);
  }

  public function getStudentDayBoundaries(int|string $student_id, \DateTimeInterface $date = new \DateTime(), ?array $restricted_child_care_ids = NULL): ?array {
    $cid = 'sdb:' . $student_id . ':' . $date->format('Y-m-d');
    if (is_array($restricted_child_care_ids) && !empty($restricted_child_care_ids)) {
      $cid .= ':' . implode(',', $restricted_child_care_ids);
    }

    if (array_key_exists($cid, $this->lookup)) {
      return $this->lookup[$cid];
    }

    $segments = $this->getStudentChildCareSchemaSegments($student_id, $date, $restricted_child_care_ids);

    $lowest_from = NULL;
    $highest_to = NULL;

    foreach ($segments as $segment) {
      $type = $segment['type'];

      if ($type !== self::SCHEMA_SEGMENT_TYPE_SCHOOL_DAY && $type !== self::SCHEMA_SEGMENT_TYPE_SCHOOL_CHILD_CARE) {
        continue;
      }

      if ($type === self::SCHEMA_SEGMENT_TYPE_SCHOOL_CHILD_CARE && $segment['from'] < ($lowest_from ?? PHP_INT_MAX)) {
        $lowest_from = $segment['from'];
      }
      if ($segment['to'] > ($highest_to ?? PHP_INT_MIN)) {
        $highest_to = $segment['to'];
      }
    }

    $day_boundaries = $lowest_from !== NULL && $highest_to !== NULL && $lowest_from < ($highest_to - 10 * 60)
      ? [
        'from' => $lowest_from,
        'to' => $highest_to,
      ]
      : NULL;

     $this->lookup[$cid] = $day_boundaries;
    return $day_boundaries;

  }

  public function getCacheableMetadata(?\DateTimeInterface $date, array $options = []): CacheableMetadata {
    $cache = new CacheableMetadata();

    if (!$date) {
      $cache->setCacheMaxAge(0);
      return $cache;
    }

    $cache->addCacheTags([
      'school_week_list',
      'node_list:day_absence',
      'school_week_deviation_list',
      'ssr_school_week_per_grade',
      'ssr_child_care_list',
      'ssr_child_care_placement_list',
      'ssr_child_care_schema_list',
      'ssr_cc_deviation_list',
      'ssr_cc_deviation_student_list',
    ]);
    $cache->addCacheContexts([
      'route',
      'url.query_args:from',
      'url.query_args:to',
      'url.query_args:date',
      'url.query_args:groups',
    ]);

    return $cache;
  }

}
