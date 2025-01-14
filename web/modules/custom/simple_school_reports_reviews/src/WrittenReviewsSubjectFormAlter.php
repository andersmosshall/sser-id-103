<?php

namespace Drupal\simple_school_reports_reviews;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class WrittenReviewsSubjectFormAlter {

  public static function exposedViewsFormAlter(&$form, FormStateInterface $form_state) {
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    /** @var \Drupal\node\NodeInterface $written_reviews_round */
    $written_reviews_round = \Drupal::request()->get('node');
    if (is_numeric($written_reviews_round)) {
      $written_reviews_round = $node_storage->load($written_reviews_round);
    }

    if (!$written_reviews_round || $written_reviews_round->bundle() !== 'written_reviews_round') {
      return;
    }
    $cache = new CacheableMetadata();
    $cache->addCacheableDependency($written_reviews_round);
    $cache->applyTo($form);

    $written_reviews_subject_map = WrittenReviewRoundFormAlter::getWrittenReviewsSubjectMap($form_state, $written_reviews_round);
    if (empty($written_reviews_subject_map)) {
      return;
    }

    $grade_options = [];
    if (!empty($form['field_grade_value']['#options']['All'])) {
      $grade_options['All'] = $form['field_grade_value']['#options']['All'];
    }
    $subject_options = [];
    if (!empty($form['field_school_subject_target_id']['#options']['All'])) {
      $subject_options['All'] = $form['field_school_subject_target_id']['#options']['All'];
    }
    $class_options = [
      '' => t('All'),
    ];

    foreach ($written_reviews_subject_map as $grade => $classes) {
      $grade_options[$grade] = t('Grade @grade', ['@grade' => $grade]);
      foreach ($classes as $class_id => $subjects) {
        if ($class_id !== 'no_class') {
          $class = \Drupal::entityTypeManager()->getStorage('ssr_school_class')->load($class_id);
          if ($class) {
            $class_options[$class_id] = $class->label();
          }
          foreach ($subjects as $subject_id => $subject) {
            if (!empty($form['field_school_subject_target_id']['#options'][$subject_id])) {
              $subject_options[$subject_id] = $form['field_school_subject_target_id']['#options'][$subject_id];
            }
          }
        }
      }
    }


    if (!empty($form['field_grade_value'])) {
      $form['field_grade_value']['#options'] = $grade_options;
    }

    if (!empty($form['field_school_subject_target_id'])) {

      $form['field_school_subject_target_id']['#options'] = $subject_options;
    }

    if (!empty($form['field_class_target_id'])) {
      $form['field_class_target_id']['#type'] = 'select';
      unset($form['field_class_target_id']['#size']);
      $form['field_class_target_id']['#options'] = $class_options;

      $use_classes = \Drupal::moduleHandler()->moduleExists('simple_school_reports_class');
      if (!$use_classes) {
        $form['field_class_target_id']['#access'] = FALSE;
      }
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

  public static function getReviewingStudents(FormStateInterface $form_state) {
    if (!$form_state->has('reviewing_students')) {
      /** @var NodeInterface $course */
      $subject_review_node = self::getFormEntity($form_state);
      $reviewing_students = [];

      /** @var \Drupal\taxonomy\TermInterface $subject */
      $subject = current($subject_review_node->get('field_school_subject')->referencedEntities());
      if (!$subject) {
        throw new AccessDeniedHttpException();
      }

      $paragraphs_ref = [];
      $students_uids = [];

      if (!$subject_review_node->get('field_written_reviews')->isEmpty()) {
        foreach ($subject_review_node->get('field_written_reviews')->referencedEntities() as $paragraph) {
          if (!$paragraph->get('field_student')->isEmpty()) {
            $user_id = $paragraph->get('field_student')->target_id;
            $students_uids[$user_id] = $user_id;
            $paragraphs_ref[$user_id] = $paragraph;
          }
        }
      }

      $grade = $subject_review_node->get('field_grade')->value;
      $class = $subject_review_node->get('field_class')->target_id;
      $use_classes = \Drupal::moduleHandler()->moduleExists('simple_school_reports_class');
      if (!$use_classes) {
        $class = NULL;
      }

      $user_storage = \Drupal::entityTypeManager()->getStorage('user');
      if ($grade) {
        $query = $user_storage->getQuery()->accessCheck(FALSE)->condition('status', 1);

        $and_condition = $query->andConditionGroup();
        $and_condition->condition('field_grade', $grade);
        if ($class && $use_classes) {
          $and_condition->condition('field_class', $class);
        }

        if (!$class && $use_classes) {
          $active_class_ids = \Drupal::entityTypeManager()->getStorage('ssr_school_class')->getQuery()
            ->accessCheck(FALSE)
            ->condition('status', 1)
            ->execute();
          if (empty($active_class_ids)) {
            $active_class_ids = [0];
          }
          $class_condition = $query->orConditionGroup();
          $class_condition->condition('field_class', NULL, 'IS NULL');
          $class_condition->condition('field_class', $active_class_ids, 'NOT IN');
          $and_condition->condition($class_condition);
        }


        $or_condition = $query->orConditionGroup();
        $or_condition->condition($and_condition);
        if (!empty($students_uids)) {
          $or_condition->condition('uid', array_values($students_uids), 'IN');
        }

        $ids = $query->condition($or_condition)->sort('field_grade')->sort('field_first_name')->sort('field_last_name')->execute();

        if (!empty($ids)) {
          foreach ($user_storage->loadMultiple($ids) as $student) {
            $reviewing_students[$student->id()] = [
              'user' => $student,
              'paragraph' => NULL,
              'is_default' => TRUE,
            ];

            if (isset($paragraphs_ref[$student->id()])) {
              $reviewing_students[$student->id()]['paragraph'] = $paragraphs_ref[$student->id()];
              $reviewing_students[$student->id()]['is_default'] = FALSE;
            }
          }
        }
      }

      $form_state->set('reviewing_students', $reviewing_students);
    }
    return $form_state->get('reviewing_students');
  }

  public static function formAlter(&$form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'simple_school_reports_reviews/review_registration';
    $form['#attributes']['class'][] = 'written-review-registration-form';

    $subject_review_node = self::getFormEntity($form_state);
    $written_reviews_round_node = self::getQueryReferencedNode($form_state, 'written_reviews_round');

    $disabled = $written_reviews_round_node->get('field_locked')->value == 1;

    $form['title']['#access'] = FALSE;

    if (empty($form['actions']['submit']['#submit'])) {
      $form['actions']['submit']['#submit'] = [];
    }

    array_unshift($form['actions']['submit']['#submit'], [
      self::class,
      'submitForm',
    ]);

    $form['actions']['submit']['#disabled'] = $disabled;

    if ($disabled) {
      \Drupal::messenger()->addWarning(t('Current written reviews round is locked for new registrations.'));
    }

    $state_options = [
      'started' => t('Started'),
      'done' => t('Done'),
    ];

    $default_state = $subject_review_node->get('field_state')->value ?? NULL;

//    if ($default_state === 'done') {
//      $form['done_init_wrapper'] = [
//        '#type' => 'container',
//        '#states' => [
//          'visible' => [
//            ':input[name="done_init"]' => [
//              'checked' => TRUE,
//            ],
//          ],
//        ],
//        '#weight' => 997,
//      ];
//
//      $form['done_init_wrapper']['done_init'] = [
//        '#title' => t('Locked'),
//        '#description' => t('This grade registration is marked as done and therefore locked for registration. Unlock by unchecking this checkbox.'),
//        '#type' => 'checkbox',
//        '#default_value' => TRUE,
//        '#disabled' => $disabled,
//      ];
//    }

    $form['state_wrapper'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="done_init"]' => [
            'checked' => FALSE,
          ],
        ],
      ],
      '#weight' => 1000,
    ];

    $form['state_wrapper']['state'] = [
      '#title' => t('State', [], ['context' => 'ssr']),
      '#description' => t('Mark the state of this written review'),
      '#type' => 'radios',
      '#default_value' => $default_state,
      '#options' => $state_options,
      '#required' => TRUE,
      '#disabled' => $disabled,
    ];

    $states = [];
    if ($default_state === 'done' && !$disabled) {
      // Does not work with ck editor. Disable for now.
//      $states = [
//        'disabled' => [
//          ':input[name="done_init"]' => [
//            'checked' => TRUE,
//          ],
//        ],
//      ];
    }

    $form['review_registration'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['students-wrapper'],
      ],
      '#weight' => 999,
    ];

    $form['review_registration']['label'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => t('Students'),
    ];

    $review_options = [
      'na' => t('No assessment'),
      'ik' => t('Insufficient knowledge'),
      'ak' => t('Acceptable knowledge'),
      'mak' => t('More then acceptable knowledge'),
    ];

    $students = self::getReviewingStudents($form_state);
    foreach ($students as $student_uid => $data) {

      /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
      $paragraph = $data['paragraph'];

      /** @var \Drupal\user\UserInterface $student */
      $student = $data['user'];
      $form['review_registration'][$student_uid]['student'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['student-row']],
      ];

      $form['review_registration'][$student_uid]['student']['info'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['student-row--info-wrapper'],
        ],
      ];

      $student_name = $student->getDisplayName();
      $form['review_registration'][$student_uid]['student']['info']['name'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['student-row--info-name'],
        ],
        'value' => [
          '#prefix' => '<b>',
          '#suffix' => '</b>',
          '#markup' => $student_name,
        ],
      ];

      $form['review_registration'][$student_uid]['student']['review_registration'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['student-row--report-wrapper'],
        ],
      ];

      $form['review_registration'][$student_uid]['student']['review_registration']['review_wrapper'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['student-row--report-wrapper--review'],
        ],
      ];

      $default_review = NULL;
      if ($paragraph && $paragraph->get('field_review')->value) {
        $default_review = $paragraph->get('field_review')->value;
      }

      $review_label = t('Review', [], ['context' => 'written_reviews']);

      $form['review_registration'][$student_uid]['student']['review_registration']['review_wrapper']['review_' . $student_uid] = [
        '#title' => $review_label,
        '#type' => 'select',
        '#empty_option' => t('Not set'),
        '#options' => $review_options,
        '#default_value' => $default_review,
        '#disabled' => $disabled,
        '#states' => $states,
        '#attributes' => [
          'data-copy-to-all-label' => t('Copy @label from @source', ['@label' => $review_label, '@source' => $student_name]),
        ],
      ];

      $default_comment = '';
      if ($paragraph && !$paragraph->get('field_review_comment')->isEmpty()) {
        $default_comment = $paragraph->get('field_review_comment')->value;
      }

      $form['review_registration'][$student_uid]['student']['review_registration']['review_wrapper']['comment_' . $student_uid] = [
        '#title' => t('Short comment'),
        '#description' => t('The short comment will be shown in generated written review document'),
        '#type' => 'text_format',
        '#default_value' => $default_comment,
        '#disabled' => $disabled,
        '#states' => $states,
        '#rows' => 1,
        '#format' => 'wordsupported_format',
        '#allowed_formats' => ['wordsupported_format'],
        '#attributes' => [
          'data-copy-to-all-label' => t('Copy @label from @source', ['@label' => t('Comment'), '@source' => $student_name]),
        ],
      ];
    }

    if (count($students) > 1) {
      $form['select_copy'] = [
        '#type' => 'msr_input_copy',
        '#target_selectors' => ['.student-row select'],
      ];

      $form['comment_copy'] = [
        '#type' => 'msr_input_copy',
        '#target_selectors' => ['.student-row textarea'],
      ];
    }

  }

  public static function submitForm($form, FormStateInterface $form_state) {
    $connection = \Drupal::service('database');
    $transaction = $connection->startTransaction();
    try {
      $values = $form_state->getValues();

      $subject_review_node = self::getFormEntity($form_state);

      $written_reviews_round_node = self::getQueryReferencedNode($form_state, 'written_reviews_round');
      Cache::invalidateTags(['node:' .  $written_reviews_round_node->id()]);

      // Create the registration paragraphs.
      $review_paragraphs = [];
      /** @var \Drupal\Core\Entity\EntityStorageInterface $paragraph_storage */
      $paragraph_storage = \Drupal::entityTypeManager()
        ->getStorage('paragraph');


      $subject_review_node->set('field_state', $form_state->getValue('state', 'started'));

      $students = self::getReviewingStudents($form_state);
      foreach ($students as $student_uid => $data) {
        if (!empty($data['paragraph']) && $data['is_default'] === FALSE) {
          /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
          $paragraph = $data['paragraph'];
        }
        else {
          /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
          $paragraph = $paragraph_storage->create([
            'type' => 'written_review',
            'langcode' => 'sv',
          ]);
        }

        $paragraph->set('field_student', $data['user']);
        $paragraph->set('field_written_reviews_round', $written_reviews_round_node);

        if (!empty($values['review_' . $student_uid])) {
          $paragraph->set('field_review', $values['review_' . $student_uid]);
          if (!empty($values['comment_' . $student_uid])) {
            $paragraph->set('field_review_comment', $values['comment_' . $student_uid]);
          }
          else {
            $paragraph->set('field_review_comment', NULL);
          }
        }
        else {
          // Skip this paragraph completely.
          continue;
        }

        $paragraph->setNewRevision(FALSE);

        if (!$paragraph->isNew()) {
          $paragraph->save();
        }

        $review_paragraphs[] = $paragraph;
      }

      $subject_review_node->set('field_written_reviews', $review_paragraphs);
    }
    catch (\Exception $e) {
      $transaction->rollBack();
      throw $e;
    }
  }
}
