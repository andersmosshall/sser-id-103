<?php

namespace Drupal\simple_school_reports_attendance_analyse\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\simple_school_reports_core\AbsenceDayHandler;
use Drupal\simple_school_reports_core\Service\UserMetaDataServiceInterface;
use Drupal\simple_school_reports_entities\SchoolWeekInterface;
use Drupal\simple_school_reports_entities\Service\SchoolWeekServiceInterface;
use Drupal\simple_school_reports_extension_proxy\LessonHandlingTrait;
use Drupal\user\UserInterface;

/**
 *
 */
class AttendanceAnalyseService implements AttendanceAnalyseServiceInterface {

  use LessonHandlingTrait;

  protected array $lookup = [];

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
    protected SchoolWeekServiceInterface $schoolWeekService,
  ) {}

  protected function supportedPeriod(\DateTime $from, \DateTime $to): bool {
    // Let grade new year be in the middle of july.
    $grade_year_from = date('Y', \strtotime('-182 days', $from->getTimestamp()));
    $grade_year_to = date('Y', \strtotime('-182 days', $to->getTimestamp()));
    return $grade_year_from === $grade_year_to;
  }

  /**
   * @param \Drupal\simple_school_reports_entities\SchoolWeekInterface $school_week
   *
   * @return bool
   */
  protected function isAdaptedStudies(SchoolWeekInterface $school_week): bool {
    return $this->schoolWeekService->getSchoolWeekReference($school_week->id())['type'] === 'user';
  }

  /**
   * {@inheritdoc}
   */
  public function getSchoolWeek(string $uid, ?\DateTime $date = NULL): ?SchoolWeekInterface {
    return $this->schoolWeekService->getSchoolWeek($uid, $date);
  }

  /**
   * {@inheritdoc}
   */
  public function getAttendanceStatistics(string $uid, \DateTime $from, \DateTime $to, bool $include_future = FALSE): array {
    if (!$include_future) {
      $now = new \DateTime();
      if ($from > $now) {
        return $this->getAttendanceStatisticsDefault();
      }

      if ($to > $now) {
        $to = $now;
      }
    }

    if (!$this->getSchoolWeek($uid, $from)) {
      return $this->getAttendanceStatisticsDefault();
    }

    return $this->getAttendanceStatisticsAll($from, $to)[$uid] ?? $this->getAttendanceStatisticsDefault();
  }

  protected function getStudentSchema(\DateTime $day, array $school_day_info, array $course_lessons): array {
    if (empty($course_lessons)) {
      return [
        $school_day_info['lessons'],
        $school_day_info['breaks'],
        $school_day_info['length'],
      ];
    }

    $cid_parts = [
      'student_schema',
      $day->format('Y-m-d'),
      json_encode($school_day_info),
      json_encode($course_lessons),
    ];
    $cid = sha1(implode(':', $cid_parts));

    if (isset($this->lookup[$cid])) {
      return $this->lookup[$cid];
    }
    $day_clone = clone $day;
    $day_clone->setTime(0,0,0);
    $day_from = $day_clone->getTimestamp();
    $day_to = $day_from + 86399;

    $school_day_lessons = $this->optimizeLessons($school_day_info['lessons']);
    $course_lessons = $this->optimizeLessons($course_lessons);
    $course_lessons_length = $this->calculateLessonTotalLength($course_lessons);

    if ($course_lessons_length >= $school_day_info['length'] || empty($school_day_lessons)) {
      $breaks = $this->calculateBreaks($day_from, $day_to, $course_lessons);
      $schema = [
        $course_lessons,
        $breaks,
        $course_lessons_length,
      ];
      $this->lookup[$cid] = $schema;
      return $schema;
    }


    $length_diff = $school_day_info['length'] - $course_lessons_length;

    $lesson_length_adjust = $length_diff / count($school_day_lessons);

    foreach ($school_day_lessons as &$school_day_lesson) {
      $school_day_lesson['to'] -= $lesson_length_adjust;

      if ($school_day_lesson['to'] < $school_day_lesson['from']) {
        $school_day_lesson['to'] = $school_day_lesson['from'];
      }

      $school_day_lesson['length'] = $school_day_lesson['to'] - $school_day_lesson['from'];
    }

    $school_day_lessons = array_filter($school_day_lessons, function ($lesson) {
      return $lesson['length'] > 0;
    });

    $school_day_length = $school_day_info['length'];
    $lessons = array_merge($course_lessons, $school_day_lessons);
    $lessons = $this->optimizeLessons($lessons);
    $lessons = $this->verifyLessonLength($lessons, $school_day_length);
    $breaks = $this->calculateBreaks($day_from, $day_to, $lessons);

    $schema = [
      $lessons,
      $breaks,
      $school_day_length,
    ];
    $this->lookup[$cid] = $schema;
    return $schema;
  }

  public function getAttendanceStatisticsAll(\DateTime $from, \DateTime $to): array {
    $cid = 'attendance_statistics_all:' . $from->format('Y-m-d') . ':' . $to->format('Y-m-d');
    if (array_key_exists($cid, $this->lookup)) {
      return $this->lookup[$cid];
    }

    $cached = $this->cache->get($cid);
    if ($cached) {
      $this->lookup[$cid] = $cached->data;
      return $cached->data;
    }
    $from->setTime(0, 0, 0);
    $to->setTime(23, 59, 59);

    if ($from > $to) {
      return [];
    }

    if (!$this->supportedPeriod($from, $to)) {
      return [];
    }

    $base_key_points = [];

    $days = [];
    $day = clone $from;
    while ($day <= $to) {
      $base_key_points[] = $day->getTimestamp();
      $base_key_points[] = $day->getTimestamp() + 86399;

      $days[$day->format('Y-m-d')] = clone $day;
      $day->modify('+1 day');
      $day->setTime(0,0,0);
    }

    $data = [];

    $absence_day_data = [];
    $query = $this->connection->select('node__field_absence_from', 'af');
    $query->innerJoin('node__field_absence_to', 'at', 'at.entity_id = af.entity_id');
    $query->innerJoin('node__field_absence_type', 't', 't.entity_id = af.entity_id');
    $query->innerJoin('node__field_student', 's', 's.entity_id = af.entity_id');
    $results = $query->condition('af.field_absence_from_value', $to->getTimestamp(), '<')
      ->condition('at.field_absence_to_value', $from->getTimestamp(), '>')
      ->fields('af', ['field_absence_from_value'])
      ->fields('at', ['field_absence_to_value'])
      ->fields('t', ['field_absence_type_value'])
      ->fields('s', ['field_student_target_id'])
      ->execute();

    foreach ($results as $result) {
      $ad_uid = $result->field_student_target_id;
      $ad_from = $result->field_absence_from_value;
      $ad_to = $result->field_absence_to_value;
      $ad_type = $result->field_absence_type_value;

      $absence_day_data[$ad_uid][$ad_type . '_absence'][] = [
        'from' => $ad_from,
        'to' => $ad_to,
      ];
    }

    $course_attendance_data = [];
    $course_lessons = [];
    $query = $this->connection->select('paragraph__field_invalid_absence', 'ia');
    $query->innerJoin('paragraph__field_student', 's', 's.entity_id = ia.entity_id');
    $query->innerJoin('paragraphs_item_field_data', 'd', 'd.id = ia.entity_id');
    $query->innerJoin('paragraph__field_subject', 'sub', 'sub.entity_id = ia.entity_id');
    $query->innerJoin('paragraph__field_attendance_type', 'at', 'at.entity_id = ia.entity_id');
    $query->innerJoin('node__field_class_start', 'cs', 'cs.entity_id = d.parent_id');
    $query->innerJoin('node__field_class_end', 'ce', 'ce.entity_id = d.parent_id');
    $query->condition('ia.bundle', 'student_course_attendance')
      ->condition('cs.field_class_start_value', $to->getTimestamp(), '<')
      ->condition('ce.field_class_end_value', $from->getTimestamp(), '>')
      ->fields('at',['field_attendance_type_value'])
      ->fields('s', ['field_student_target_id'])
      ->fields('ia',['field_invalid_absence_value'])
      ->fields('cs', ['field_class_start_value'])
      ->fields('ce', ['field_class_end_value']);
    $results = $query->execute();

    foreach ($results as $result) {
      $ad_uid = $result->field_student_target_id;
      $ad_from = $result->field_class_start_value;
      $ad_to = $result->field_class_end_value;
      $ad_type = $result->field_attendance_type_value;
      $ad_invalid_absence = $result->field_invalid_absence_value;

      $day = (new \DateTime())->setTimestamp($ad_from)->format('Y-m-d');
      $course_lessons[$ad_uid][$day][] = [
        'from' => $ad_from,
        'to' => $ad_to,
      ];

      if ($ad_type === 'invalid_absence' || $ad_type === 'valid_absence') {
        $course_attendance_data[$ad_uid][$ad_type][] = [
          'from' => $ad_from,
          'to' => $ad_to,
        ];
        continue;
      }

      if ($ad_invalid_absence > 0) {
        $course_attendance_data[$ad_uid]['invalid_absence'][] = [
          'from' => $ad_from,
          'to' => $ad_from + $ad_invalid_absence * 60,
        ];
      }
    }

    $uids = $this->entityTypeManager->getStorage('user')->getQuery()
      ->accessCheck(FALSE)
      ->condition('roles', 'student')
      ->execute();

    foreach ($uids as $uid) {
      $data[$uid] = $this->getAttendanceStatisticsDefault();
      $data[$uid]['user_grade'] = $this->userMetaDataService->getUserGrade($uid, $from);
      $school_week = $this->getSchoolWeek($uid, $from);
      if (!$school_week) {
        continue;
      }

      $data[$uid]['adapted_studies'] = $this->isAdaptedStudies($school_week);

      $key_points = $base_key_points;
      $lessons_data = [];

      foreach ($days as $day_string => $day) {
        $school_day_info = $school_week->getSchoolDayInfo($day);

        $student_course_lessons = !empty($course_lessons[$uid][$day_string]) ? $course_lessons[$uid][$day_string] : [];

        [$lessons, $breaks, $school_day_length] = $this->getStudentSchema($day, $school_day_info, $student_course_lessons);

        $lessons_data[$day_string] = [
          'lessons' => $lessons,
          'breaks' => $breaks,
          'length' => $school_day_length,
        ];

        foreach ($lessons as $lesson) {
          $key_points[] = $lesson['from'];
          $key_points[] = $lesson['to'];
        }
      }

      if (!empty($absence_day_data[$uid])) {
        foreach ($absence_day_data[$uid] as $absence_day_types) {
          foreach ($absence_day_types as $absence_day) {
            $ad_from = $absence_day['from'];
            $ad_to = $absence_day['to'];

            $key_points[] = $ad_from;
            $key_points[] = $ad_to;
          }
        }
      }

      if (!empty($course_attendance_data[$uid])) {
        foreach ($course_attendance_data[$uid] as $course_attendance_types) {
          foreach ($course_attendance_types as $course_attendance) {
            $ad_from = $course_attendance['from'];
            $ad_to = $course_attendance['to'];

            $key_points[] = $ad_from;
            $key_points[] = $ad_to;
          }
        }
      }

      $key_points = array_unique($key_points);
      sort($key_points);

      foreach ($key_points as $index => $k_from) {
        if (!isset($key_points[$index + 1])) {
          continue;
        }
        $k_to = $key_points[$index + 1];

        $from_object = (new \DateTime())->setTimestamp($k_from);
        $day_index = $from_object->format('N');
        $day_string = $from_object->format('Y-m-d');

        if (empty($lessons_data[$day_string]['lessons'])) {
          continue;
        }

        foreach ($lessons_data[$day_string]['breaks'] as $break) {
          if ($k_from >= $break['from'] && $k_to <= $break['to']) {
            continue 2;
          }
        }

        $length = $k_to - $k_from;

        if (empty($data[$uid]['per_day'][$day_string])) {
          $data[$uid]['per_day'][$day_string] = $this->getAttendanceStatisticsDefault(TRUE);
        }

        // Check for leave absence.
        if (!empty($absence_day_data[$uid]['leave_absence'])) {
          foreach ($absence_day_data[$uid]['leave_absence'] as $absence_day) {
            if ($k_from >= $absence_day['from'] && $k_to <= $absence_day['to']) {
              $data[$uid]['leave_absence'] += $length;
              $data[$uid]['leave_absence_' . $day_index] += $length;
              $data[$uid]['per_day'][$day_string]['leave_absence'] += $length;
              continue 2;
            }
          }
        }

        // Check for reported absence.
        if (!empty($absence_day_data[$uid]['reported_absence'])) {
          foreach ($absence_day_data[$uid]['reported_absence'] as $absence_day) {
            if ($k_from >= $absence_day['from'] && $k_to <= $absence_day['to']) {
              $data[$uid]['reported_absence'] += $length;
              $data[$uid]['reported_absence_' . $day_index] += $length;
              $data[$uid]['per_day'][$day_string]['reported_absence'] += $length;
              continue 2;
            }
          }
        }

        // Check for valid absence.
        if (!empty($course_attendance_data[$uid]['valid_absence'])) {
          foreach ($course_attendance_data[$uid]['valid_absence'] as $valid_absence) {
            if ($k_from >= $valid_absence['from'] && $k_to <= $valid_absence['to']) {
              $data[$uid]['valid_absence'] += $length;
              $data[$uid]['valid_absence_' . $day_index] += $length;
              $data[$uid]['per_day'][$day_string]['valid_absence'] += $length;
              continue 2;
            }
          }
        }

        // Check for invalid absence.
        if (!empty($course_attendance_data[$uid]['invalid_absence'])) {
          foreach ($course_attendance_data[$uid]['invalid_absence'] as $invalid_absence) {
            if ($k_from >= $invalid_absence['from'] && $k_to <= $invalid_absence['to']) {
              $data[$uid]['invalid_absence'] += $length;
              $data[$uid]['invalid_absence_' . $day_index] += $length;
              $data[$uid]['per_day'][$day_string]['invalid_absence'] += $length;
              continue 2;
            }
          }
        }

        // Fallback to attended.
        $data[$uid]['attended'] += $length;
        $data[$uid]['attended_' . $day_index] += $length;
        $data[$uid]['per_day'][$day_string]['attended'] += $length;
      }

      // Calculate totals.
      $sum_part_keys = [
        'attended',
        'reported_absence',
        'leave_absence',
        'valid_absence',
        'invalid_absence',
      ];
      foreach ($sum_part_keys as $sum_part_key) {
        $data[$uid]['total'] += $data[$uid][$sum_part_key];

        for ($day_key = 1; $day_key <= 7; $day_key++) {
          $data[$uid]['total_' . $day_key] += $data[$uid][$sum_part_key . '_' . $day_key];
        }

        foreach ($data[$uid]['per_day'] as $day_string => $per_day_data) {
          $data[$uid]['per_day'][$day_string]['total'] += $per_day_data[$sum_part_key];
        }
      }
    }

    $cache_tags = [
      'school_week_list',
      'node_list:day_absence',
      'node_list:course_attendance_report',
      'school_week_deviation_list',
      'ssr_school_week_per_grade',
    ];
    $this->cache->set($cid, $data, CacheBackendInterface::CACHE_PERMANENT, $cache_tags);
    $this->lookup[$cid] = $data;

    return $data;
  }

  protected function getAttendanceStatisticsDefault(bool $day_item = FALSE): array {
    $data = [
      'total' => 0,
      'attended' => 0,
      'reported_absence' => 0,
      'leave_absence' => 0,
      'valid_absence' => 0,
      'invalid_absence' => 0,
      'user_grade' => NULL,
      'adapted_studies' => FALSE,
      'per_day' => [],
    ];

    if ($day_item) {
      unset($data['per_day']);
      return $data;
    }

    for ($day_key = 1; $day_key <= 7; $day_key++) {
      $data['total_' . $day_key] = 0;
      $data['attended_' . $day_key] = 0;
      $data['reported_absence_' . $day_key] = 0;
      $data['leave_absence_' . $day_key] = 0;
      $data['valid_absence_' . $day_key] = 0;
      $data['invalid_absence_' . $day_key] = 0;
    }

    return $data;
  }

  public function getAttendanceStatisticsViewsSortSource(\DateTime $from, \DateTime $to): array {
    $cid = 'asvss:' . $from->format('Y-m-d') . ':' . $to->format('Y-m-d');
    if (array_key_exists($cid, $this->lookup)) {
      return $this->lookup[$cid];
    }

    $data = [];
    foreach ($this->getAttendanceStatisticsAll($from, $to) as $uid => $attendance_data) {
      if ($attendance_data['total'] === 0) {
        continue;
      }

      $attendance = round(($attendance_data['attended'] / $attendance_data['total']) * 1000000);
      $data[$attendance][] = $uid;
    }
    $this->getAttendanceStatisticsAll($from, $to);

    $this->lookup[$cid] = $data;
    return $data;
  }
}
