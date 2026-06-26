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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class ChildCareCheckInService
 */
class ChildCareCheckInService implements ChildCareCheckInServiceInterface {

  use StringTranslationTrait;

  protected array $lookup = [];

  public function __construct(
    protected Connection $connection,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected TimeInterface $time,
    protected ChildCareServiceInterface $childCareService,
    protected ChildCareSchemaServiceInterface $childCareSchemaService,
    protected AccountInterface $currentUser,
    protected RequestStack $requestStack,
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

  protected function getCheckInData(\DateTimeInterface $date = new \DateTime()): array {
    $cid = 'checkin:' . $date->format('Y-m-d');
    if (array_key_exists($cid, $this->lookup)) {
      return $this->lookup[$cid];
    }
    $data = [];

    [$from, $to] = $this->getFromTo($date);

    $query = $this->connection->select('ssr_child_care_check_in', 'c');
    $or_condition = $query->orConditionGroup();
    $or_condition->condition('c.to', $from, '>');
    $or_condition->isNull('c.to');
    $query->condition($or_condition);

    $query->condition('c.from', $to, '<')
      ->condition('c.from', $from, '>=')
      ->fields('c', ['student', 'child_care', 'from', 'to', 'id'])
      ->orderBy('c.from', 'ASC');
    $results = $query->execute();

    foreach ($results as $result) {
      $student_id = $result->student;
      $child_care_id = $result->child_care;
      $data[$student_id][] = [
        'from' => $result->from,
        'to' => $result->to,
        'child_care_id' => $child_care_id,
        'check_in_id' => $result->id,
      ];
    }

    $this->lookup[$cid] = $data;
    return $data;
  }

  public function getChildCareCheckIns(string|int $student_id, \DateTimeInterface $date = new \DateTime(), ?array $restricted_child_care_ids = NULL): array {
    $data = $this->getCheckInData($date)[$student_id] ?? [];
    if (is_array($restricted_child_care_ids)) {
      $data = array_filter($data, fn($item) => in_array($item['child_care_id'], $restricted_child_care_ids));
    }
    return $data;
  }

  public function getLatestCheckIn(string|int $student_id, string|int $child_care_id, \DateTimeInterface $date = new \DateTime()): ?array {
    $check_ins = $this->getChildCareCheckIns($student_id, $date, [$child_care_id]);
    if (empty($check_ins)) {
      return NULL;
    }
    return $check_ins[count($check_ins) - 1];
  }

  public function getActiveCheckIn(string|int $student_id, string|int $child_care_id, \DateTimeInterface $date = new \DateTime()): ?array {
    $check_ins = $this->getChildCareCheckIns($student_id, $date, [$child_care_id]);
    if (empty($check_ins)) {
      return NULL;
    }
    $check_ins = array_reverse($check_ins);
    $check_in = array_find($check_ins, function($item) {
      $to = $item['to'] ?? NULL;
      return $to === NULL;
    }) ?? NULL;
    return $check_in;
  }

  public function buildStudentCheckIns(string|int $student_id, \DateTimeInterface $date = new \DateTime(), ?array $restricted_child_care_ids = NULL): array {
    $build = [];
    $segments = $this->getChildCareCheckIns($student_id, $date, $restricted_child_care_ids);

    if (empty($segments)) {
      $build['#empty'] = TRUE;
    }

    $build['#attached']['library'][] = 'simple_school_reports_child_care_support/child_care_check_in_overview';

    $build['segments'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['child-care-segments'],
      ],
    ];


    $segments_per_group = [];
    foreach ($segments as $segment) {
      $group_id = $segment['child_care_id'];
      $segments_per_group[$group_id][] = $segment;
    }

    foreach ($segments_per_group as $child_care_id => $segments) {
      $build['segments'][$child_care_id] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['child-care-segment'],
        ],
      ];

      /** @var \Drupal\simple_school_reports_child_care_support\ChildCareInterface|null $child_care */
      $child_care = $this->entityTypeManager->getStorage('ssr_child_care')->load($child_care_id);

