<?php

namespace Drupal\simple_school_reports_grade_registration;

use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use Drupal\node\NodeInterface;
use Drupal\simple_school_reports_core\SchoolTypeHelper;

class StudentGroupIEFormAlter {

  public static function parentFormAlter(&$form, FormStateInterface $form_state) {
    if (empty($form['actions']['submit']['#submit'])) {
      $form['actions']['submit']['#submit'] = [];
    }
    $form['actions']['submit']['#submit'][] = [self::class, 'handleNodeSaveQueue'];
  }

  public static function basicInfoHandler(&$form, FormStateInterface $form_state) {
    $entity = self::getFormEntity($form);
    if (isset($form['group_grundlaggande_info']) && $entity) {
      $form['group_grundlaggande_info']['#open'] = $entity->isNew();
    }
  }

  public static function getFormEntity(array $form) {
    if (isset($form['#entity']) && $form['#entity'] instanceof NodeInterface) {
      return $form['#entity'];
    }
    return NULL;
  }

  public static function gradeSubjectHandler(&$form, FormStateInterface $form_state) {
    $form['#entity_builders'][] = [self::class, 'studentGroupBuilder'];

    $form['subject_divider'] = [
      '#type' => 'html_tag',
      '#tag' => 'h4',
      '#value' => t('Grading subjects'),
    ];

    $entity = self::getFormEntity($form);
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = \Drupal::entityTypeManager();
    $this_grade_round = 0;
    $form_object = $form_state->getFormObject();
    if ($form_object instanceof EntityFormInterface) {
      $this_grade_round = $form_object->getEntity()->id();
    }

    $subjects = $entity_type_manager->getStorage('taxonomy_term')->loadByProperties(['vid' => 'school_subject', 'field_school_type_versioned' => SchoolTypeHelper::getSchoolTypeVersions('GR'), 'status' => 1]);

    $query = $entity_type_manager->getStorage('node')->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'grade_round')
      ->condition('nid', $this_grade_round, '<>')
      ->sort('created', 'DESC');

    $or_condition = $query->orConditionGroup();
    $or_condition->condition('field_anonymized', 0)->notExists('field_anonymized');
    $query->condition($or_condition);

    $other_grade_round_nids = $query->execute();
    $other_grade_rounds = $entity_type_manager->getStorage('node')->loadMultiple($other_grade_round_nids);
    foreach ($other_grade_rounds as $grade_round) {
      $field_default_grade_round_options[$grade_round->id()] = $grade_round->label();
    }

    $teachers_options = [];
    $query = $entity_type_manager->getStorage('user')->getQuery()->accessCheck(FALSE)
      ->sort('field_first_name');

    $or_condition = $query->orConditionGroup();
    $or_condition->condition('uid', \Drupal::currentUser()->id())->condition('roles', ['teacher'], 'IN');
    $uids = $query->condition($or_condition)->execute();
    $users = $entity_type_manager->getStorage('user')->loadMultiple($uids);

    /** @var \Drupal\user\UserInterface $user */
    foreach ($users as $user) {
      $teachers_options[$user->id()] = $user->getDisplayName();
    }

    $subject_map = [];
    $grade_subject_defaults = [];

    $catalog_ids = Settings::get('ssr_catalog_id');

    /** @var \Drupal\taxonomy\TermInterface $subject */
    foreach ($subjects as $subject) {
      $code = $subject->get('field_subject_code_new')->value;
      if (!$code || !isset($catalog_ids[$code])) {
        continue;
      }
      $subject_map[$subject->id()] = $subject->getName();
      if ($subject->get('field_language_code')->value) {
        $subject_map[$subject->id()] .= ' ' . $subject->get('field_language_code')->value;
      }
      $grade_subject_defaults[$subject->id()] = [
        'field_teacher' => [],
        'field_default_grade_round' => NULL,
        'field_school_subject' => ['target_id' => $subject->id()],
        'field_state' => NULL,
        'grade_subject_nid' => NULL,
      ];
    }

    /** @var NodeInterface $grade_subject */
    foreach ($entity->get('field_grade_subject')->referencedEntities() as $grade_subject) {
      if (!$grade_subject->get('field_school_subject')->isEmpty()) {
        $subject = current($grade_subject->get('field_school_subject')->referencedEntities());
        $subject_map[$subject->id()] = $subject->getName();
        $grade_subject_defaults[$subject->id()] = [
          'field_teacher' =>  array_column($grade_subject->get('field_teacher')->getValue(), 'target_id'),
          'field_default_grade_round' => !$grade_subject->get('field_default_grade_round')->isEmpty() ? $grade_subject->get('field_default_grade_round')->target_id : NULL,
          'field_state' => $grade_subject->get('field_state')->value,
          'field_school_subject' => ['target_id' => $subject->id()],
          'grade_subject_nid' => $grade_subject->id(),
        ];
      }
    }
    asort($subject_map);
    $form['grade_subjects'] = [
      '#tree' => TRUE,
    ];


