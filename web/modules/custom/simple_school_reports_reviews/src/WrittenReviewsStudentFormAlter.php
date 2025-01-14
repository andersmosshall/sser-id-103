<?php

namespace Drupal\simple_school_reports_reviews;

use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\simple_school_reports_core\Service\ReplaceTokenServiceInterface;

class WrittenReviewsStudentFormAlter {

  public static function getFormEntity(FormStateInterface $form_state) {
    $form_object = $form_state->getFormObject();
    if ($form_object instanceof EntityFormInterface) {
      return $form_object->getEntity();
    }
    return NULL;
  }

  public static function formAlter(&$form, FormStateInterface $form_state) {
    $node = self::getFormEntity($form_state);

    if (!$node || $node->bundle() !== 'written_reviews') {
      return;
    }

    /** @var \Drupal\node\NodeInterface $written_reviews_round_node */
    $written_reviews_round_node = current($node->get('field_written_reviews_round')->referencedEntities());

    /** @var \Drupal\user\UserInterface $student */
    $student = current($node->get('field_student')->referencedEntities());
    if (!$student || !$written_reviews_round_node) {
      return;
    }

    $disabled = $written_reviews_round_node->get('field_locked')->value == 1;

    $form['actions']['submit']['#disabled'] = $disabled;
    $form['field_school_efforts']['widget'][0]['#disabled'] = $disabled;
    $form['field_grade']['widget']['#disabled'] = $disabled;

    $replace_context = [
      ReplaceTokenServiceInterface::STUDENT_REPLACE_TOKENS => $student,
    ];

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = \Drupal::entityTypeManager();
    $standard_phrases = $entity_type_manager->getStorage('taxonomy_term')->loadByProperties(['vid' => 'written_reviews_standard_phrase', 'status' => 1]);

    /** @var \Drupal\simple_school_reports_core\Service\ReplaceTokenServiceInterface $replace_service */
    $replace_service = \Drupal::service('simple_school_reports_core.replace_token_service');

    usort($standard_phrases, function ($a, $b) {
      $weight_a = $a->getWeight();
      $weight_b = $b->getWeight();
      return $weight_a <=> $weight_b;
    });

    $options = [];
    /** @var \Drupal\taxonomy\TermInterface $standard_phrase */
    foreach ($standard_phrases as $standard_phrase) {
      $item = $standard_phrase->label();
      $item = $replace_service->handleText($item, $replace_context);
      $options[$standard_phrase->id()] = strip_tags($item);
    }

    if (!empty($options)) {
      $form['standard_phrases'] = [
        '#type' => 'standard_phrase_select',
        '#options' => $options,
        '#ck_editor_id' => 'edit-field-school-efforts-0-value',
        '#disabled' => $disabled,
        '#weight' => 999,
      ];
    }
  }

}