      // Take last segment.
      $last_check_in = 0;
      $last_check_out = 0;
      foreach ($segments as $segment) {
        if ($last_check_out === NULL) {
          continue;
        }

        if ($segment['from'] > $last_check_out) {
          $last_check_out = $segment['from'];
        }
        if ($segment['to'] === NULL) {
          $last_check_in = $segment['from'];
          $last_check_out = NULL;
          continue;
        }
        if ($segment['to'] > $last_check_out) {
          $last_check_out = $segment['to'];
        }
      }


      $label = NULL;
      $class = [];
      $data_checked_in = NULL;
      $data_checked_out = NULL;

      if ($last_check_out) {
        $to = new \DateTime();
        $to->setTimestamp($last_check_out);
        $label = $this->t('Checked out @time @group', [
          '@time' => $to->format('H:i'),
          '@group' => $child_care ? '(' . $child_care->getShortLabel() . ')' : '',
        ]);
        $class = 'child-care-segment--checked-out';
        $data_checked_out = $last_check_out;
      }
      elseif ($last_check_in) {
        $from = new \DateTime();
        $from->setTimestamp($last_check_in);
        $label = $this->t('Checked in @time @group', [
          '@time' => $from->format('H:i'),
          '@group' => $child_care ? '(' . $child_care->getShortLabel() . ')' : '',
        ]);
        $class = 'child-care-segment--checked-in';
        $data_checked_in = $last_check_in;
      }


      $title_parts = [];
      foreach ($segments as $segment) {
        $from = NULL;
        if (is_numeric($segment['from'])) {
          $from = new \DateTime();
          $from->setTimestamp($segment['from']);
        }
        $to = NULL;
        if (is_numeric($segment['to'])) {
          $to = new \DateTime();
          $to->setTimestamp($segment['to']);
        }
        $title_parts[] = ($from?->format('H:i') ?? '') . '-' . ($to?->format('H:i') ?? '');
      }
      $title = implode(', ', $title_parts);

