<?php

namespace Drupal\simple_school_reports_core\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\OptGroup;
use Drupal\options\Plugin\Field\FieldFormatter\OptionsDefaultFormatter;
use Drupal\user\UserInterface;

/**
 * Plugin implementation of the 'list_default' formatter.
 *
 * @FieldFormatter(
 *   id = "ssr_gender_formatter",
 *   label = @Translation("Gender formatter"),
 *   field_types = {
 *     "list_string",
 *   }
 * )
 */
class GenderFormatter extends OptionsDefaultFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    /** @var UserInterface $user */
    $user = $items->getEntity();

    if (!$user instanceof UserInterface || !$user->hasRole('student')) {
      return parent::viewElements($items, $langcode);
    }

    $elements = [];

    // Only collect allowed options if there are actually items to display.
    if ($items->count()) {
      $provider = $items->getFieldDefinition()
        ->getFieldStorageDefinition()
        ->getOptionsProvider('value', $user);
      // Flatten the possible options, to support opt groups.
      $options = OptGroup::flattenOptions($provider->getPossibleOptions());

      foreach ($items as $delta => $item) {
        $value = $item->value;

        $output = self::resolveGenderValue($value, $user);
        if (!$output) {
          // If the stored value is in the current set of allowed values, display
          // the associated label, otherwise just display the raw value.
          $output = isset($options[$value]) ? $options[$value] : $value;
        }

        $elements[$delta] = [
          '#markup' => $output,
          '#allowed_tags' => FieldFilteredMarkup::allowedTags(),
        ];
      }
    }

    return $elements;
  }

  public static function resolveGenderValue($value, UserInterface $user) {
    $student_gender_map = [
      'male' => t('Boy'),
      'female' => t('Girl'),
    ];

    if ($user->hasRole('student') && isset($student_gender_map[$value])) {
      return $student_gender_map[$value];
    }
    return NULL;
  }

}
