<?php

namespace Drupal\simple_school_reports_iup;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\simple_school_reports_core\Service\ReplaceTokenServiceInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class IUPFormAlter {

  public static function setDefaultDates(&$form, FormStateInterface $form_state) {
    $node = self::getFormEntity($form_state);
    $set_default_values = $node->isNew();
    if ($set_default_values) {
      $now_start = new DrupalDateTime();
      $now_start->setTime(0,0);
      $now_end = new DrupalDateTime();
      $now_end->setTime(23,59);
    }

    $form['#attached']['library'][] = 'simple_school_reports_iup/iup_round';
    $form['#attributes']['class'][] = 'iup-round-form';

    if (!empty($form['field_document_date'])) {
      $form['field_document_date']['widget'][0]['value']['#date_increment'] = 86400;
      $form['field_document_date']['widget'][0]['value']['#description'] = NULL;

      if ($set_default_values) {
        $form['field_document_date']['widget'][0]['value']['#default_value'] = $now_start;
      }
    }
  }

  public static function exposedViewsFormAlter(&$form, FormStateInterface $form_state) {
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    /** @var \Drupal\node\NodeInterface $iup_round */
    $iup_round = \Drupal::request()->get('node');
    if (is_numeric($iup_round)) {
      $iup_round = $node_storage->load($iup_round);
    }

    if (!$iup_round || $iup_round->bundle() !== 'iup_round') {
      return;
    }
    $cache = new CacheableMetadata();
    $cache->addCacheableDependency($iup_round);
    $cache->applyTo($form);

    $grades = simple_school_reports_core_allowed_user_grade();
    unset($grades[0]);
    unset($grades[-99]);
    unset($grades[99]);

    if (!empty($form['field_grade_value'])) {
      $grade_options = [];
      if (!empty($form['field_grade_value']['#options']['All'])) {
        $grade_options['All'] = $form['field_grade_value']['#options']['All'];
      }
      foreach (array_keys($grades) as $grade) {
        $grade_options[$grade] = t('Grade @grade', ['@grade' => $grade]);
      }
      $form['field_grade_value']['#options'] = $grade_options;
    }
  }

  public static function getFormEntity(FormStateInterface $form_state) {
    $form_object = $form_state->getFormObject();
    if ($form_object instanceof EntityFormInterface) {
      return $form_object->getEntity();
    }
    return NULL;
  }

  public static function formAlter(&$form, FormStateInterface $form_state) {
    $node = self::getFormEntity($form_state);

    if (!$node || $node->bundle() !== 'iup') {
      return;
    }

    /** @var \Drupal\node\NodeInterface $iup_round_node */
    $iup_round_node = current($node->get('field_iup_round')->referencedEntities());

    /** @var \Drupal\user\UserInterface $student */
    $student = current($node->get('field_student')->referencedEntities());
    if (!$student || !$iup_round_node) {
      return;
    }
    $locked = $iup_round_node->get('field_locked')->value;
    // Redirect if locked.
    if ($locked) {
      \Drupal::messenger()->addError('This IUP-round is locked, IUPs can not be edited.');
      throw new AccessDeniedHttpException();
    }

    $state_options = [
      'started' => t('Started'),
      'done' => t('Done and agreed'),
    ];
    $default_state = $node->get('field_state')->value ?? NULL;

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

    $form['state_wrapper']['field_state'] = [
      '#title' => t('State', [], ['context' => 'ssr']),
      '#description' => t('Mark the state of this IUP registration'),
      '#type' => 'radios',
      '#default_value' => $default_state,
      '#options' => $state_options,
      '#required' => TRUE,
    ];

    $form['field_document_date']['#weight'] = 1001;
    $form['field_document_date']['#states']['visible'][] = [
      ':input[name="field_state"]' => [
        'value' => 'done',
      ],
    ];
    if ($node->get('field_state')->value !== 'done') {
      $date = (new \DateTime())->format('Y-m-d');
      $form['field_document_date']['widget'][0]['value']['#default_value'] = $date;
    }

    if ($default_state === 'done') {
      $messages = [];
      $messages['warning'][] = t('This IUP is marked as done and agreed, it is not advised to edit it.');

      $form['done_message_wrapper'] = [
        '#type' => 'container',
        '#states' => [
          'visible' => [
            ':input[name="field_state"]' => [
              'value' => 'done',
            ],
          ],
        ],
        '#weight' => -999,
      ];
      $form['done_message_wrapper']['message'] = [
        '#theme' => 'status_messages',
        '#message_list' => $messages,
        '#status_headings' => [
          'status' => t('Status message'),
          'error' => t('Error message'),
          'warning' => t('Warning message'),
        ],
      ];
    }


    $replace_context = [
      ReplaceTokenServiceInterface::STUDENT_REPLACE_TOKENS => $student,
    ];


    $form['field_iup_round']['widget']['#disabled'] = TRUE;


    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = \Drupal::entityTypeManager();
    $standard_phrases = $entity_type_manager->getStorage('taxonomy_term')->loadByProperties(['vid' => 'iup_standard_phrase', 'status' => 1]);

    /** @var \Drupal\simple_school_reports_core\Service\ReplaceTokenServiceInterface $replace_service */
    $replace_service = \Drupal::service('simple_school_reports_core.replace_token_service');

    usort($standard_phrases, function ($a, $b) {
      $weight_a = $a->getWeight();
      $weight_b = $b->getWeight();
      return $weight_a <=> $weight_b;
    });

    $options = [];
    /** @var \Drupal\taxonomy\TermInterface $standard_phrase */
    foreach ($standard_phrases as $standard_phrase) {

      $fields = array_column($standard_phrase->get('field_iup_context')->getValue(), 'value');
      if (!empty($fields)) {
        $item = $standard_phrase->label();
        $item = $replace_service->handleText($item, $replace_context);
        $item = strip_tags($item);
        $text = $item;

        if ($standard_phrase->get('field_use_long_phrase')->value) {
          $text = $standard_phrase->get('field_long_phrase')->value;
          $format = $standard_phrase->get('field_long_phrase')->format ?? 'plain_text_ck';
          $text = (string) check_markup($text, $format);
          $text = str_replace(['<p>', '</p>'], '', $text);
          $text = $replace_service->handleText($text, $replace_context);
        }

        foreach ($fields as $field) {
          $options[$field][$standard_phrase->id()] = [
            'label' => $item,
            'text' => $text,
          ];
        }
      }
    }


    if (!empty($options)) {
      foreach ($options as $field => $local_options) {
        $form[$field . '_sp'] = [
          '#type'   => 'container',
          '#weight' => -10,
        ];
        $form[$field . '_sp']['sp'] = [
          '#type' => 'standard_phrase_select',
          '#options' => $local_options,
          '#ck_editor_id' => 'edit-' . str_replace('_', '-', $field) . '-0-value',
        ];
      }
    }

    if (\Drupal::requestStack()->getCurrentRequest()->get('open_first')) {
      $form['#after_build'][] = [self::class, 'openFirstAfterBuild'];
    }
  }

