<?php

namespace Drupal\simple_school_reports_core\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Select;

/**
 *
 * @FormElement("ssr_multi_select")
 */
class SsrMultiSelect extends Select {
  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $info['#multiple'] = TRUE;
    $info['#filter_placeholder'] = t('Type to filter');
    return $info;
  }

  public static function processSelect(&$element, FormStateInterface $form_state, &$complete_form) {
    $element['#multiple'] = TRUE;

    if (!empty($element['#filter_placeholder'])) {
      $element['#attributes']['data-filter-placeholder'] = $element['#filter_placeholder'];
    }

    $element['#attributes']['class'][] = 'ssr-multi-select';
    $element['#attached']['library'][] = 'simple_school_reports_core/ssr_multi_select';

    return parent::processSelect($element, $form_state, $complete_form);
  }
}
