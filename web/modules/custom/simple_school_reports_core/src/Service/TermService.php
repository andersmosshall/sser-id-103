<?php

namespace Drupal\simple_school_reports_core\Service;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\simple_school_reports_core\Form\ResetInvalidAbsenceMultipleForm;

/**
 * Class TermService
 *
 * @package Drupal\simple_school_reports_core\Service
 */
class TermService implements TermServiceInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  protected $activeTerm;

  /**
   * TermService constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Get active term object.
   *
   * @return \Drupal\taxonomy\TermInterface|null
   */
  protected function getActiveTerm() {
    if (!$this->activeTerm) {
      $this->activeTerm = current($this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties(['vid' => 'term', 'status' => 1]));
    }
    return $this->activeTerm;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentTermStart($as_object = TRUE) {
    $term = $this->getActiveTerm();
    if ($term && $term->hasField('field_term_start') && !$term->get('field_term_start')->isEmpty()) {
      $timestamp = (int) $term->get('field_term_start')->value;
      if (!$as_object) {
        return $timestamp;
      }
      $object = new DrupalDateTime();
      $object->setTimestamp($timestamp);
      return $object;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentTermEnd($as_object = TRUE) {
    $term = $this->getActiveTerm();
    if ($term && $term->hasField('field_term_end') && !$term->get('field_term_end')->isEmpty()) {
      $timestamp = (int) $term->get('field_term_end')->value;
      if (!$as_object) {
        return $timestamp;
      }
      $object = new DrupalDateTime();
      $object->setTimestamp($timestamp);
      return $object;
    }

    return NULL;
  }


  /**
   * Alter the term add/edit form.
   *
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return void
   */
  public static function termFormAlter(&$form, FormStateInterface $form_state) {
    $term = $form_state->getFormObject() && method_exists($form_state->getFormObject(), 'getEntity')? $form_state->getFormObject()->getEntity() : NULL;

    $form['relations']['#access'] = FALSE;
    unset($form['field_term_start']['widget'][0]['value']['#description']);
    unset($form['field_term_end']['widget'][0]['value']['#description']);
    unset($form['actions']['overview']);

    $from_date = new DrupalDateTime();
    if ($term && !$term->isNew() && !$term->get('field_term_start')->isEmpty()) {
      $from_date->setTimestamp($term->get('field_term_start')->value);
    }
    $from_date->setTime(0,0);


    $to_date = new DrupalDateTime();
    if ($term && !$term->isNew() && !$term->get('field_term_end')->isEmpty()) {
      $to_date->setTimestamp($term->get('field_term_end')->value);
    }
    $to_date->setTime(23,59);

    $form['field_term_start']['widget'][0]['value']['#default_value'] = $from_date->format('Y-m-d');
    $form['field_term_end']['widget'][0]['value']['#default_value'] = $to_date->format('Y-m-d');
    $form['field_term_start']['widget'][0]['value']['#date_increment'] = 86400;
    $form['field_term_end']['widget'][0]['value']['#date_increment'] = 86400;
    $form['status']['widget']['#title'] = t('Active');

    $form['calculate_invalid_absence'] = [
      '#type' => 'checkbox',
      '#title' => t('Calculate students invalid absence for this dates.'),
      '#default_value' => !$term || $term->isNew(),
      '#weight' => 4,
    ];

    $form['calculate_invalid_absence']['#states']['visible'][] = [
      ':input[name="status[value]"]' => [
        'checked' => TRUE,
      ],
    ];

    $form['actions']['submit']['#submit'][] = [
      self::class,
      'submitTermTerm',
    ];
  }

  public static function submitTermTerm(&$form, FormStateInterface $form_state) {
    $term = $form_state->getFormObject() && method_exists($form_state->getFormObject(), 'getEntity')? $form_state->getFormObject()->getEntity() : NULL;

    if ($term) {
      $values = $form_state->getValues();
      $from_date = !empty($values['field_term_start'][0]['value']) ? $values['field_term_start'][0]['value'] : NULL;
      $to_date = !empty($values['field_term_end'][0]['value']) ? $values['field_term_end'][0]['value'] : NULL;
      if ($from_date && $to_date) {
        $from_date = is_string($from_date) ? new DrupalDateTime($from_date . ' 00:00:00') : $from_date;
        $to_date = is_string($to_date) ? new DrupalDateTime($to_date . ' 23:59:59') : $to_date;
        if (!empty($values['status']['value'])) {
          $terms_to_unpublish = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['vid' => 'term', 'status' => 1]);
          foreach ($terms_to_unpublish as $term_to_unpublish) {
            if ($term->id() !== $term_to_unpublish->id()) {
              $term_to_unpublish->set('status', FALSE);
              $term_to_unpublish->save();
            }
          }
        }

        if ($form_state->getValue('calculate_invalid_absence', FALSE) && !empty($values['status']['value'])) {
          $attendance_report_nids = \Drupal::entityTypeManager()
            ->getStorage('node')
            ->getQuery()
            ->condition('type', 'course_attendance_report')
            ->condition('field_class_start', $to_date->getTimestamp(), '<')
            ->condition('field_class_end', $from_date->getTimestamp(), '>')
            ->accessCheck(FALSE)
            ->execute();

          $uids = [];
          /** @var \Drupal\Core\Database\Connection $connection */
          $connection = \Drupal::service('database');
          $query = $connection->select('user__roles', 'r')->condition('r.roles_target_id', 'student');
          $query->innerJoin('users_field_data', 'd', 'd.uid = r.entity_id');
          $query->condition('d.status', 1);
          $query->fields('d', ['uid']);

          $results = $query->execute();

          foreach ($results as $result) {
            $uids[] = $result->uid;
          }

          if (!empty($uids)) {
            $batch = [
              'title' => t('Calculating invalid absence'),
              'init_message' => t('Calculating invalid absence'),
              'progress_message' => t('Processed @current out of @total.'),
              'operations' => [],
            ];

            foreach ($uids as $uid) {
              $batch['operations'][] = [[ResetInvalidAbsenceMultipleForm::class, 'calculateInvalidAbsence'], [$uid, $attendance_report_nids]];
            }

            batch_set($batch);
          }
        }
      }
    }
  }

  /**
   * @param \DateTime|null $relative_date
   *
   * @return array
   *   Array with keys 'start' and 'end' with \DateTime objects.
   */
  protected function getDefaultSchoolYear(?\DateTime $relative_date = NULL): array {
    if (!$relative_date) {
      $relative_date = new \DateTime();
    }

    $relative_year = (int) $relative_date->format('Y');

    $school_year_switch = new \DateTime($relative_year . '-07-15 00:00:00');

    if ($relative_date < $school_year_switch) {
      $start_year = ($relative_year - 1);
      $end_year = $relative_year;
    }
    else {
      $start_year = $relative_year;
      $end_year = ($relative_year + 1);
    }

    $start = new \DateTime($start_year . '-08-10 00:00:00');
    $term_switch = new \DateTime($end_year . '-01-07 00:00:00');
    $end = new \DateTime($end_year . '-08-09 23:59:59');

    $ht_term_index_date_source = new \DateTime($start_year . '-10-01 00:00:00');
    $vt_term_index_date_source = new \DateTime($end_year . '-03-01 00:00:00');

    return [
      'start' => $start,
      'end' => $end,
      'term_switch' => $term_switch,
      'ht_term_index' => $ht_term_index_date_source->getTimestamp(),
      'vt_term_index' => $vt_term_index_date_source->getTimestamp(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultSchoolYearStart(bool $as_object = TRUE, ?\DateTime $relative_date = NULL): \DateTime|int {
    $default_school_year = $this->getDefaultSchoolYear($relative_date);
    return $as_object ? $default_school_year['start'] : $default_school_year['start']->getTimestamp();
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultSchoolYearEnd(bool $as_object = TRUE, ?\DateTime $relative_date = NULL): \DateTime|int {
    $default_school_year = $this->getDefaultSchoolYear($relative_date);
    return $as_object ? $default_school_year['end'] : $default_school_year['end']->getTimestamp();
  }

}
