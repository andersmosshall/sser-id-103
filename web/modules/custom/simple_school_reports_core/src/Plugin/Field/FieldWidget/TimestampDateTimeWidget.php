<?php

namespace Drupal\simple_school_reports_core\Plugin\Field\FieldWidget;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'datetime timestamp' widget.
 *
 * @FieldWidget(
 *   id = "ssr_date_time_timestamp",
 *   label = @Translation("Datetime Timestamp (minute level)"),
 *   field_types = {
 *     "timestamp",
 *   }
 * )
 */
class TimestampDateTimeWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'seconds_value' => 0,
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $form['seconds_value'] = [
      '#type' => 'select',
      '#title' => $this->t('Seconds value'),
      '#options' => [
        0 => '00',
        59 => '59',
      ],
      '#default_value' => $this->getSetting('seconds_value'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = t('Add date and time with precision at minute level');

    $seconds_value = $this->getSetting('seconds_value');
    $summary[] = t('Seconds value: @setting', ['@setting' => $seconds_value]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $default_value = isset($items[$delta]->value) ? DrupalDateTime::createFromTimestamp($items[$delta]->value) : NULL;

    if ($default_value instanceof DrupalDateTime) {
      $hours = (int) $default_value->format('H');
      $minutes = (int) $default_value->format('i');
      $default_value->setTime($hours, $minutes, 0);
    }

    $element['value'] = $element + [
      '#type' => 'datetime',
      '#default_value' => $default_value,
      '#date_increment' => 60,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$item) {
      if (isset($item['value']) && is_string($item['value'])) {
        $date = new DrupalDateTime($item['value']);
      }
      elseif (isset($item['value']) && $item['value'] instanceof DrupalDateTime) {
        $date = $item['value'];
      }
      elseif (isset($item['value']['object']) && $item['value']['object'] instanceof DrupalDateTime) {
        $date = $item['value']['object'];
      }
      else {
        $date = NULL;
      }

      if ($date instanceof DrupalDateTime) {
        $hours = (int) $date->format('H');
        $minutes = (int) $date->format('i');
        $date->setTime($hours, $minutes, $this->getSetting('seconds_value'));
      }

      $item['value'] = $date instanceof DrupalDateTime ? $date->getTimestamp() : NULL;
    }
    return $values;
  }

}
