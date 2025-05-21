<?php

namespace Drupal\simple_school_reports_grade_stats;

use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Form\FormStateInterface;

class GradeStatisticsFormAlter {

  public static function getFormEntity(FormStateInterface $form_state) {
    $form_object = $form_state->getFormObject();
    if ($form_object instanceof EntityFormInterface) {
      return $form_object->getEntity();
    }
    return NULL;
  }

  public static function formAlter(&$form, FormStateInterface $form_state) {
    $node = self::getFormEntity($form_state);
    $form['#attached']['library'][] = 'simple_school_reports_grade_stats/grade_statistics_node';
    $form['#attributes']['class'][] = 'grade-statistics-form';

    if ($node->isNew()) {
      if (!empty($form['field_school_subjects']['widget']['#options'])) {
        $default_value = array_keys($form['field_school_subjects']['widget']['#options']);
        $form['field_school_subjects']['widget']['#default_value'] = $default_value;
      }
    }
  }
}
