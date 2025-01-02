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
 *   id = "ssr_date_timestamp",
 *   label = @Translation("Date Timestamp"),
 *   field_types = {
 *     "timestamp",
 *   }
 * )
 */
class TimestampDateWidget extends WidgetBase {


  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'end_of_day' => FALSE,
        'noon' => FALSE,
        'placeholder' => '',
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $form['end_of_day'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('End of day'),
      '#default_value' => $this->getSetting('end_of_day'),
    ];
    $form['noon'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Noon'),
      '#default_value' => $this->getSetting('noon'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $end_of_day = $this->getSetting('end_of_day');
    $summary[] = t('End of day: @setting', ['@setting' => $end_of_day ? t('Yes') : t('No')]);

    $noon = $this->getSetting('noon');
    $summary[] = t('Noon: @setting', ['@setting' => $noon ? t('Yes') : t('No')]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $default_value = isset($items[$delta]->value) ? DrupalDateTime::createFromTimestamp($items[$delta]->value) : NULL;

    if ($default_value instanceof DrupalDateTime) {
      if ($this->getSetting('end_of_day')) {
        $default_value->setTime(23,59,59);
      }
      elseif ($this->getSetting('noon')) {
        $default_value->setTime(12,0,0);
      }
      else {
        $default_value->setTime(0,0,0);
      }
      $default_value = $default_value->format('Y-m-d');
    }

    $element['value'] = $element + [
      '#type' => 'date',
      '#default_value' => $default_value,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$item) {
      if (isset($item['value']) && is_string($item['value']) && !empty($item['value'])) {
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
        if ($this->getSetting('end_of_day')) {
          $date->setTime(23,59,59);
        }
        elseif ($this->getSetting('noon')) {
          $date->setTime(12,0,0);
        }
        else {
          $date->setTime(0,0,0);
        }
      }

      $item['value'] = $date instanceof DrupalDateTime ? $date->getTimestamp() : NULL;
    }
    return $values;
  }

}
