<?php

namespace Drupal\simple_school_reports_student_di;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use Drupal\node\NodeInterface;

class DIRoundFormAlter {

  public static function basicInfoHandlerAfterBuild($form, FormStateInterface $form_state) {
    $entity = self::getFormEntity($form_state);
    if (isset($form['group_grundlaggande_info']) && $entity) {
      $form['group_grundlaggande_info']['#open'] = $entity->isNew();
    }
    return $form;
  }

  public static function getFormEntity(FormStateInterface $form_state) {
    $form_object = $form_state->getFormObject();
    if ($form_object instanceof EntityFormInterface) {
      return $form_object->getEntity();
    }
    return NULL;
  }

  public static function newStudentDevelopmentInterviewFormAlter(&$form, FormStateInterface $form_state) {
    unset($form['field_student_groups']);
    $options = [
      'generate' => t('Generate student groups automatically and assign teachers by analysing the mentors in each grade'),
      'no_generate' => t('Do not generate any student groups, I want to add them manually'),
    ];
    $form['pre_save_option'] = [
      '#type' => 'radios',
      '#title' => t('Generate student groups'),
      '#options' => $options,
      '#default_value' => 'generate',
    ];

    $grade_options = simple_school_reports_core_allowed_user_grade();
    unset($grade_options[-99]);
    unset($grade_options[99]);

    $default_student_grades = [];
    foreach ($grade_options as $key => $name) {
      $default_student_grades[] = $key;
    }

    $form['student_grades_wrapper'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="pre_save_option"]' => ['value' => 'generate'],
        ],
      ],
      'student_grades' => [
        '#type' => 'checkboxes',
        '#title' => t('Select grades for generating student groups.'),
        '#options' => $grade_options,
        '#default_value' => $default_student_grades,
      ],
    ];

    $use_classes = \Drupal::moduleHandler()->moduleExists('simple_school_reports_class');
    if ($use_classes) {
      $form['student_grades_wrapper']['student_grades']['#description'] = t('The selected grades will be devided up in their respective classes if applicable.');
    }

    $form['student_grades_settings'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => t('Select settings to be applied to all student groups'),
      '#description' => t('These settings can also be set or modified for each student group individually later.'),
      '#states' => [
        'visible' => [
          ':input[name="pre_save_option"]' => ['value' => 'generate'],
        ],
      ],
    ];

    $module_handler = \Drupal::service('module_handler');
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');

    if ($module_handler->moduleExists('simple_school_reports_iup')) {
      $iup_round_options = [
        NULL => t('None'),
      ];

      $iup_round_nids = $node_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('type', 'iup_round')
        ->condition('status', 1)
        ->sort('field_document_date', 'DESC')
        ->execute();

      $iup_rounds = $node_storage->loadMultiple($iup_round_nids);
      foreach ($iup_rounds as $iup_round) {
        $iup_round_options[$iup_round->id()] = $iup_round->label();
      }

      $form['student_grades_settings']['iup_round'] = [
        '#type' => 'select',
        '#title' => t('IUP round'),
        '#options' => $iup_round_options,
        '#default_value' => NULL,
        '#description' => t('Select the IUP round to be used for all student groups.'),
      ];
    }

    if ($module_handler->moduleExists('simple_school_reports_reviews')) {
      $written_review_round_options = [
        NULL => t('None'),
      ];

      $written_review_nids = $node_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('type', 'written_reviews_round')
        ->condition('status', 1)
        ->sort('field_document_date', 'DESC')
        ->execute();

      $written_reviews = $node_storage->loadMultiple($written_review_nids);
      foreach ($written_reviews as $written_review) {
        $written_review_round_options[$written_review->id()] = $written_review->label();
      }

      $form['student_grades_settings']['written_review_round'] = [
        '#type' => 'select',
        '#title' => t('Written review round'),
        '#options' => $written_review_round_options,
        '#default_value' => NULL,
        '#description' => t('Select the written review round to be used for all student groups.'),
      ];
    }

    $form['student_grades_settings']['remind_student'] = [
      '#type' => 'checkbox',
      '#title' => t('Remind student'),
      '#default_value' => FALSE,
      '#description' => t('Select if the remind student setting for all student groups.'),
    ];

    if (empty($form['actions']['submit']['#submit'])) {
      $form['actions']['submit']['#submit'] = [];
    }
    $form['actions']['submit']['#submit'][] = [self::class, 'batchGenerateStudentGroups'];
  }

  public static function batchGenerateStudentGroups(&$form, FormStateInterface $form_state) {
    $entity = self::getFormEntity($form_state);
    $nid = $entity ? $entity->id() : NULL;
    if (!$nid) {
      return;
    }
    $form_state->setRedirect('entity.node.edit_form', ['node' => $nid]);


    if ($form_state->getValue('pre_save_option') !== 'generate') {
      return;
    }

    $grades = [];

    foreach ($form_state->getValue('student_grades', []) as $value => $selected) {
      if ($selected !== 0) {
        $grades[] = $value;
      }
    }

    if (!empty($grades)) {
      $student_map = [];
      $class_map = [];
      $use_classes = \Drupal::moduleHandler()->moduleExists('simple_school_reports_class');

      /** @var \Drupal\Core\Database\Connection $connection */
      $connection = \Drupal::service('database');

      $query = $connection->select('user__roles', 'r');
      $query->innerJoin('user__field_grade', 'g', 'g.entity_id = r.entity_id');
      $query->leftJoin('user__field_class', 'c', 'c.entity_id = r.entity_id');
      $query->leftJoin('user__field_mentor', 'm', 'm.entity_id = r.entity_id');
      $query->condition('r.roles_target_id', 'student')
        ->fields('c',['field_class_target_id'])
        ->fields('g',['field_grade_value'])
        ->fields('m',['field_mentor_target_id'])
        ->fields('r',['entity_id']);
      $results = $query->execute();

      /** @var \Drupal\simple_school_reports_core\Service\UserMetaDataServiceInterface $user_meta_data */
      $user_meta_data = \Drupal::service('simple_school_reports_core.user_meta_data');
      $user_weight = $user_meta_data->getUserWeights();

      $class_ids = [];

      foreach ($results as $result) {
        $class_id = $use_classes ? $result->field_class_target_id : NULL;
        if ($class_id) {
          $class_ids[$class_id] = $class_id;
        }
        if (!$class_id) {
          $class_id = 'none';
        }
        $class_map[$result->field_grade_value][$class_id] = $class_id;

        if (isset($user_weight[$result->entity_id])) {
          $student_map[$result->field_grade_value][$class_id][$result->entity_id] = ['target_id' => $result->entity_id];
        }
        if (isset($user_weight[$result->field_mentor_target_id])) {
          $teacher_map[$result->field_grade_value][$class_id][$result->field_mentor_target_id] = ['target_id' => $result->field_mentor_target_id];
        }
      }

      $class_names = [
        'none' => empty($class_ids) ? '' : 'Okänd klass',
      ];
      if (!empty($class_ids)) {
        foreach (\Drupal::entityTypeManager()->getStorage('ssr_school_class')->loadMultiple($class_ids) as $class) {
          $class_names[$class->id()] = $class->label();
        }
      }

      $grade_student_groups = [];

      /** @var \Drupal\node\NodeStorageInterface $node_storage */
      $node_storage = \Drupal::entityTypeManager()->getStorage('node');
      foreach ($grades as $grade) {
        if (empty($class_map[$grade])) {
          continue;
        }

        $grade_options = simple_school_reports_core_allowed_user_grade();
        $grade_label = $grade_options[$grade] ?? $grade;

        $grade_class_names = $class_names;
        if (count($class_map[$grade]) <= 1) {
          $grade_class_names['none'] = '';
        }

        foreach ($class_map[$grade] as $class_id) {
          $class_name = $grade_class_names[$class_id] ?? '';
          $title_prefix = $class_name
            ? mb_strtolower($grade_label) . ' (' . $class_name . ')'
            : mb_strtolower($grade_label);
          if (empty($student_map[$grade][$class_id])) {
            \Drupal::messenger()->addWarning(t('There is no students in grade @grade', ['@grade' => $title_prefix]));
            continue;
          }

          $grade_student_group = $node_storage->create([
            'type' => 'di_student_group',
            'title' => 'Årskurs ' . $title_prefix,
            'langcode' => 'sv',
          ]);

          $grade_student_group->set('field_student', array_values($student_map[$grade][$class_id]));
          if ($iup_round = $form_state->getValue('iup_round')) {
            $grade_student_group->set('field_iup_round', ['target_id' => $iup_round]);
          }
          if ($written_review = $form_state->getValue('written_review_round')) {
            $grade_student_group->set('field_written_reviews_round', ['target_id' => $written_review]);
          }

          $remind_student = (bool) $form_state->getValue('remind_student', FALSE);
          $grade_student_group->set('field_remind_student', $remind_student);

          $grade_student_group->setNewRevision(FALSE);
          $grade_student_group->save();
          $grade_student_groups[] = $grade_student_group;
        }
      }

      if (!empty($grade_student_groups)) {
        $entity->set('field_student_groups', $grade_student_groups);
        $entity->setNewRevision(FALSE);
        $entity->save();
      }
    }
  }

  public static function handleRedirect($form, FormStateInterface $form_state) {
    $form_state->setRedirect('view.student_development_interview_rounds.list');
  }
}
