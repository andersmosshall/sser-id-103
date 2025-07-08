<?php

namespace Drupal\simple_school_reports_core;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\simple_school_reports_core\Service\EmailService;
use Drupal\simple_school_reports_core\Service\ReplaceTokenServiceInterface;
use Drupal\simple_school_reports_maillog\SsrMaillogInterface;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CourseAttendanceReportFormAlter {

  public static function nodeFormAlter(&$form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'simple_school_reports_core/attendance_report';
    $form['#attributes']['class'][] = 'attendance-report-form';
    unset($form['field_class_start']['widget'][0]['value']['#description']);
    $form['field_class_start']['widget'][0]['value']['#date_increment'] = 60;
    $node = self::courseAttendanceReportNode($form_state);

    if (!empty($form['field_course_sub_group']['widget'][0]['value'])) {

      $sub_group_options = [
        'default' => t('Full group'),
      ];

      $default_value = !empty($form['field_course_sub_group']['widget'][0]['value']['#default_value']) ? $form['field_course_sub_group']['widget'][0]['value']['#default_value'] : 'default';
      if ($default_value !== 'default') {
        $sub_group_options[$default_value] = '?';
      }

      $course = self::getCourseNode($form_state);
      if ($course && !$course->get('field_ssr_schema')->isEmpty()) {
        /** @var \Drupal\simple_school_reports_core\Service\CourseServiceInterface $course_service */
        $course_service = \Drupal::service('simple_school_reports_core.course_service');

        /** @var \Drupal\simple_school_reports_schema_support\SSRSchemaEntryInterface $ssr_schema_entry */
        foreach ($course->get('field_ssr_schema')->referencedEntities() as $ssr_schema_entry) {
          if (!$ssr_schema_entry->get('deviated')->value) {
            continue;
          }

          $relevant_groups = $ssr_schema_entry->get('relevant_groups')->value;

          if ($relevant_groups <= 0) {
            continue;
          }

          for ($i = 1; $i <= $relevant_groups; $i++) {
            if (!$ssr_schema_entry->hasField('display_name_' . $i)) {
              continue;
            }

            $sub_group_id = $ssr_schema_entry->id() . ':' . $i;
            $students = $course_service->getStudentIdsInCourse($course->id(), $sub_group_id);
            if (!empty($students) || $sub_group_id === $default_value) {
              $sub_group_options[$sub_group_id] = $ssr_schema_entry->get('display_name_' . $i)->value;
            }
          }
        }
      }

      if (count($sub_group_options) > 1) {
        $form['field_course_sub_group']['widget'][0]['value']['#type'] = 'select';
        unset($form['field_course_sub_group']['widget'][0]['value']['#size']);
        $form['field_course_sub_group']['widget'][0]['value']['#options'] = $sub_group_options;
      }
      else {
        $form['field_course_sub_group']['widget'][0]['value']['#type'] = 'hidden';
        unset($form['field_course_sub_group']['widget'][0]['value']['#size']);
        $form['field_course_sub_group']['widget'][0]['value']['#value'] = array_keys($sub_group_options)[0];
      }
    }

    if ($form_state->has('step') && $form_state->get('step') === 2) {
      self::formStepTwo($form, $form_state, $node);
      return;
    }

    if (!$node->isNew()) {
      self::formStepTwo($form, $form_state, $node);
      return;
    }

    $form_state->set('step', 1);
    self::formStepOne($form, $form_state, $node);
  }

  public static function courseAttendanceReportNode(FormStateInterface $form_state) {
    $form_object = $form_state->getFormObject();
    if ($form_object instanceof EntityFormInterface) {
      return $form_object->getEntity();
    }
    return NULL;
  }

  public static function getCourseNode(FormStateInterface $form_state) {
    if (!$form_state->has('course_node')) {
      $course = NULL;

      $node = self::courseAttendanceReportNode($form_state);
      if ($node && !$node->isNew() && !$node->get('field_course')->isEmpty()) {
        $nid = $node->get('field_course')->target_id;
      }
      else {
        $nid = \Drupal::request()->get('course_id');
      }

      if ($nid) {
        /** @var \Drupal\node\NodeStorageInterface $node_storage */
        $node_storage = \Drupal::entityTypeManager()->getStorage('node');
        $course = $node_storage->load($nid);
      }

      $form_state->set('course_node', $course);
    }
    else {
      $course = $form_state->get('course_node');
    }

    if (!($course instanceof NodeInterface && $course->bundle() === 'course' && $course->access('update'))) {
      throw new AccessDeniedHttpException();
    }

    return $course;
  }

  public static function getCourseStudents(FormStateInterface $form_state, string $field_course_sub_group = 'default') {
    if (!$form_state->has('course_students')) {
      /** @var NodeInterface $course */
      $course = self::getCourseNode($form_state);
      $course_students = [];

      /** @var \Drupal\simple_school_reports_core\Service\CourseServiceInterface $course_service */
      $course_service = \Drupal::service('simple_school_reports_core.course_service');
      $student_ids = $course_service->getStudentIdsInCourse($course->id(), $field_course_sub_group);

      if (!empty($student_ids)) {
        $students = \Drupal::entityTypeManager()->getStorage('user')->loadMultiple($student_ids);
        foreach ($students as $student) {
          $course_students[$student->id()] =  [
            'user' => $student,
            'paragraph' => NULL,
            'absence' => NULL,
            'set_valid_absence' => FALSE,
          ];
        }
      }

      $node = self::courseAttendanceReportNode($form_state);

      if (!$node->get('field_student_course_attendance')->isEmpty()) {
        foreach ($node->get('field_student_course_attendance')->referencedEntities() as $paragraph) {
          if ($student = $paragraph->get('field_student')->entity) {
            $user_id = $student->id();
            $course_students[$user_id]['student'] = $student;
            $course_students[$user_id]['paragraph'] = $paragraph;
          }
        }
      }

      if ($node->isNew()) {
        $step1_values = $form_state->get('step1_values', []);
        $start_time = isset($step1_values['field_class_start']) ? $step1_values['field_class_start']->getTimestamp() : NULL;
        $duration = isset($step1_values['field_duration']) ? $step1_values['field_duration'] : NULL;
      }
      else {
        $start_time = $node->get('field_class_start')->value;
        $duration = $node->get('field_duration')->value;
      }

      if ($start_time && $duration) {
        $end_time = $start_time + $duration * 60;
        foreach (AbsenceDayHandler::getAbsenceNodesFromPeriod(array_keys($course_students), $start_time, $end_time) as $absence_node) {
          if (!$absence_node->get('field_absence_from')
              ->isEmpty() && !$absence_node->get('field_absence_to')
              ->isEmpty()) {
            $from_date = new DrupalDateTime();
            $from_date->setTimestamp($absence_node->get('field_absence_from')->value);
            $to_date = new DrupalDateTime();
            $to_date->setTimestamp($absence_node->get('field_absence_to')->value);
            $course_students[$absence_node->get('field_student')->target_id]['absence'] = t('Reported absence (@date)', ['@date' => $from_date->format('Y-m-d H:i') . ' - ' . $to_date->format('H:i')]);
            $course_students[$absence_node->get('field_student')->target_id]['set_valid_absence'] = $to_date->getTimestamp() - $from_date->getTimestamp() > $duration * 60;
          }
        }


        // Check for adapted studies.
        foreach ($course_students as $student_id => $student_data) {
          $student = $student_data['user'];
          /** @var \Drupal\simple_school_reports_entities\SchoolWeekInterface|null $school_week */
          $school_week = $student->get('field_adapted_studies')->entity;
          if ($school_week) {
            $start_date = (new \DateTime())->setTimestamp($start_time);

            $school_day_info = $school_week->getSchoolDayInfo($start_date);

            if ($school_day_info['length'] === 0) {
              $course_students[$student_id]['absence'] = t('Adapted studies');
              $course_students[$student_id]['set_valid_absence'] = TRUE;
            }
            else {
              $school_day_from = $school_day_info['from'] ?? 0;
              $school_day_to = $school_day_info['to'] ?? 0;

              if ($school_day_to <= $start_time || $school_day_from >= $end_time) {
                $course_students[$student_id]['absence'] = t('Adapted studies');
                $course_students[$student_id]['set_valid_absence'] = TRUE;
              }
              elseif ($school_day_from < $start_time && $school_day_to > $end_time) {
                // Do nothing, this is an ordinary school day in the sence of
                // this report.
              }
              elseif (empty($course_students[$student_id]['absence'])) {
                $course_students[$student_id]['absence'] = t('Adapted studies (@date)', ['@date' => (new \DateTime())->setTimestamp($school_day_from)->format('H:i') . ' - ' . (new \DateTime())->setTimestamp($school_day_to)->format('H:i')]);
              }
            }
          }
        }
      }

      $form_state->set('course_students', $course_students);
    }
    return $form_state->get('course_students');
  }

  public static function formStepOne(&$form, FormStateInterface $form_state, NodeInterface $node) {
    $form['field_course']['widget'][0]['target_id']['#disabled'] = TRUE;

    $course = self::getCourseNode($form_state);
    $form['field_course']['widget'][0]['target_id']['#default_value'] = $course;

    $class_start_default = NULL;
    $duration_default = NULL;
    $sub_group_default  = 'default';

    $calendar_event_options = [];
    $today = new \DateTime();
    $today->setTime(23, 59, 59);

    /** @var \Drupal\simple_school_reports_schema_support\Service\SchemaSupportServiceInterface $schema_support_service */
    $schema_support_service = \Drupal::service('simple_school_reports_schema_support.schema_support');

    $suggested_calendar_event_id =  \Drupal::request()->query->get('calendar_event_id');
    if ($suggested_calendar_event_id) {
      /** @var \Drupal\simple_school_reports_entities\CalendarEventInterface $suggested_calendar_event */
      $suggested_calendar_event = \Drupal::entityTypeManager()->getStorage('ssr_calendar_event')->load($suggested_calendar_event_id);
      if ($suggested_calendar_event && $suggested_calendar_event->get('field_course')->target_id == $course->id()) {
        $calendar_event_options[$suggested_calendar_event_id] = $schema_support_service->resolveCalenderEventName($suggested_calendar_event, FALSE);
      }
    }

    if (ssr_use_schema()) {
      $calendar_event_ids = \Drupal::entityTypeManager()->getStorage('ssr_calendar_event')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('bundle', 'course')
        ->condition('status', 1)
        ->condition('cancelled', FALSE)
        ->condition('completed', FALSE)
        ->condition('field_course', $course->id())
        ->condition('from', $today->getTimestamp(), '<')
        ->range(0, 10)
        ->sort('from', 'DESC')
        ->execute();
      foreach ($calendar_event_ids as $calendar_event_id) {
        /** @var \Drupal\simple_school_reports_entities\CalendarEventInterface $calendar_event */
        $calendar_event = \Drupal::entityTypeManager()->getStorage('ssr_calendar_event')->load($calendar_event_id);
        $calendar_event_options[$calendar_event_id] = $schema_support_service->resolveCalenderEventName($calendar_event, FALSE);
      }
    }

    $has_calendar_events = !empty($calendar_event_options);

    if ($has_calendar_events) {
      $field_class_start_weight = $form['field_class_start']['#weight'] ?? 1;

      $form['input_type'] = [
        '#type' => 'radios',
        '#title' => t('Report method'),
        '#options' => [
          'calendar_event' => t('Lesson from schema'),
          'manual' => t('Divergent lesson'),
        ],
        '#default_value' => 'calendar_event',
        '#weight' => $field_class_start_weight - 0.002,
      ];

      $form['calendar_event'] = [
        '#type' => 'select',
        '#title' => t('Lesson'),
        '#options' => $calendar_event_options,
        '#empty_option' => t('Select lesson'),
        '#default_value' => array_key_first($calendar_event_options),
        '#weight' => $field_class_start_weight - 0.001,
      ];

      $form['field_class_start']['widget'][0]['value']['#required'] = FALSE;
      $form['field_duration']['widget'][0]['value']['#required'] = FALSE;

      // Set states depending on input type.
      $form['calendar_event']['#states'] = [
        'visible' => [
          ':input[name="input_type"]' => [
            'value' => 'calendar_event',
          ],
        ],
        'required' => [
          ':input[name="input_type"]' => [
            'value' => 'calendar_event',
          ],
        ],
      ];
      $form['field_class_start']['#states']['visible'] = [
        ':input[name="input_type"]' => [
          'value' => 'manual',
        ],
      ];
      $form['field_class_start']['widget'][0]['value']['#states']['required'] = [
        ':input[name="input_type"]' => [
          'value' => 'manual',
        ],
      ];

      $form['field_duration']['#states']['visible'] = [
        ':input[name="input_type"]' => [
          'value' => 'manual',
        ],
      ];
      $form['field_duration']['widget'][0]['value']['#states']['required'] = [
        ':input[name="input_type"]' => [
          'value' => 'manual',
        ],
      ];

      $form['field_course_sub_group']['#states']['visible'] = [
        ':input[name="input_type"]' => [
          'value' => 'manual',
        ],
      ];
      $form['field_course_sub_group']['widget'][0]['value']['#states']['required'] = [
        ':input[name="input_type"]' => [
          'value' => 'manual',
        ],
      ];
    }

    if (!$has_calendar_events) {
      $form['field_class_start']['widget'][0]['value']['#required'] = TRUE;
      $form['field_duration']['widget'][0]['value']['#required'] = TRUE;
    }

    if (!$has_calendar_events && !$course->get('field_ssr_schema')->isEmpty()) {
      $now = new DrupalDateTime();
      $day = $now->format('N');
      $now_time = $now->getTimestamp();
      $smallest_diff = $now_time;
      $this_day = new \DateTime();
      $this_day->setTime(0, 0, 0);

      /** @var \Drupal\simple_school_reports_schema_support\SSRSchemaEntryInterface $ssr_schema_entry */
      foreach ($course->get('field_ssr_schema')->referencedEntities() as $ssr_schema_entry) {
        if ($ssr_schema_entry->get('week_day')->value !== $day) {
          continue;
        }

        $lesson_start_ts = $this_day->getTimestamp() + $ssr_schema_entry->get('from')->value;
        $lesson_diff = abs($lesson_start_ts - $now_time);
        if ($lesson_diff < $smallest_diff) {
          $smallest_diff = $lesson_diff;
          $start_time = new DrupalDateTime();
          $start_time->setTimestamp($lesson_start_ts);
          $class_start_default = $start_time;
          $duration_default = $ssr_schema_entry->get('length')->value;
          $sub_group_default = 'default';
        }
      }
    }

    $form['field_class_start']['widget'][0]['value']['#default_value'] = $class_start_default;
    $form['field_duration']['widget'][0]['value']['#default_value'] = $duration_default;
    $form['field_course_sub_group']['widget'][0]['value']['#default_value'] = $sub_group_default;
    $form['field_course_sub_group']['widget'][0]['value']['#required'] = TRUE;

    $form['#validate'][] = [self::class, 'validateStepOne'];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => t('Next'),
      '#submit' => [[self::class, 'submitStepOne']],
    ];
  }

  public static function validateOccurance($field_class_start, $field_duration, $field_course_sub_group, $form, FormStateInterface $form_state) {
    $course = self::getCourseNode($form_state);
    /** @var \Drupal\node\NodeStorage $node_storage */
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');

    $nid = current($node_storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('type','course_attendance_report')
      ->condition('field_course', $course->id())
      ->condition('field_class_start', $field_class_start->getTimestamp())
      ->condition('field_duration', $field_duration)
      ->condition('field_course_sub_group', $field_course_sub_group)
      ->execute()
    );

    if ($nid) {
      $query = [];

      $destination = \Drupal::request()->get('destination');
      if ($destination) {
        $query['destination'] = $destination;
      }

      $link = Link::fromTextAndUrl(t('here'), Url::fromRoute('entity.node.edit_form', ['node' => $nid], ['query' => $query]))->toString();
      $form_state->setError($form, t('There is already an attendance report for this date, time and duration. Edit it @link', ['@link' => $link]));
    }

    // Check for overlapping absence reports.
    if (!$nid) {
      $field_class_end = $field_class_start->getTimestamp() + $field_duration * 60;

      // Check for overlapping reports.
      $nid = current($node_storage
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('type','course_attendance_report')
        ->condition('field_course', $course->id())
        ->condition('field_class_start', $field_class_end, '<')
        ->condition('field_class_end', $field_class_start->getTimestamp(), '>')
        ->condition('field_course_sub_group', $field_course_sub_group)
        ->execute()
      );

      if ($nid) {
        $form_state->setError($form, t('There are one or more attendance reports partly or completely overlapping this report.'));
      }
    }
  }

  public static function validateStepOne(&$form, FormStateInterface $form_state) {

    $input_type = $form_state->getValue('input_type');

    if ($input_type === 'calendar_event') {
      $calender_event_id = $form_state->getValue('calendar_event');
      $calender_event = $calender_event_id
        ? \Drupal::entityTypeManager()->getStorage('ssr_calendar_event')->load($calender_event_id)
        : NULL;

      try {
        $field_class_start = (new DrupalDateTime())->setTimestamp((int) $calender_event->get('from')->value);
        $field_class_start->setTime($field_class_start->format('H'), $field_class_start->format('i'), 0);
        $field_duration = (int) floor(($calender_event->get('to')->value - $calender_event->get('from')->value) / 60);
        $field_course_sub_group = $calender_event->get('field_course_sub_group')->value ?? 'default';

        $form_state->setValue(['field_class_start', 0, 'value'], $field_class_start);
        $form_state->setValue(['field_duration', 0, 'value'], $field_duration);
        $form_state->setValue(['field_course_sub_group', 0, 'value'], $field_course_sub_group);
      }
      catch (\Exception $e) {
        $form_state->setError($form, t('Something went wrong. Try again.'));
        return;
      }

      if (empty($calender_event)) {
        $form_state->setErrorByName('calendar_event', t('Select a lesson'));
        return;
      }
    }
    else {
      /** @var DrupalDateTime $field_class_start */
      $field_class_start = $form_state->getValue('field_class_start')[0]['value'];
      $field_duration = $form_state->getValue('field_duration')[0]['value'];
      $field_course_sub_group = $form_state->getValue('field_course_sub_group')[0]['value'];

      if (!$field_class_start instanceof DrupalDateTime) {
        $form_state->setErrorByName('field_class_start', t('Invalid date'));
      }

      if (!is_numeric($field_duration) || $field_duration <= 0) {
        $form_state->setErrorByName('field_duration', t('Invalid duration'));
      }
    }

    if (!$field_class_start instanceof DrupalDateTime) {
      return;
    }

    if (!is_numeric($field_duration) || $field_duration <= 0) {
      return;
    }
    self::validateOccurance($field_class_start, $field_duration, $field_course_sub_group, $form, $form_state);
  }

  public static function submitStepOne(&$form, FormStateInterface $form_state) {
    $calendar_event_id = $form_state->getValue('input_type') === 'calendar_event'
      ? $form_state->getValue('calendar_event')
      : NULL;

    $step1_values = [
      'field_class_start' => $form_state->getValue('field_class_start')[0]['value'],
      'field_duration' => $form_state->getValue('field_duration')[0]['value'],
      'field_course_sub_group' => $form_state->getValue('field_course_sub_group')[0]['value'],
      'calendar_event' => $calendar_event_id,
    ];

    $form_state->set('step', 2);
    $form_state->set('step1_values', $step1_values);
    $form_state->setRebuild();
  }

  public static function formStepTwo(&$form, FormStateInterface $form_state, NodeInterface $node) {
    $form_state->set('step', 2);
    $form['field_course']['widget'][0]['target_id']['#disabled'] = TRUE;
    $form['field_class_start']['widget'][0]['value']['#disabled'] = TRUE;
    $form['field_duration']['widget'][0]['value']['#disabled'] = TRUE;
    $form['field_course_sub_group']['widget'][0]['value']['#disabled'] = TRUE;

    $course = self::getCourseNode($form_state);

    /** @var \Drupal\simple_school_reports_core\Service\UserMetaDataServiceInterface $user_meta_data */
    $user_meta_data = \Drupal::service('simple_school_reports_core.user_meta_data');

    $duration = 1440;
    if ($node->isNew()) {
      $step1_values = $form_state->get('step1_values', []);
      $form['field_course']['widget'][0]['target_id']['#default_value'] = $course;
      $form['field_class_start']['widget'][0]['value']['#default_value'] = $step1_values['field_class_start'];
      $form['field_duration']['widget'][0]['value']['#default_value'] = $step1_values['field_duration'];
      $form['field_course_sub_group']['widget'][0]['value']['#default_value'] = $step1_values['field_course_sub_group'];
      $field_course_sub_group = $step1_values['field_course_sub_group'];

      $duration = $step1_values['field_duration'];

      $form['title'] = [
        '#type' => 'value',
        '#value' => $course->label() . ' ' . $step1_values['field_class_start']->format('Y-m-d H:i') . ' (' . $duration . ' min)',
      ];
    }
    else {
      $field_course_sub_group = $node->get('field_course_sub_group')->value ?? 'default';
      if ($node->get('field_duration')->value) {
        $duration = $node->get('field_duration')->value;
      }
    }


    $subject = current($course->get('field_school_subject')->referencedEntities());
    if ($subject instanceof TermInterface) {
      $subject_code = $subject->get('field_subject_code')->value ?? '';
       $context = [
        'subject_code' => $subject_code,
        'handled' => FALSE,
      ];
      \Drupal::moduleHandler()->alter('course_attendance_report_step_two', $form, $form_state, $context);
      if ($context['handled']) {
        return;
      }
    }


    $description = NULL;
    if (!$node->isNew()) {
      $description = t('Mail will only be sent for students for which invalid absence has been changed.');
    }

    $form['send_mail'] = [
      '#type' => 'checkbox',
      '#title' => t('Send message about invalid absence'),
      '#default_value' => TRUE,
      '#description'=> $description,
      '#weight' => 997,
    ];

    $form['report'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['report-wrapper'],
      ],
      '#weight' => 999,
    ];

    $course_students = self::getCourseStudents($form_state, $field_course_sub_group);

    if (!empty($course_students)) {
      $form['report']['label'] = [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#attributes' => [
          'class' => ['header'],
        ],
        '#value' => t('Student attendance'),
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


      $default_attending = TRUE;
      if ($paragraph && $paragraph->get('field_attendance_type')->value !== 'attending') {
        $default_attending = FALSE;
      }
      elseif ($data['set_valid_absence'] && !$paragraph) {
        $default_attending = FALSE;
      }

      $form['report'][$id]['student']['report']['attendance_info']['attending_' . $id] = [
        '#type' => 'checkbox',
        '#title' => t('Attending'),
        '#default_value' => $default_attending,
      ];

      $default_attendance_type = 'invalid_absence';
      if ($paragraph && $paragraph->get('field_attendance_type')->value === 'valid_absence') {
        $default_attendance_type = 'valid_absence';
      }
      elseif ($data['set_valid_absence'] && !$paragraph) {
        $default_attendance_type = 'valid_absence';
      }



      $form['report'][$id]['student']['report']['attendance_info']['attendance_type'] = [
        '#type' => 'container',
        '#states' => [
          'invisible' => [
            ':input[name="attending_' . $id . '"]' => [
              'checked' => TRUE,
            ],
          ],
        ],
        'attendance_type_' . $id => [
          '#type' => 'radios',
          '#default_value' => $default_attendance_type,
          '#options' => [
            'valid_absence' => t('Valid absence'),
            'invalid_absence' => t('Invalid absence'),
          ],
        ],
      ];

      $form['report'][$id]['student']['report']['absence_time'] = [
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

      $default_invalid_absence = 0;
      if ($paragraph && $paragraph->get('field_invalid_absence')->value) {
        $default_invalid_absence = $paragraph->get('field_invalid_absence')->value;
      }

      $form['report'][$id]['student']['report']['absence_time']['invalid_absence_' . $id] = [
        '#title' => t('Invalid absence in minutes'),
        '#type' => 'number',
        '#min' => 0,
        '#max' => $duration,
        '#default_value' => $default_invalid_absence,
      ];
    }

    if (empty($form['actions']['submit']['#submit'])) {
      $form['actions']['submit']['#submit'] = [];
    }

    $form['#validate'][] = [self::class, 'validateStepTwo'];

    array_unshift($form['actions']['submit']['#submit'], [
      self::class,
      'submitStepTwo',
    ]);

    $form['actions']['submit']['#submit'][] = [
      self::class,
      'handleMailReports',
    ];
  }

  public static function validateStepTwo(&$form, FormStateInterface $form_state) {
    $node = self::courseAttendanceReportNode($form_state);
    if ($node->isNew()) {
      $step1_values = $form_state->get('step1_values', []);

      /** @var DrupalDateTime $field_class_start */
      $field_class_start = $step1_values['field_class_start'];
      $field_duration = $step1_values['field_duration'];
      $field_course_sub_group = $step1_values['field_course_sub_group'];

      self::validateOccurance($field_class_start, $field_duration, $field_course_sub_group, $form, $form_state);
    }
  }

  public static function submitStepTwo(&$form, FormStateInterface $form_state) {
    $connection = \Drupal::service('database');
    $transaction = $connection->startTransaction();
    $send_mail = $form_state->getValue('send_mail', FALSE);
    $mail_data = [];
    try {
      $start_time = 0;
      $duration = 0;
      $course = self::getCourseNode($form_state);

      /** @var NodeInterface $node */
      $node = self::courseAttendanceReportNode($form_state);
      if ($node->isNew()) {
        $step1_values = $form_state->get('step1_values', []);


        if (!empty($step1_values['calendar_event'])) {
          $node->set('field_calendar_event', ['target_id' => $step1_values['calendar_event']]);
        }

        $field_course_sub_group = $step1_values['field_course_sub_group'];

        $node->set('field_course', $course);
        $node->set('field_duration', $step1_values['field_duration']);
        $node->set('field_class_start', $step1_values['field_class_start']);
        $node->set('field_course_sub_group', $step1_values['field_course_sub_group']);
        $start_time = isset($step1_values['field_class_start']) ? $step1_values['field_class_start']->getTimestamp() : NULL;
        $duration = $step1_values['field_duration'];
      }
      else {
        $field_course_sub_group = $node->get('field_course_sub_group')->value ?? 'default';
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

      $course_students = self::getCourseStudents($form_state, $field_course_sub_group);
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

        $attendance_type = 'attending';
        $attending = $form_state->getValue('attending_' . $id, TRUE);
        if (!$attending) {
          $attendance_type = $form_state->getValue('attendance_type_' . $id, 'valid_absence');
        }

        $paragraph->set('field_attendance_type', $attendance_type);

        $old_invalid_absence = !$paragraph->isNew() ? (int) $paragraph->get('field_invalid_absence')->value : 0;
        $invalid_absence = $attendance_type === 'valid_absence' ? 0 : $duration;

        if ($attendance_type === 'attending') {
          $invalid_absence = $form_state->getValue('invalid_absence_' . $id, 0);
        }

        $paragraph->set('field_invalid_absence', $invalid_absence);
        $paragraph->set('field_invalid_absence_original', $invalid_absence);

        $paragraph->set('field_student', $data['user']);
        $paragraph->set('field_subject', ['target_id' => $course->get('field_school_subject')->target_id]);
        $paragraph->setNewRevision(FALSE);

        if (!$paragraph->isNew()) {
          $paragraph->save();
        }

        if ($send_mail && $old_invalid_absence !== (int) $invalid_absence) {
          /** @var \Drupal\simple_school_reports_core\Service\EmailServiceInterface $email_service */
          $email_service = \Drupal::service('simple_school_reports_core.email_service');

          $mail_data[] = [
            'student_uid' => $data['user']->id(),
            'student_name' => $data['user']->getDisplayName(),
            'student_first_name' => $data['user']->get('field_first_name')->value,
            'student_last_name' => $data['user']->get('field_last_name')->value,
            'student_mail' => $email_service->getUserEmail($data['user']),
            'invalid_absence' => $invalid_absence,
          ];
        }
        $paragraphs[] = $paragraph;
      }

      $node->set('field_student_course_attendance', $paragraphs);
      $form_state->set('step', 2);
      $form_state->set('mail_data', $mail_data);
    }
    catch (\Exception $e) {
      $transaction->rollBack();
      throw $e;
    }
  }

  public static function getStudentCourseAttendanceReports(array $uids, int $filter_from, int $filter_to, $only_invalid = FALSE, $has_original_invalid_absence = FALSE) {
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $attendance_report_nids = $node_storage->getQuery()
      ->condition('type', 'course_attendance_report')
      ->condition('field_class_start', $filter_to, '<')
      ->condition('field_class_end', $filter_from, '>')
      ->accessCheck(FALSE)
      ->execute();

    if (empty($attendance_report_nids)) {
      return [];
    }

    $paragraph_storage = \Drupal::entityTypeManager()->getStorage('paragraph');
    $query = $paragraph_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'student_course_attendance')
      ->condition('field_student', $uids, 'IN')
      ->condition('parent_id', $attendance_report_nids, 'IN');

    if ($only_invalid) {
      $query->condition('field_attendance_type', 'invalid_absence');
    }

    if ($has_original_invalid_absence) {
      $query->condition('field_invalid_absence_original', 0, '>');
    }

    $pids = $query->execute();
    if (!empty($pids)) {
      return $paragraph_storage->loadMultiple($pids);
    }

    return [];
  }

  public static function handleMailReports(&$form, FormStateInterface $form_state) {
    if ($form_state->getValue('send_mail') && $mail_data = $form_state->get('mail_data')) {
      if (!empty($mail_data)) {
        // Initialize batch.
        $batch = [
          'title' => t('Sending mails'),
          'init_message' => t('Sending mails'),
          'progress_message' => t('Processed @current out of @total.'),
          'operations' => [],
        ];

        $node = self::courseAttendanceReportNode($form_state);
        /** @var \Drupal\simple_school_reports_core\Service\EmailServiceInterface $email_service */
        $email_service = \Drupal::service('simple_school_reports_core.email_service');
        /** @var \Drupal\simple_school_reports_core\Service\MessageTemplateServiceInterface $template_service */
        $template_service = \Drupal::service('simple_school_reports_core.message_template_service');
        /** @var \Drupal\simple_school_reports_core\Service\UserMetaDataServiceInterface $user_meta_data */
        $user_meta_data = \Drupal::service('simple_school_reports_core.user_meta_data');

        $message_template = $template_service->getMessageTemplates('attendance_report', 'email');
        $subject_template = !empty($message_template['subject']) ? $message_template['subject'] : NULL;
        $message_template = !empty($message_template['message']) ? $message_template['message'] : NULL;

        if (!$subject_template || !$message_template) {
          return;
        }

        foreach ($mail_data as $data) {
          $recipient_data = [];
          $student_uid = $data['student_uid'];

          if ($user_meta_data->caregiversHasAccess($student_uid)) {
            $recipient_data = $email_service->getCaregiverRecipients($data['student_uid']) ?? [];
            if (empty($recipient_data)) {
              \Drupal::messenger()->addWarning(t('@student misses caregiver(s) with email address set.', ['@student' => $data['student_name']]));
            }
          }

          if ($user_meta_data->isAdult($student_uid)) {
            if (!empty($data['student_mail'])) {
              $recipient_data[$student_uid] = [
                'mail' => $data['student_mail'],
                'first_name' => $data['student_first_name'],
                'last_name' => $data['student_last_name'],
                'full_name' => $data['student_first_name'] . ' ' . $data['student_last_name'],
              ];
            }
            else {
              \Drupal::messenger()->addWarning(t('@student does not have a valid email address set.', ['@student' => $data['student_name']]));
            }
          }

          foreach ($recipient_data as $recipient_uid => $caregiver_mail_data) {
            $replace_context = [
              ReplaceTokenServiceInterface::STUDENT_REPLACE_TOKENS => ['target_id' => $data['student_uid'], 'entity_type' => 'user'],
              ReplaceTokenServiceInterface::RECIPIENT_REPLACE_TOKENS => ['target_id' => $recipient_uid, 'entity_type' => 'user'],
              ReplaceTokenServiceInterface::CURRENT_USER_REPLACE_TOKENS => ['target_id' => \Drupal::currentUser()->id(), 'entity_type' => 'user'],
              ReplaceTokenServiceInterface::ATTENDANCE_REPORT_TOKENS => ['target_id' => $node->id(), 'entity_type' => 'node'],
              ReplaceTokenServiceInterface::INVALID_ABSENCE_TOKENS => $data['invalid_absence'],
            ];

            $options = [
              'maillog_student_context' => $data['student_uid'],
              'maillog_mail_type' => SsrMaillogInterface::MAILLOG_TYPE_COURSE_ATTENDANCE,
            ];
            $batch['operations'][] = [[EmailService::class, 'batchSendMail'], [$caregiver_mail_data['mail'], $subject_template, $message_template, $replace_context, [], $options]];
          }
        }

        if (!empty($batch['operations'])) {
          $batch['op_delay'] = 500;
          batch_set($batch);
        }
      }
    }
  }

  public static function handleInsert(NodeInterface $node) {
    if ($node->bundle() !== 'course_attendance_report') {
      return;
    }

    try {
      $calendar_event = $node->get('field_calendar_event')->entity;

      if (!$calendar_event) {
        // Look for calender event that matches the report.
        $from = $node->get('field_class_start')->value;
        $to = $node->get('field_class_end')->value;
        $course = $node->get('field_course')->target_id;

        $calendar_event_id = current(\Drupal::entityTypeManager()->getStorage('ssr_calendar_event')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('bundle', 'course')
          ->condition('completed', FALSE)
          ->condition('field_course', $course)
          ->condition('from', $from, '=')
          ->condition('to', $to, '=')
          ->range(0, 1)
          ->execute());

        if ($calendar_event_id) {
          $calendar_event = \Drupal::entityTypeManager()->getStorage('ssr_calendar_event')->load($calendar_event_id);
        }
      }

      if ($calendar_event) {
        $calendar_event->set('cancelled', FALSE);
        $calendar_event->set('completed', TRUE);
        $calendar_event->save();
      }

    }
    catch (\Exception $e) {
      \Drupal::messenger()->addError(t('Something went wrong. Try again.'));
    }


  }

}
