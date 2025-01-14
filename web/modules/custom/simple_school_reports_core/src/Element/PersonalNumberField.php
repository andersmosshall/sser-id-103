<?php

namespace Drupal\simple_school_reports_core\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Textfield;

/**
 * Provides a personal_number textfield.
 *
 * @FormElement("personal_number")
 */
class PersonalNumberField extends Textfield {

  public function getInfo() {
    $class = get_class($this);
    $info = parent::getInfo();
    unset($info['#size']);
    unset($info['#maxlength']);

    $info['#element_validate'] = [[$class, 'validatePersonalNumber']];
    $info['#placeholder'] = 'ååmmdd-nnnn';
    $info['#attributes']['#autocomplete'] = 'off';

    return $info;
  }

  public static function validatePersonalNumber(
    &$element,
    FormStateInterface $form_state
  ) {

    $value = trim($element['#value']);
    if (!$value) {
      return;
    }
    /** @var \Drupal\simple_school_reports_core\Pnum $pnum_serivice */
    $pnum_serivice = \Drupal::service('simple_school_reports_core.pnum');
    $result = $pnum_serivice->normalizeIfValid($value);
    if (!$result) {
      $form_state->setError($element, t('Invalid personal number'));
    }
  }
}
