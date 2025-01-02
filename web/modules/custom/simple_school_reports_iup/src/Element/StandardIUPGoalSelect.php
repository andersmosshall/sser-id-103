<?php

namespace Drupal\simple_school_reports_iup\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Select;

/**
 *
 * @FormElement("standard_iup_goal_select")
 */
class StandardIUPGoalSelect extends Select {
  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $info['#default_value'] = NULL;
    $info['#empty_option'] = t('Select standard goal.');
    $info['#options_map'] = [];
    return $info;
  }

  public static function processSelect(&$element, FormStateInterface $form_state, &$complete_form) {
    $options = [];
    $text_map = [];
    foreach ($element['#options_map'] as $key => $options_map_item) {
      $options[$key] = $options_map_item['label'];
      $text_map[$key] = $options_map_item['text'];
    }
    $element['#options'] = $options;
    $element['#attached']['drupalSettings']['iupGoalMap'] = $text_map;

    $element = parent::processSelect($element, $form_state, $complete_form);

    $element['#attached']['library'][] = 'simple_school_reports_iup/standard_iup_goal_select';
    $element['#prefix'] = '<div class="standard-iup-goal-select--wrapper"><div class="button--wrapper"><a class="button button--primary">' . t('Choose') . '</a></div><div class="select--wrapper">';
    $element['#suffix'] = '</div></div>';

    return $element;
  }
}
