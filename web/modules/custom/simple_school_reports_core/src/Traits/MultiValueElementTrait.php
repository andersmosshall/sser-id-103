<?php

namespace Drupal\simple_school_reports_core\Traits;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\SortArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\FocusFirstCommand;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\file\FileInterface;
use Drupal\file\FileUsage\FileUsageInterface;

/**
 * Helper to replicate what WidgetBase does with multi-value fields.
 *
 * Does not include weights for table-sort, but that could be added later.
 * If doing so, make it conditional via a default FALSE parameter so existing
 * UIs and config structures won't need to change.
 *
 * Use this by calling the buildMultiValueElement() method and passing in the
 * arguments needed to build a single value element. It will automatically be
 * repeated for each item in the list of existing items, adding one empty item
 * while below the max items limit.
 */
trait MultiValueElementTrait {

  use StringTranslationTrait;

  /**
   * File usage service.
   *
   * Only required if using file elements.
   */
  protected FileUsageInterface $fileUsage;

  /**
   * Ajax submit callback for the "Remove" button.
   *
   * This re-numbers form elements and removes an item.
   *
   * @param array $form
   *   The form array to remove elements from.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function multiValueDeleteSubmit(
    array &$form,
    FormStateInterface $form_state,
  ) {
    $button = $form_state->getTriggeringElement();
    $delta = (int) $button['#delta'];
    $array_parents = array_slice($button['#array_parents'], 0, -3);
    $parent_element = NestedArray::getValue(
      $form,
      $array_parents
    );
    $id_prefix = $parent_element['#id_prefix'];
    $field_state = static::getWidgetState($id_prefix, $form_state);
    $user_input = $form_state->getUserInput();
    $input_path = $parent_element['#parents'];
    $field_input = NestedArray::getValue(
      $user_input,
      $input_path,
      $exists
    );
    if ($exists) {
      $field_values = [];
      foreach ($field_input as $key => $input) {
        if (\is_numeric($key) && $key >= $delta) {
          if ((int) $key === $delta) {
            --$key;
            continue;
          }
        }
        $field_values[$key] = $input;
      }
      NestedArray::setValue(
        $user_input,
        $input_path,
        $field_values
      );
      $form_state->setUserInput($user_input);
    }

    $deleted_item = $parent_element[$delta];
    $field_state['deleted_item'] = $delta;

    unset($parent_element[$delta]);
    NestedArray::setValue($form, $array_parents, $parent_element);

    if ($field_state['items_count'] > 0) {
      $field_state['items_count']--;
    }

    $user_input = $form_state->getUserInput();
    $input = NestedArray::getValue(
      $user_input,
      $input_path,
      $exists
    );
    $weight = -1 * $field_state['items_count'];
    foreach ($input as $key => $item) {
      if ($item) {
        $input[$key]['_weight'] = $weight++;
      }
    }
    // Reset indices.
    $input = array_values($input);

    NestedArray::setValue($user_input, $input_path, $input);
    $form_state->setUserInput($user_input);
    static::setWidgetState($id_prefix, $form_state, $field_state);
    $form_state->setRebuild();

    $container_element = NestedArray::getValue(
      $form,
      array_slice($parent_element['#array_parents'], 0, -1)
    );
    if (isset($container_element['#deleteItemCallback'])) {
      $callback = $form_state->prepareCallback(
        $container_element['#deleteItemCallback']
      );
      call_user_func($callback, $form_state, $deleted_item, $delta);
    }
  }

  /**
   * Ajax callback for the "Add another item" button.
   *
   * This returns the new page content to replace the page content made obsolete
   * by the form submission.
   */
  public static function multiValueAddMoreAjax(
    array $form,
    FormStateInterface $form_state,
  ) {
    $button = $form_state->getTriggeringElement();

    // Go one level up in the form, to the element container.
    $parent_element = NestedArray::getValue(
      $form,
      array_slice($button['#array_parents'], 0, -1)
    );

    // Add a DIV around the delta receiving the Ajax effect.
    $delta = $parent_element['#max_delta'];
    // Construct an attribute to add to div for use as selector to set the
    // focus on.
    $focus_attribute = 'data-drupal-selector="' . $parent_element['#id_prefix'] . '-focus-target"';
    $parent_element[$delta]['#prefix'] = '<div class="ajax-new-content" ' . $focus_attribute . '>' . ($parent_element[$delta]['#prefix'] ?? '');
    $parent_element[$delta]['#suffix'] = ($parent_element[$delta]['#suffix'] ?? '') . '</div>';

    // Turn render array into response with AJAX commands.
    $response = new AjaxResponse();
    $response->addCommand(new InsertCommand(NULL, $parent_element));
    // Add command to set the focus on first focusable element within the div.
    $response->addCommand(new FocusFirstCommand("[$focus_attribute]"));
    return $response;
  }

