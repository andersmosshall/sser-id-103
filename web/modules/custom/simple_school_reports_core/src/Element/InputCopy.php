<?php

namespace Drupal\simple_school_reports_core\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html as HtmlUtility;
use Drupal\Core\Render\Element\RenderElementBase;

/**
 * Input copy element.
 *
 * @RenderElement("msr_input_copy")
 */
class InputCopy extends RenderElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = static::class;
    return [
      '#target_selectors' => [],
      '#process' => [
        [$class, 'processGroup'],
        [$class, 'processContainer'],
      ],
      '#pre_render' => [
        [$class, 'preRenderGroup'],
      ],
      '#theme_wrappers' => ['container'],
    ];
  }

  /**
   * Processes input copy element.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   container.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   */
  public static function processContainer(&$element, FormStateInterface $form_state, &$complete_form) {
    // Generate the ID of the element if it's not explicitly given.
    if (!isset($element['#id'])) {
      $element['#id'] = HtmlUtility::getUniqueId(implode('-', $element['#parents']) . '-wrapper');
    }

    if (!empty($element['#target_selectors'])) {
      $element['#attributes']['data-target-selectors'] = json_encode($element['#target_selectors']);
      $element['#attributes']['style'] = 'display: none';
      $element['#attributes']['class'][] = 'input-copy-element';
      $element['#attached']['library'][] = 'simple_school_reports_core/input_copy_element';

      $element['info'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['input-copy-element--label'],
        ],
        'value' => ['#plain_text' => ''],
      ];
      $element['trigger'] = [
        '#markup' => '<div class="input-copy-element--trigger"><a class="button button--primary">' . t('Copy to all') . '</a></div>',
      ];
    }

    return $element;
  }

}
