<?php

namespace Drupal\simple_school_reports_attendance_analyse\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\simple_school_reports_core\SchoolSubjectHelper;
use Drupal\simple_school_reports_core\Service\UserMetaDataServiceInterface;
use Drupal\simple_school_reports_entities\SchoolWeekInterface;
use Drupal\simple_school_reports_entities\Service\SchoolWeekServiceInterface;

/**
 *
 */
class AttendanceAnalyseService implements AttendanceAnalyseServiceInterface {

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

  protected function getStudentLessons(\DateTime $day, array $school_day_info, array $other_lessons): array {
    if (empty($other_lessons)) {
      return $school_day_info['lessons'];
    }

    $cid_parts = [
      'student_schema',
      $day->format('Y-m-d'),
      json_encode($school_day_info),
      json_encode($other_lessons),
    ];
    $cid = sha1(implode(':', $cid_parts));

    if (isset($this->lookup[$cid])) {
      return $this->lookup[$cid];
    }

    $final_lessons = [];
    $lessons = array_merge($school_day_info['lessons'], $other_lessons);

    $target_length = $school_day_info['length'];
    $reported_length = 0;

    $reported_lessons = [];
    $secondary_lessons = [];
    $dynamic_lessons = [];

    foreach ($lessons as $key => $lesson) {
      if (empty($lesson['from']) || empty($lesson['to']) || empty($lesson['length']) || $lesson['to'] <= $lesson['from'] || empty($lesson['type'])) {
        unset($lessons[$key]);
        continue;
      }

      $type = $lesson['type'];
      if ($type === 'reported') {
        $reported_length += $lesson['length'];
        $reported_lessons[] = $lesson;
      }
      elseif ($type === 'dynamic') {
        $dynamic_lessons[] = $lesson;
      }
      else {
        $secondary_lessons[] = $lesson;
      }
    }

    if ($reported_length >= $target_length) {
      $final_lessons = $reported_lessons;
    }
    else {
      $final_lessons = $reported_lessons;
      // Remove secondary lessons that are fully overlapping or overlapping by
      // part with reported lessons.
      foreach ($secondary_lessons as $key => $lesson) {
        foreach ($reported_lessons as $reported_lesson) {
          if ($lesson['to'] <= $reported_lesson['from'] || $lesson['from'] >= $reported_lesson['to']) {
            $final_lessons[] = $lesson;
            continue;
          }
          unset($secondary_lessons[$key]);
        }
      }

      $current_length = 0;
      foreach ($final_lessons as $lesson) {
        $current_length += $lesson['length'];
        if ($current_length >= $target_length) {
          break;
        }
      }
      if ($current_length < $target_length && !empty($dynamic_lessons)) {
        // Adjust the dynamic lessons to fill the gap.
        $target_dynamic_length = floor(($target_length - $current_length) / count($dynamic_lessons));
        foreach ($dynamic_lessons as $lesson) {
          $lesson['length'] = $target_dynamic_length;
          $lesson['to'] = $lesson['from'] + $target_dynamic_length;
          $lesson['attended'] = $target_dynamic_length;
          $final_lessons[] = $lesson;
        }
      }
    }

    // Sort final lessons list by start time.
    usort($final_lessons, function ($a, $b) {
      return $a['from'] <=> $b['from'];
    });

    $this->lookup[$cid] = array_values($final_lessons);
    return $this->lookup[$cid];
  }

