<?php

namespace Drupal\simple_school_reports_attendance_analyse\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\simple_school_reports_entities\SchoolWeekInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides form for copy school week to other sources.
 */
class SchoolWeekCopyForm extends FormBase {

  /**
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ModuleHandlerInterface $moduleHandler,
    protected StateInterface $state,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('state'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'school_week_copy_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options = [];

    $grades = simple_school_reports_core_allowed_user_grade();
    unset($grades[-99]);
    unset($grades[99]);

    foreach ($grades as $grade => $label) {
      $label = $grade > 0 ? $this->t('Grade @grade', ['@grade' => $grade]) : $label;
      $options['g:' . $grade] = $label;
    }

    $use_classes = $this->moduleHandler->moduleExists('simple_school_reports_class');
    if ($use_classes) {
      /** @var \Drupal\simple_school_reports_class_support\Service\SsrClassServiceInterface $class_service */
      $class_service = \Drupal::service('simple_school_reports_class_support.class_service');
      $classes = $class_service->getSortedClasses();
      foreach ($classes as $class) {
        $options['c:' . $class->id()] = $class->label();
      }
    }

    $form['source_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Source to copy school week from'),
      '#required' => TRUE,
      '#options' => $options,
    ];

    $form['targets'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Targets to create school week on'),
      '#description' => $this->t('Note that any current school week on the target will be replaced.'),
      '#options' => $options,
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Copy'),
    ];
    return $form;
  }

  protected function getSource(FormStateInterface $form_state): ?SchoolWeekInterface {
    $source_id = $form_state->getValue('source_id');

    [$type, $id] = explode(':', $source_id, 2);
    if ($type === 'c' && $id) {
      return $this->entityTypeManager->getStorage('ssr_school_class')->load($id)?->get('school_week')->entity;
    }
    if ($type === 'g' && $id) {
      $state = $this->state->get('ssr_school_week_per_grade', []);
      $school_week_id = $state[$id] ?? NULL;

      return $school_week_id ? $this->entityTypeManager->getStorage('school_week')->load($school_week_id) : NULL;
    }

    return NULL;
  }

  protected function getTargetIds(FormStateInterface $form_state): array {
    $source_id = $form_state->getValue('source_id');
    $targets = $form_state->getValue('targets');

    $filtered_targets = [];

    foreach ($targets as $target_id => $value) {
      if (!!$value && $target_id !== $source_id) {
        $filtered_targets[] = $target_id;
      }
    }

    return $filtered_targets;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $source = $this->getSource($form_state);
    if (!$source) {
      $form_state->setErrorByName('source_id', $this->t('The selected source does not have a school week.'));
      return;
    }

    $targets = $this->getTargetIds($form_state);
    if (empty($targets)) {
      $form_state->setErrorByName('target_ids', $this->t('Select at least one target that is not the same as the source.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $source = $this->getSource($form_state);
    $targets = $this->getTargetIds($form_state);

    if (!$source || empty($targets)) {
      $this->messenger()->addError($this->t('Something went wrong. Try again.'));
      return;
    }

    // Initialize batch (to set title).
    $batch = [
      'title' => $this->t('Copying school week'),
      'init_message' => $this->t('Copying school week'),
      'progress_message' => $this->t('Processed @current out of @total.'),
      'finished' => [self::class, 'finished'],
      'operations' => [],
    ];

    foreach ($targets as $target_id) {
      $batch['operations'][] = [
        [self::class, 'copyToTarget'],
        [$source, $target_id],
      ];
    }
    if (count($batch['operations']) < 10) {
      $batch['progressive'] = FALSE;
    }
    batch_set($batch);
  }

  public static function copyToTarget(SchoolWeekInterface $source, string $target_id, &$context) {
    $new_school_week = $source->createDuplicate();
    [$type, $id] = explode(':', $target_id, 2);

    if ($type === 'c') {
      $class = $id ? \Drupal::entityTypeManager()->getStorage('ssr_school_class')->load($id) : NULL;
      if (!$class) {
        return;
      }
      $class->set('school_week', $new_school_week);
      $class->save();
      $context['results']['copied'][] = $target_id;
    }

    if ($type === 'g') {
      $grade = $id;
      if ($grade === NULL) {
        return;
      }
      $new_school_week->save();
      $school_week_state = \Drupal::state()->get('ssr_school_week_per_grade', []);
      $school_week_state[$grade] = $new_school_week->id();
      \Drupal::state()->set('ssr_school_week_per_grade', $school_week_state);
      $context['results']['copied'][] = $target_id;
    }
  }

  public static function finished($success, $results) {
    if (!$success || empty($results['copied'])) {
      \Drupal::messenger()->addError(t('Something went wrong. Try again.'));
      return;
    }

    \Drupal::messenger()->addStatus(t('@count items saved', ['@count'  => count($results['copied'])]));
  }

}
