<?php

namespace Drupal\simple_school_reports_extension_proxy\Plugin\Field\FieldWidget;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use Drupal\simple_school_reports_entities\SsrMeetingInterface;

/**
 * Plugin implementation of the 'ssr_di_student_select_widget' widget.
 *
 * @FieldWidget(
 *   id = "ssr_di_student_select_widget",
 *   label = @Translation("Student select for development interview"),
 *   field_types = {
 *     "entity_reference",
 *   },
 *   multiple_values = TRUE
 * )
 */
class DIStudentSelectWidget extends OptionsSelectWidget {

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return $field_definition->getTargetEntityTypeId() === 'ssr_meeting' && $field_definition->getName() === 'field_student';
  }

  /**
   * {@inheritdoc}
   */
  protected function getOptions(FieldableEntityInterface $entity) {
    $options = [];

    if ($entity instanceof SsrMeetingInterface && $entity->bundle() === 'student_di') {
      $student_group = $entity->get('node_parent')->entity;
      if ($student_group instanceof NodeInterface && $student_group->bundle() === 'di_student_group') {
        $students = $student_group->get('field_student')->referencedEntities();
        foreach ($students as $student) {
          $options[$student->id()] = $student->label();
        }
      }
    }

    if ($empty_label = $this->getEmptyLabel()) {
      $options = ['_none' => $empty_label] + $options;
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $cache = new CacheableMetadata();
    $cache->addCacheContexts(['user']);
    $cache->applyTo($element);
    return $element;
  }

}
