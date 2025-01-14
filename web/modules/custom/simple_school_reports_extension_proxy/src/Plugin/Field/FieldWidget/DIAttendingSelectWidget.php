<?php

namespace Drupal\simple_school_reports_extension_proxy\Plugin\Field\FieldWidget;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsButtonsWidget;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use Drupal\simple_school_reports_entities\SsrMeetingInterface;
use Drupal\user\UserInterface;

/**
 * Plugin implementation of the 'ssr_di_attending_select_widget' widget.
 *
 * @FieldWidget(
 *   id = "ssr_di_attending_select_widget",
 *   label = @Translation("Attending select for development interview"),
 *   field_types = {
 *     "entity_reference",
 *   },
 *   multiple_values = TRUE
 * )
 */
class DIAttendingSelectWidget extends OptionsButtonsWidget {

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return $field_definition->getTargetEntityTypeId() === 'ssr_meeting' && $field_definition->getName() === 'attending';
  }

  protected function resolveTeachers(FieldableEntityInterface $entity, ?FormStateInterface $form_state, bool $return_as_object = TRUE): array {
    $user_inputs = $form_state?->getUserInput() ?? [];
    $has_user_inputs = !empty($user_inputs);

    $uids = [];
    if ($has_user_inputs) {
      $teachers = $user_inputs['field_teachers'] ?? [];
      foreach ($teachers as $teacher_form_data) {
        if (!empty($teacher_form_data['target_id'])) {
          $src = str_replace(')', '', $teacher_form_data['target_id']);
          $parts = explode('(', $src);
          $uids[] = array_pop($parts);
        }
      }
    }
    else {
      $uids = array_column($entity->get('field_teachers')->getValue(), 'target_id');
    }

    if (!$return_as_object) {
      return $uids;
    }

    $user_storage = \Drupal::entityTypeManager()->getStorage('user');
    if (!empty($uids)) {
      return $user_storage->loadMultiple($uids);
    }
    return [];
  }

  protected function resolveStudents(FieldableEntityInterface $entity, ?FormStateInterface $form_state, bool $return_as_object = TRUE): array {
    $user_inputs = $form_state?->getUserInput() ?? [];
    $has_user_inputs = !empty($user_inputs);

    $uids = [];
    if ($has_user_inputs) {
      $student = $user_inputs['field_student'] ?? NULL;
      if (!empty($student) && $student !== '_none' && is_numeric($student)) {
        $uids[] = $student;
      }
    }
    else {
      $uids = array_column($entity->get('field_student')->getValue(), 'target_id');
    }

    if (!$return_as_object) {
      return $uids;
    }

    $user_storage = \Drupal::entityTypeManager()->getStorage('user');
    if (!empty($uids)) {
      return $user_storage->loadMultiple($uids);
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getOptions(FieldableEntityInterface $entity, ?FormStateInterface $form_state = NULL) {
    $options = [];

    /** @var \Drupal\user\UserInterface[] $teachers */
    $teachers = $this->resolveTeachers($entity, $form_state);

    foreach ($teachers as $teacher) {
      $options[$teacher->id()] = $teacher->getDisplayName();
    }

    $students = $this->resolveStudents($entity, $form_state);

    foreach ($students as $student) {
      $options[$student->id()] = $student->getDisplayName();
      foreach ($student->get('field_caregivers')->referencedEntities() as $caregiver) {
        $options[$caregiver->id()] = $caregiver->getDisplayName();
      }
    }

    if ($empty_label = $this->getEmptyLabel()) {
      $options = ['_none' => $empty_label] + $options;
    }

    return $options;
  }

  protected function requiredValues(FieldableEntityInterface $entity, FormStateInterface $form_state) {
    $teachers = $this->resolveTeachers($entity, $form_state, FALSE);
    $students = $this->resolveStudents($entity, $form_state, FALSE);
    return array_merge($teachers, $students);
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $default_value = $element['#default_value'];
    $ui = $form_state->getUserInput();
    if (!empty($ui)) {
      $element['#options'] = $this->getOptions($items->getEntity(), $form_state);
      if (!empty($ui['attending'])) {
        $default_value = $ui['attending'];
      }
    }


    $required_elements = $this->requiredValues($items->getEntity(), $form_state);

    foreach ($required_elements as $value) {
      if (!in_array($value, $default_value)) {
        $default_value[] = $value;
      }
    }

    foreach ($default_value as $key => $value) {
      if (!isset($element['#options'][$value])) {
        unset($default_value[$key]);
      }
    }
    $default_value = array_values($default_value);

    if (!empty($ui)) {
      $ui['attending'] = $default_value;
      $element['#value'] = $default_value;
      $form_state->setValue('attending', array_values($default_value));
      $form_state->setUserInput($ui);
    }

    $element['#default_value'] = $default_value;
    $element['#attributes']['data-required-values'] = json_encode($required_elements);
    $element['#attached']['library'][] = 'simple_school_reports_extension_proxy/di_attending_select_widget';

    $cache = new CacheableMetadata();
    $cache->addCacheContexts(['user']);

    $entity = $items->getEntity();
    $cache->addCacheableDependency($entity);
    $cache->applyTo($element);

    return $element;
  }

  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    return parent::extractFormValues($items, $form, $form_state);
  }

}