  /**
   * Ajax refresh callback for the "Remove" button.
   *
   * This returns the new widget element content to replace
   * the previous content made obsolete by the form submission.
   *
   * @param array $form
   *   The form array to remove elements from.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function multiValueDeleteAjax(
    array &$form,
    FormStateInterface $form_state,
  ) {
    $button = $form_state->getTriggeringElement();
    return NestedArray::getValue(
      $form,
      array_slice($button['#array_parents'], 0, -4)
    );
  }

  /**
   * Submission handler for the "Add another item" button.
   */
  public static function multiValueAddMoreSubmit(
    array $form,
    FormStateInterface $form_state,
  ) {
    $button = $form_state->getTriggeringElement();
    // Go one level up in the form, to the element container.
    $element = NestedArray::getValue(
      $form,
      array_slice($button['#array_parents'], 0, -1)
    );
    $id_prefix = $element['#id_prefix'];

    // Increment the items count.
    $field_state = static::getWidgetState($id_prefix, $form_state);
    $field_state['items_count']++;
    static::setWidgetState($id_prefix, $form_state, $field_state);

    $form_state->setRebuild();
  }

  /**
   * Multi-value #after_build callback for table-like styling.
   *
   * This should have been accomplished with a custom #theme function
   * and preprocess hooks, but meh...
   *
   * @param array $element
   *   The container element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array
   *   The container element with table tags mixed in.
   */
  public static function afterBuildMultiValueElement(
    array $element,
    FormStateInterface $form_state,
  ) {
    $id_prefix = $element['#id_prefix'];
    $field_state = static::getWidgetState($id_prefix, $form_state);
    $field_state['array_parents'] = $element['#array_parents'];
    static::setWidgetState($id_prefix, $form_state, $field_state);

    return $element;
  }

