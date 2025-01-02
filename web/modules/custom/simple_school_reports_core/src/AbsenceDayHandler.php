<?php

namespace Drupal\simple_school_reports_core;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\node\NodeInterface;

class AbsenceDayHandler {

  public static function createAbsenceDayNode($field_values) {
    $field_values['type'] = 'day_absence';
    /** @var \Drupal\node\NodeStorageInterface $node_storage */
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');

    $hash = self::getHash($field_values);

    $nid = current($node_storage->getQuery()->condition('uuid', $hash . '---', 'STARTS_WITH')->accessCheck(FALSE)->execute());
    if ($nid) {
      $node = $node_storage->load($nid);
      $node->set('field_absence_type', $field_values['field_absence_type'] ?: 'reported');
    }
    else {
      $node = $node_storage->create($field_values);
    }

    $node->setPublished();
    $node->save();
  }

  public static function preSave(NodeInterface $absence_day) {
    $uuid_service = \Drupal::service('uuid');
    $uuid = $uuid_service->generate();

    $uid = $absence_day->get('field_student')->target_id;

    $values = [
      'field_student' => ['target_id' => $uid],
      'field_absence_from' => $absence_day->get('field_absence_from')->value,
      'field_absence_to' => $absence_day->get('field_absence_to')->value,
    ];

    Cache::invalidateTags(['user:' . $uid]);

    $absence_day->set('uuid', self::getHash($values) . '---' . $uuid);

    // Double checks.
    if ($values['field_absence_from'] > $values['field_absence_to']) {
      throw new \RuntimeException('invalid data: field_absence_from > field_absence_to');
    }

    $date_from = new DrupalDateTime();
    $date_from->setTimestamp($values['field_absence_from']);

    $date_to = new DrupalDateTime();
    $date_to->setTimestamp($values['field_absence_to']);

    if ($date_from->format('Y-m-d') !== $date_to->format('Y-m-d')) {
      throw new \RuntimeException('invalid data: field_absence_from date != field_absence_to date');
    }

    Cache::invalidateTags([
      'node_list:day_absence:' . $date_from->format('Y-m-d'),
      'node_list:day_absence:' . $uid,
      'node_list:day_absence:' . $date_from->format('Y-m-d') . ':' . $uid,
    ]);

    $invalid_attendance_reports = CourseAttendanceReportFormAlter::getStudentCourseAttendanceReports([$absence_day->get('field_student')->target_id], $values['field_absence_from'], $values['field_absence_to'], TRUE);
    if (!empty($invalid_attendance_reports)) {
      /** @var \Drupal\paragraphs\ParagraphInterface $attendance_report */
      foreach ($invalid_attendance_reports as $attendance_report) {
        $attendance_report->setNewRevision(FALSE);
        $attendance_report->set('field_attendance_type', 'valid_absence');
        $attendance_report->set('field_invalid_absence', 0);
        $attendance_report->save();
      }
    }
  }

  public static function delete(NodeInterface $absence_day) {
    // Check if there is other absence nodes already for this user. In that
    // case we won't put back original invalid absence report.
    $values = [
      'field_student' => $absence_day->get('field_student')->target_id,
      'field_absence_from' => $absence_day->get('field_absence_from')->value,
      'field_absence_to' => $absence_day->get('field_absence_to')->value,
    ];

    $attendance_reports = CourseAttendanceReportFormAlter::getStudentCourseAttendanceReports([$values['field_student']], $values['field_absence_from'], $values['field_absence_to'], FALSE, TRUE);

    if (!empty($attendance_reports)) {
      /** @var \Drupal\paragraphs\ParagraphInterface $attendance_report */
      foreach ($attendance_reports as $attendance_report) {
        $report_node =  $attendance_report->getParentEntity();
        if ($report_node && $report_node->bundle() === 'course_attendance_report' && $report_node->get('field_duration')->value === $attendance_report->get('field_invalid_absence_original')->value) {
          if (empty(self::getAbsenceNodesFromPeriod([$values['field_student']], $report_node->get('field_class_start')->value, $report_node->get('field_class_end')->value, TRUE))) {
            $attendance_report->setNewRevision(FALSE);
            $attendance_report->set('field_attendance_type', 'invalid_absence');
            $attendance_report->set('field_invalid_absence', $attendance_report->get('field_invalid_absence_original')->value);
            $attendance_report->save();
          }
        }
      }
    }
    $uid = $absence_day->get('field_student')->target_id;
    $date_from = new DrupalDateTime();
    $date_from->setTimestamp($values['field_absence_from']);
    Cache::invalidateTags([
      'node_list:day_absence:' . $date_from->format('Y-m-d'),
      'node_list:day_absence:' . $uid,
      'node_list:day_absence:' . $date_from->format('Y-m-d') . ':' . $uid,
    ]);
  }

  public static function getHash(array $values) {
    $source = !empty($values['field_student']['target_id']) ? $values['field_student']['target_id'] : '';
    $source .= ':';
    $source .= !empty($values['field_absence_from']) ? $values['field_absence_from'] : '';
    $source .= ':';
    $source .= !empty($values['field_absence_to']) ? $values['field_absence_to'] : '';
    return sha1($source);
  }

  public static function getAbsenceNodesFromPeriod(array $uids, int $filter_from, int $filter_to, $only_nids = FALSE) {
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');

    $absence_nids = $node_storage->getQuery()
      ->condition('type', 'day_absence')
      ->condition('field_student', $uids, 'IN')
      ->condition('field_absence_from', $filter_to, '<')
      ->condition('field_absence_to', $filter_from, '>')
      ->accessCheck(FALSE)
      ->execute();

    if (!empty($absence_nids)) {
      return $only_nids ? $absence_nids : $node_storage->loadMultiple($absence_nids);
    }

    return [];
  }

}
