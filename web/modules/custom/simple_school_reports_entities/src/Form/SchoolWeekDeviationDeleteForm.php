<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_entities\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the school week deviation entity edit forms.
 */
final class SchoolWeekDeviationDeleteForm extends ContentEntityDeleteForm {

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    /** @var \Drupal\simple_school_reports_entities\SchoolWeekDeviationInterface $entity */
    $entity = $this->getEntity();

    $grades = array_column($entity->get('grade')->getValue(), 'value');
    if (count($grades) > 1) {
      sort($grades);
      $grade_labels = [];
      $grade_labels_map = simple_school_reports_core_allowed_user_grade();
      foreach ($grades as $grade) {
        $grade_labels[] = $grade_labels_map[$grade] ?? $grade;
      }

      $form['grade_info'] = [
        '#type' => 'html_tag',
        '#tag' => 'em',
        '#prefix' => '<div>',
        '#suffix' => '</div>',
        '#value' => $this->t('Note! This deviation will be removed for following school grades: @grades', [
          '@grades' => implode(', ', $grade_labels),
        ]),
      ];
    }

    return $form;
  }

}