  /**
   * Validation callback to unpack multi-value elements.
   *
   * @param array $element
   *   Form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public static function validateMultiValueElement(
    array &$element,
    FormStateInterface $form_state,
  ): void {
    $id_prefix = $element['#id_prefix'];
    $key_exists = NULL;
    $values = $form_state->getValues();
    $values = NestedArray::getValue(
      $values,
      $element['#parents'],
      $key_exists
    );
    if (!$key_exists) {
      return;
    }

    $field_state = static::getWidgetState($id_prefix, $form_state);

    // The original delta, before drag-and-drop reordering, is needed to
    // route errors to the correct form element.
    foreach (array_keys($values) as $delta) {
      $values[$delta]['_original_delta'] = $delta;
    }
    usort($values, function ($a, $b) {
      return SortArray::sortByKeyInt($a, $b, '_weight');
    });
    $values = array_values(
      array_filter(
        $values,
        static fn(array $value): bool => !empty($value['value'][0]) || !empty($value['value']['target_id']) || !empty($value['value']['name']) || !empty($value['value']['url'])
      )
    );
    $form_state->setValueForElement($element, $values);

    // Put delta mapping in $form_state, so that flagErrors() can use it.
    foreach ($values as $delta => $value) {
      $field_state['original_deltas'][$delta] = $value['_original_delta'] ?? $delta;
      unset($field_state['items'][$delta]['_original_delta'], $field_state['items'][$delta]['_weight'], $field_state['items'][$delta]['_actions']);
    }
    static::setWidgetState($id_prefix, $form_state, $field_state);
  }

  /**
   * Callback for when a file item is deleted.
   *
   * Ensures temporarily uploaded files are deleted when a whole item/row is
   * deleted, as opposed to when the actual file widget's delete button is used.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $element
   *   The removed item's element. Note that it has already been removed from
   *   the form.
   */
  public static function deleteFile(
    FormStateInterface $form_state,
    array $element,
  ): void {
    $files = $element['value']['#files'];
    if (empty($files)) {
      return;
    }
    foreach ($files as $file) {
      if ($file instanceof FileInterface && $file->isTemporary()) {
        $file->delete();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getWidgetState(
    string $id_prefix,
    FormStateInterface $form_state,
  ): ?array {
    $storage = $form_state->getStorage();
    return NestedArray::getValue(
      $storage,
      static::getWidgetStateParents($id_prefix)
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function setWidgetState(
    string $id_prefix,
    FormStateInterface $form_state,
    array $field_state,
  ): void {
    $storage = &$form_state->getStorage();
    NestedArray::setValue(
      $storage,
      static::getWidgetStateParents($id_prefix),
      $field_state
    );
  }

  /**
   * Build a multi-value element.
   *
   * This is based on what WidgetBase does for 'Add another item'.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $options
   *   An associative array containing the following keys:
   *   - title: The title of the multi-value element.
   *   - description: The description of the multi-value element.
   * @param string $id_prefix
   *   A unique id/name prefix for this element.
   * @param array $existing_items
   *   Any existing items.
   * @param callable(array, int, mixed): array $build_single
   *   A callable to build a single element.
   *   First argument is the new container element.
   *   Second argument is the item delta being built.
   *   Third argument is any value (or NULL) of an existing item at the delta.
   *   Return value is the element with fields to construct a single element.
   * @param int $cardinality
   *   The max number of items allowed.
   * @param int $showItems
   *   The minimum number of items to show. Cannot more than $cardinality.
   *
   * @return array
   *   The multi-value container.
   */
  protected function buildMultiValueElement(
    FormStateInterface $form_state,
    array $options,
    string $id_prefix,
    array $existing_items,
    callable $build_single,
    int $cardinality = PHP_INT_MAX,
    int $showItems = 1,
  ): array {

    $title = $options['title'] ?? $this->t('Items');
    $description = $options['description'] ?? NULL;

    if (!$field_state = static::getWidgetState($id_prefix, $form_state)) {
      $field_state = [
        'items_count' => count($existing_items),
        'array_parents' => [],
        // We need a working copy of the items to track deletions.
        // WidgetBase has the entity's item list, and this is our equivalence.
        'items' => array_map(static fn($item): array => ['value' => $item],
          $existing_items),
      ];
      static::setWidgetState($id_prefix, $form_state, $field_state);
    }
    $items = $field_state['items'];

    // Remove deleted items from the field item list.
    if (isset($field_state['deleted_item']) && isset($items[$field_state['deleted_item']])) {
      unset($items[$field_state['deleted_item']]);
      $items = array_values($items);
      $field_state['items'] = $items;
      unset($field_state['deleted_item']);
      static::setWidgetState($id_prefix, $form_state, $field_state);
    }

    $wrapper_id = Html::getUniqueId($id_prefix . '-add-more-wrapper');

    // Never show more than the cardinality.
    $max = $field_state['items_count'] ?? \max(
    // Never show less than the number of existing items, unless it's more.
      \min(
        \count($items),
        $cardinality,
      ) - 1,
      // Never show more than the cardinality.
      \min($showItems, $cardinality) - 1
    );
    $field_state['items_count'] = $max;
    static::setWidgetState($id_prefix, $form_state, $field_state);

    $container = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#max_delta' => $max,
      '#id_prefix' => $id_prefix,
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
      'items' => [
        '#theme' => 'field_multiple_value_form',
        '#title' => $title,
        '#description' => $description,
        '#id_prefix' => $id_prefix,
        // Compatibility with field_multiple_value_form.
        '#field_name' => $id_prefix,
        // Compatibility with field_multiple_value_form.
        '#cardinality_multiple' => $cardinality > 1,
        '#element_validate' => [[static::class, 'validateMultiValueElement']],
        '#after_build' => [[static::class, 'afterBuildMultiValueElement']],
      ],
    ];

    for ($delta = 0; $delta <= $max; $delta++) {
      // Add a new empty item if it doesn't exist yet at this delta.
      if (!isset($items[$delta]['value'])) {
        $items[] = ['value' => NULL];
        $field_state['items'] = $items;
        static::setWidgetState($id_prefix, $form_state, $field_state);
      }
      $single_element = ($build_single)(
        $container['items'],
        $delta,
        $items[$delta]['value']
      );
      $single_element['#weight'] = 1;

      $container['items'][$delta] = [
        '#delta' => $delta,
        '#weight' => $delta,
        'value' => $single_element,
        '_weight' => [
          '#type' => 'weight',
          '#title' => $this->t(
            'Weight for row @number',
            ['@number' => $delta + 1]
          ),
          '#title_display' => 'invisible',
          // Note: this 'delta' is the FAPI #type 'weight' element's property.
          '#delta' => $max,
          '#default_value' => $items[$delta]['_weight'] ?? $delta,
          '#weight' => 100,
        ],
        '_actions' => [
          '#weight' => 101,
          'delete' => [
            '#delta' => $delta,
            '#name' => "{$id_prefix}_{$delta}_remove_button",
            '#type' => 'submit',
            '#value' => $this->t('Remove'),
            '#validate' => [],
            '#submit' => [[static::class, 'multiValueDeleteSubmit']],
            '#limit_validation_errors' => [],
            '#ajax' => [
              'callback' => [static::class, 'multiValueDeleteAjax'],
              'wrapper' => $wrapper_id,
              'effect' => 'fade',
            ],
          ],
        ],
      ];
    }

    if ($delta < $cardinality) {
      $container['add_more'] = [
        '#type' => 'submit',
        '#name' => strtr($id_prefix, '-', '_') . '_add_more',
        '#value' => t('Add another item'),
        '#attributes' => ['class' => ['field-add-more-submit']],
        '#limit_validation_errors' => [],
        '#submit' => [[static::class, 'multiValueAddMoreSubmit']],
        '#ajax' => [
          'callback' => [static::class, 'multiValueAddMoreAjax'],
          'wrapper' => $wrapper_id,
          'effect' => 'fade',
        ],
      ];
    }

    return $container;
  }

  /**
   * Helper to build a multi-value file element.
   *
   * The form still needs to handle adding/removing usage on files when the form
   * is submitted.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $options
   *   An associative array containing the following keys:
   *    - title: The title of the multi-value element.
   *    - description: The description of the multi-value element.
   * @param string $id_prefix
   *   A unique id/name prefix for this element.
   * @param array $element_base
   *   The properties to put on each file element, such as '#upload_location',
   *   '#upload_validators', etc.
   * @param array $existing_files
   *   Any existing file ids to use as default values.
   * @param int $cardinality
   *   The max number of items allowed.
   * @param int $show_items
   *   The minimum number of items to show. Cannot more than $cardinality.
   *
   * @return array
   *   The multi-value container.
   *
   * @see ::buildMultiValueElement()
   */
  protected function buildMultiValueFileElement(
    FormStateInterface $form_state,
    array $options,
    string $id_prefix,
    array $element_base,
    array &$existing_files,
    int $cardinality = PHP_INT_MAX,
    int $show_items = 1,
  ) {
    $items = array_map(static fn(string $fid): array => [$fid],
      $existing_files);
    $container = $this->buildMultiValueElement(
      $form_state,
      $options,
      $id_prefix,
      $items,
      static fn(array $container, int $delta, mixed $value) =>
        $element_base + [
          '#type' => 'managed_file',
          '#default_value' => $value,
        ],
      $cardinality,
      $show_items,
    );
    $container['#deleteItemCallback'] = '::deleteFile';
    return $container;
  }

  /**
   * Submit helper for multi-value file elements.
   *
   * Takes care of updating file usage and marking temporary files permanent.
   * It sets the file's usage id to the file id, so if you want to use something
   * else, you'll need to replicate what this function does.
   *
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state.
   * @param array $container_element
   *   The element generated by ::buildMultiValueFileElement().
   * @param array $existing_file_ids
   *   The currently stored file ids for comparison.
   * @param string $module
   *   The calling module to register the usage with.
   * @param string $usage_type
   *   The usage type to register.
   *
   * @return string[]
   *   The new file ids to save.
   */
  protected function saveMultiValueFileElement(
    FormStateInterface $formState,
    array $container_element,
    array $existing_file_ids,
    string $module,
    string $usage_type,
  ): array {
    $key_exists = NULL;
    $values = $formState->getValues();
    $values = NestedArray::getValue(
      $values,
      $container_element['items']['#parents'],
      $key_exists
    );
    if (!$key_exists) {
      return [];
    }

    $fids = array_filter(
      array_map(static fn(array $value): ?string => $value['value'][0] ?? NULL,
        $values)
    );

    $removed = array_diff($existing_file_ids, $fids);
    foreach ($removed as $fid) {
      /** @var \Drupal\file\FileInterface|null $file */
      $file = $this->entityTypeManager->getStorage('file')->load($fid);
      if (!$file) {
        continue;
      }
      $this->fileUsage->delete($file, $module, $usage_type, $fid);
    }

    $new_fids = array_diff($fids, $existing_file_ids);

    foreach ($new_fids as $fid) {
      /** @var \Drupal\file\FileInterface|null $file */
      $file = $this->entityTypeManager->getStorage('file')->load($fid);
      if (!$file) {
        continue;
      }
      $this->fileUsage->add($file, $module, $usage_type, $fid);
    }
    return $fids;
  }

  /**
   * Returns the location of processing information within $form_state.
   *
   * @param string $id_prefix
   *   The field name.
   *
   * @return array
   *   The location of processing information within $form_state.
   */
  protected static function getWidgetStateParents(string $id_prefix) {
    return array_merge(
      ['multivalue_field_storage', '#parents'],
      ['#fields', $id_prefix],
    );
  }

}
