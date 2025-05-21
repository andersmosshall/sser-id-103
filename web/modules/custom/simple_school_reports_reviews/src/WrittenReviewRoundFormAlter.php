<?php

namespace Drupal\simple_school_reports_reviews;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use Drupal\node\NodeInterface;

class WrittenReviewRoundFormAlter {

  public static function setDefaultDates(&$form, FormStateInterface $form_state) {
    $node = self::getFormEntity($form_state);
    $set_default_values = $node->isNew();
    if ($set_default_values) {
      $now_start = new DrupalDateTime();
      $now_start->setTime(0,0);
      $now_end = new DrupalDateTime();
      $now_end->setTime(23,59);
    }

    $form['#attached']['library'][] = 'simple_school_reports_reviews/written_review_round';
    $form['#attributes']['class'][] = 'written-review-round-form';

    if (!empty($form['field_document_date'])) {
      $form['field_document_date']['widget'][0]['value']['#date_increment'] = 86400;
      $form['field_document_date']['widget'][0]['value']['#description'] = NULL;

      if ($set_default_values) {
        $form['field_document_date']['widget'][0]['value']['#default_value'] = $now_start;
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

  public static function getWrittenReviewsSubjectMap(FormStateInterface $form_state, ?NodeInterface $node = NULL) {
    if (!$form_state->get('written_reviews_subject_map')) {
      $map = [];
      $node = $node ? $node : self::getFormEntity($form_state);
      $use_classes = \Drupal::moduleHandler()->moduleExists('simple_school_reports_class');

      if ($node && !$node->isNew()) {
        $subject_nids = array_column($node->get('field_written_reviews_subject')->getValue(), 'target_id');
        if (!empty($subject_nids)) {
          /** @var \Drupal\Core\Database\Connection $connection */
          $connection = \Drupal::service('database');

          $query = $connection->select('node__field_grade', 'g');
          $query->innerJoin('node__field_school_subject', 'sub', 'sub.entity_id = g.entity_id');
          $query->leftJoin('node__field_class', 'c', 'c.entity_id = g.entity_id');
          $results = $query->condition('g.entity_id', $subject_nids, 'IN')
            ->fields('g', ['entity_id', 'field_grade_value'])
            ->fields('c', ['field_class_target_id'])
            ->fields('sub', ['field_school_subject_target_id'])
            ->execute();

          foreach ($results as $result) {
            $class_id = $result->field_class_target_id ?? 'no_class';
            if (!$use_classes) {
              $class_id = 'no_class';
            }
            $map[$result->field_grade_value][$class_id][$result->field_school_subject_target_id] = $result->entity_id;
          }
        }
      }

      $form_state->set('written_reviews_subject_map', $map);
    }

    return $form_state->get('written_reviews_subject_map');
  }

  public static function WrittenReviewRoundFormAlter(&$form, FormStateInterface $form_state) {
    $node = self::getFormEntity($form_state);
    if (!$node) {
      return;
    }

    $grade_options = simple_school_reports_core_allowed_user_grade();
    unset($grade_options[-99]);
    unset($grade_options[99]);

    $written_reviews_subject_map = self::getWrittenReviewsSubjectMap($form_state);

    $default_student_grades = [];
    if ($node->isNew()) {
      foreach ($grade_options as $key => $name) {
        if ($key >= 4) {
          $default_student_grades[] = $key;
        }
      }
    }
    else {
      foreach ($grade_options as $key => $name) {
        if (!empty($written_reviews_subject_map[$key])) {
          $default_student_grades[] = $key;
        }
      }
    }

    $form['setup'] = [
      '#type' => 'container',
      '#weight' => 998,
    ];

    if (!$node->isNew()) {
      $form['do_setup'] = [
        '#type' => 'checkbox',
        '#title' => t('Define grades and subjects'),
        '#weight' => 997,
      ];
      $form['setup']['#states'] = [
        'visible' => [
          ':input[name="do_setup"]' => ['checked' => TRUE],
        ],
      ];
    }
    else {
      $form['do_setup'] = [
        '#type' => 'value',
        '#value' => TRUE,
      ];
    }

    $form['setup']['grades'] = [
      '#type' => 'checkboxes',
      '#title' => t('Select grades for this written reviews round'),
      '#options' => $grade_options,
      '#default_value' => $default_student_grades,
    ];

    $use_classes = \Drupal::moduleHandler()->moduleExists('simple_school_reports_class');
    if ($use_classes) {
      $form['setup']['grades']['#description'] = t('The selected grades will be devided up in their respective classes if applicable.');
    }

    $subject_options = [];
    $default_subjects = [];

    $subjects = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['vid' => 'school_subject', 'status' => 1]);
    /** @var \Drupal\taxonomy\TermInterface $subject */
    $catalog_ids = Settings::get('ssr_written_reviews_catalog_id');

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
    }

    asort($subject_options);
    foreach ($subject_options as $key => $value) {
      if (empty($default_subjects_to_unset[$key])) {
        $default_subjects[] = $key;
      }
    }

    if (!$node->isNew()) {
      $default_subjects = [];
      $written_reviews_subject_map_subjects = current($written_reviews_subject_map);
      foreach ($subject_options as $key => $value) {
        if (!empty($written_reviews_subject_map_subjects[$key])) {
          $default_subjects[] = $key;
        }
      }
    }

    $form['setup']['subjects'] = [
      '#type' => 'checkboxes',
      '#title' => t('Select subjects for this written reviews round'),
      '#options' => $subject_options,
      '#default_value' => $default_subjects,
    ];

    if (empty($form['actions']['submit']['#submit'])) {
      $form['actions']['submit']['#submit'] = [];
    }

    $form['actions']['submit']['#submit'][] = [self::class, 'postFormSubmit'];
  }

  public static function postFormSubmit(&$form, FormStateInterface $form_state) {
    if (!$form_state->getValue('do_setup')) {
      return;
    }

    $node = self::getFormEntity($form_state);
    $written_reviews_subject_map = self::getWrittenReviewsSubjectMap($form_state);

    $use_classes = \Drupal::moduleHandler()->moduleExists('simple_school_reports_class');

    $grades = [];
    $subjects = [];
    $classes = [];

    foreach ($form_state->getValue('grades', []) as $value => $selected) {
      if ($selected !== 0) {
        $grades[] = $value;
        $classes[$value] = [
          'no_class' => 'no_class',
        ];

        if ($use_classes) {
          /** @var \Drupal\simple_school_reports_class_support\Service\SsrClassServiceInterface $class_service */
          $class_service = \Drupal::service('simple_school_reports_class_support.class_service');
          $class_ids = $class_service->getClassIdsByGrade($value);
          foreach ($class_ids as $class_id) {
            $classes[$value][$class_id] = $class_id;
          }

          foreach (array_keys($written_reviews_subject_map[$value] ?? []) as $class_id) {
            $classes[$value][$class_id] = $class_id;
          }
        }
      }
    }

    foreach ($form_state->getValue('subjects', []) as $value => $selected) {
      if ($selected) {
        $subjects[] = $value;
      }
    }

    $batch = [
      'title' => t('Setup written reviews round'),
      'init_message' => t('Setup written reviews round'),
      'progress_message' => t('Processed @current out of @total.'),
      'finished' => [self::class, 'finished'],
      'operations' => [],
    ];

    if (!empty($grades)) {
      foreach ($grades as $grade) {
        foreach ($classes[$grade] as $class_id => $class_name) {
          foreach ($subjects as $subject) {
            $written_reviews_subject_nid = !empty($written_reviews_subject_map[$grade][$class_id][$subject]) ? $written_reviews_subject_map[$grade][$class_id][$subject] : NULL;
            $batch['operations'][] = [[self::class, 'includeGradeSubject'], [$node, $grade, $class_id, $subject, $written_reviews_subject_nid]];
          }
        }
      }

    }

    if (empty($batch['operations'])) {
      $node->set('field_written_reviews_subject', []);
      $node->setNewRevision(FALSE);
      $node->save();
      return;
    }

    batch_set($batch);
  }

  public static function includeGradeSubject($node, $grade, $class_id, $subject_id, $written_reviews_subject_nid, &$context) {
    $context['results']['node'] = $node;

    if (empty($context['results']['field_written_reviews_subject_nodes'])) {
      $context['results']['field_written_reviews_subject_nodes'] = [];
    }

    $subject = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($subject_id);
    if ($subject) {
      $node_storage = \Drupal::entityTypeManager()->getStorage('node');

      $written_reviews_subject_node = NULL;
      if ($written_reviews_subject_nid) {
        $written_reviews_subject_node = $node_storage->load($written_reviews_subject_nid);
      }
      if (!$written_reviews_subject_node) {
        $class_value = NULL;
        $class_fragment = '';
        $use_classes = \Drupal::moduleHandler()->moduleExists('simple_school_reports_class');
        if ($use_classes && !empty($class_id) && $class_id !== 'no_class') {
          $class = \Drupal::entityTypeManager()->getStorage('ssr_school_class')->load($class_id);
          if ($class) {
            $class_value = ['target_id' => $class->id()];
            $class_fragment = ' (' . $class->label() . ')';
          }
        }

        if ($class_id === 'no_class') {
          // Skip if there is no students in this grade with no class.
          $query = \Drupal::entityTypeManager()->getStorage('user')->getQuery()
            ->accessCheck(FALSE)
            ->condition('status', 1)
            ->condition('field_grade', $grade);

          $active_class_ids = \Drupal::entityTypeManager()->getStorage('ssr_school_class')->getQuery()
            ->accessCheck(FALSE)
            ->condition('status', 1)
            ->execute();

          $or_condition = $query->orConditionGroup()
            ->condition('field_class', NULL, 'IS NULL');
          if (!empty($active_class_ids)) {
            $or_condition->condition('field_class', $active_class_ids, 'NOT IN');
          }

          $query->condition($or_condition);
          $student_uids = $query->execute();
          if (empty($student_uids)) {
            return;
          }
        }

        $written_reviews_subject_node = $node_storage->create([
          'type' => 'written_reviews_subject_state',
          'title' => 'Årskurs ' . $grade . $class_fragment . ' - ' . $subject->label(),
          'langcode' => 'sv',
        ]);
        $written_reviews_subject_node->set('field_school_subject', $subject);
        $written_reviews_subject_node->set('field_grade', $grade);
        $written_reviews_subject_node->set('field_class', $class_value);
        $written_reviews_subject_node->setNewRevision(FALSE);
        $written_reviews_subject_node->save();
      }

      $context['results']['field_written_reviews_subject_nodes'][] = $written_reviews_subject_node;
    }
  }

  public static function finished($success, $results) {
    if ($success && !empty($results['field_written_reviews_subject_nodes']) && !empty($results['node'])) {
      try {
        $node = $results['node'];
        $node->set('field_written_reviews_subject', $results['field_written_reviews_subject_nodes']);
        $node->setNewRevision(FALSE);
        $node->save();
        return;
      }
      catch (\Exception $e) {
        // Do nothing.
      }
    }
    \Drupal::messenger()->addError('Something went wrong when written reviews round was setting up. Try again.');
  }

  public static function getFullTermStamp(NodeInterface $written_reviews_round): string {
    if ($written_reviews_round->bundle() !== 'written_reviews_round' || $written_reviews_round->get('field_term_type')->isEmpty() || $written_reviews_round->get('field_document_date')->isEmpty()) {
      return '';
    }

    $term_type_full_suffix = '';
    $timestamp = $written_reviews_round->get('field_document_date')->value;

    if ($timestamp) {
      $date = new DrupalDateTime();
      $date->setTimestamp($timestamp);
      $term_type_full_suffix = $date->format('Y')[2] . $date->format('Y')[3];
    }

    return $written_reviews_round->get('field_term_type')->value . $term_type_full_suffix;
  }
}
