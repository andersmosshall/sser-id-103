<?php

namespace Drupal\simple_school_reports_absence_make_up;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use Drupal\simple_school_reports_core\CourseAttendanceReportFormAlter;

class CourseAttendanceReportAbsenceMakeupFormAlter {

  public static function formStepTwo(&$form, FormStateInterface $form_state) {

    $node = CourseAttendanceReportFormAlter::courseAttendanceReportNode($form_state);
    $duration = 0;
    if ($node->isNew()) {
      $step1_values = $form_state->get('step1_values', []);
      $duration = $step1_values['field_duration'];
    }
    else {
      $duration = $node->get('field_duration')->value;
    }

    $form['report'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['report-wrapper'],
      ],
      '#weight' => 999,
    ];

    $course_students = CourseAttendanceReportFormAlter::getCourseStudents($form_state);

    if (!empty($course_students)) {
      $form['report']['label'] = [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#attributes' => [
          'class' => ['header'],
        ],
        '#value' => t('Student attendance'),
      ];

      $messages = [];
      $messages['warning'][] = t('Note that you in this report check which students that are attending and for how long they were attending. No mails is sent to caregivers. No invalid absence is registered for this class.');

      $form['report']['description'] = [
        '#theme' => 'status_messages',
        '#message_list' => $messages,
        '#status_headings' => [
          'status' => t('Status message'),
          'error' => t('Error message'),
          'warning' => t('Warning message'),
        ],
      ];
    }

    foreach ($course_students as $id => $data) {

      /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
      $paragraph = $data['paragraph'];

      /** @var \Drupal\user\UserInterface $student */
      $student = $data['user'];
      $form['report'][$id]['student'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['student-row']],
      ];

      $form['report'][$id]['student']['info'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['student-row--info-wrapper'],
        ],
      ];

      $form['report'][$id]['student']['info']['name'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['student-row--info-name'],
        ],
        'value' => [
          '#prefix' => '<b>',
          '#suffix' => '</b>',
          '#markup' => $student->getDisplayName(),
        ],
      ];

      if ($data['absence']) {
        $form['report'][$id]['student']['info']['absence'] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['student-row--info-absence'],
          ],
          'value' => [
            '#markup' => $data['absence'],
          ],
        ];
      }

      $form['report'][$id]['student']['report'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['student-row--report-wrapper'],
        ],
      ];

      $form['report'][$id]['student']['report']['attendance_info'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['student-row--report-wrapper--attendance-info'],
        ],
      ];


      $default_attending = FALSE;
      if ($paragraph && $paragraph->get('field_attendance_type')->value === 'attending') {
        $default_attending = TRUE;
      }

      $form['report'][$id]['student']['report']['attendance_info']['attending_' . $id] = [
        '#type' => 'checkbox',
        '#title' => t('Attending'),
        '#default_value' => $default_attending,
      ];


      $form['report'][$id]['student']['report']['attending_time'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['student-row--report-wrapper--absence-time'],
        ],
        '#states' => [
          'visible' => [
            ':input[name="attending_' . $id . '"]' => [
              'checked' => TRUE,
            ],
          ],
        ],
      ];

      $attending_time_default = $duration;
      if ($paragraph && $paragraph->get('field_invalid_absence')->value) {
        $attending_time_default = abs($paragraph->get('field_invalid_absence')->value);
      }

      $form['report'][$id]['student']['report']['attending_time']['attending_time_' . $id] = [
        '#title' => t('Attending time in minutes'),
        '#type' => 'number',
        '#min' => 0,
        '#max' => $duration,
        '#default_value' => $attending_time_default,
      ];
    }

    if (empty($form['actions']['submit']['#submit'])) {
      $form['actions']['submit']['#submit'] = [];
    }

    array_unshift($form['actions']['submit']['#submit'], [
      self::class,
      'submitStepTwo',
    ]);
  }

  public static function submitStepTwo(&$form, FormStateInterface $form_state) {
    $connection = \Drupal::service('database');
    $transaction = $connection->startTransaction();
    try {
      $start_time = 0;
      $duration = 0;
      $course = CourseAttendanceReportFormAlter::getCourseNode($form_state);

      /** @var NodeInterface $node */
      $node = CourseAttendanceReportFormAlter::courseAttendanceReportNode($form_state);
      if ($node->isNew()) {
        $step1_values = $form_state->get('step1_values', []);
        $node->set('field_course', $course);
        $node->set('field_duration', $step1_values['field_duration']);
        $node->set('field_class_start', $step1_values['field_class_start']);
        $start_time = isset($step1_values['field_class_start']) ? $step1_values['field_class_start']->getTimestamp() : NULL;
        $duration = $step1_values['field_duration'];
      }
      else {
        if ($node->get('field_duration')->value) {
          $duration = $node->get('field_duration')->value;
        }
        if ($node->get('field_class_start')->value) {
          $start_time = $node->get('field_class_start')->value;
        }
      }

      if ($start_time && $duration) {
        $node->set('field_class_end', $start_time + $duration * 60);
      }

      $course_students = CourseAttendanceReportFormAlter::getCourseStudents($form_state);
      /** @var \Drupal\Core\Entity\EntityStorageInterface $paragraph_storage */
      $paragraph_storage = \Drupal::entityTypeManager()
        ->getStorage('paragraph');

      $paragraphs = [];

      foreach ($course_students as $id => $data) {
        if (!empty($data['paragraph'])) {
          /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
          $paragraph = $data['paragraph'];
        }
        else {
          /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
          $paragraph = $paragraph_storage->create([
            'type' => 'student_course_attendance',
            'langcode' => 'sv',
          ]);
        }

        $attending = $form_state->getValue('attending_' . $id, TRUE);
        $attending_time = $form_state->getValue('attending_time_' . $id, FALSE);

        // Skip if not attending.
        if (!$attending || !$attending_time) {
          if (!$paragraph->isNew()) {
            $paragraph->delete();
          }
          continue;
        }

        $paragraph->set('field_attendance_type', 'attending');
        $invalid_absence = $attending_time * -1;
        $paragraph->set('field_invalid_absence', $invalid_absence);
        $paragraph->set('field_invalid_absence_original', $invalid_absence);
        $paragraph->set('field_student', $data['user']);
        $paragraph->set('field_subject', $course->get('field_school_subject')->target_id);
        $paragraph->setNewRevision(FALSE);

        if (!$paragraph->isNew()) {
          $paragraph->save();
        }
        $paragraphs[] = $paragraph;
      }

      $node->set('field_student_course_attendance', $paragraphs);
      $form_state->set('step', 2);
    }
    catch (\Exception $e) {
      $transaction->rollBack();
      throw $e;
    }
  }

}
