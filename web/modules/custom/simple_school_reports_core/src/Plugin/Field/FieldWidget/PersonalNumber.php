<?php

namespace Drupal\simple_school_reports_core\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextfieldWidget;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the 'text_textfield' widget.
 *
 * @FieldWidget(
 *   id = "string_personal_number",
 *   label = @Translation("Personal number text field"),
 *   field_types = {
 *     "string"
 *   },
 * )
 */
class PersonalNumber extends StringTextfieldWidget {

  /**
   * @var \Drupal\simple_school_reports_core\Pnum
   */
  protected $pnum;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance =  parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->pnum = $container->get('simple_school_reports_core.pnum');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $main_widget = parent::formElement($items, $delta, $element, $form, $form_state);
    $main_widget['value']['#type'] = 'personal_number';
    $main_widget['value']['#attributes']['#autocomplete'] = 'off';
    $main_widget['value']['#attributes']['#autocorrect'] = 'none';
    return $main_widget;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    if (empty($form_state->getErrors())) {
      foreach ($values as &$value) {
        if (is_array($value) && !empty($value['value'])) {
          $value['value'] = $this->pnum->normalizeIfValid($value['value']) ?? '';
        }
      }

    }
    return $values;
  }
}
