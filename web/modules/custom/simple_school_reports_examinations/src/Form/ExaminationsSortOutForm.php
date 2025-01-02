<?php

namespace Drupal\simple_school_reports_examinations\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\simple_school_reports_examinations_support\AssessmentGroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides form for sorting out examinations.
 */
class ExaminationsSortOutForm extends FormBase {

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
  public function buildForm(array $form, FormStateInterface $form_state, ?AssessmentGroupInterface $ssr_assessment_group = NULL) {
    if (!$ssr_assessment_group instanceof AssessmentGroupInterface) {
      throw new AccessDeniedHttpException();
    }

    $form['assessment_group'] = [
      '#type' => 'value',
      '#value' => $ssr_assessment_group->id(),
    ];


    $default_limit = (new \DateTime('now'));
    // Set a default limit of 2 years ago.
    $default_limit->setTime(12,0,0);
    $default_limit->sub(new \DateInterval('P2Y'));
    $default_limit->setDate($default_limit->format('Y'), 7, 15);

    $form['date_limit'] = [
      '#type' => 'date',
      '#title' => $this->t('Date limit'),
      '#description' => $this->t('Delete all examinations in assessment group @label older than this date.', [
        '@label' => $ssr_assessment_group->label(),
      ]),
      '#default_value' => $default_limit->format('Y-m-d'),
      '#required' => TRUE,
    ];


    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Sort out examinations'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $date_limit = $form_state->getValue('date_limit');
    $assessment_group_id = $form_state->getValue('assessment_group');
    if (!$date_limit || !$assessment_group_id) {
      $this->messenger()->addError($this->t('Something went wrong. Try again.'));
    }

    $date_limit = new \DateTime($date_limit . ' 23:59:59');
    $ids = $this->entityTypeManager->getStorage('ssr_examination')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('deadline', $date_limit->getTimestamp(), '<=')
      ->condition('assessment_group', $assessment_group_id)
      ->execute();

    if (empty($ids)) {
      $this->messenger()->addMessage($this->t('No examinations to sort out.'));
      return;
    }

    // Initialize batch (to set title).
    $batch = [
      'title' => $this->t('Removing examinations'),
      'init_message' => $this->t('Removing examinations'),
      'progress_message' => $this->t('Processed @current out of @total.'),
      'finished' => [self::class, 'finished'],
      'operations' => [],
    ];
    foreach ($ids as $id) {
      $batch['operations'][] = [
        [self::class, 'deleteExamination'],
        [$id],
      ];
    }
    if (count($batch['operations']) < 10) {
      $batch['progressive'] = FALSE;
    }
    batch_set($batch);
  }

  public static function deleteExamination(string $id, &$context) {
    \Drupal::entityTypeManager()->getStorage('ssr_examination')->load($id)?->delete();
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
