<?php

namespace Drupal\simple_school_reports_extension_proxy;

trait LessonHandlingTrait {

  public function calculateLessonTotalLength(array &$lessons): int {
    $total_length = 0;
    foreach ($lessons as &$lesson) {
      if (!isset($lesson['from']) || !isset($lesson['to'])) {
        continue;
      }

      $lesson['length'] = $lesson['to'] - $lesson['from'];

      $total_length += $lesson['length'];
    }
    return $total_length;
  }

  public function sortLessons(array $lessons): array {
    usort($lessons, function ($a, $b) {
      return $a['from'] <=> $b['from'];
    });

    return $lessons;
  }

  public function verifyLessonLength(array $lessons, int &$target_length): array {
    if (empty($lessons)) {
      return $lessons;
    }

    $lessons = $this->optimizeLessons($lessons);
    $total_length = $this->calculateLessonTotalLength($lessons);

    // Adjust the target length if the total length is greater than the target
    // length.
    if ($total_length > $target_length) {
      $target_length = $total_length;
    }

    if ($total_length === $target_length) {
      return $lessons;
    }

    $date = date('Y-m-d', $lessons[array_key_first($lessons)]['from']);
    $noon = strtotime($date . ' 12:00:00');

    $length_before_noon = 0;
    foreach ($lessons as $lesson) {
      if ($lesson['from'] > $noon) {
        break;
      }

      if ($lesson['to'] < $noon) {
        $length_before_noon += $lesson['length'];
      }

      if ($lesson['from'] < $noon && $lesson['to'] > $noon) {
        $length_before_noon += $noon - $lesson['from'];
        break;
      }
    }
    $length_after_noon = $total_length - $length_before_noon;

    $length_to_add = $target_length - $total_length;
    $new_length_before_noon = $length_before_noon;
    $new_length_after_noon = $length_after_noon;

    while ($length_to_add > 0) {
      $diff = $new_length_before_noon - $new_length_after_noon;
      if ($diff > 0) {
        $add = min($diff, $length_to_add);
        $new_length_after_noon += $add;
        $length_to_add -= $add;
        continue;
      }
      if ($diff < 0) {
        $diff = abs($diff);
        $add = min($diff, $length_to_add);
        $new_length_before_noon += $add;
        $length_to_add -= $add;
        continue;
      }

      $add = $length_to_add / 2;
      $new_length_before_noon += $add;
      $new_length_after_noon += $add;
      $length_to_add = 0;
    }


    $length_diff_before_noon = $new_length_before_noon - $length_before_noon;
    $length_diff_after_noon = $new_length_after_noon - $length_after_noon;


    if ($length_diff_before_noon > 0) {
      $first_lesson = &$lessons[0];

      $lesson_max_to = isset($lessons[1]) ? $lessons[1]['from'] - 600 : $first_lesson['to'];

      $extend_to = $lesson_max_to - $first_lesson['to'];
      if ($extend_to > 0) {
        $extend_to = min($extend_to, $length_diff_before_noon);
        $first_lesson['to'] += $extend_to;
        $first_lesson['length'] = $first_lesson['to'] - $first_lesson['from'];
        $length_diff_before_noon -= $extend_to;
      }
      if ($length_diff_before_noon > 0) {
        $first_lesson['from'] -= $length_diff_before_noon;
        $first_lesson['length'] = $first_lesson['to'] - $first_lesson['from'];
      }
    }

    if ($length_diff_after_noon > 0) {
      $last_key = array_key_last($lessons);
      $last_lesson = &$lessons[$last_key];

      $lesson_min_from = isset($lessons[$last_key - 1]) ? $lessons[$last_key - 1]['to'] + 600 : $last_lesson['from'];

      $extend_from = $last_lesson['from'] - $lesson_min_from;
      if ($extend_from > 0) {
        $extend_from = min($extend_from, $length_diff_after_noon);
        $last_lesson['from'] -= $extend_from;
        $last_lesson['length'] = $last_lesson['to'] - $last_lesson['from'];
        $length_diff_after_noon -= $extend_from;
      }
      if ($length_diff_after_noon > 0) {
        $last_lesson['to'] += $length_diff_after_noon;
        $last_lesson['length'] = $last_lesson['to'] - $last_lesson['from'];
      }
    }

    return $lessons;
  }

  public function calculateBreaks(int $global_from, int $global_to, array $lessons): array {
    $breaks = [];

    if (!empty($lessons)) {
      $first_key = array_key_first($lessons);
      $last_key = array_key_last($lessons);

      $first_lesson = $lessons[$first_key];
      $last_lesson = $lessons[$last_key];

      $global_from = min($global_from, $first_lesson['from']);
      $global_to = max($global_to, $last_lesson['to']);

      $lessons = $this->sortLessons($lessons);
    }

    $current_from = $global_from;
    foreach ($lessons as $lesson) {
      if ($lesson['from'] > $current_from) {
        $breaks[] = [
          'from' => $current_from,
          'to' => $lesson['from'],
        ];
      }
      $current_from = $lesson['to'];
    }

    if ($current_from < $global_to) {
      $breaks[] = [
        'from' => $current_from,
        'to' => $global_to,
      ];
    }

    $this->calculateLessonTotalLength($breaks);
    return $breaks;
  }

  public function optimizeLessons(array $lessons): array {
    if (count($lessons) <= 1) {
      return $lessons;
    }

    // Analyze the lessons and adjust the overlap.
    $lessons = $this->sortLessons($lessons);

    $new_lessons = [];
    $current_lesson = $lessons[array_key_first($lessons)];

    foreach ($lessons as $lesson) {
      // If the current lesson overlaps with the next lesson, merge them
      if ($current_lesson['to'] >= $lesson['from']) {
        $current_lesson['to'] = max($current_lesson['to'], $lesson['to']);
      } else {
        // If no overlap, push the current lesson to the merged array
        $new_lessons[] = $current_lesson;
        $current_lesson = $lesson;
      }
    }

    // Add the last lesson to the merged array
    $new_lessons[] = $current_lesson;
    return $new_lessons;

  }

}
