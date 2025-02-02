<?php

namespace Drupal\simple_school_reports_attendance_period_analyse\Service;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\simple_school_reports_attendance_analyse\Service\AttendanceAnalyseServiceInterface;
use Drupal\simple_school_reports_core\Service\UserMetaDataServiceInterface;

/**
 *
 */
class AttendancePeriodAnalyseService implements AttendancePeriodAnalyseServiceInterface {

  protected array $lookup = [];

  /**
   * @param \Drupal\Core\State\StateInterface $state
   */
  public function __construct(
    protected StateInterface $state,
    protected CacheBackendInterface $cache,
    protected AttendanceAnalyseServiceInterface $attendanceAnalyseService,
    protected UserMetaDataServiceInterface $userMetaDataService,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getAbsencePercentageLimits(): array {
    $cid = 'processed_apl_limits';
    if (isset($this->lookup[$cid])) {
      return $this->lookup[$cid];
    }

    $processed_limits = [0];

    $limits = $this->state->get('ssr.absence_percentage_limits', [15, 30, 50]);

    foreach ($limits as $limit) {
      if (is_numeric($limit) && $limit >= 1 && $limit <= 99) {
        $processed_limits[] = (int) $limit;
      }
    }

    $processed_limits[] = 100;
    $processed_limits = array_unique($processed_limits);
    sort($processed_limits);
    $processed_limits = array_values($processed_limits);

    $this->lookup[$cid] = $processed_limits;
    return $processed_limits;
  }

  /**
   * {@inheritdoc}
   */
  public function setAbsencePercentageLimits(array $limits): void {
    $limits_to_store = [];
    foreach ($limits as $limit) {
      if (is_numeric($limit) && $limit >= 1 && $limit <= 99) {
        $limits_to_store[] = (int) $limit;
      }
    }

    $limits_to_store = array_unique($limits_to_store);
    sort($limits_to_store);

    Cache::invalidateTags(['absence_percentage_limits']);
    $this->state->set('ssr.absence_percentage_limits', $limits_to_store);
  }

  /**
   * {@inheritdoc}
   */
  public function getAttendancePeriodData(array $uids, \DateTime $from, \DateTime $to): array {
    if (empty($uids) || $from > $to) {
      return [];
    }

    $now = new \DateTime();
    if ($from > $now) {
      return [];
    }

    if ($to > $now) {
      $to = $now;
    }

    sort($uids);

    $cid_parts = ['attendance_period_data'];
    $cid_parts[] = $from->format('Y-m-d');
    $cid_parts[] = $to->format('Y-m-d');
    $cid_parts[] = implode(',', $uids);
    $cid = sha1(implode(':', $cid_parts));

    if (isset($this->lookup[$cid])) {
      return $this->lookup[$cid];
    }

    $cache = $this->cache->get($cid);
    if (!empty($cache)) {
      $this->lookup[$cid] = $cache->data;
      return $cache->data;
    }

    $base_data = [
      'invalid_absence_limits' => [],
      'valid_absence_limits' => [],
      'total_absence_limits' => [],
      'school_lengths' => [
        'grade' => NULL,
        'adapted_studies' => [],
      ],
      'count_students' => 0,
      'other_grades' => FALSE,
    ];
    $limits = $this->getAbsencePercentageLimits();
    array_pop($limits);
    $limits = array_reverse($limits);

    foreach ($limits as $limit) {
      $base_data['invalid_absence_limits'][$limit] = 0;
      $base_data['valid_absence_limits'][$limit] = 0;
      $base_data['total_absence_limits'][$limit] = 0;
    }

    $data = [
      'all' => $base_data,
    ];
    $has_data = FALSE;

    $stats = $this->attendanceAnalyseService->getAttendanceStatisticsAll($from, $to);
    foreach ($uids as $uid) {
      $student_stats = $stats[$uid] ?? ['total' => 0];
      if ($student_stats['total'] <= 0) {
        continue;
      }

      $has_data = TRUE;


      $grade = $student_stats['user_grade'] ?? -99;
      $user_grade_now = $this->userMetaDataService->getUserGrade($uid);

      if (empty($data['grade'][$grade])) {
        $data['grade'][$grade] = $base_data;
      }

      if (!$data['all']['other_grades'] && $grade !== $user_grade_now) {
        $data['all']['other_grades'] = TRUE;
        $data['grade'][$grade]['other_grades'] = TRUE;
      }

      $data['all']['count_students']++;
      $data['grade'][$grade]['count_students']++;

      $is_adapted = $student_stats['adapted_studies'] ?? FALSE;

      $formatted_total = $this->getTimeString($student_stats['total']);

      if ($is_adapted) {
        /** @var \Drupal\user\UserInterface $user */
        $user = $this->entityTypeManager->getStorage('user')->load($uid);
        $name = $user?->getDisplayName() ?? 'unknown';
        $data['grade'][$grade]['school_lengths']['adapted_studies'][] = $formatted_total . ' - ' . $name;
      }
      else {
        $data['grade'][$grade]['school_lengths']['grade'] = $formatted_total;
      }

      $absence = [
        'invalid_absence' => (int) round(($student_stats['invalid_absence'] / $student_stats['total']) * 100),
        'valid_absence' => (int) round((($student_stats['valid_absence'] + $student_stats['leave_absence'] + $student_stats['reported_absence']) / $student_stats['total']) * 100),
        'total_absence' => (int) round((($student_stats['invalid_absence'] + $student_stats['valid_absence'] + $student_stats['leave_absence'] + $student_stats['reported_absence']) / $student_stats['total']) * 100),
      ];

      foreach ($absence as $type => $value) {
        $limit_key = $type . '_limits';
        foreach ($data['all'][$limit_key] as $limit => $count) {
          if ($value >= $limit) {
            $data['all'][$limit_key][$limit]++;
            $data['grade'][$grade][$limit_key][$limit]++;
            break;
          }
        }
      }
    }

    if (!$has_data) {
      $data = [];
    }

    if (!empty($data['grade'])) {
      ksort($data['grade']);
    }

    $cache_tags = [
      'school_week_list',
      'node_list:day_absence',
      'node_list:course_attendance_report',
      'absence_percentage_limits',
      'user_list:student',
      'school_week_deviation_list',
      'ssr_school_week_per_grade',
      'ssr_schema_entry_list',
      'ssr_calendar_event_list',
    ];
    $this->cache->set($cid, $data, Cache::PERMANENT, $cache_tags);
    $this->lookup[$cid] = $data;
    return $data;
  }

  protected function getTimeString(int $length): string {

    if ($length === 0) {
      return '-';
    }

    $hours = floor($length / 3600);
    $min = round(($length % 3600) / 60);

    if ($hours < 10) {
      $hours = '0' . $hours;
    }
    else {
      $hours = number_format($hours, 0, ',', ' ');
    }
    if ($min < 10) {
      $min = '0' . $min;
    }

    return $hours . ':' . $min;
  }

}