  public static function openFirstAfterBuild($form, FormStateInterface $form_state) {
    $form['group_hdig']['#open'] = TRUE;
    return $form;
  }

  public static function iefIUPGoalFormAlter(&$form, FormStateInterface $form_state) {
    $form['field_teacher']['widget']['add_more']['#value'] = t('Add another teacher');

    // Work around to force in a title description.
    $form['title_description'] = [
      '#type'   => 'container',
      '#weight' => $form['title']['#weight'] + 0.5,
    ];
    $form['title_description']['element'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['form-item__description'],
        'style' => 'transform: translateY(-8px)',
      ],
      '#value' => t('Add a short label for the goal. Is shown when listing and reviewing of IUP goals.'),
    ];

    if (!empty($form['field_iup_goal']) && !empty($form['#entity']) && $form['#entity'] instanceof NodeInterface && $form['#entity']->isNew()) {

      $node = self::getFormEntity($form_state);
      /** @var \Drupal\user\UserInterface $student */
      $student = current($node->get('field_student')->referencedEntities());
      if (!$student) {
        return;
      }

      $replace_context = [
        ReplaceTokenServiceInterface::STUDENT_REPLACE_TOKENS => $student,
      ];

      /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
      $entity_type_manager = \Drupal::entityTypeManager();
      $standard_goals = $entity_type_manager->getStorage('taxonomy_term')->loadByProperties(['vid' => 'iup_standard_goal', 'status' => 1]);

      /** @var \Drupal\simple_school_reports_core\Service\ReplaceTokenServiceInterface $replace_service */
      $replace_service = \Drupal::service('simple_school_reports_core.replace_token_service');

      usort($standard_goals, function ($a, $b) {
        $weight_a = $a->getWeight();
        $weight_b = $b->getWeight();
        return $weight_a <=> $weight_b;
      });

      $options = [];
      /** @var \Drupal\taxonomy\TermInterface $standard_goal */
      foreach ($standard_goals as $standard_goal) {
        $item = $standard_goal->label();
        $item = $replace_service->handleText($item, $replace_context);

        $subject_id = '_none';
        if ($standard_goal->get('field_school_subject')->target_id) {
          $subject_id = $standard_goal->get('field_school_subject')->target_id;
        }
        $text = $standard_goal->get('field_iup_goal')->value;

        if ($text) {
          $format = $standard_goal->get('field_iup_goal')->format ?? 'plain_text_ck';
          $text = (string) check_markup($text, $format);
          $text = str_replace(['<p>', '</p>'], '', $text);
          $text = $replace_service->handleText($text, $replace_context);
          $options[$standard_goal->id() . ':' . $subject_id] = [
            'label' => strip_tags($item),
            'text' => $text,
          ];
        }


      }

      if (!empty($options)) {
        $form['standard_goals'] = [
          '#type'   => 'container',
          '#weight' => $form['field_iup_goal']['#weight'] + 0.5,
        ];
        $form['standard_goals']['sp'] = [
          '#type' => 'standard_iup_goal_select',
          '#options_map' => $options,
        ];
      }
    }
  }

  public static function getFullTermStamp(NodeInterface $iup_round): string {
    if ($iup_round->bundle() !== 'iup_round' || $iup_round->get('field_term_type')->isEmpty() || $iup_round->get('field_document_date')->isEmpty()) {
      return '';
    }

    $term_type_full_suffix = '';
    $timestamp = $iup_round->get('field_document_date')->value;

    if ($timestamp) {
      $date = new DrupalDateTime();
      $date->setTimestamp($timestamp);
      $term_type_full_suffix = $date->format('Y')[2] . $date->format('Y')[3];
    }

    return $iup_round->get('field_term_type')->value . $term_type_full_suffix;
  }
}
