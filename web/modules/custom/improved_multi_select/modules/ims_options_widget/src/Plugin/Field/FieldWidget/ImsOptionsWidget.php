<?php

namespace Drupal\ims_options_widget\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'ims_options_select' widget.
 *
 * @FieldWidget(
 *   id = "ims_options_select",
 *   label = @Translation("IMS select list"),
 *   field_types = {
 *     "entity_reference",
 *     "list_integer",
 *     "list_float",
 *     "list_string"
 *   },
 *   multiple_values = TRUE
 * )
 */
class ImsOptionsWidget extends OptionsSelectWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $options = $this->getOptions($items->getEntity());
    $default = $this->getSelectedOptions($items);
    $ordered = [];
    array_walk($default, function ($item) use (&$options, &$ordered) {
      $ordered[$item] = $options[$item];
      unset($options[$item]);
    });
    $element['#type'] = 'select';
    $element['#options'] = $ordered + $options;
    $element['#default_value'] = $default;
    $element['#multiple'] = TRUE;

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function supportsGroups() {
    return FALSE;
  }

}
