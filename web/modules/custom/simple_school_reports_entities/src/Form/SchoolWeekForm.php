<?php

namespace Drupal\simple_school_reports_entities\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the school week entity edit forms.
 */
class SchoolWeekForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    self::alterFieldCopy($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $result = parent::save($form, $form_state);

    $entity = $this->getEntity();

    $message_arguments = ['%label' => $entity->toLink()->toString()];
    $logger_arguments = [
      '%label' => $entity->label(),
      'link' => $entity->toLink($this->t('View'))->toString(),
    ];

    switch ($result) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('New school week %label has been created.', $message_arguments));
        $this->logger('simple_school_reports_entities')->notice('Created new school week %label', $logger_arguments);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The school week %label has been updated.', $message_arguments));
        $this->logger('simple_school_reports_entities')->notice('Updated school week %label.', $logger_arguments);
        break;
    }

    $form_state->setRedirect('entity.school_week.canonical', ['school_week' => $entity->id()]);

    return $result;
  }

  public static function alterFieldCopy(array &$form, FormStateInterface $form_state) {

    for ($day_index = 1; $day_index <= 7; $day_index++) {
      if (isset($form['length_' . $day_index])) {
        $form['length_' . $day_index]['#attributes']['class'][] = 'school-week-length';
      }

      if (isset($form['from_' . $day_index])) {
        $form['from_' . $day_index]['#attributes']['class'][] = 'school-week-from';
      }

      if (isset($form['to_' . $day_index])) {
        $form['to_' . $day_index]['#attributes']['class'][] = 'school-week-to';
      }
    }


    $form['length_copy'] = [
      '#type' => 'msr_input_copy',
      '#target_selectors' => ['.school-week-length input'],
    ];

    $form['from_copy'] = [
      '#type' => 'msr_input_copy',
      '#target_selectors' => ['.school-week-from input'],
    ];

    $form['to_copy'] = [
      '#type' => 'msr_input_copy',
      '#target_selectors' => ['.school-week-to input'],
    ];

    $form['#attached']['library'][] = 'simple_school_reports_entities/school_week_form';
  }

  public static function addCalculateFromSchoolWeekStates(array &$form, FormStateInterface $form_state) {
    if (!ssr_use_schema()) {
      return;
    }

    for ($day_index = 1; $day_index <= 7; $day_index++) {
      if (isset($form['length_' . $day_index])) {
        $form['length_' . $day_index]['#states'] = [
          'visible' => [
            ':input[name="calculate_from_schema"]' => ['checked' => FALSE],
          ],
        ];
      }

      if (isset($form['from_' . $day_index])) {
        $form['from_' . $day_index]['#states'] = [
          'visible' => [
            ':input[name="calculate_from_schema"]' => ['checked' => FALSE],
          ],
        ];
      }

      if (isset($form['to_' . $day_index])) {
        $form['to_' . $day_index]['#states'] = [
          'visible' => [
            ':input[name="calculate_from_schema"]' => ['checked' => FALSE],
          ],
        ];
      }
    }
  }
}
