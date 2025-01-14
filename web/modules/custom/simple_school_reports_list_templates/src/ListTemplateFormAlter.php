<?php

namespace Drupal\simple_school_reports_list_templates;

use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Form\FormStateInterface;

class ListTemplateFormAlter {

  public static function getFormEntity(FormStateInterface $form_state) {
    $form_object = $form_state->getFormObject();
    if ($form_object instanceof EntityFormInterface) {
      return $form_object->getEntity();
    }
    return NULL;
  }

  public static function formAlter(&$form, FormStateInterface $form_state) {
    $form['field_grades']['#states']['invisible'][] = [
      ':input[name="field_mentoring_students[value]"]' => [
        'checked' => TRUE,
      ],
    ];

    $use_classes = \Drupal::moduleHandler()->moduleExists('simple_school_reports_class');
    if ($use_classes) {
      $form['field_grades']['#states']['invisible'][] = [
        ':input[name="field_use_classes[value]"]' => [
          'checked' => TRUE,
        ],
      ];
      $form['field_use_classes']['#states']['invisible'][] = [
        ':input[name="field_mentoring_students[value]"]' => [
          'checked' => TRUE,
        ],
      ];
      $form['field_classes']['#states']['invisible'][] = [
        ':input[name="field_mentoring_students[value]"]' => [
          'checked' => TRUE,
        ],
      ];
      $form['field_classes']['#states']['invisible'][] = [
        ':input[name="field_use_classes[value]"]' => [
          'checked' => FALSE,
        ],
      ];
    }
  }

  public static function fieldFormAlter(&$form, FormStateInterface $form_state, $delta) {
    $form['descriptions_wrapper'] = [
      '#type' => 'container',
      '#weight' => 0.5,
    ];

    // Add birth data description.
    $form['descriptions_wrapper']['birth_data'] = [
      '#type' => 'container',
      '#weight' => 0.5,
    ];
    $form['descriptions_wrapper']['birth_data']['#states']['visible'][] = [
      'select[name="field_list_template_field[' . $delta . '][subform][field_field_type]"]' => ['value' => 'birth_data'],
    ];
    $form['descriptions_wrapper']['birth_data']['description'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => t('Show birth data. If there is no personal number registered or logged in user does not have access to view it, the last four digits will be masked as "****".'),
    ];

    $form['field_size']['#states']['visible'][] = [
      'select[name="field_list_template_field[' . $delta . '][subform][field_field_type]"]' => ['value' => 'custom'],
    ];
    $form['field_label']['#states']['visible'][] = [
      'select[name="field_list_template_field[' . $delta . '][subform][field_field_type]"]' => ['value' => 'custom'],
    ];

  }
}
