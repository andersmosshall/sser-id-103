<?php

namespace Drupal\simple_school_reports_student_di\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Form controller for the meeting entity edit forms.
 */
class StudentDiSeriesForm extends FormBase {

  protected EntityTypeManagerInterface $entityTypeManager;

  protected Connection $connection;

  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->connection = $container->get('database');
    return $instance;
  }

  public function getFormId() {
    return 'student_di_series_form';
  }


  public function buildCancelLink() {
    // Prepare cancel link.
    $query = $this->getRequest()->query;
    $url = NULL;
    // If a destination is specified, that serves as the cancel link.
    if ($query->has('destination')) {
      $options = UrlHelper::parse($query->get('destination'));
      // @todo Revisit this in https://www.drupal.org/node/2418219.
      try {
        $url = \Drupal\Core\Url::fromUserInput('/' . ltrim($options['path'], '/'), $options);
      }
      catch (\InvalidArgumentException $e) {
        // Suppress the exception and fall back to the form's cancel URL.
      }
    }
    // Check for a route-based cancel link.
    if (!$url) {
      $node = $this->getRouteMatch()->getRawParameter('node') ?? '-1';
      $url = \Drupal\Core\Url::fromRoute('view.student_development_interview_meetings.list', ['node' => $node]);
    }

    return [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#attributes' => ['class' => ['button', 'dialog-cancel']],
      '#url' => $url,
      '#cache' => [
        'contexts' => [
          'url.query_args:destination',
        ],
      ],
    ];
  }

  protected function getTeachers(FormStateInterface $form_state, $as_entities = TRUE): array {
    $teachers = [];
    $values = $form_state->get('step1_values') ?? [];
    if (!empty($values['teachers'])) {
      $teachers = array_column($values['teachers'], 'target_id');
    }

    if (!$as_entities || empty($teachers)) {
      return $teachers;
    }

    return array_values($this->entityTypeManager->getStorage('user')->loadMultiple($teachers));
  }

  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL) {
    if (!$node || $node->bundle() !== 'student_development_interview') {
      throw new BadRequestHttpException();
    }

    $step = $form_state->get('step') ?? 1;

    switch ($step) {
      case 1:
        $this->stepOne($form, $form_state, $node);
        break;
      case 2:
        $this->stepTwo($form, $form_state, $node);
        break;
    }

    $form['actions']['#type'] = 'actions';
    if ($step === 1) {
      $form['actions']['cancel'] = $this->buildCancelLink();
    }
    else {
      $form['actions']['previous'] = [
        '#type' => 'submit',
        '#value' => $this->t('Previous'),
        '#submit' => ['::previousStep'],
        '#limit_validation_errors' => [],
        '#validate' => [],
        '#button_type' => 'secondary',
        '#to_step' => $step - 1,
      ];
    }

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $step === 2 ? $this->t('Save') : $this->t('Next'),
      '#button_type' => 'primary',
    ];

    if ($step === 1) {
      $form['actions']['submit']['#submit'] = ['::submitStepOne'];
    }

    return $form;
  }

  protected function stepOne(array &$form, FormStateInterface $form_state, NodeInterface $node) {
    $values = $form_state->get('step1_values') ?? [];

    $sg_options = [];
    foreach ($node->get('field_student_groups')->referencedEntities() as $student_group) {
      $sg_options[$student_group->id()] = $student_group->label();
    }

    $form['student_group'] = [
      '#type' => 'select',
      '#title' => $this->t('Student group in @wrapper', ['@wrapper' => $node->label()]),
      '#options' => $sg_options,
      '#default_value' => $values['student_group'] ?? NULL,
      '#required' => TRUE,
    ];

    $default_teachers = $this->getTeachers($form_state);

    if (empty($default_teachers)) {
      /** @var \Drupal\user\UserInterface $current_user */
      $current_user = $this->entityTypeManager->getStorage('user')->load($this->currentUser()->id());
      if ($current_user->hasRole('teacher')) {
        $default_teachers[] = $current_user;
      }
    }

    $form['teachers'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'user',
      '#tags' => TRUE,
      '#default_value' => $default_teachers,
      '#selection_handler' => 'default',
      '#required' => TRUE,
      '#selection_settings' => [
        'include_anonymous' => FALSE,
        'filter' => [
          'role' => ['teacher', 'administrator'],
        ],
      ],
      '#title' => $this->t('Teachers'),
      '#description' => $this->t('Select teachers for the meetings. If multiple teacher should be selected, separate them with comma.'),
    ];

    $form['start_datetime'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Start date'),
      '#description' => $this->t('Select the start date and time for the first meeting.'),
      '#default_value' => $values['start_datetime'] ?? NULL,
      '#date_increment' => 60,
      '#required' => TRUE,
    ];

    $form['number'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of meetings'),
      '#description' => $this->t('Select the number of meetings to create.'),
      '#min' => 1,
      '#max' => 50,
      '#default_value' => $values['number'] ?? 1,
      '#required' => TRUE,
    ];

    $form['meeting_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Meeting length'),
      '#description' => $this->t('Select the length of each meeting in minutes.'),
      '#min' => 5,
      '#max' => 240,
      '#default_value' =>  $values['meeting_length'] ?? NULL,
      '#required' => TRUE,
    ];

    $form['break'] = [
      '#type' => 'number',
      '#title' => $this->t('Break'),
      '#description' => $this->t('Select the length of the break between each meeting in minutes.'),
      '#min' => 0,
      '#max' => 60,
      '#default_value' => $values['break'] ?? 5,
      '#required' => TRUE,
    ];
  }

  public function submitStepOne(array &$form, FormStateInterface $form_state) {
    $form_state->set('step', 2);
    $form_state->setRebuild(TRUE);
    $form_state->set('step1_values', $form_state->getValues());
  }

  protected function getMeetingKeys($form_state) {
    $values = $form_state->get('step1_values') ?? [];

    $start_datetime = $values['start_datetime'];
    $number = $values['number'];
    $meeting_length = $values['meeting_length'];
    $break = $values['break'];

    $meeting_keys = [];
    $start_time = $start_datetime->getTimestamp();
    $meeting_keys[] = $start_time . ':' . ($start_time + ($meeting_length * 60));
    for ($i = 1; $i < $number; $i++) {
      $start_time += ($meeting_length * 60) + ($break * 60);
      $meeting_keys[] = $start_time . ':' . ($start_time + ($meeting_length * 60));
    }

    return $meeting_keys;
  }

  protected function stepTwo(array &$form, FormStateInterface $form_state, NodeInterface $node) {

    $values = $form_state->get('step1_values') ?? [];
    $names = [];
    foreach ($this->getTeachers($form_state) as $teacher) {
      $names[] = $teacher->getDisplayName();
    }

    if (!empty($names)) {
      $form['teachers_label'] = [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $this->t('Teachers'),
      ];
      $student_group = $this->entityTypeManager->getStorage('node')->load($values['student_group']);
      $form['teachers_info'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('Teachers for the meetings in the student development interview: @wrapper - @student_group', [
          '@wrapper' => $node->label(),
          '@student_group' => $student_group->label(),
        ]),
      ];
      $form['teachers_info'] = [
        '#theme' => 'item_list',
        '#items' => $names,
      ];
    }

    $meeting_keys = $this->getMeetingKeys($form_state);

    foreach ($meeting_keys as $meeting_key) {
      [$start_time, $end_time] = explode(':', $meeting_key);
      $form['separator_' . $meeting_key] = [
        '#type' => 'html_tag',
        '#tag' => 'hr',
      ];

      $form['label_' . $meeting_key] = [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#value' => date('Y-m-d H:i', $start_time) . ' - ' . date('H:i', $end_time),
      ];

      $form['included_' . $meeting_key] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Included'),
        '#default_value' => TRUE,
      ];

      $form['meeting_wrapper_' . $meeting_key] = [
        '#type' => 'container',
        '#title' => $this->t('Meeting @start_time - @end_time', ['@start_time' => date('Y-m-d H:i', $start_time), '@end_time' => date('Y-m-d H:i', $end_time)]),
      ];

      $form['meeting_wrapper_' . $meeting_key]['location_'. $meeting_key] = [
        '#type' => 'textfield',
        '#title' => $this->t('Location (optional)'),
        '#attributes' => ['class' => ['ssr-meeting-location']],
        '#states' => [
          'visible' => [
            ':input[name="included_' . $meeting_key .'"]' => [
              'checked' => TRUE,
            ],
          ],
        ],
      ];

      $form['meeting_wrapper_' . $meeting_key]['meeting_link_'. $meeting_key] = [
        '#type' => 'url',
        '#title' => $this->t('Meeting link (optional)'),
        '#attributes' => ['class' => ['ssr-meeting-link']],
        '#states' => [
          'visible' => [
            ':input[name="included_' . $meeting_key .'"]' => [
              'checked' => TRUE,
            ],
          ],
        ],
      ];
    }

    if (count($meeting_keys) > 1) {
      $form['location_copy'] = [
        '#type' => 'msr_input_copy',
        '#target_selectors' => ['.ssr-meeting-location'],
      ];

      $form['link_copy'] = [
        '#type' => 'msr_input_copy',
        '#target_selectors' => ['.ssr-meeting-link'],
      ];
    }

    $form['sep'] = [
      '#type' => 'html_tag',
      '#tag' => 'hr',
    ];

    if (!empty($names)) {
      $form['validate_dates'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Validate dates'),
        '#description' => $this->t('Validate that the dates are not overlapping with other meetings for the selected teachers.'),
        '#default_value' => TRUE,
      ];
    }
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (isset($form_state->getTriggeringElement()['#to_step'])) {
      return;
    }

    if ($form_state->get('step') === 2 && $form_state->getValue('validate_dates')) {
      $values = $form_state->get('step1_values') ?? [];

      $meeting_keys = $this->getMeetingKeys($form_state);
      $teachers = $this->getTeachers($form_state, FALSE);

      if (empty($meeting_keys) || empty($teachers)) {
        $form_state->setError($form, $this->t('Something went wrong.'));
        return;
      }

      $start_time = $meeting_keys[0];
      $end_time = end($meeting_keys);
      $start_time = explode(':', $start_time)[0];
      $end_time = explode(':', $end_time)[1];

      $student_group = $values['student_group'] ?? NULL;
      if (empty($student_group)) {
        $form_state->setError($form, $this->t('Something went wrong.'));
        return;
      }

      $double_booked = [];

      $meeting_ids = $this->entityTypeManager->getStorage('ssr_meeting')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('bundle', 'student_di')
        ->condition('to', $start_time, '>')
        ->condition('from', $end_time, '<')
        ->condition('field_teachers', $teachers, 'IN')
        ->execute();

      if (empty($meeting_ids)) {
        return;
      }

      $meetings = $this->entityTypeManager->getStorage('ssr_meeting')->loadMultiple($meeting_ids);

      foreach ($meetings as $meeting) {
        $meeting_start = $meeting->get('from')->value;
        $meeting_end = $meeting->get('to')->value;

        foreach ($meeting_keys as $meeting_key) {
          $included = $form_state->getValue('included_' . $meeting_key);
          if (!$included) {
            continue;
          }

          [$start, $end] = explode(':', $meeting_key);
          // Double check to be sure. This should not be possible.
          if (!($meeting_end <= $start || $meeting_start >= $end)) {
            foreach ($meeting->get('field_teachers')->referencedEntities() as $teacher) {
              if (!in_array($teacher->id(), $teachers)) {
                continue;
              }
              $double_booked[$meeting_key][$teacher->id()] = $teacher->getDisplayName();
            }
          }
        }
      }

      if (!empty($double_booked)) {
        foreach ($double_booked as $meeting_key => $teachers) {
          $meeting_time = date('Y-m-d H:i', explode(':', $meeting_key)[0]);
          $form_state->setError($form['included_' . $meeting_key], $this->t('The following teachers are not available for the meeting @time: @teachers', ['@time' => $meeting_time,'@teachers' => implode(', ', $teachers)]));
        }
        $form_state->setError($form, $this->t('One or many teachers is not available for at least one of the meetings.'));
      }
    }
  }

  public function previousStep(array &$form, FormStateInterface $form_state) {
    $to_step = $form_state->getTriggeringElement()['#to_step'] ?? 1;
    $form_state->set('step', $to_step);
    $form_state->setRebuild(TRUE);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $transaction = $this->connection->startTransaction();
    try {
      $meeting_keys = $this->getMeetingKeys($form_state);
      $teachers = $this->getTeachers($form_state);
      $values = $form_state->get('step1_values') ?? [];

      $meeting_count = 0;

      foreach ($meeting_keys as $meeting_key) {
        [$start_time, $end_time] = explode(':', $meeting_key);
        $included = $form_state->getValue('included_' . $meeting_key);
        if (!$included) {
          continue;
        }

        $meeting = $this->entityTypeManager->getStorage('ssr_meeting')->create([
          'bundle' => 'student_di',
          'label' => 'Utvecklingssamtal',
          'node_parent' => $values['student_group'],
          'field_teachers' => $teachers,
          'from' => $start_time,
          'to' => $end_time,
          'location' => $form_state->getValue('location_' . $meeting_key),
          'meeting_link' => [
            'title' => $form_state->getValue('meeting_link_' . $meeting_key),
            'uri' => $form_state->getValue('meeting_link_' . $meeting_key),
          ],
        ]);
        $meeting->save();
        $meeting_count++;
      }
      $this->messenger()->addStatus($this->t('@count meetings has been created.', ['@count' => $meeting_count]));
    }
    catch (\Exception $e) {
      $transaction->rollBack();
    }
  }

}
