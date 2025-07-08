<?php

namespace Drupal\simple_school_reports_grade_registration;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use Drupal\node\NodeInterface;
use Drupal\simple_school_reports_core\SchoolGradeHelper;

class GradeRoundFormAlter {

  public static function basicInfoHandlerAfterBuild($form, FormStateInterface $form_state) {
    $entity = self::getFormEntity($form_state);
    if (isset($form['group_grundlaggande_info']) && $entity) {
      $form['group_grundlaggande_info']['#open'] = $entity->isNew();
    }
    return $form;
  }

  public static function setDefaultDates(&$form, FormStateInterface $form_state) {
    $node = self::getFormEntity($form_state);
    $set_default_values = $node->isNew();
    if ($set_default_values) {
      $now_start = new DrupalDateTime();
      $now_start->setTime(0,0);
      $now_end = new DrupalDateTime();
      $now_end->setTime(23,59);
    }

    $form['#attached']['library'][] = 'simple_school_reports_grade_registration/grade_round';
    $form['#attributes']['class'][] = 'grade-round-form';

    if (!empty($form['field_document_date'])) {
      $form['field_document_date']['widget'][0]['value']['#date_increment'] = 86400;
      $form['field_document_date']['widget'][0]['value']['#description'] = NULL;
      if ($set_default_values) {
        $form['field_document_date']['widget'][0]['value']['#default_value'] = $now_start;
      }
    }

    if (!empty($form['field_invalid_absence_from'])) {
      $form['field_invalid_absence_from']['widget'][0]['value']['#date_increment'] = 86400;
      $form['field_invalid_absence_from']['widget'][0]['value']['#description'] = NULL;
      if ($set_default_values) {
        $form['field_invalid_absence_from']['widget'][0]['value']['#default_value'] = $now_start;
      }
    }

    if (!empty($form['field_invalid_absence_to'])) {
      $form['field_invalid_absence_to']['widget'][0]['value']['#date_increment'] = 86400;
      $form['field_invalid_absence_to']['widget'][0]['value']['#description'] = NULL;
      if ($set_default_values) {
        $form['field_invalid_absence_to']['widget'][0]['value']['#default_value'] = $now_end;
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

  public static function newGradeRoundFormAlter(&$form, FormStateInterface $form_state) {
    unset($form['field_student_groups']);
    $options = [
      'generate' => t('Generate student groups automatically and assign grading teachers by analysing the courses in simple school reports'),
      'no_generate' => t('Do not generate any student groups, I want to add them manually'),
    ];
    $form['pre_save_option'] = [
      '#type' => 'radios',
      '#title' => t('Generate student groups'),
      '#options' => $options,
      '#default_value' => 'generate',
    ];

    $grade_options = SchoolGradeHelper::getSchoolGradesMap(['FKLASS', 'GR']);

    $default_student_grades = [];
    foreach ($grade_options as $key => $name) {
      if ($key >= 6) {
        $default_student_grades[] = $key;
      }
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

    $subject_options = [];
    $default_subjects = [];

    $subjects = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['vid' => 'school_subject', 'status' => 1]);
    /** @var \Drupal\taxonomy\TermInterface $subject */
    $catalog_ids = Settings::get('ssr_catalog_id');

    $default_subjects_to_unset = [];

    /** @var \Drupal\taxonomy\TermInterface $subject */
    foreach ($subjects as $subject) {
      $code = $subject->get('field_subject_code')->value;
      if (!$code || !isset($catalog_ids[$code])) {
        continue;
      }
      $subject_options[$subject->id()] = $subject->getName();

      if ($subject->get('field_language_code')->value) {
        $subject_options[$subject->id()] .= ' ' . $subject->get('field_language_code')->value;
      }

      if ($subject->get('field_block_parent')->target_id) {
        $default_subjects_to_unset[$subject->get('field_block_parent')->target_id] = TRUE;
      }
      if (!$subject->get('field_subject_specify')->isEmpty()) {
        $default_subjects_to_unset[$subject->id()] = TRUE;
      }
    }

    asort($subject_options);
    foreach ($subject_options as $key => $value) {
      if (empty($default_subjects_to_unset[$key])) {
        $default_subjects[] = $key;
      }
    }

    $form['subjects_wrapper'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="pre_save_option"]' => ['value' => 'generate'],
        ],
      ],
      'subjects' => [
        '#type' => 'checkboxes',
        '#title' => t('Select for which subjects grading teachers will be searched for.'),
        '#options' => $subject_options,
        '#default_value' => $default_subjects,
      ],
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
    $subjects = [];

    foreach ($form_state->getValue('student_grades', []) as $value => $selected) {
      if ($selected !== 0) {
        $grades[] = $value;
      }
    }

    foreach ($form_state->getValue('subjects', []) as $value => $selected) {
      if ($selected) {
        $subjects[] = $value;
      }
    }

    if (!empty($grades)) {
      $batch = [
        'title' => t('Generating student groups'),
        'init_message' => t('Generating student groups'),
        'progress_message' => t('Processed @current out of @total.'),
        'operations' => [],
      ];


      $principle = NULL;

      $user_storage = \Drupal::entityTypeManager()->getStorage('user');
      /** @var \Drupal\user\UserInterface $current_user */
      $current_user = $user_storage->load(\Drupal::currentUser()->id());
      if ($current_user->hasRole('principle')) {
        $principle = $current_user;
      }
      else {
        $principle_uid = current($user_storage->getQuery()->accessCheck(FALSE)->condition('roles', 'principle')->execute());
        if ($principle_uid) {
          $principle = $user_storage->load($principle_uid);
        }
      }

      if (!$principle) {
        \Drupal::messenger()->addError('There is no principle in the system, no student groups were added.');
        return;
      }


      $student_map = [];

      $use_classes = \Drupal::moduleHandler()->moduleExists('simple_school_reports_class');

      /** @var \Drupal\Core\Database\Connection $connection */
      $connection = \Drupal::service('database');

      $query = $connection->select('user__roles', 'r');
      $query->innerJoin('user__field_grade', 'g', 'g.entity_id = r.entity_id');
      $query->leftJoin('user__field_class', 'c', 'c.entity_id = r.entity_id');
      $query->condition('r.roles_target_id', 'student')
        ->fields('c',['field_class_target_id'])
        ->fields('g',['field_grade_value'])
        ->fields('r',['entity_id']);
      $results = $query->execute();

      $allowed_teachers = array_values($user_storage->getQuery()->accessCheck(FALSE)->condition('status', 1)->execute());

      /** @var \Drupal\simple_school_reports_core\Service\UserMetaDataServiceInterface $user_meta_data */
      $user_meta_data = \Drupal::service('simple_school_reports_core.user_meta_data');
      $user_weight = $user_meta_data->getUserWeights();

      $class_ids = [];

      foreach ($results as $result) {
        if (isset($user_weight[$result->entity_id])) {
          $class_id = $use_classes ? $result->field_class_target_id : NULL;

          if ($class_id) {
            $class_ids[$class_id] = $class_id;
          }

          if (!$class_id) {
            $class_id = 'none';
          }

          $student_map[$result->field_grade_value][$class_id][$result->entity_id] = ['target_id' => $result->entity_id];
        }
      }

      $class_map = [];
      if ($use_classes && !empty($class_ids)) {
        foreach (\Drupal::entityTypeManager()->getStorage('ssr_school_class')->loadMultiple($class_ids) as $class) {
          $class_map[$class->id()] = $class->label();
        }
      }

      $grade_student_groups = [];

      /** @var \Drupal\node\NodeStorageInterface $node_storage */
      $node_storage = \Drupal::entityTypeManager()->getStorage('node');
      foreach ($grades as $grade) {
        if (empty($student_map[$grade])) {
          \Drupal::messenger()->addWarning(t('There is no students in grade @grade', ['@grade' => $grade]));
          continue;
        }

        $has_classes = $use_classes && count($student_map[$grade]) > 1;

        foreach ($student_map[$grade] as $class_id => $target_students) {
          $label_suffix = $has_classes ? ' Okänd klass' : '';
          if (!empty($class_map[$class_id])) {
            $label_suffix = ' ' . $class_map[$class_id];
          }

          $grade_student_group = $node_storage->create([
            'type' => 'grade_student_group',
            'title' => $label_suffix
              ? 'Åk ' . $grade . $label_suffix
              : 'Årskurs ' . $grade,
            'langcode' => 'sv',
          ]);

          /** @var \Drupal\Core\Extension\ModuleHandlerInterface $module_handler */
          $module_handler = \Drupal::service('module_handler');
          $grade_system = $grade < 6 && $module_handler->moduleExists('simple_school_reports_geg_grade_registration') ? 'geg_grade_system' : 'af_grade_system';
          $grade_student_group->set('field_grade_system', $grade_system);
          $document_type = $grade < 6 ? 'none' : 'term';

          if ($grade === 9 && $entity->get('field_term_type')->value === 'vt') {
            $document_type = 'final';
          }
          $grade_student_group->set('field_document_type', $document_type);
          $grade_student_group->set('field_grade', $grade);

          if (!empty($class_id) && $class_id !== 'none') {
            $grade_student_group->set('field_class', ['target_id' => $class_id]);
          }

          $grade_student_group->set('field_student', array_values($target_students));
          $grade_student_group->set('field_principle', $principle);

          $grade_student_group->setNewRevision(FALSE);
          $grade_student_group->save();
          $grade_student_groups[] = $grade_student_group;

          $batch['operations'][] = [[self::class, 'setGradeSubjects'], [$grade_student_group, array_keys($target_students), $subjects, $allowed_teachers]];
        }
      }

      if (!empty($grade_student_groups)) {
        $entity->set('field_student_groups', $grade_student_groups);
        $entity->setNewRevision(FALSE);
        $entity->save();
        batch_set($batch);
      }
    }
  }

  public static function handleRedirect($form, FormStateInterface $form_state) {
    $form_state->setRedirect('view.grade_registration_rounds.active');
  }

  public static function setGradeSubjects(NodeInterface $grade_student_group, array $student_uids, array $subject_ids, array $allowed_teachers) {
    $grade_subjects = [];

    /** @var \Drupal\node\NodeStorageInterface $node_storage */
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');


    /** @var \Drupal\Core\Database\Connection $connection */
    $connection = \Drupal::service('database');

    $query = $connection->select('node__field_teacher', 't');
    $query->innerJoin('node__field_student', 's', 's.entity_id = t.entity_id');
    $query->innerJoin('node__field_school_subject', 'sub', 'sub.entity_id = t.entity_id');
    $query->condition('t.bundle', 'course')
      ->condition('sub.field_school_subject_target_id', $subject_ids, 'IN')
      ->condition('s.field_student_target_id', $student_uids, 'IN')
      ->condition('t.field_teacher_target_id', $allowed_teachers, 'IN')
      ->fields('sub',['field_school_subject_target_id'])
      ->fields('t',['field_teacher_target_id']);
    $results = $query->execute();

    $teachers = [];

    foreach ($results as $result) {
      $teachers[$result->field_school_subject_target_id][$result->field_teacher_target_id] = ['target_id' => $result->field_teacher_target_id];
    }

    $subject_ids = array_keys($teachers);
    $subjects = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadMultiple($subject_ids);

    foreach ($subjects as $subject) {
      $subject_teachers = $teachers[$subject->id()];

      /** @var NodeInterface $grade_subject_node */
      $grade_subject_node = $node_storage->create([
        'type' => 'grade_subject',
        'title' => $grade_student_group->label() . ' - ' . $subject->label(),
        'langcode' => 'sv',
      ]);

      $grade_subject_node->set('field_school_subject', $subject);
      $grade_subject_node->set('field_teacher', $subject_teachers);
      $grade_subject_node->setNewRevision(FALSE);
      $grade_subject_node->save();
      $grade_subjects[] = $grade_subject_node;
    }

    $grade_student_group->set('field_grade_subject', $grade_subjects);
    $grade_student_group->setNewRevision(FALSE);
    $grade_student_group->save();

  }

  public static function getFullTermStamp(NodeInterface $grade_round): string {
    if ($grade_round->bundle() !== 'grade_round' || $grade_round->get('field_term_type')->isEmpty() || $grade_round->get('field_document_date')->isEmpty()) {
      return '';
    }

    $term_type_full_suffix = '';
    $timestamp = $grade_round->get('field_document_date')->value;

    if ($timestamp) {
      $date = new DrupalDateTime();
      $date->setTimestamp($timestamp);
      $term_type_full_suffix = $date->format('Y')[2] . $date->format('Y')[3];
    }

    return $grade_round->get('field_term_type')->value . $term_type_full_suffix;
  }
}
