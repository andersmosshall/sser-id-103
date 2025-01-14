<?php

namespace Drupal\simple_school_reports_attendance_analyse\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides form for sorting out school week deviations.
 */
class SchoolWeekDeviationsSortOutForm extends FormBase {

  /**
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'school_week_deviations_sort_out_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $default_limit = (new \DateTime('now'));
    // Set a default limit of 3 years ago.
    $default_limit->setTime(12,0,0);
    $default_limit->sub(new \DateInterval('P3Y'));
    $default_limit->setDate($default_limit->format('Y'), 7, 15);

    $form['date_limit'] = [
      '#type' => 'date',
      '#title' => $this->t('Date limit'),
      '#description' => $this->t('Delete all deviations that are older than this date.'),
      '#default_value' => $default_limit->format('Y-m-d'),
      '#required' => TRUE,
    ];


    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Sort out school week deviations'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $to_delete = [];
    $date_limit = $form_state->getValue('date_limit');
    if (!$date_limit) {
      $this->messenger()->addError($this->t('Something went wrong. Try again.'));
    }

    $date_limit = new \DateTime($date_limit . ' 23:59:59');
    $ids = $this->entityTypeManager->getStorage('school_week_deviation')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('to_date', $date_limit->getTimestamp(), '<=')
      ->execute();

    if (empty($ids)) {
      $this->messenger()->addMessage($this->t('No deviations to sort out.'));
      return;
    }

    // Initialize batch (to set title).
    $batch = [
      'title' => $this->t('Removing school week deviations'),
      'init_message' => $this->t('Removing school week deviations'),
      'progress_message' => $this->t('Processed @current out of @total.'),
      'finished' => [self::class, 'finished'],
      'operations' => [],
    ];
    foreach ($ids as $id) {
      $batch['operations'][] = [
        [self::class, 'deleteSchoolWeekDeviation'],
        [$id],
      ];
    }
    if (count($batch['operations']) < 10) {
      $batch['progressive'] = FALSE;
    }
    batch_set($batch);
  }

  public static function deleteSchoolWeekDeviation(string $id, &$context) {
    \Drupal::entityTypeManager()->getStorage('school_week_deviation')->load($id)?->delete();
    $context['results']['removed'][] = $id;
  }

  public static function finished($success, $results) {
    if (!$success || empty($results['removed'])) {
      \Drupal::messenger()->addError(t('Something went wrong. Try again.'));
      return;
    }

    \Drupal::messenger()->addStatus(t('@count items removed', ['@count'  => count($results['removed'])]));
  }

}
