<?php

namespace Drupal\simple_school_reports_grade_registration;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\simple_school_reports_core\AbsenceDayHandler;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class GradeSubjectFormAlter {

  public static function formAlter(&$form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'simple_school_reports_grade_registration/grade_registration';
    $form['#attributes']['class'][] = 'grade-registration-form';

    $subject_grade_node = self::getFormEntity($form_state);
    $grade_round = self::getQueryReferencedNode($form_state, 'grade_round');
    $grade_student_group = self::getQueryReferencedNode($form_state, 'grade_student_group');

    $disabled = $grade_round->get('field_locked')->value == 1;

    $form['title']['#access'] = FALSE;
    $form['field_teacher']['#access'] = FALSE;
    $form['field_default_grade_round']['#access'] = FALSE;
    $form['field_school_subject']['widget']['#disabled'] = TRUE;

    $form['#validate'][] = [self::class, 'validateForm'];
    if (empty($form['actions']['submit']['#submit'])) {
      $form['actions']['submit']['#submit'] = [];
    }

    array_unshift($form['actions']['submit']['#submit'], [
      self::class,
      'submitForm',
    ]);

    $form['actions']['submit']['#disabled'] = $disabled;

    if ($disabled) {
      \Drupal::messenger()->addWarning(t('Current grade round is locked for grade registration.'));
    }

    $state_options = [
      'started' => t('Started'),
      'done' => t('Done'),
    ];

    $initial_state = $subject_grade_node->get('field_state')->value ?? NULL;

    $form['initial_state'] = [
      '#type' => 'value',
      '#value' => $initial_state,
    ];

    $done_init = $initial_state === 'done';

    if ($done_init) {
      $form['done_init_wrapper'] = [
        '#type' => 'container',
        '#states' => [
          'visible' => [
            ':input[name="done_init"]' => [
              'checked' => TRUE,
            ],
          ],
        ],
        '#weight' => 997,
      ];

      $form['done_init_wrapper']['done_init'] = [
        '#title' => t('Locked'),
        '#description' => t('This grade registration is marked as done and therefore locked for registration. Unlock by unchecking this checkbox.'),
        '#type' => 'checkbox',
        '#default_value' => TRUE,
        '#disabled' => $disabled,
      ];

    }

    $form['state_wrapper'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="done_init"]' => [
            'checked' => FALSE,
          ],
        ],
      ],
      '#weight' => 998,
    ];

    $form['state_wrapper']['state'] = [
      '#title' => t('State', [], ['context' => 'ssr']),
      '#description' => t('Mark the state of this grade registration'),
      '#type' => 'radios',
      '#default_value' => $initial_state,
      '#options' => $state_options,
      '#required' => TRUE,
      '#disabled' => $disabled,
    ];

    $states = [];
    if ($done_init && !$disabled) {
      $states = [
        'disabled' => [
          ':input[name="done_init"]' => [
            'checked' => TRUE,
          ],
        ],
      ];
    }

    $form['grade_registration'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['students-wrapper'],
      ],
      '#weight' => 999,
    ];

    $form['grade_registration']['label'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => t('Students'),
    ];

    $exclude_reason_options = [
      'pending' => t('Grade will be set later'),
      'n_a' => t('Student does not study the subject'),
      'adapted_studies' => t('Adapted studies'),
    ];

    $grade_options = [];

    if (!$grade_student_group->get('field_grade_system')->isEmpty()) {
      /** @var \Drupal\taxonomy\TermStorageInterface $term_storage */
      $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');

      $grade_items = $term_storage->loadTree($grade_student_group->get('field_grade_system')->value, 0, NULL, TRUE);
      /** @var \Drupal\taxonomy\TermInterface $grade_item */
      foreach ($grade_items as $grade_item) {
        $grade_options[$grade_item->id()] = $grade_item->label();
      }
    }

    $grading_teacher_options = [];

    if (!$subject_grade_node->get('field_teacher')->isEmpty()) {
      /** @var \Drupal\user\UserInterface $teacher */
      foreach ($subject_grade_node->get('field_teacher')->referencedEntities() as $teacher) {
        $grading_teacher_options[$teacher->id()] = $teacher->label();
      }
    }

    $students = self::getGradingStudents($form_state);
    foreach ($students as $student_uid => $data) {
      /** @var \Drupal\user\UserInterface $student */
      $student = $data['user'] ?? NULL;

      // Skip removed students.
      if (!$student) {
        continue;
      }

      /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
      $paragraph = $data['paragraph'];


      $form['grade_registration'][$student_uid]['student'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['student-row']],
      ];

      $form['grade_registration'][$student_uid]['student']['info'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['student-row--info-wrapper'],
        ],
      ];

      $form['grade_registration'][$student_uid]['student']['info']['name'] = [
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

      $form['grade_registration'][$student_uid]['student']['grade_registration'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['student-row--report-wrapper'],
        ],
      ];

      $form['grade_registration'][$student_uid]['student']['grade_registration']['grade_info'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['student-row--report-wrapper--grade-info'],
        ],
      ];

      $local_exclude_reason_options = $exclude_reason_options;
      $default_exclude_student = FALSE;
      $default_exclude_reason = 'n_a';
      if ($paragraph && $paragraph->get('field_exclude_reason')->value) {
        $default_exclude_student = TRUE;
        $default_exclude_reason = $paragraph->get('field_exclude_reason')->value;
      }
      else if ($data['is_default']) {
        $default_exclude_student = TRUE;
        $default_exclude_reason = 'is_default';
        $local_exclude_reason_options = [
          'is_default' => $data['default_note'],
          'pending' => $exclude_reason_options['pending'],
          'n_a' => $exclude_reason_options['n_a'],
          'adapted_studies' => $exclude_reason_options['adapted_studies'],
        ];

      }

      $form['grade_registration'][$student_uid]['student']['grade_registration']['grade_info']['exclude_' . $student_uid] = [
        '#type' => 'checkbox',
        '#title' => t('Exclude student / Set later'),
        '#default_value' => $default_exclude_student,
        '#disabled' => $disabled,
        '#states' => $states,
      ];

      $form['grade_registration'][$student_uid]['student']['grade_registration']['grade_info']['exclude_reason'] = [
        '#type' => 'container',
        '#states' => [
          'visible' => [
            ':input[name="exclude_' . $student_uid . '"]' => [
              'checked' => TRUE,
            ],
          ],
        ],
        'exclude_reason_' . $student_uid => [
          '#type' => 'radios',
          '#title' => t('Select reason for excluded student'),
          '#default_value' => $default_exclude_reason,
          '#options' => $local_exclude_reason_options,
          '#disabled' => $disabled,
          '#states' => $states,
        ],
      ];

      $form['grade_registration'][$student_uid]['student']['grade_registration']['grade_wrapper'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['student-row--report-wrapper--grade'],
        ],
        '#states' => [
          'invisible' => [
            ':input[name="exclude_' . $student_uid . '"]' => [
              'checked' => TRUE,
            ],
          ],
        ],
      ];

      $default_grade = $data['grade'];
      if ($paragraph && $paragraph->get('field_grade')->target_id) {
        $default_grade = $paragraph->get('field_grade')->target_id;
      }

      $form['grade_registration'][$student_uid]['student']['grade_registration']['grade_wrapper']['grade_select_wrapper'] = [
        '#type' => 'container',
      ];

      $form['grade_registration'][$student_uid]['student']['grade_registration']['grade_wrapper']['grade_select_wrapper']['grade_' . $student_uid] = [
        '#title' => t('Grade'),
        '#type' => 'select',
        '#empty_option' => t('Not set'),
        '#options' => $grade_options,
        '#default_value' => $default_grade,
        '#disabled' => $disabled,
        '#states' => $states,
      ];
      $form['grade_registration'][$student_uid]['student']['grade_registration']['grade_wrapper']['grade_select_wrapper']['trial_' . $student_uid] = [
        '#title' => t('Grade from trial'),
        '#type' => 'checkbox',
        '#default_value' => $paragraph?->get('field_trial')->value ?? FALSE,
        '#disabled' => $disabled,
        '#states' => $states,
      ];

      $default_grading_teacher = NULL;
      $grading_teacher_options_local = $grading_teacher_options;

      if ($paragraph && $paragraph->get('field_teacher')->target_id) {
        $default_grading_teacher = $paragraph->get('field_teacher')->target_id;
        if (empty($grading_teacher_options_local[$default_grading_teacher])) {
          $grading_teacher_options_local[$default_grading_teacher] = '???';
        }
      }

      if (count($grading_teacher_options_local) === 1) {
        $default_grading_teacher = array_keys($grading_teacher_options_local)[0];
      }

      $form['grade_registration'][$student_uid]['student']['grade_registration']['grade_wrapper']['grading_teacher_' . $student_uid] = [
        '#title' => t('Grading teacher'),
        '#type' => 'select',
        '#empty_option' => t('Not set'),
        '#options' => $grading_teacher_options_local,
        '#default_value' => $default_grading_teacher,
        '#disabled' => $disabled,
        '#states' => $states,
      ];

      if (count($grading_teacher_options_local) > 1) {
        $default_joint_grading = [];
        if ($paragraph) {
          foreach (array_column($paragraph->get('field_joint_grading')->getValue(), 'target_id') as $joint_grading_uid) {
            $default_joint_grading[] = $joint_grading_uid;
            if (empty($grading_teacher_options_local[$default_grading_teacher])) {
              $grading_teacher_options_local[$joint_grading_uid] = '???';
            }
          }
        }

        $form['grade_registration'][$student_uid]['student']['grade_registration']['grade_wrapper']['joint_grading_' . $student_uid] = [
          '#title' => t('Joint grader'),
          '#type' => 'checkboxes',
          '#options' => $grading_teacher_options_local,
          '#default_value' => $default_joint_grading,
          '#disabled' => $disabled,
          '#states' => $states,
        ];
      }

      $default_comment = $data['comment'];
      if ($paragraph && !$paragraph->get('field_comment')->isEmpty()) {
        $default_comment = $paragraph->get('field_comment')->value;
      }

      $form['grade_registration'][$student_uid]['student']['grade_registration']['grade_wrapper']['comment_' . $student_uid] = [
        '#title' => t('Short comment'),
        '#description' => t('The short comment will be shown in generated grade document'),
        '#type' => 'textfield',
        '#default_value' => $default_comment,
        '#maxlength' => 10,
        '#disabled' => $disabled,
        '#states' => $states,
      ];

      if ($paragraph && $done_init) {
        $form['grade_registration'][$student_uid]['student']['grade_registration']['update_reason_wrapper'] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['student-row--update-reason-wrapper'],
            'style' => ['display: none;'],
            'data-initial-state' => self::makeGradeStateHash($paragraph, TRUE, $default_exclude_student),
          ],
        ];

        $update_reason_options = [
          'correction' => t('Correction'),
          'change' => t('Change', [], ['context' => 'update_reason']),
        ];
        $default_update_reason = 'correction';

        $form['grade_registration'][$student_uid]['student']['grade_registration']['update_reason_wrapper']['update_reason_' . $student_uid] = [
          '#type' => 'radios',
          '#title' => t('Update reason'),
          '#default_value' => $default_update_reason,
          '#options' => $update_reason_options,
          '#disabled' => $disabled,
          '#states' => $states,
        ];
      }
    }

    if (\Drupal::currentUser()->hasPermission('administer simple school reports settings')) {
      $form['empty_grades'] = [
        '#title' => t('Empty grades'),
        '#description' => t('By checking this, when save, all registered grades in this group and subject will be erased.'),
        '#type' => 'checkbox',
        '#default_value' => FALSE,
        '#weight' => 1100,
        '#disabled' => $disabled,
      ];
    }

  }

  public static function getFormEntity(FormStateInterface $form_state) {
    $form_object = $form_state->getFormObject();
    if ($form_object instanceof EntityFormInterface) {
      return $form_object->getEntity();
    }
    return NULL;
  }

  public static function getQueryReferencedNode(FormStateInterface $form_state, string $bundle) {
    if (!$form_state->has($bundle)) {
      $node = NULL;
      $nid = \Drupal::request()->get($bundle);
      if ($nid) {
        /** @var \Drupal\node\NodeStorageInterface $node_storage */
        $node_storage = \Drupal::entityTypeManager()->getStorage('node');
        $node = $node_storage->load($nid);
      }
      $form_state->set('course_node', $node);
    }
    else {
      $node = $form_state->get('course_node');
    }

    if (!($node instanceof NodeInterface && $node->bundle() === $bundle && $node->access('view'))) {
      throw new AccessDeniedHttpException();
    }

    return $node;
  }

  public static function getGradingStudents(FormStateInterface $form_state) {
    if (!$form_state->has('grading_students')) {
      /** @var NodeInterface $course */
      $grade_student_group = self::getQueryReferencedNode($form_state, 'grade_student_group');
      $subject_grade_node = self::getFormEntity($form_state);
      $grading_students = [];

      $grade_options = [];

      $grade_system = !$grade_student_group->get('field_grade_system')->isEmpty() ? $grade_student_group->get('field_grade_system')->value : NULL;

      if ($grade_system) {
        /** @var \Drupal\taxonomy\TermStorageInterface $term_storage */
        $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');

        $grade_items = $term_storage->loadTree($grade_system, 0, NULL, TRUE);
        /** @var \Drupal\taxonomy\TermInterface $grade_item */
        foreach ($grade_items as $grade_item) {
          $grade_options[$grade_item->id()] = $grade_item->label();
        }
      }

      $default_comment = '';
      $default_comment_parts = [];

      /** @var \Drupal\taxonomy\TermInterface $subject */
      $subject = current($subject_grade_node->get('field_school_subject')->referencedEntities());
      if (!$subject) {
        throw new AccessDeniedHttpException();
      }

      if (!$subject->get('field_language_code')->isEmpty()) {
        $default_comment_parts[] = $subject->get('field_language_code')->value;
      }

      if (!empty($default_comment_parts)) {
        $default_comment .= implode(' ', $default_comment_parts);
      }

      if ($grade_student_group->hasField('field_student') && !$grade_student_group->get('field_student')
          ->isEmpty()) {

        $student_source = [];
        /** @var \Drupal\simple_school_reports_core\Service\UserMetaDataServiceInterface $user_meta_data */
        $user_meta_data = \Drupal::service('simple_school_reports_core.user_meta_data');
        $user_weight = $user_meta_data->getUserWeights(FALSE);

        /** @var \Drupal\user\UserInterface $student */
        foreach ($grade_student_group->get('field_student')->referencedEntities() as $student) {
          $weight = $user_weight[$student->id()] ?? 'unsorted_' . $student->id();
          $student_source[$weight] = $student;
        }

        ksort($student_source);
        foreach ($student_source as $student) {
          $grading_students[$student->id()] = [
            'user' => $student,
            'paragraph' => NULL,
            'is_default' => FALSE,
            'comment' => $default_comment,
            'grade' => NULL,
          ];
        }
      }

      foreach (array_keys($grading_students) as $user_id) {
        $student_no_set_uids[$user_id] = $user_id;
      }

      if (!$subject_grade_node->get('field_grade_registration')->isEmpty()) {
        foreach ($subject_grade_node->get('field_grade_registration')->referencedEntities() as $paragraph) {
          if (!$paragraph->get('field_student')->isEmpty()) {
            $user_id = $paragraph->get('field_student')->target_id;
            $grading_students[$user_id]['paragraph'] = $paragraph;
            $grading_students[$user_id]['is_default'] = FALSE;

            unset($student_no_set_uids[$user_id]);
          }
        }
      }

      if ($default_grade_round_nid = $subject_grade_node->get('field_default_grade_round')->target_id) {
        if (!empty($student_no_set_uids) && $grade_system) {
          /** @var \Drupal\simple_school_reports_extension_proxy\Service\GradeSupportServiceInterface $grade_support_service */
          $grade_support_service = \Drupal::service('simple_school_reports_extension_proxy.grade_support');

          $default_grade_data = $grade_support_service->getDefaultGradeRoundData($default_grade_round_nid, $subject->id(), $grade_system, $student_no_set_uids);
          foreach ($default_grade_data as $user_id => $data) {

            if (isset($grade_options[$data['grade']])) {
              $grading_students[$user_id]['grade'] = $data['grade'];
              $grading_students[$user_id]['comment'] = $data['comment'];
              $grading_students[$user_id]['is_default'] = TRUE;
              $grading_students[$user_id]['default_note'] = t('Use grade @grade from @term', ['@grade' => $grade_options[$data['grade']], '@term' => $data['term_info']]);
            }
          }
        }
      }

      $form_state->set('grading_students', $grading_students);
    }
    return $form_state->get('grading_students');

  }

  public static function validateForm($form, FormStateInterface $form_state) {
    $grade_round = self::getQueryReferencedNode($form_state, 'grade_round');
    $disabled = $grade_round->get('field_locked')->value === 1;

    if ($disabled) {
      $form_state->setError($form, t('Current grade round is locked for grade registration.'));
    }

    // Validate so each grade has an assigned teacher.
    if ($form_state->getValue('empty_grades', FALSE)) {
      return;
    }

    $done_init = $form_state->getValue('done_init');
    if ($done_init) {
      return;
    }

    $values = $form_state->getValues();

    $students = self::getGradingStudents($form_state);
    foreach ($students as $student_uid => $data) {
      // Skip removed students.
      if (empty($data['user'])) {
        continue;
      }

      if (empty($values['exclude_' . $student_uid])) {
        if (empty($values['grade_' . $student_uid])) {
          $form_state->setErrorByName('grade_' . $student_uid, t('@name field is required.', ['@name' => t('Grade')]));
        }

        if (empty($values['grading_teacher_' . $student_uid])) {
          $form_state->setErrorByName('grading_teacher_' . $student_uid, t('@name field is required.', ['@name' => t('Grading teacher')]));
        }
      }
      else {
        if ($values['state'] === 'done' && $values['exclude_reason_' . $student_uid] === 'pending') {
          $form_state->setErrorByName('exclude_reason_' . $student_uid, t('You can not set state to "Done" if there is pending grades to set.'));
          $form_state->setErrorByName('state', t('You can not set state to "Done" if there is pending grades to set.'));
        }
      }
    }
  }

  public static function makeGradeStateHash(ParagraphInterface $paragraph, bool $client = FALSE, bool $default_exclude_student = FALSE): string {
    if ($paragraph->isNew()) {
      return 'new';
    }

    $student_uid = $paragraph->get('field_student')->target_id;

    $src = [];
    $src['exclude_' . $student_uid] = $default_exclude_student;
    $src['exclude_reason_' . $student_uid] = $paragraph->get('field_exclude_reason')->value ?? 'n_a';
    $src['grade_' . $student_uid] = $paragraph->get('field_grade')->target_id;
    $src['trial_' . $student_uid] = !!$paragraph->get('field_trial')->value;
    $src['grading_teacher_' . $student_uid] = $paragraph->get('field_teacher')->target_id;
    $src['comment_' . $student_uid] = $paragraph->get('field_comment')->value;

    $joint_grading_uids = array_column($paragraph->get('field_joint_grading')->getValue(), 'target_id');
    foreach ($joint_grading_uids as $joint_grading_uid) {
      $src['joint_grading_' . $student_uid . '[' . $joint_grading_uid . ']'] = TRUE;
    }

    if (!$client) {
      $src['student'] = $paragraph->get('field_student')->target_id;
      $src['school_subject'] = $paragraph->get('field_school_subject')->target_id;
      $src['round'] = $paragraph->get('field_grade_round')->target_id;
      $src['final_grade'] = !!$paragraph->get('field_final_grade')->value;
    }

    foreach ($src as $key => $value) {
      if (empty($value)) {
        unset($src[$key]);
      }
    }

    // Sort by array key.
    ksort($src);

    return Json::encode($src);
  }

  public static function submitForm($form, FormStateInterface $form_state) {
    $connection = \Drupal::service('database');
    $transaction = $connection->startTransaction();
    try {
      $done_init = $form_state->getValue('done_init');
      $values = $form_state->getValues();

      $subject_grade_node = self::getFormEntity($form_state);

      $grade_round = self::getQueryReferencedNode($form_state, 'grade_round');
      $grade_student_group = self::getQueryReferencedNode($form_state, 'grade_student_group');
      Cache::invalidateTags(['node:' .  $grade_round->id()]);

      // Create the registration paragraphs.
      $grade_paragraphs = [];
      /** @var \Drupal\Core\Entity\EntityStorageInterface $paragraph_storage */
      $paragraph_storage = \Drupal::entityTypeManager()
        ->getStorage('paragraph');

      if ($form_state->getValue('empty_grades', FALSE)) {
        $subject_grade_node->set('field_state', NULL);
      }
      else {
        $subject_grade_node->set('field_state', $form_state->getValue('state', 'started'));
        $students = $done_init ? [] : self::getGradingStudents($form_state);
        foreach ($students as $student_uid => $data) {
          if (!empty($data['paragraph']) && $data['is_default'] === FALSE) {
            /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
            $paragraph = $data['paragraph'];
            // Do not change for removed students.
            if (empty($data['user'])) {
              $grade_paragraphs[] = $paragraph;
              continue;
            }
          }
          else {
            // Skip default data that has not been overridden.
            if ($data['is_default'] === TRUE && !empty($values['exclude_' . $student_uid]) && $values['exclude_reason_' . $student_uid] === 'is_default') {
              continue;
            }

            /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
            $paragraph = $paragraph_storage->create([
              'type' => 'grade_registration',
              'langcode' => 'sv',
            ]);
          }

          $original_state_hash = self::makeGradeStateHash($paragraph);

          $initial_state = $form_state->getValue('initial_state', NULL);
          $new_state = $form_state->getValue('state', NULL);
          $gone_to_done = $initial_state !== 'done' && $new_state === 'done';

          if ($paragraph->isNew() || $gone_to_done) {
            $paragraph->set('field_created', \Drupal::time()->getRequestTime());
            $paragraph->set('field_registered_by', ['target_id' => \Drupal::currentUser()->id()]);
          }

          $paragraph->set('field_student', $data['user']);
          $paragraph->set('field_school_subject', ['target_id' => $subject_grade_node->get('field_school_subject')->target_id]);
          $paragraph->set('field_grade_round', $grade_round);
          $paragraph->set('field_final_grade', $grade_student_group->get('field_document_type')->value === 'final');

          if (!empty($values['exclude_' . $student_uid])) {
            $paragraph->set('field_comment', NULL);
            $paragraph->set('field_grade', NULL);
            $paragraph->set('field_teacher', NULL);
            $paragraph->set('field_joint_grading', []);
            $paragraph->set('field_final_grade', NULL);
            $paragraph->set('field_trial', NULL);

            $paragraph->set('field_exclude_reason', $values['exclude_reason_' . $student_uid]);
          }
          else {
            $paragraph->set('field_exclude_reason', NULL);

            $paragraph->set('field_trial', $values['trial_' . $student_uid]);
            $paragraph->set('field_comment', $values['comment_' . $student_uid]);
            $paragraph->set('field_grade', $values['grade_' . $student_uid]);
            $teacher_uid = $values['grading_teacher_' . $student_uid];
            $paragraph->set('field_teacher', $teacher_uid);

            $joint_grading = [];
            foreach ($values['joint_grading_' . $student_uid] ?? [] as $joint_grading_uid) {
              if ($joint_grading_uid === $teacher_uid || !$joint_grading_uid) {
                continue;
              }
              $joint_grading[] = ['target_id' => $joint_grading_uid];
            }
            $paragraph->set('field_joint_grading', $joint_grading);
          }

          $is_updated = self::makeGradeStateHash($paragraph) !== $original_state_hash;
          $paragraph->setNewRevision(FALSE);

          if ($is_updated) {
            $paragraph->setNewRevision(TRUE);
            $paragraph->set('field_created', \Drupal::time()->getRequestTime());
            $paragraph->set('field_registered_by', ['target_id' => \Drupal::currentUser()->id()]);
            $update_reason = $values['update_reason_' . $student_uid] ?? 'correction';
            $paragraph->set('field_update_reason', $update_reason);
          }

          if (!$paragraph->isNew()) {
            $paragraph->save();
          }

          $grade_paragraphs[] = $paragraph;
        }
      }
      if (!$done_init) {
        $subject_grade_node->set('field_grade_registration', $grade_paragraphs);
      }
    }
    catch (\Exception $e) {
      $transaction->rollBack();
      throw $e;
    }
  }
}
