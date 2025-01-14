<?php

namespace Drupal\simple_school_reports_class;

use Drupal\Core\Form\FormStateInterface;

class ClassInsteadOfStudentsFormAlter {

  public static function applyStatesToForm(array &$form, FormStateInterface $form_state, string $class_field, string $student_field) {
    if (!empty($form[$class_field]) && !empty($form[$student_field])) {
      // Hide the student field if the class field is not empty.
      $form[$student_field]['#states'] = [
        'visible' => [
          ':input[name="' . $class_field . '"]' => ['value' => '_none'],
        ],
      ];
    }
  }
}