  protected function optimizeAbsenceData(array $original_absence_data): array {
    $leave_absences = [];
    $reported_absences = [];

    $key_points = [];
    foreach ($original_absence_data as $absence) {
      $key_points[] = $absence['from'];
      $key_points[] = $absence['to'];
      if ($absence['type'] === 'leave') {
        $leave_absences[] = $absence;
      }
      else {
        $reported_absences[] = $absence;
      }
    }

    sort($key_points);

    $absence_parts = [];

    foreach ($key_points as $index => $k_from) {
      if (!isset($key_points[$index + 1])) {
        continue;
      }
      $k_to = $key_points[$index + 1];

      // Check for leave absence that has higher prio.
      foreach ($leave_absences as $absence_day) {
        if ($k_from >= $absence_day['from'] && $k_to <= $absence_day['to']) {
          $absence_parts[] = [
            'from' => $k_from,
            'to' => $k_to,
            'type' => 'leave',
          ];
          continue 2;
        }
      }

      // Check for reported absence.
      foreach ($reported_absences as $absence_day) {
        if ($k_from >= $absence_day['from'] && $k_to <= $absence_day['to']) {
          $absence_parts[] = [
            'from' => $k_from,
            'to' => $k_to,
            'type' => 'reported',
          ];
          continue 2;
        }
      }
    }

    return $absence_parts;
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

    $days = [];
    $day = clone $from;
    while ($day <= $to) {
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

      if ($ad_from === $ad_to) {
        continue;
      }

      if ($ad_from > $ad_to) {
        $temp = $ad_from;
        $ad_from = $ad_to;
        $ad_to = $temp;
      }

      $ad_from_date_string = (new \DateTime())->setTimestamp($ad_from)->format('Y-m-d');
      $ad_to_date_string = (new \DateTime())->setTimestamp($ad_to)->format('Y-m-d');

      if ($ad_from_date_string !== $ad_to_date_string) {
        $absence_day_data[$ad_uid]['multi'][] = [
          'from' => $ad_from,
          'to' => $ad_to,
          'type' => $ad_type,
        ];
        continue;
      }

      $absence_day_data[$ad_uid][$ad_from_date_string][] = [
        'from' => $ad_from,
        'to' => $ad_to,
        'type' => $ad_type,
      ];
    }

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
      ->fields('ce', ['field_class_end_value'])
      ->fields('sub', ['field_subject_target_id']);
    $results = $query->execute();

    foreach ($results as $result) {
      $ad_uid = $result->field_student_target_id;
      $ad_from = $result->field_class_start_value;
      $ad_to = $result->field_class_end_value;
      $ad_type = $result->field_attendance_type_value;
      $ad_invalid_absence = ($result->field_invalid_absence_value ?? 0) * 60;
      $subject_id = $result->field_subject_target_id;

      if ($ad_from === $ad_to) {
        continue;
      }

      if ($ad_from > $ad_to) {
        $temp = $ad_from;
        $ad_from = $ad_to;
        $ad_to = $temp;
      }

      $subject_short_name = SchoolSubjectHelper::getSubjectShortName($subject_id);

      // Special treatment for CBT (Bonustimme).
      if ($subject_short_name === 'CBT') {
        if ($ad_type !== 'attending' || $ad_invalid_absence >= 0) {
          continue;
        }
        $attended = abs($ad_invalid_absence);
        $ad_to = $ad_from + $attended;
        $ad_invalid_absence = 0;
      }

      $day = (new \DateTime())->setTimestamp($ad_from)->format('Y-m-d');
      $course_lesson_length = abs($ad_to - $ad_from);

      $course_lesson_attending = 0;
      $course_lesson_invalid_absence = $ad_type === 'invalid_absence' ? $course_lesson_length : 0;
      $course_lesson_valid_absence = $ad_type === 'valid_absence' ? $course_lesson_length : 0;

      if ($ad_type === 'attending') {
        $course_lesson_valid_absence = 0;
        $course_lesson_invalid_absence = $ad_invalid_absence;
        $course_lesson_attending = max(0, $course_lesson_length - $course_lesson_invalid_absence);
      }

      $course_lessons[$ad_uid][$day][] = [
        'from' => $ad_from,
        'to' => $ad_to,
        'type' => 'reported',
        'subject' => $subject_short_name,
        'length' => $course_lesson_length,
        'attended' => $course_lesson_attending,
        'reported_absence' => 0,
        'leave_absence' => 0,
        'valid_absence' => $course_lesson_valid_absence,
        'invalid_absence' => $course_lesson_invalid_absence,
      ];
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

      $lessons_data = [];

      foreach ($days as $day_string => $day) {
        $include_base_lessons = TRUE;
        $school_day_info = $school_week->getSchoolDayInfo($day, $include_base_lessons);

        $student_course_lessons = !empty($course_lessons[$uid][$day_string]) ? $course_lessons[$uid][$day_string] : [];

        $lessons = $this->getStudentLessons($day, $school_day_info, $student_course_lessons);
        $lessons_data[$day_string] = $lessons;
      }

      foreach ($lessons_data as $day_string => $lessons) {
        if (empty($data[$uid]['per_day'][$day_string])) {
          $data[$uid]['per_day'][$day_string] = $this->getAttendanceStatisticsDefault(TRUE);
        }

        $day_object = $days[$day_string];
        $day_index = $day_object->format('N');

        // Calculate statistics.
        $absence = array_merge($absence_day_data[$uid][$day_string] ?? [], $absence_day_data[$uid]['multi'] ?? []);
        $absence = $this->optimizeAbsenceData($absence);

        foreach ($lessons as $lesson) {
          $length = $lesson['length'];

          $lesson_from = $lesson['from'];
          $lesson_to = $lesson['to'];

          $attended = &$lesson['attended'];
          $reported_absence = &$lesson['reported_absence'];
          $leave_absence = &$lesson['leave_absence'];
          $valid_absence = &$lesson['valid_absence'];
          $invalid_absence = &$lesson['invalid_absence'];

          foreach ($absence as $absence_day) {
            $absence_from = $absence_day['from'];
            $absence_to = $absence_day['to'];

            // Ignore absence that outside of lesson time.
            if ($lesson_from >= $absence_to || $lesson_to <= $absence_from) {
              continue;
            }

            // If absence is fully covering the lesson.
            if ($absence_from <= $lesson_from && $absence_to >= $lesson_to) {
              if ($absence_day['type'] === 'leave') {
                $leave_absence += $length;
              }
              else {
                $reported_absence += $length;
              }

              $attended = 0;
              $reported_absence = $absence_day['type'] !== 'leave' ? $length : 0;
              $leave_absence = $absence_day['type'] === 'leave' ? $length : 0;
              $valid_absence = 0;
              $invalid_absence = 0;
              break;
            }

            $overlap_from = max($lesson_from, $absence_from);
            $overlap_to = min($lesson_to, $absence_to);
            $overlap_length = $overlap_to - $overlap_from;

            if ($absence_day['type'] === 'leave') {
              $leave_absence += $overlap_length;
            }
            else {
              $reported_absence += $overlap_length;
            }

            $attended -= $overlap_length;
            $diff = 0;
            if ($attended < 0) {
              $diff = abs($attended);
              $attended = 0;
            }

            if ($invalid_absence >= 0) {
              $reduce = min($diff, $invalid_absence);
              $invalid_absence -= $reduce;
              $diff -= $reduce;
            }

            if ($diff > 0 && $valid_absence > 0) {
              $reduce = min($diff, $valid_absence);
              $valid_absence -= $reduce;
            }

            if ($attended === 0 && $invalid_absence === 0 && $valid_absence === 0) {
              break;
            }
          }

          $data[$uid]['per_day'][$day_string]['lessons'][] = $lesson;

          // Fill the parts.
          $data[$uid]['attended'] += $attended;
          $data[$uid]['attended_' . $day_index] += $attended;
          $data[$uid]['per_day'][$day_string]['attended'] += $attended;

          $data[$uid]['reported_absence'] += $reported_absence;
          $data[$uid]['reported_absence_' . $day_index] += $reported_absence;
          $data[$uid]['per_day'][$day_string]['reported_absence'] += $reported_absence;

          $data[$uid]['leave_absence'] += $leave_absence;
          $data[$uid]['leave_absence_' . $day_index] += $leave_absence;
          $data[$uid]['per_day'][$day_string]['leave_absence'] += $leave_absence;

          $data[$uid]['valid_absence'] += $valid_absence;
          $data[$uid]['valid_absence_' . $day_index] += $valid_absence;
          $data[$uid]['per_day'][$day_string]['valid_absence'] += $valid_absence;

          $data[$uid]['invalid_absence'] += $invalid_absence;
          $data[$uid]['invalid_absence_' . $day_index] += $invalid_absence;
          $data[$uid]['per_day'][$day_string]['invalid_absence'] += $invalid_absence;
        }
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
      $data['lessons'] = [];
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
