<?php

namespace Drupal\simple_school_reports_core\Element;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Select;

/**
 *
 * @FormElement("standard_phrase_select")
 */
class StandardPhraseSelect extends Select {
  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $info['#default_value'] = NULL;
    $info['#empty_option'] = t('Select standard phrase to add.');
    $info['#ck_editor_id'] = NULL;
    return $info;
  }

  public static function processSelect(&$element, FormStateInterface $form_state, &$complete_form) {
    $local_options = [];
    $text_map = [];
    foreach ($element['#options'] as $key => $options_item) {
      $local_item_label = NULL;
      $local_item_text = NULL;
      if (is_array($options_item)) {
        $local_item_label = $options_item['label'] ?? NULL;
        $local_item_text = $options_item['text'] ?? NULL;
      } else {
        $local_item_label = $options_item;
        $local_item_text = $options_item;
      }

      if (!$local_item_label) {
        continue;
      }

      $local_options[$key] = $local_item_label;
      $text_map[$key] = $local_item_text;
    }
    $element['#options'] = $local_options;
    $element['#attributes']['data-text-map'] = Json::encode($text_map);

    $element = parent::processSelect($element, $form_state, $complete_form);

    $ck_editor_id = isset($element['#ck_editor_id']) ? $element['#ck_editor_id'] : NULL;
    if ($ck_editor_id) {
      $element['#attributes']['data-ck-editor-id'] = $ck_editor_id;
    }

    $element['#attached']['library'][] = 'simple_school_reports_core/standard_phrase_select';
    $element['#prefix'] = '<div class="standard-phrase-select--wrapper"><div class="button--wrapper"><a class="button button--primary">' . t('Add') . '</a></div><div class="select--wrapper">';
    $element['#suffix'] = '</div></div>';

    return $element;
  }
}
