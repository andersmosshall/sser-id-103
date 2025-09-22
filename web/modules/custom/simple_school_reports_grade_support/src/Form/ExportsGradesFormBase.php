<?php

namespace Drupal\simple_school_reports_grade_support\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\ConfirmFormHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\simple_school_reports_core\SchoolGradeHelper;
use Drupal\simple_school_reports_core\SchoolTypeHelper;
use Drupal\simple_school_reports_core\Service\EmailService;
use Drupal\simple_school_reports_entities\Service\SyllabusServiceInterface;
use Drupal\simple_school_reports_grade_support\Service\GradableCourseServiceInterface;
use Drupal\simple_school_reports_grade_support\Service\GradeService;
use Drupal\simple_school_reports_grade_support\Service\GradeServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a form base for course grade registration.
 */
abstract class ExportsGradesFormBase extends ConfirmFormBase implements TrustedCallbackInterface {

  /**
   * @var string
   */
  protected string $step = 'init';

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected GradableCourseServiceInterface $gradableCourseService,
    protected GradeServiceInterface $gradeService,
    protected ModuleHandlerInterface $moduleHandler,
    protected Connection $database,
    protected SyllabusServiceInterface $syllabusService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('simple_school_reports_grade_support.gradable_course'),
      $container->get('simple_school_reports_grade_support.grade_service'),
      $container->get('module_handler'),
      $container->get('database'),
      $container->get('simple_school_reports_entities.syllabus_service'),
    );
  }

  abstract public function getSchoolTypeVersions(): array;

  /**
   * @return string
   */
  abstract public function getCancelRoute(): string;

  public function getConfiguration(): array {
    $codes = [];

    $configuration = [
      // Usage.
      'use_grade_documents' => TRUE,
      'use_invalid_absence' => TRUE,
      'use_final_grade_document' => TRUE,
      'use_signature_documents' => TRUE,
      'use_grade_catalog' => TRUE,

      // Labels.
      'final_grade_label' => $this->t('Final grades'),

      // Content.
      'grade_confirm_items' => [
        'intro' => '',
        'show_codes' => TRUE,
      ],
      'final_confirm_items' => [
        'intro' => '',
        'show_codes' => TRUE,
      ],

      // Data,
      'codes' => $codes,
    ];

    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    if ($this->step === 'confirm_final') {
      return $this->t('Confirm before exporting documents');
    }
    return $this->t('Export documents');
  }

  public function getDescription() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Continue');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url($this->getCancelRoute());
  }

  protected function getSyllabusIds(): array {
    return $this->gradableCourseService->getGradableSyllabusIds($this->getSchoolTypeVersions());
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $step = $form_state->get('step') ?? NULL;
    if (!$step) {
      $step = 'init';
      $form_state->set('step', $step);
    }

    if ($step === 'init') {
      $form = $this->buildInitStep($form, $form_state);
    }
    if ($step === 'confirm_final') {
      $form = $this->buildConfirmStep($form, $form_state);
    }

    $this->step = $step;
    return parent::buildForm($form, $form_state);
  }

  public function buildInitStep(array $form, FormStateInterface $form_state): array {
    $school_types = $this->getSchoolTypeVersions();
    $syllabus_ids = $this->getSyllabusIds();

    $student_ids = $this->gradeService->getStudentIdsWithGrades($syllabus_ids);
    if (empty($student_ids)) {
      throw new NotFoundHttpException();
    }
    $student_ids = $this->entityTypeManager->getStorage('user')->loadMultiple($student_ids);

    $configuration = $this->getConfiguration();

    $student_options = [];
    if (!empty($student_ids)) {
      $users = $this->entityTypeManager->getStorage('user')->loadMultiple($student_ids);
      foreach ($users as $user) {
        $student_options[$user->id()] = $user->label();
      }
    }
    if (empty($student_options)) {
      throw new AccessDeniedHttpException();
    }
    $default_value = array_keys($student_options);
    $form['student_ids'] = [
      '#type' => 'ssr_multi_select',
      '#title' => $this->t('Students'),
      '#description' => $this->t('Select the list of students to constrain any export to.'),
      '#options' => $student_options,
      '#default_value' => $default_value,
      '#filter_placeholder' => $this->t('Enter name or grade/class to filter'),
      '#required' => TRUE,
    ];

    if (!empty($configuration['use_grade_documents'])) {
      $form['grade_documents'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Export grade documents'),
        '#default_value' => FALSE,
      ];

      $form['grade_documents_settings'] = [
        '#type' => 'details',
        '#title' => $this->t('Export grade documents settings'),
        '#open' => TRUE,
        '#states' => [
          'visible' => [
            ':input[name="grade_documents"]' => ['value' => TRUE],
          ],
        ],
      ];

      $form['grade_documents_settings']['grade_doc_include_invalid_absence'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Include invalid absence'),
        '#default_value' => TRUE,
      ];

      $form['grade_documents_settings']['grade_doc_invalid_absence_from'] = [
        '#type' => 'data',
        '#title' => $this->t('Invalid absence from'),
        '#default_value' => NULL,
        '#states' => [
          'visible' => [
            ':input[name="grade_doc_include_invalid_absence"]' => ['checked' => TRUE],
          ],
        ],
      ];

      $form['grade_documents_settings']['grade_doc_invalid_absence_to'] = [
        '#type' => 'data',
        '#title' => $this->t('Invalid absence from'),
        '#default_value' => NULL,
        '#states' => [
          'visible' => [
            ':input[name="grade_doc_include_invalid_absence"]' => ['checked' => TRUE],
          ],
        ],
      ];



    }

    return $form;
  }

  public function buildConfirmStep(array $form, FormStateInterface $form_state,): array {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $step = $form_state->get('step') ?? 'init';

    if ($step === 'init') {
      $form_state->set('export_values', $form_state->getValues());;
    }
    $values = $form_state->get('export_values');

    if ($next_step = $this->getNextStep($step, $values, $form_state)) {
      $form_state->set('step', $next_step);
      $form_state->setRebuild(TRUE);
      return;
    }

    $form_state->set('step', 'export');

    // Initialize batch (to set title).
    $batch = [
      'title' => $this->t('Collecting data and building documents'),
      'init_message' => $this->t('Collecting data and building documents'),
      'progress_message' => $this->t('Processed @current out of @total.'),
      'finished' => [$this, 'finished'],
      'operations' => [],
    ];

    $student_ids = $values['student_ids'] ?? [];

    $form_state->setRedirect($this->getCancelRoute());
    if (!empty($batch['operations'])) {
      batch_set($batch);
    }
    else {
      $this->messenger()->addWarning($this->t('Something went wrong'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getNextStep(string $current_step, array $export_values, FormStateInterface $form_state): ?string {
    return NULL;
  }

  /**
   * Access check for the form.
   *
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   */
  public function access(?AccountInterface $account = NULL): AccessResultInterface {
    return AccessResult::allowedIfHasPermission($account, 'administer simple school reports settings');
  }

  public function batchTest(string $student_id, &$context) {
    /** @var \Drupal\user\UserInterface|null $student */
    $student = $this->entityTypeManager->getStorage('user')->load($student_id);
    $student_name = $student?->getDisplayName() ?? $student_id;

    \Drupal::messenger()->addMessage($student_name);

    $context['results']['student_names'][] = $student_name;
  }

  public function finished($success, $results) {
    if (!$success || empty($results['student_names'])) {
      $this->messenger()->addError(t('Something went wrong'));
      return;
    }
    $this->messenger()->addMessage($this->t('Export finished.'));
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['batchTest', 'finished'];
  }

}