    foreach ($subject_map as $subject_id => $subject_name) {
      $form['grade_subjects'][$subject_id] = [
        '#type' => 'details',
        '#title' => t('@name - loading...', ['@name' => $subject_name]),
        '#open' => FALSE,
        '#attributes' => [
          'class' => ['subject-grade-container'],
        ],
      ];

      $form['grade_subjects'][$subject_id]['hidden_label'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $subject_name,
        '#attributes' => [
          'class' => ['label', 'hidden'],
        ],
      ];


      $form['grade_subjects'][$subject_id]['grade_subject_nid'] = [
        '#type' => 'value',
        '#value' => $grade_subject_defaults[$subject_id]['grade_subject_nid'],
      ];
      $form['grade_subjects'][$subject_id]['grade_subject_name'] = [
        '#type' => 'value',
        '#value' => $subject_name,
      ];
      $form['grade_subjects'][$subject_id]['field_school_subject'] = [
        '#type' => 'value',
        '#value' => $grade_subject_defaults[$subject_id]['field_school_subject'],
      ];
      $form['grade_subjects'][$subject_id]['field_state'] = [
        '#type' => 'value',
        '#value' => $grade_subject_defaults[$subject_id]['field_state'],
      ];


      if (!empty($field_default_grade_round_options)) {
        $default_id = $grade_subject_defaults[$subject_id]['field_default_grade_round'];
        if ($default_id && empty($field_default_grade_round_options[$default_id])) {
          $default_id = NULL;
        }
        $form['grade_subjects'][$subject_id]['field_default_grade_round'] = [
          '#type' => 'select',
          '#title' => t('Use grade from'),
          '#description' => t('If set, grades will be imported from this grade round as default grades when teachers add grades.'),
          '#default_value' => $default_id,
          '#empty_option' => t('Not set'),
          '#options' => $field_default_grade_round_options,
        ];
      }

      $form['grade_subjects'][$subject_id]['field_teacher'] = [
        '#type' => 'select',
        '#title' => t('Grading teachers'),
        '#description' => t('Only grading teachers will have permission to set grade for this subject and student group.'),
        '#default_value' => $grade_subject_defaults[$subject_id]['field_teacher'],
        '#options' => $teachers_options,
        '#multiple' => TRUE,
      ];

    }

  }

  public static function studentGroupBuilder($entity_type, NodeInterface $node,  &$form, FormStateInterface $form_state) {
    $delta = isset($form['#ief_row_delta']) ? $form['#ief_row_delta'] : NULL;
    if ($delta === NULL) {
      return;
    }
    $values = $form_state->getValue(['field_student_groups', 'form', $delta]);
    if ($values === NULL) {
      $values = $form_state->getValue(['field_student_groups', 'form', 'inline_entity_form', 'entities', $delta, 'form']);
      if ($values === NULL) {
        return;
      }
    }

    /** @var \Drupal\node\NodeStorageInterface $node_storage */
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');

    $grade_subject_nodes = [];
    if (!empty($values['grade_subjects'])) {

      foreach ($values['grade_subjects'] as $subject_id => $grade_subject_data) {
        $add = FALSE;
        if ($grade_subject_data['grade_subject_nid']) {
          /** @var NodeInterface $grade_subject_node */
          $grade_subject_node = $node_storage->load($grade_subject_data['grade_subject_nid']);
          $add = TRUE;
        }
        else {
          /** @var NodeInterface $grade_subject_node */
          $grade_subject_node = $node_storage->create([
            'type' => 'grade_subject',
            'langcode' => 'sv',
          ]);
        }
        $title = !empty($values['title'][0]['value']) ? $values['title'][0]['value'] : '';
        $grade_subject_node->set('title',  $title . ' - ' . $grade_subject_data['grade_subject_name']);

        if (!empty($grade_subject_data['field_default_grade_round'])) {
          $add = TRUE;
          $grade_subject_data['field_default_grade_round'] = ['target_id' => $grade_subject_data['field_default_grade_round']];
        }

        if (!empty($grade_subject_data['field_teacher'])) {
          $add = TRUE;
          foreach ($grade_subject_data['field_teacher'] as $key => $uid) {
            $grade_subject_data['field_teacher'][$key] = ['target_id' => $uid];
          }
        }

        foreach ($grade_subject_data as $field => $value) {
          if ($grade_subject_node->hasField($field)) {
            if (is_array($value)) {
              $value = array_values($value);
            }
            $grade_subject_node->set($field, $value);
          }
        }

        if ($add) {
          $grade_subject_nodes[] = $grade_subject_node;
          if (!$grade_subject_node->isNew()) {
            self::queNodeNeedSaving($form_state, $grade_subject_node);
          }
        }
      }
    }

    $node->set('field_grade_subject', $grade_subject_nodes);
    $node->setNewRevision(FALSE);
  }

  public static function queNodeNeedSaving($form_state, NodeInterface $node) {
    $node_save_queue = $form_state->get('node_save_queue') ?? [];
    $node_save_queue[$node->id()] = $node;
    $form_state->set('node_save_queue', $node_save_queue);
  }

  public static function handleNodeSaveQueue($form, FormStateInterface $form_state) {
    $node_save_queue = $form_state->get('node_save_queue') ?? [];
    foreach ($node_save_queue as $node_save) {
      if ($node_save instanceof NodeInterface) {
        $node_save->save();
      }
    }
  }
}