      $build['segments'][$child_care_id]['label'] = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => $label,
        '#attributes' => [
          'class' => [$class],
          'title' => $title,
          'data-checked-in' => $data_checked_in,
          'data-checked-out' => $data_checked_out,
          'data-child-care-id' => $child_care_id,
        ],
      ];
    }

    return $build;
  }

  public function allowChildCareCheckIn(string|int $child_care_id, \DateTimeInterface $date = new \DateTime(), ?AccountInterface $account = NULL): bool {
    if (!$account) {
      $account = $this->currentUser;
    }
    $cid = 'accc:' . $child_care_id . ':' . $date->format('Y-m-d') . ':' . $account->id();
    if (array_key_exists($cid, $this->lookup)) {
      return $this->lookup[$cid];
    }
    if ($account->hasPermission('administer simple school reports child care support')) {
      $this->lookup[$cid] = TRUE;
      return TRUE;
    }

    $child_care = $this->entityTypeManager->getStorage('ssr_child_care')->load($child_care_id);
    if (!$child_care) {
      $this->lookup[$cid] = FALSE;
      return FALSE;
    }
    $this->lookup[$cid] = $child_care->access('check_in_out', $account);
    return $this->lookup[$cid];
  }

  public function allowChildCareCheckOut(string|int $child_care_id, \DateTimeInterface $date = new \DateTime(), ?AccountInterface $account = NULL): bool {
    return $this->allowChildCareCheckIn($child_care_id, $date, $account);
  }

  public function allowStudentCheckIn(int|string $student_id, \DateTimeInterface $date = new \DateTime(), ?array $restricted_child_care_ids = NULL, ?AccountInterface $account = NULL): bool {
    if (!$account) {
      $account = $this->currentUser;
    }
    // Never allow in the future.
    if ($date > new \DateTime()) {
      return FALSE;
    }

    $child_care_ids = $this->childCareService->getChildCareGroups($student_id, $date);
    if (is_array($restricted_child_care_ids)) {
      $child_care_ids = array_intersect($child_care_ids, $restricted_child_care_ids);
    }

    // Never allow to check in student with no placements.
    if (empty($child_care_ids)) {
      return FALSE;
    }

    // Always allow for admin.
    if ($account->hasPermission('administer simple school reports child care support')) {
      return TRUE;
    }

    // Only allow check in/out on current day.
    $today = (new \DateTime())->format('Y-m-d');
    if ($date->format('Y-m-d') !== $today) {
      return FALSE;
    }

    // Allow if allowed to check in any child care.
    return !!array_find($child_care_ids, function($child_care_id) use ($student_id, $date, $account) {
      return $this->allowChildCareCheckIn($child_care_id, $date, $account);
    });
  }

  public function allowStudentCheckOut(int|string $student_id, \DateTimeInterface $date = new \DateTime(), ?array $restricted_child_care_ids = NULL, ?AccountInterface $account = NULL): bool {
    if (!$account) {
      $account = $this->currentUser;
    }
    if (!$this->allowStudentCheckIn($student_id, $date, $restricted_child_care_ids, $account)) {
      return FALSE;
    }
    $check_ins = $this->getChildCareCheckIns($student_id, $date, $restricted_child_care_ids);
    $check_in = array_find($check_ins, function($item) {
      return $item['to'] === NULL;
    });
    return !!$check_in;
  }

  public function getCacheableMetadata(?\DateTimeInterface $date, array $options = []): CacheableMetadata {
    $cache = new CacheableMetadata();

    if (!$date) {
      $cache->setCacheMaxAge(0);
      return $cache;
    }

    $today = new \DateTime();
    $today->setTime(0, 0, 0);

    if ($date > $today) {
      $cache->addCacheContexts(['current_day']);
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
      'ssr_child_care_check_in_list:' . $date->format('Y-m-d'),
    ]);
    $cache->addCacheContexts([
      'route',
      'user',
      'url.query_args:from',
      'url.query_args:to',
      'url.query_args:date',
      'url.query_args:groups',
      'url.query_args:filter',
      'url.query_args:include_unscheduled',
    ]);

    if ($today->format('Y-m-d') === $date->format('Y-m-d')) {
      $cache->setCacheMaxAge(0);
    }

    return $cache;
  }

  public function getStudentIdsFromRequest(): array {
    $uids = $this->childCareService->getStudentIdsFromRequest();
    if (empty($uids)) {
      return [];
    }
    $date = $this->childCareService->getDateFromRequest();
    $groups = $this->childCareService->getChildCareIdsFromRequest();
    if (!$date || empty($groups)) {
      return [];
    }

    $request = $this->requestStack->getCurrentRequest();

    $filter = $request->query->get('filter', 'all');
    $include_unscheduled =  $request->query->get('include_unscheduled');

    if ($filter === 'not_checked_in') {

      return array_filter($uids, function($student_id) use ($date, $groups, $include_unscheduled) {
        if (!$include_unscheduled) {
          $needs = $this->childCareSchemaService->getChildCareStudentNeed($student_id, $date);
          if (empty($needs)) {
            return FALSE;
          }
        }

        $check_ins = $this->getChildCareCheckIns($student_id, $date, $groups);
        return empty($check_ins);
      });
    }

    if ($filter === 'checked_in') {
      return array_filter($uids, function($student_id) use ($date, $groups) {
        $check_ins = $this->getChildCareCheckIns($student_id, $date, $groups);

        $check_in = array_find($check_ins, function($item) {
          $to = $item['to'] ?? NULL;
          return $to === NULL;
        }) ?? NULL;

        return !!$check_in;
      });
    }

    if ($filter === 'checked_out') {
      return array_filter($uids, function($student_id) use ($date, $groups) {
        $check_ins = $this->getChildCareCheckIns($student_id, $date, $groups);
        if (empty($check_ins)) {
          return FALSE;
        }

        $check_in = array_find($check_ins, function($item) {
          $to = $item['to'] ?? NULL;
          return $to === NULL;
        }) ?? NULL;

        return !$check_in;
      });
    }


    if ($include_unscheduled) {
      return $uids;
    }

    return array_filter($uids, function($student_id) use ($date) {
      $needs = $this->childCareSchemaService->getChildCareStudentNeed($student_id, $date);
      return !empty($needs);
    });

  }

  public function getPointOfInterest(string|int $student_id, \DateTimeInterface $date = new \DateTime(), ?array $restricted_child_care_ids = NULL): ?array {
    // Only calculate point of interest for current day.
    $today = (new \DateTime())->format('Y-m-d');
    if ($date->format('Y-m-d') !== $today) {
      return NULL;
    }

    $now = new \DateTime();

    $cid = 'poi:' . $student_id . ':' . $date->format('Y-m-d') . ':' . implode(',', $restricted_child_care_ids ?? ['all']);
    if (array_key_exists($cid, $this->lookup)) {
      return $this->lookup[$cid];
    }

    $segments = $this->childCareSchemaService->getStudentChildCareSchemaSegments($student_id, $date, $restricted_child_care_ids);
    $child_care_segments = [];

    $lowest_from = NULL;
    $highest_to = NULL;

    foreach ($segments as $segment) {
      $type = $segment['type'];

      if ($type !== ChildCareSchemaServiceInterface::SCHEMA_SEGMENT_TYPE_SCHOOL_DAY && $type !== ChildCareSchemaServiceInterface::SCHEMA_SEGMENT_TYPE_SCHOOL_CHILD_CARE) {
        continue;
      }

      if ($type === ChildCareSchemaServiceInterface::SCHEMA_SEGMENT_TYPE_SCHOOL_CHILD_CARE) {
        $child_care_segments[] = $segment;
      }

      if ($type === ChildCareSchemaServiceInterface::SCHEMA_SEGMENT_TYPE_SCHOOL_CHILD_CARE && $segment['from'] < ($lowest_from ?? PHP_INT_MAX)) {
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

    if (!$day_boundaries) {
      $this->lookup[$cid] = NULL;
      return NULL;
    }

    $check_ins = $this->getChildCareCheckIns($student_id, $date, $restricted_child_care_ids);
    $check_ins_per_group = [];
    foreach ($check_ins as $check_in) {
      $check_ins_per_group[$check_in['child_care_id']][] = $check_in;
    }

    $is_checked_in = !!(array_find($check_ins, function($item) {
      $to = $item['to'] ?? NULL;
      return $to === NULL;
    }) ?? NULL);
    $was_checked_in = !$is_checked_in && !empty($check_ins);
    $is_not_checked_in = empty($check_ins);


    // If not checked in, find the first child care section unless day boundaries are passed.
    if ($is_not_checked_in) {
      $segment = $child_care_segments[0] ?? NULL;
      $this->lookup[$cid] = $segment && $now->getTimestamp() < $day_boundaries['to']
        ? [
          'type' => 'check_in',
          'time' => $segment['from'],
        ]
        : NULL;
      return $this->lookup[$cid];
    }

    // If was checked in, find the first child care section for a child care that user is previously not checked in to.
    if ($was_checked_in) {
      $checked_in_groups = array_keys($check_ins_per_group);
      $segment = array_find($child_care_segments, function($item) use ($checked_in_groups) {
        $segment_child_care_ids = $item['child_care_ids'] ?? [];
        // Check if $segment_child_care_ids has any id that is not in $checked_in_groups.
        return empty(array_intersect($segment_child_care_ids, $checked_in_groups));
      });
      $this->lookup[$cid] = $segment && $now->getTimestamp() < $day_boundaries['to']
        ? [
          'type' => 'check_in',
          'time' => $segment['from'],
        ]
        : NULL;
      return $this->lookup[$cid];
    }

    // If checked in, use the day boundary to time.
    if ($is_checked_in) {
      $this->lookup[$cid] = [
        'type' => 'check_out',
        'time' => $day_boundaries['to'],
      ];
      return $this->lookup[$cid];
    }

    $this->lookup[$cid] = NULL;
    return NULL;
  }

}
