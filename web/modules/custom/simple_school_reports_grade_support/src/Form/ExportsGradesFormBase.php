<?php

namespace Drupal\simple_school_reports_grade_support\Form;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Drupal\simple_school_reports_core\Form\ActivateSyllabusFormBase;
use Drupal\simple_school_reports_core\Pnum;
use Drupal\simple_school_reports_core\SchoolGradeHelper;
use Drupal\simple_school_reports_core\SchoolTypeHelper;
use Drupal\simple_school_reports_core\Service\FileTemplateServiceInterface;
use Drupal\simple_school_reports_core\Service\OrganizationsServiceInterface;
use Drupal\simple_school_reports_core\Service\TermServiceInterface;
use Drupal\simple_school_reports_entities\Service\ProgrammeServiceInterface;
use Drupal\simple_school_reports_entities\Service\SyllabusServiceInterface;
use Drupal\simple_school_reports_grade_support\GradeInterface;
use Drupal\simple_school_reports_grade_support\GradeSigningInterface;
use Drupal\simple_school_reports_grade_support\Service\GradableCourseServiceInterface;
use Drupal\simple_school_reports_grade_support\Service\GradeServiceInterface;
use Drupal\simple_school_reports_grade_support\Utilities\GradeInfo;
use Drupal\user\UserInterface;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
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

  protected ?array $config = NULL;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected GradableCourseServiceInterface $gradableCourseService,
    protected GradeServiceInterface $gradeService,
    protected ModuleHandlerInterface $moduleHandler,
    protected Connection $database,
    protected SyllabusServiceInterface $syllabusService,
    protected TermServiceInterface $termService,
    protected ProgrammeServiceInterface $programmeService,
    protected UuidInterface $uuid,
    protected FileTemplateServiceInterface $fileTemplateService,
    protected FileSystemInterface $fileSystem,
    protected Pnum $pnum,
    protected OrganizationsServiceInterface $organizationsService,
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
      $container->get('simple_school_reports_core.term_service'),
      $container->get('simple_school_reports_entities.programme_service'),
      $container->get('uuid'),
      $container->get('simple_school_reports_core.file_template_service'),
      $container->get('file_system'),
      $container->get('simple_school_reports_core.pnum'),
      $container->get('simple_school_reports_core.organizations_service'),
    );
  }

  abstract public function getSchoolType(): string;

  public function getSchoolTypeVersions(): array {
    return SchoolTypeHelper::getSchoolTypeVersions($this->getSchoolType());
  }

  /**
   * @return string
   */
  abstract public function getCancelRoute(): string;

  public function getConfiguration(): array {
    if ($this->config !== NULL) {
      return $this->config;
    }


    $codes = [
      'P' => ' Kursen har betygsatts efter prövning.',
    ];

    $default_term_index = $this->termService->getDefaultTermIndex();
    $parsed_term_index = $this->termService->parseDefaultTermIndex($default_term_index);

    $default_invalid_absence_from = $parsed_term_index['term_start'];
    $default_invalid_absence_to = new \DateTime('now');

    $configuration = [
      // Usage.
      'use_grade_documents' => TRUE,
      'use_final_grade_document' => TRUE,
      'use_signature_documents' => TRUE,
      'use_grade_catalog' => TRUE,

      // Labels.
      'final_grade_label' => 'Slutbetyg',

      // Content.
      'grade_confirm_items' => [
        'intro' => 'Markeringar för prövning (P) kommer sättas automatiskt. Andra markeringar kan behöva justeras manuellt i efterhand i genererade dokument vid behov.',
        'pre_show_codes' => 'Följande markeringar kan användas i dokumenten:',
        'show_codes' => TRUE,
      ],
      'final_grade_confirm_items' => [
        'intro' => 'Markeringar för prövning (P) kommer sättas automatiskt. Andra markeringar kan behöva justeras manuellt i efterhand i genererade dokument vid behov.',
        'disclaimer' => 'Det är starkt rekommenderat att genererade filer för slutbetyg kontrolleras att de är korrekta i avseende för markeringar, program och liknande.',
        'pre_show_codes' => 'Följande markeringar kan användas i dokumenten:',
        'show_codes' => TRUE,
      ],
      'codes_info_prefix' => '** - Ämne har inte lästs på grund av anpassad studiegång',
      'grade_catalog_info' => '** - Ämne har inte lästs på grund av anpassad studiegång',

      // Data,
      'codes' => $codes,
      'default_invalid_absence_from' => $default_invalid_absence_from,
      'default_invalid_absence_to' => $default_invalid_absence_to,
      'default_document_date' => new \DateTime('now'),
      'grade_document_exclude_reason_map' => [
        GradeInterface::EXCLUDE_REASON_ADAPTED_STUDIES => '**',
      ],
      'grade_catalog_exclude_reason_map' => [
        GradeInterface::EXCLUDE_REASON_ADAPTED_STUDIES => '**',
      ],
    ];

    $this->config = $configuration;
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

    $this->step = $step;
    return parent::buildForm($form, $form_state);
  }

  public function buildInitStep(array $form, FormStateInterface $form_state): array {
    $syllabus_ids = $this->getSyllabusIds();

    $student_ids = $this->gradeService->getStudentIdsWithGrades($syllabus_ids);
    if (empty($student_ids)) {
      throw new NotFoundHttpException();
    }

    $configuration = $this->getConfiguration();

    $form['document_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Document date'),
      '#default_value' => $configuration['default_document_date']->format('Y-m-d'),
      '#required' => TRUE,
    ];

    $student_options = [];
    $users = $this->entityTypeManager->getStorage('user')->loadMultiple($student_ids);
    foreach ($users as $user) {
      $student_options[$user->id()] = $user->label();
    }
    if (empty($student_options)) {
      throw new AccessDeniedHttpException();
    }
    $default_students = array_keys($student_options);
    $form['student_ids'] = [
      '#type' => 'ssr_multi_select',
      '#title' => $this->t('Students'),
      '#description' => $this->t('Select the list of students to constrain any export to.'),
      '#options' => $student_options,
      '#default_value' => $default_students,
      '#filter_placeholder' => $this->t('Enter name or grade/class to filter'),
      '#required' => TRUE,
    ];

    $principal_uids = $this->entityTypeManager->getStorage('user')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 1)
      ->condition('roles', 'principle')
      ->sort('field_first_name', 'ASC')
      ->sort('field_last_name', 'ASC')
      ->execute();

    if (empty($principal_uids)) {
      throw new NotFoundHttpException();
    }
    $principal_options = [];
    $users = $this->entityTypeManager->getStorage('user')->loadMultiple($principal_uids);
    /** @var \Drupal\user\UserInterface $user */
    foreach ($users as $user) {
      $principal_options[$user->id()] = $user->getDisplayName();
    }

    $default_principal = NULL;
    if (isset($principal_options[$this->currentUser()->id()])) {
      $default_principal = $this->currentUser()->id();
    }
    else if (count($principal_options) === 1) {
      $default_principal = array_key_first($principal_options);
    }

    $form['principal'] = [
      '#type' => 'select',
      '#title' => $this->t('Principal'),
      '#description' => $this->t('Select the list of students to constrain any export to.'),
      '#options' => $principal_options,
      '#default_value' => $default_principal,
      '#filter_placeholder' => $this->t('Enter name or grade/class to filter'),
      '#required' => TRUE,
    ];


    // Check for open grade rounds.
    $open_grade_rounds = $this->entityTypeManager->getStorage('ssr_grade_reg_round')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 1)
      ->condition('bundle', 'gy')
      ->condition('open', TRUE)
      ->execute();

    if (!empty($open_grade_rounds)) {
      $form['close_open_grade_rounds'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Close all open grade rounds'),
        '#description' => $this->t('There are @count open grade rounds.', ['@count' => count($open_grade_rounds)]),
      ];
    }

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
            ':input[name="grade_documents"]' => ['checked' => TRUE],
          ],
        ],
      ];

      $form['grade_documents_settings']['grade_doc_include_invalid_absence'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Include invalid absence'),
        '#default_value' => TRUE,
      ];

      $form['grade_documents_settings']['grade_doc_invalid_absence_from'] = [
        '#type' => 'date',
        '#title' => $this->t('Invalid absence from'),
        '#default_value' => $configuration['default_invalid_absence_from']->format('Y-m-d'),
        '#states' => [
          'visible' => [
            ':input[name="grade_doc_include_invalid_absence"]' => ['checked' => TRUE],
          ],
        ],
      ];

      $form['grade_documents_settings']['grade_doc_invalid_absence_to'] = [
        '#type' => 'date',
        '#title' => $this->t('Invalid absence to'),
        '#default_value' => $configuration['default_invalid_absence_to']->format('Y-m-d'),
        '#states' => [
          'visible' => [
            ':input[name="grade_doc_include_invalid_absence"]' => ['checked' => TRUE],
          ],
        ],
      ];
      $form['grade_documents_settings']['confirm'] = $this->buildConfirmSection('grade');
    }

    if (!empty($configuration['use_final_grade_document'])) {
      $form['final_grade_documents'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Export @name', ['@name' => $configuration['final_grade_label']]),
        '#default_value' => FALSE,
      ];

      $form['final_grade_documents_settings'] = [
        '#type' => 'details',
        '#title' => $this->t('Export grade documents settings'),
        '#open' => TRUE,
        '#states' => [
          'visible' => [
            ':input[name="final_grade_documents"]' => ['checked' => TRUE],
          ],
        ],
      ];

      $form['final_grade_documents_settings']['final_grade_student_ids'] = [
        '#type' => 'ssr_multi_select',
        '#title' => $this->t('Students with final grades'),
        '#description' => $this->t('Only students set in this list will be included in the final grade export or leave empty to include all selected students above.'),
        '#options' => $student_options,
        '#default_value' => NULL,
        '#filter_placeholder' => $this->t('Enter name or grade/class to filter'),
      ];

      $form['final_grade_documents_settings']['confirm'] = $this->buildConfirmSection('final_grade');
    }

    if (!empty($configuration['use_signature_documents'])) {
      $form['signature_documents'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Export signature documents'),
        '#default_value' => FALSE,
      ];

      $form['signature_documents_settings'] = [
        '#type' => 'details',
        '#title' => $this->t('Export signature documents settings'),
        '#open' => TRUE,
        '#states' => [
          'visible' => [
            ':input[name="signature_documents"]' => ['checked' => TRUE],
          ],
        ],
      ];

      $school_type = $this->getSchoolType();

      $link = Link::createFromRoute($this->t('here'), 'view.ssr_grade_sign_attest.' . mb_strtolower($school_type), [])->toString();
      $form['signature_documents_settings']['info'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('Signature documents will ge generated for grades that has not yet been signed. Grades that are pending to be attested in the next tab will be excluded as well. To start over with the unsigned grades completely, clear any pending attestations @link.', ['@link' => $link]),
      ];
    }

    if (!empty($configuration['use_grade_catalog'])) {
      $form['grade_catalog'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Export grade catalog'),
        '#default_value' => FALSE,
      ];

      $form['grade_catalog_settings'] = [
        '#type' => 'details',
        '#title' => $this->t('Export grade catalog settings'),
        '#open' => TRUE,
        '#states' => [
          'visible' => [
            ':input[name="grade_catalog"]' => ['checked' => TRUE],
          ],
        ],
      ];

      $date_walk = new \DateTime('now');
      $number_of_terms = 10;
      $catalog_terms_options = [];
      for ($i = 1; $i <= $number_of_terms; $i++) {
        $term_index = $this->termService->getDefaultTermIndex($date_walk);
        $term_info = $this->termService->parseDefaultTermIndex($term_index);
        $catalog_terms_options[$term_index] = $term_info['semester_name'];

        $date_walk = $term_info['arbitrary_term_data'];
        $date_walk->sub(new \DateInterval('P6M'));
      }

      $form['grade_catalog_settings']['catalog_terms'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Terms to include in catalog'),
        '#options' => $catalog_terms_options,
        '#default_value' => [array_key_first($catalog_terms_options)],
      ];

      $term_combine_options = [
        'combine' => $this->t('Combine to one catalog'),
        'split' => $this->t('One catalog for each selected terms'),
      ];

      $form['grade_catalog_settings']['catalog_term_combine'] = [
        '#type' => 'radios',
        '#title' => $this->t('Combine terms setting'),
        '#options' => $term_combine_options,
        '#default_value' => 'combine',
      ];

      $form['grade_catalog_settings']['grade_catalog_include_invalid_absence'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Include invalid absence'),
        '#default_value' => FALSE,
      ];

    }

    return $form;
  }

  protected function buildConfirmSection(string $type): array {
    $form = [];
    $configuration = $this->getConfiguration();

    $has_content_to_confirm = FALSE;
    foreach (($configuration[$type . '_confirm_items'] ?? []) as $key => $value) {
      if (is_bool($value)) {
        if ($key === 'show_codes' && !!$value) {
          $codes = $configuration['codes'] ?? [];
          if (!empty($codes)) {
            $code_names = [];

            foreach ($codes as $code => $name) {
              $code_names[] = $code . ' = ' . $name;
            }

            $form['codes'] = [
              '#theme' => 'item_list',
              '#items' => $code_names,
            ];
          }
        }
        continue;
      }

      if (is_string($value)) {
        $has_content_to_confirm = TRUE;
        $prefix = !str_starts_with($value, '<p') ? '<p>' : '';
        $suffix = !str_starts_with($value, '<p') ? '</p>' : '';
        $form[$key] = [
          '#markup' => $value,
          '#prefix' => $prefix,
          '#suffix' => $suffix,
        ];
      }

      if (is_array($value)) {
        $has_content_to_confirm = TRUE;
        $form[$key] = $value;
      }
    }

    if ($has_content_to_confirm) {
      $form['confirm_' . $type] = [
        '#type' => 'checkbox',
        '#title' => $this->t('I confirm that I have read and understood the above information.'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $step = $form_state->get('step') ?? 'init';
    if ($step === 'init') {
      $this->validateInitForm($form, $form_state);
    }
  }

  public function validateInitForm(array &$form, FormStateInterface $form_state) {
    $use_grade_documents = $form_state->getValue('grade_documents');
    $use_final_grade_document = $form_state->getValue('final_grade_documents');
    $use_signature_documents = $form_state->getValue('signature_documents');
    $use_grade_catalog = $form_state->getValue('grade_catalog');

    $configuration = $this->getConfiguration();

    if ($use_grade_documents || $use_final_grade_document) {
      $templates_to_use[] = 'grade_document';
    }
    if ($use_signature_documents) {
      $templates_to_use[] = 'sign_document';
    }
    if ($use_grade_catalog) {
      $templates_to_use[] = 'grade_catalog';
    }
    foreach ($templates_to_use as $template_to_use) {
      $template_file_path = $this->fileTemplateService->getFileTemplateRealPath($template_to_use);
      if (!$template_file_path) {
        $form_state->setError($form, $this->t('Template file for @template_to_use is not found.', ['@template_to_use' => $template_to_use]));
        return;
      }
    }

    if (!$use_grade_documents && !$use_final_grade_document && !$use_signature_documents && !$use_grade_catalog) {
      $form_state->setError($form, $this->t('At least one export type must be selected.'));
      return;
    }

    if ($use_grade_documents) {
      if ($form_state->getValue('grade_doc_include_invalid_absence')) {
        $invalid_absence_from = $form_state->getValue('grade_doc_invalid_absence_from');
        $invalid_absence_to = $form_state->getValue('grade_doc_invalid_absence_to');

        if (!$invalid_absence_from || !$invalid_absence_to || $invalid_absence_from > $invalid_absence_to) {
          $form_state->setErrorByName('grade_doc_invalid_absence_from', $this->t('Invalid absence period must be valid.'));
          $form_state->setErrorByName('grade_doc_invalid_absence_to', $this->t('Invalid absence period must be valid.'));
        }
      }
      $confirmed = $form_state->getValue('confirm_grade');
      if (!$confirmed) {
        $form_state->setErrorByName('confirm_grade', $this->t('You must confirm the information about @name.', ['@name' => $this->t('grades')]));
      }
    }

    if ($use_final_grade_document) {
      $confirmed = $form_state->getValue('confirm_final_grade');
      if (!$confirmed) {
        $form_state->setErrorByName('confirm_final_grade', $this->t('You must confirm the information about @name.', ['@name' => $configuration['final_grade_label']]));;
      }
    }

    if ($use_grade_catalog) {
      $catalog_terms = $form_state->getValue('catalog_terms', []);
      $selected_terms = [];

      foreach ($catalog_terms as $term_index => $checked) {
        if ($checked) {
          $selected_terms[$term_index] = $term_index;
        }
      }

      $is_sequential = true; // Assume true initially

      $selected_catalog_term_indexes = array_values($selected_terms);
      sort($selected_catalog_term_indexes);

      ## 2. Check for Trivial Cases (0 or 1 selections)
      // If there are 0 or 1 selections, the condition of continuity is trivially met.
      if (count($selected_terms) <= 1) {
        $is_sequential = true;
      } else {

        ## 3. Find the Range of Keys to Check
        $all_keys = array_keys($catalog_terms);
        sort($all_keys);

        // Find the index of the first selected key in the full key set
        $first_selected_key = reset($selected_catalog_term_indexes);
        $start_index = array_search($first_selected_key, $all_keys);

        // Find the index of the last selected key in the full key set
        $last_selected_key = end($selected_catalog_term_indexes);
        $end_index = array_search($last_selected_key, $all_keys);

        // Extract the subset of keys from the first selected item to the last selected item
        // The length of the subset is $end_index - $start_index + 1
        $range_to_check = array_slice($all_keys, $start_index, $end_index - $start_index + 1);

        ## 4. Validate the Range
        foreach ($range_to_check as $key) {
          // Check if the value for this key in the original data is 'false' (0)
          // If any item *within the selected range* is unselected, the sequence is broken.
          if (!isset($selected_terms[$key])) {
            $is_sequential = false;
            break;
          }
        }
      }

      if (empty($selected_terms)) {
        $form_state->setErrorByName('catalog_terms', $this->t('At least one term must be selected.'));
      }

      $catalog_term_combine = $form_state->getValue('catalog_term_combine');
      if (empty($catalog_term_combine)) {
        $form_state->setErrorByName('catalog_term_combine', $this->t('Select a combine terms setting.'));
      }

      if ($catalog_term_combine === 'combine' && count($selected_terms) > 1 && !$is_sequential) {
        $form_state->setErrorByName('catalog_terms', $this->t('When combining terms, the selected terms cannot have gaps between first and last selected term.'));
      }
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $step = $form_state->get('step') ?? 'init';

    if ($step === 'init') {
      $form_state->set('export_values', $form_state->getValues());;
    }
    $values = $form_state->get('export_values');

    $values['final_grade_student_ids'] = !empty($values['final_grade_student_ids'])
      ? $values['final_grade_student_ids']
      : $values['student_ids'] ?? [];

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

    $this->addBatchOperations($batch, $values);

    $form_state->setRedirect($this->getCancelRoute());
    if (!empty($batch['operations'])) {
      batch_set($batch);
    }
    else {
      $this->messenger()->addWarning($this->t('Something went wrong'));
    }
  }

  protected function addBatchOperations(array &$batch, array $values) {
    $student_ids = $values['student_ids'] ?? [];

    $use_grade_documents = $values['grade_documents'] ?? FALSE;
    $use_final_grade_document = $values['final_grade_documents'] ?? FALSE;
    $use_signature_documents = $values['signature_documents'] ?? FALSE;
    $use_grade_catalog = $values['grade_catalog'] ?? FALSE;

    $batch['operations'][0] = [[$this, 'prepareContextData'], [$values]];


    $templates_to_use = [];
    if ($use_grade_documents) {
      $templates_to_use[] = 'grade_document';
    }
    if ($use_final_grade_document) {
      $templates_to_use[] = 'final_grade_document';
    }
    if ($use_signature_documents) {
      $templates_to_use[] = 'sign_document';
    }
    if ($use_grade_catalog) {
      $templates_to_use[] = 'grade_catalog';
    }
    $batch['operations'][] = [[$this, 'prepareWorkingPaths'], [$templates_to_use]];
    $batch['operations'][] = [[$this, 'prepareTemplates'], [$templates_to_use]];

    $invalid_absence_periods = [];
    $grade_document_invalid_absence_key = NULL;
    if ($use_grade_documents && ($values['grade_doc_include_invalid_absence'] ?? FALSE)) {
      $from = $values['grade_doc_invalid_absence_from'] ?? NULL;
      $to = $values['grade_doc_invalid_absence_to'] ?? NULL;

      $from_date = $from ? new \DateTime($from . ' 00:00:00') : NULL;
      $to_date = $from ? new \DateTime($to . ' 23:59:59') : NULL;
      $invalid_absence_periods[] = [
        'from' => $from_date,
        'to' => $to_date,
      ];
      $grade_document_invalid_absence_key = $from_date->getTimestamp() . ':' . $to_date->getTimestamp();
    }
    $catalog_term_combine = $values['catalog_term_combine'] ?? 'combine';
    $catalog_term_indexes = [];
    if ($use_grade_catalog) {
      foreach ($values['catalog_terms'] ?? [] as $term_index => $checked) {
        if ($checked) {
          $catalog_term_indexes[] = $term_index;
        }
      }
    }
    // Sort the catalog term indexes from low to high.
    sort($catalog_term_indexes);

    if ($use_grade_catalog && ($values['grade_catalog_include_invalid_absence'] ?? FALSE) && !empty($catalog_term_indexes)) {
      if ($catalog_term_combine === 'combine') {
        $first_term_index = $catalog_term_indexes[0];
        $last_term_index = $catalog_term_indexes[count($catalog_term_indexes) - 1];

        $first_term_info = $this->termService->parseDefaultTermIndex($first_term_index);
        $last_term_info = $this->termService->parseDefaultTermIndex($last_term_index);

        $invalid_absence_periods[] = [
          'from' => $first_term_info['term_start'],
          'to' => $last_term_info['term_end'],
        ];
      }
      else {
        foreach ($catalog_term_indexes as $term_index) {
          $term_info = $this->termService->parseDefaultTermIndex($term_index);
          $invalid_absence_periods[] = [
            'from' => $term_info['term_start'],
            'to' => $term_info['term_end'],
          ];
        }
      }
    }
    foreach ($invalid_absence_periods as $invalid_absence_period) {
      $invalid_absence_key = $invalid_absence_period['from']->getTimestamp() . ':' . $invalid_absence_period['to']->getTimestamp();
      $batch['operations'][] = [[$this, 'fetchAttendanceReportNids'], [$invalid_absence_period['from'], $invalid_absence_period['to'], $invalid_absence_key]];
      $batch['operations'][] = [[$this, 'calculateInvalidAbsence'], [$student_ids, $invalid_absence_key]];
    }


    $grade_references = $this->gradeService->getGradeReferences($student_ids,  $this->getSyllabusIds());


    $batch['operations'][] = [[$this, 'parseGradeReferences'], [$grade_references]];

    // Process the grades in chunks of if 50 at the time.
    $grades_batch = [];
    $grades_batch_size = 50;
    foreach ($grade_references as $grade_reference) {
      $grades_batch[] = $grade_reference;
      $grades_batch_count = count($grades_batch);
      if ($grades_batch_count >= $grades_batch_size) {
        $batch['operations'][] = [[$this, 'processGrades'], [$grades_batch]];
        $grades_batch = [];
      }
    }
    if (!empty($grades_batch)) {
      $batch['operations'][] = [[$this, 'processGrades'], [$grades_batch]];
    }

    foreach ($student_ids as $student_id) {
      $batch['operations'][] = [[$this, 'sanityCheckStudent'], [$student_id]];
    }


    if ($use_grade_documents) {
      foreach ($student_ids as $student_id) {
        if ($use_final_grade_document && in_array($student_id, $values['final_grade_student_ids'])) {
          continue;
        }
        $batch['operations'][] = [[$this, 'makeGradeDocument'], [$student_id, $grade_document_invalid_absence_key]];
      }
    }


    if ($use_final_grade_document) {
      foreach ($values['final_grade_student_ids'] ?? [] as $student_id) {
        $batch['operations'][] = [[$this, 'makeFinalGradeDocument'], [$student_id]];
      }
    }

    if ($use_signature_documents) {
      foreach ($grade_references as $grade_reference) {
        $batch['operations'][] = [[$this, 'makeSignatureDocument'], []];
      }
    }

    if ($use_grade_catalog && !empty($catalog_term_indexes)) {
      if ($catalog_term_combine === 'combine') {
        $first_term_index = $catalog_term_indexes[0];
        $last_term_index = $catalog_term_indexes[count($catalog_term_indexes) - 1];

        $first_term_info = $this->termService->parseDefaultTermIndex($first_term_index);
        $last_term_info = $this->termService->parseDefaultTermIndex($last_term_index);

        $values['catalog_sub_indexes'][] = $first_term_info['term_start']->getTimestamp() . ':' . $last_term_info['term_end']->getTimestamp();

        // The catalog groups will be resolved later. Prepare for worst case,
        // that each student ends up in a separate catalog group.
        foreach ($student_ids as $student_id) {
          $batch['operations'][] = [[$this, 'makeGradeCatalog'], [$first_term_info['term_start'], $last_term_info['term_end']]];
        }
      }
      else {
        foreach ($catalog_term_indexes as $term_index) {
          $term_info = $this->termService->parseDefaultTermIndex($term_index);
          // The catalog groups will be resolved later. Prepare for worst case,
          // that each student ends up in a separate catalog group.

          $values['catalog_sub_indexes'][] = $term_info['term_start']->getTimestamp() . ':' . $term_info['term_end']->getTimestamp();

          foreach ($student_ids as $student_id) {
            $batch['operations'][] = [[$this, 'makeGradeCatalog'], [$term_info['term_start'], $term_info['term_end']]];
          }
        }
      }
    }

    // Update first context batch in case changes has been made.
    $batch['operations'][0] = [[$this, 'prepareContextData'], [$values]];
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

  public function prepareContextData(array $values, array &$context) {
    $context['results']['form_values'] = $values;
  }

  public function prepareWorkingPaths($templates_to_use, array &$context) {
    $destination_path = 'public://ssr_tmp' . DIRECTORY_SEPARATOR . $this->uuid->generate() . DIRECTORY_SEPARATOR;
    $destination_path = mb_strtolower($destination_path);
    $destination_real_path = $destination_path;
    $this->fileSystem->prepareDirectory($destination_path, FileSystemInterface::CREATE_DIRECTORY);
    $destination_real_path = $this->fileSystem->realpath($destination_real_path);
    $context['results']['destination'] = $destination_real_path;

    $final_destination_path = 'public://ssr_generated' . DIRECTORY_SEPARATOR . $this->uuid->generate() . DIRECTORY_SEPARATOR;
    $final_destination_path = mb_strtolower($final_destination_path);
    $final_destination_real_path = $final_destination_path;
    $this->fileSystem->prepareDirectory($final_destination_path, FileSystemInterface::CREATE_DIRECTORY);
    $final_destination_real_path = $this->fileSystem->realpath($final_destination_real_path);
    $context['results']['final_destination'] = $final_destination_real_path;
    $context['results']['final_destination_uri'] = $final_destination_path;

    foreach ($templates_to_use as $template_name) {
      $base_path = 'public://ssr_tmp' . DIRECTORY_SEPARATOR . $this->uuid->generate() . DIRECTORY_SEPARATOR . $template_name . DIRECTORY_SEPARATOR;
      $base_path = mb_strtolower($base_path);
      $working_path = $base_path;
      $this->fileSystem->prepareDirectory($base_path, FileSystemInterface::CREATE_DIRECTORY);
      $working_path = $this->fileSystem->realpath($working_path);
      $context['results'][$template_name . '_path'] = $working_path;
    }
  }

  public function prepareTemplates(array $templates_to_use, array &$context) {
    foreach ($templates_to_use as $template_name) {
      $template_to_use = $template_name;
      if ($template_name === 'final_grade_document') {
        $template_to_use = 'grade_document';
      }

      $template_file_path = $this->fileTemplateService->getFileTemplateRealPath($template_to_use);
      if (!$template_file_path) {
        continue;
      }

      $base_path = 'public://ssr_tmp' . DIRECTORY_SEPARATOR . $this->uuid->generate() . DIRECTORY_SEPARATOR . $template_name . DIRECTORY_SEPARATOR;
      $working_path = $base_path;
      $this->fileSystem->prepareDirectory($base_path, FileSystemInterface::CREATE_DIRECTORY);
      $working_path = $this->fileSystem->realpath($working_path);

      $zip = new \ZipArchive();
      $res = $zip->open($template_file_path);
      if ($res === TRUE) {
        $zip->extractTo($working_path);
        $zip->close();
      }

      $doc_logo = 'doc_logo_right';
      $doc_logo_name = 'image1.jpeg';
      if ($doc_logo_file_path = $this->fileTemplateService->getFileTemplateRealPath($doc_logo)) {
        $logo_path = $working_path . DIRECTORY_SEPARATOR . 'xl' . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . $doc_logo_name;
        $this->fileSystem->copy($doc_logo_file_path, $logo_path, FileExists::Replace);
      }

      $template_path = $context['results'][$template_name . '_path'];
      $context['results'][$template_name . '_path'] = $template_path . DIRECTORY_SEPARATOR . $template_name . '.xlsx';
      $this->fileTemplateService->doZip($working_path, $template_path, $template_name . '.xlsx', TRUE);
      $this->fileSystem->deleteRecursive($working_path);
    }
  }

  public function fetchAttendanceReportNids(\DateTime $from, \DateTime $to, string $invalid_absence_key, array &$context) {
    $attendance_report_nids = $this->entityTypeManager
      ->getStorage('node')
      ->getQuery()
      ->condition('type', 'course_attendance_report')
      ->condition('field_class_start', $to->getTimestamp(), '<')
      ->condition('field_class_end', $from->getTimestamp(), '>')
      ->accessCheck(FALSE)
      ->execute();
    $context['results']['attendance_report_nids'][$invalid_absence_key] = $attendance_report_nids;
  }

  public function calculateInvalidAbsence(array $student_ids, string $invalid_absence_key, array &$context) {
    $attendance_report_nids = $context['results']['attendance_report_nids'][$invalid_absence_key] ?? [];

    if (empty($attendance_report_nids) || empty($student_ids)) {
      return;
    }

    $context['results']['invalid_absence'][$invalid_absence_key] = [];

    $invalid_absence = [];
    foreach ($student_ids as $student_id) {
      $invalid_absence[$student_id] = 0;
    }

    /** @var \Drupal\Core\Database\Connection $connection */
    $connection = \Drupal::service('database');
    $query = $connection->select('paragraph__field_invalid_absence', 'ia');
    $query->innerJoin('paragraph__field_student', 's', 's.entity_id = ia.entity_id');
    $query->innerJoin('paragraphs_item_field_data', 'd', 'd.id = ia.entity_id');
    $query->condition('ia.bundle', 'student_course_attendance')
      ->condition('ia.field_invalid_absence_value', 0, '<>')
      ->condition('s.field_student_target_id', $student_ids, 'IN')
      ->condition('d.parent_id', $attendance_report_nids, 'IN')
      ->fields('ia',['field_invalid_absence_value'])
      ->fields('s',['field_student_target_id']);

    $results = $query->execute();
    foreach ($results as $result) {
      $student_id = $result->field_student_target_id;
      if (!isset($invalid_absence[$student_id])) {
        continue;
      }
      $invalid_absence[$student_id] += $result->field_invalid_absence_value;
    }

    foreach ($student_ids as $student_id) {
      $student_invalid_absence = (int) ($invalid_absence[$student_id] / 60);
      if ($student_invalid_absence > 0) {
        $context['results']['invalid_absence'][$invalid_absence_key][$student_id] = $student_invalid_absence;
      }
    }
  }

  public function parseGradeReferences(array $grade_references, array &$context) {
    $grades = $this->gradeService->parseGradesFromReferences($grade_references);

    $context['results']['student_grades_map'] = [];
    $context['results']['grade_info'] = [];
    foreach ($grades as $student_id => $grades_for_student) {
      /**
       * @var string|int $syllabus_id
       * @var \Drupal\simple_school_reports_grade_support\Utilities\GradeInfo $grade_info
       */
      foreach ($grades_for_student as $syllabus_id => $grade_info) {
        $context['results']['student_grades_map'][$student_id][$syllabus_id] = $grade_info->revisionId;
        $context['results']['grade_info'][$grade_info->revisionId] = $grade_info;
      }
    }
  }

  public function processGrades(array $grade_references_batch, array &$context) {
    $grade_ids = [];
    $grade_revision_ids = [];
    /** @var \Drupal\simple_school_reports_grade_support\Utilities\GradeReference $grade_reference */
    foreach ($grade_references_batch as $grade_reference) {
      $grade_ids[] = $grade_reference->id;
      $grade_revision_ids[] = $grade_reference->revisionId;
    }

    if (empty($grade_ids)) {
      return;
    }

    // Preload all grade entities in the batch.
    $this->entityTypeManager->getStorage('ssr_grade')->loadMultiple($grade_ids);

    $form_values = $context['results']['form_values'] ?? [];
    $final_grade_student_ids = $form_values['final_grade_student_ids'] ?? [];
    $use_signature_documents = $form_values['signature_documents'] ?? FALSE;

    $users_pre_load = [];

    $grade_revision_ids_in_signature_documents = [];
    if ($use_signature_documents) {
      $query = $this->database->select('ssr_grade_signing__grades', 'gsg');
      $query->innerJoin('ssr_grade_signing', 'gs', 'gs.id = gsg.entity_id');
      $query->condition('gsg.grades_target_revision_id', $grade_revision_ids, 'IN');
      $query->condition('gs.status', TRUE);
      $query->fields('gsg', ['grades_target_revision_id']);
      $results = $query->execute();
      foreach ($results as $result) {
        $grade_revision_ids_in_signature_documents[] = $result->grades_target_revision_id;
      }
    }


    foreach ($grade_revision_ids as $grade_revision_id) {
      /** @var \Drupal\simple_school_reports_grade_support\Utilities\GradeInfo|null $grade_info */
      $grade_info = $context['results']['grade_info'][$grade_revision_id] ?? NULL;
      if (!$grade_info) {
        continue;
      }


      $student_id = $grade_info->student;
      $main_grader = $grade_info->mainGrader;
      if ($student_id) {
        $users_pre_load[$student_id] = $student_id;
      }
      if ($main_grader) {
        $users_pre_load[$main_grader] = $main_grader;
      }

      if (in_array($student_id, $final_grade_student_ids) && !$grade_info->replaced) {
        /** @var \Drupal\simple_school_reports_grade_support\GradeInterface|null $grade_entity */
        $grade_entity = $this->entityTypeManager->getStorage('ssr_grade')->load($grade_info->id);
        if ($grade_entity && !$grade_entity->get('final_grade')->value) {
          try {
            $grade_entity->setSyncing(TRUE);
            $grade_entity->set('final_grade', TRUE);
            $grade_entity->save();
          }
          catch (\Exception $e) {
            \Drupal::logger('ssr_grade_export')->error($e->getMessage());
          }
        }
      }


      if (
        $use_signature_documents
        && !in_array($grade_revision_id, $grade_revision_ids_in_signature_documents)
      ) {
        $grade_signing_group_key = $this->getSignatureDocumentKey($grade_info, $context['results']['form_values']['principal'] ?? '');
        $context['results']['grade_signing_groups'][$grade_signing_group_key][] = $grade_revision_id;
      }
    }

    if (!empty($users_pre_load)) {
      $this->entityTypeManager->getStorage('user')->loadMultiple(array_values($users_pre_load));
    }
  }

  protected function getSignatureDocumentKey(GradeInfo $grade_info, string $principal_id): string {
    $key_parts = [];
    $key_parts[] = $grade_info->courseId ?? '';
    $key_parts[] = $grade_info->syllabusId ?? '';
    $has_grade = $this->gradeService->hasGrade($grade_info);
    if ($has_grade && $grade_info->mainGrader) {
      $key_parts[] = $grade_info->mainGrader;
    }
    else {
      $key_parts[] = $principal_id;
    }
    return implode(':', $key_parts);
  }

  protected function parseSignatureDocumentKey(string $key): array {
    $parts = explode(':', $key);
    $course_id = $parts[0] ?? '';
    $syllabus_id = $parts[1] ?? '';
    $grader_id = $parts[2] ?? '';
    return [$course_id, $syllabus_id, $grader_id];
  }

  public function sanityCheckStudent(string|int $student_id, array &$context) {
    /** @var \Drupal\user\UserInterface $student */
    $student = $this->entityTypeManager->getStorage('user')->load($student_id);
    if (!$student || !$student->hasRole('student')) {
      return;
    }

    $context['results']['is_student'][$student_id] = TRUE;

    $ssn = 'ååmmdd-nnnn';
    $has_valid_ssn = FALSE;

    if (!$student->get('field_birth_date_source')->isEmpty()) {
      if ($student->get('field_birth_date_source')->value === 'ssn') {
        $student_ssn = $student->get('field_ssn')->value;
        if ($student_ssn) {
          $student_ssn = $this->pnum->normalizeIfValid($student_ssn, FALSE);
          if ($student_ssn) {
            $ssn = $student_ssn;
            $has_valid_ssn = TRUE;
          }
        }
      }
      else {
        $birth_date = $student->get('field_birth_date')->value;
        if ($birth_date) {
          $date = new \DateTime();
          $date->setTimestamp($birth_date);

          $ssn = $date->format('ymd') . '-nnnn';
        }
      }
    }

    $has_grades = !empty($context['results']['student_grades_map'][$student_id]);

    if (!$has_valid_ssn && $has_grades) {
      $name = '';
      _simple_school_reports_core_resolve_name($name, $student, TRUE);
      $this->messenger()->addWarning(t('@name misses a valid personal number, document is generated anyway with value @value', ['@name' => $name, '@value' => $ssn]));
    }

    $group = NULL;
    $group_type = 'none';

    $grade_options = SchoolGradeHelper::getSchoolGradesShortName();
    $student_grade = $student->get('field_grade')->value;
    if ($student_grade && !empty($grade_options[$student_grade])) {
      $group = $grade_options[$student_grade];
      $group_type = 'grade';
    }

    $use_classes = $this->moduleHandler->moduleExists('simple_school_reports_class');
    if ($use_classes) {
      $class = $student->get('field_class')->entity;
      if ($class) {
        $group = $class->label();
        $group_type = 'class';
      }
    }

    $context['results']['student_ssn'][$student->id()] = $ssn;
    $context['results']['group_type'][$student->id()] = $group_type;

    $group = $group ?? 'Okänd årskurs';
    $context['results']['group'][$student->id()] = $group;

    $group_index = sha1($group);
    foreach ($context['results']['form_values']['catalog_sub_indexes'] ?? [] as $catalog_sub_index) {
      $context['results']['grade_catalog_groups'][$catalog_sub_index][$group_index]['name'] = $group;
      $context['results']['grade_catalog_groups'][$catalog_sub_index][$group_index]['student_ids'][] = $student->id();
    }
  }

  protected function prepareGradeSpreadsheet(
    string $template_path,
    string $type,
    UserInterface $student,
    int $grade_rows,
    array $info_items,
    string $label,
    string $sublabel,
    string $document_date,
    ?string $invalid_absence_key,
    ?string $sign_label,
    ?string $sign_name,
    array &$context
  ): ?Spreadsheet {

    $file_type = 'Xlsx';
    $reader = IOFactory::createReader($file_type);
    $reader->setLoadAllSheets();
    $spreadsheet = $reader->load($template_path);

    $excel_sheet = $spreadsheet->getSheet(0);

    $excel_sheet->setCellValue('A1', $label);
    $excel_sheet->setCellValue('A2', $sublabel);
    $excel_sheet->setCellValue('A4', $document_date);

    $context['ssr_document_context']['document_label'] = $label;

    $school_unit = $this->organizationsService->getOrganization('school_unit', $this->getSchoolType());
    $excel_sheet->setCellValue('A7', $school_unit?->label() ?? '-');
    $school_unit_code = $school_unit?->get('school_unit_code')->value ?? '';

    $excel_sheet->setCellValue('G7', $school_unit_code);

    $school_organization = $this->organizationsService->getOrganization('school_organization', $this->getSchoolType());
    $excel_sheet->setCellValue('A8', $school_organization?->label() ?? '-');

    $student_last_name = $student->get('field_last_name')->value ?? '-';
    $student_first_name = $student->get('field_first_name')->value ?? '-';

    $excel_sheet->setCellValue('A11', $student_last_name . ', ' . $student_first_name);

    $ssn = $context['results']['student_ssn'][$student->id()] ?? '';
    $excel_sheet->setCellValue('G11', $ssn);

    $subject_row_first = 21;
    $notes_row = 26;
    $invalid_absence_row = 28;
    $codes_info_row = 31;

    $subject_row_to_copy = 22;
    // Prepare subject rows.
    $rows_to_copy = $grade_rows - 3;
    if ($rows_to_copy < 0) {
      $rows_to_copy = 0;
    }
    if ($rows_to_copy > 0) {
      $excel_sheet->insertNewRowBefore($subject_row_to_copy, $rows_to_copy);
      $notes_row += $rows_to_copy;
      $invalid_absence_row += $rows_to_copy;
      $codes_info_row += $rows_to_copy;
    }

    for ($row_to_clear = $subject_row_first; $row_to_clear <= $subject_row_first + 3 + $rows_to_copy; $row_to_clear++) {
      $cols_to_check = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
      foreach ($cols_to_check as $col_to_check) {
        if (!empty($excel_sheet->getCell($col_to_check . $row_to_clear)->getValueString())) {
          $excel_sheet->setCellValue($col_to_check . $row_to_clear, '');
        }
      }
    }

    $info_item_sequence = ['A13:A14', 'A15:A16', 'A17:A18', 'G13:G14', 'G15:G16', 'G17:G18', ];
    if (count($info_items) <= 4) {
      $info_item_sequence = ['A13:A14', 'A17:A18', 'G13:G14', 'G17:G18'];
    }

    foreach ($info_item_sequence as $index => $info_item_sequence_item) {
      [$label_cell_id, $value_cell_id] = explode(':', $info_item_sequence_item);
      $info_item = $info_items[$index] ?? [];
      $excel_sheet->setCellValue($label_cell_id, $info_item['label'] ?? '');
      $excel_sheet->setCellValue($value_cell_id, $info_item['value'] ?? '');
    }

    if (count($info_items) <= 4) {
      // Remove row 15 + 16.
      $excel_sheet->removeRow(15, 2);

      $subject_row_first -= 2;
      $notes_row -= 2;
      $invalid_absence_row -= 2;
      $codes_info_row -= 2;
    }

    $context['ssr_document_context']['label_row'] = $subject_row_first - 1;
    $context['ssr_document_context']['item_row_first'] = $subject_row_first;
    $context['ssr_document_context']['notes_row'] = $notes_row;
    $context['ssr_document_context']['invalid_absence_row'] = $invalid_absence_row;
    $context['ssr_document_context']['codes_info_row'] = $codes_info_row;

    $context['ssr_document_context']['cols'] = [
      'course' => 'A',
      'course_code' => 'B',
      'course_extra' => 'C',
      'points' => 'D',
      'grade' => 'E',
      'codes' => 'F',
      'remark' => 'G',
    ];

    $context['ssr_document_context']['labels'] = [
      'course' => 'Kurs',
      'course_code' => 'Kurskod',
      'course_extra' => '',
      'points' => 'Poäng',
      'grade' => 'Betyg',
      'codes' => 'Markering',
      'remark' => 'Övriga upplysningar',
    ];

    $configuration = $this->getConfiguration();
    $codes_info = $configuration['codes_info_prefix'] ?? '';
    $codes = $configuration['codes'] ?? [];
    foreach ($codes as $code => $label) {
      if (!empty($codes_info)) {
        $codes_info .= ' ';
      }
      $codes_info .= $label . ' - ' . $code . '.';
    }
    $excel_sheet->setCellValue('A' . $codes_info_row, $codes_info);

    if (empty($invalid_absence_key)) {
      $excel_sheet->removeRow($invalid_absence_row - 1, 2);
    }
    else {
      $invalid_absence = $context['results']['invalid_absence'][$invalid_absence_key][$student->id()] ?? 0;
      $excel_sheet->setCellValue('A' . $invalid_absence_row, $invalid_absence . ' h');
    }

    // Setup signer.
    $footer = $excel_sheet->getHeaderFooter()->getOddFooter();
    if (!$sign_label && !$sign_name) {
      $footer = '';
    }
    else {
      $footer = str_replace(['Rektor', '[SignLabel]'], $sign_label ?? '', $footer);
      $footer = str_replace(['Namn', '[Signer]'], $sign_name ?? '', $footer);
    }
    $excel_sheet->getHeaderFooter()->setOddFooter($footer);

    return $spreadsheet;
  }

  protected function populateGradeSpreadsheet(
    Spreadsheet $spreadsheet,
    UserInterface $user,
    array $grade_info_items,
    string $type,
    array &$context,
  ): void {
    $document_context = $context['ssr_document_context'];

    $label_row = $document_context['label_row'];
    $item_row = $document_context['item_row_first'];
    $notes_row = $document_context['notes_row'] ?? null;
    $cols = $document_context['cols'];

    $skip_replaced = !empty($document_context['skip_replaced']);

    $notes_items = [];

    $configuration = $this->getConfiguration();

    $excel_sheet = $spreadsheet->getSheet(0);

    $cols_in_use = [];
    foreach ($grade_info_items as $grade_info_id) {
      /** @var \Drupal\simple_school_reports_grade_support\Utilities\GradeInfo|null $grade_info */
      $grade_info = $context['results']['grade_info'][$grade_info_id] ?? NULL;
      if (!$grade_info) {
        continue;
      }

      if ($grade_info->replaced && $skip_replaced) {
        continue;
      }
      $syllabus_id = $grade_info->syllabusId;

      $syllabus_info = $this->syllabusService->getSyllabusInfo($syllabus_id);

      $diploma_project_label = NULL;

      if ($this->syllabusService->useDiplomaProject($syllabus_id)) {
        $grade_entity = $this->entityTypeManager->getStorage('ssr_grade')->loadRevision($grade_info->revisionId);
        $diploma_project_label = $grade_entity?->get('diploma_project_label')->value ?? NULL;
        $diploma_project_description = $grade_entity?->get('diploma_project_description')->value ?? NULL;

        if ($diploma_project_description) {
          $diploma_project_description = $syllabus_info['subject_code'] . ': ' . $diploma_project_description;
          if (!str_ends_with($diploma_project_description, '.')) {
            $diploma_project_description .= '.';
          }
          $notes_items[] = $diploma_project_description;
        }
      }

      foreach ($cols as $key => $col) {
        $cell_id = $col . $item_row;

        if ($key === 'course') {
          $cols_in_use[$col] = $col;
          $excel_sheet->setCellValue($cell_id, $syllabus_info['label'] ?? '');
        }
        if ($key === 'student') {
          $cols_in_use[$col] = $col;
          if ($grade_info->student) {
            $student = $this->entityTypeManager->getStorage('user')->load($grade_info->student);
            if ($student) {
              $student_last_name = $student->get('field_last_name')->value ?? '-';
              $student_first_name = $student->get('field_first_name')->value ?? '-';
              $excel_sheet->setCellValue($cell_id, $student_first_name . ' ' . $student_last_name);
            }
          }
        }
        if ($key === 'student_ssn') {
          $cols_in_use[$col] = $col;
          $student_id = $grade_info->student;
          if ($student_id) {
            $ssn = $context['results']['student_ssn'][$student_id] ?? '';
            $excel_sheet->setCellValue($cell_id, $ssn);
          }
        }
        if ($key === 'course_code') {
          $idendifier = $syllabus_info['identifier'] ?? '';
          $course_code = ActivateSyllabusFormBase::parseSyllabusIdentifier($idendifier)['course_code'] ?? '';
          if (!empty($course_code)) {
            $cols_in_use[$col] = $col;
          }
          $excel_sheet->setCellValue($cell_id, $syllabus_info['course_code'] ?? '');
        }
        if ($key === 'course_extra') {
          $cols_in_use[$col] = $col;
          $excel_sheet->setCellValue($cell_id, '');
        }
        if ($key === 'levels') {
          $cols_in_use[$col] = $col;
          $levels_numerical = $syllabus_info['previous_levels_numerical'] ?? [];
          if (!empty($previous_levels_numerical)) {
            if (!empty($syllabus_info['level_numerical'])) {
              $levels_numerical[] = $syllabus_info['level_numerical'];
            }
          }
          $excel_sheet->setCellValue($cell_id, implode(',', $levels_numerical));
        }
        if ($key === 'points') {
          $points = $syllabus_info['points'] ?? '';
          if ($skip_replaced) {
            $points = $syllabus_info['aggregated_points'] ?? '';
          }

          if (!empty($points)) {
            $points .= 'p';
            $cols_in_use[$col] = $col;
          }
          $excel_sheet->setCellValue($cell_id, $points);
        }
        if ($key === 'grade') {
          $cols_in_use[$col] = $col;
          $grade_label = $this->gradeService->getGradeLabel($grade_info, $configuration['grade_document_exclude_reason_map']);
          $excel_sheet->setCellValue($cell_id, $grade_label);
        }
        if ($key === 'codes') {
          $cols_in_use[$col] = $col;
          $codes = $this->gradeService->getCodes($grade_info);
          $excel_sheet->setCellValue($cell_id, implode(',', $codes));
        }
        if ($key === 'remark') {
          $cols_in_use[$col] = $col;
          $remark_items = [];
          if ($grade_info->remark) {
            $remark_items[] = $grade_info->remark;
          }
          if ($syllabus_info['language_code']) {
            $remark_items[] = $syllabus_info['language_code'];
          }

          if ($diploma_project_label) {
            $remark_items[] = $diploma_project_label;
          }

          $excel_sheet->setCellValue($cell_id, implode(', ', $remark_items));
        }

        if ($key === 'joint_graders_suffixed') {
          $cols_in_use[$col] = $col;
          $user_storage = $this->entityTypeManager->getStorage('user');

          // Preload all joint graders.
          if (!empty($grade_info->jointGraders)) {
            $user_storage->loadMultiple($grade_info->jointGraders);
          }
          if (!empty($grade_info->jointGraders)) {
            $cols_in_use[$col] = $col;
            $value = $excel_sheet->getCell($cell_id)->getValueString() ?? '';
            $joint_grader_names_short = [];
            foreach ($grade_info->jointGraders as $joint_grader_id) {
              $joint_grader = $user_storage->load($joint_grader_id);
              if ($joint_grader) {
                $joint_grader_names_short[$joint_grader_id] = $joint_grader->get('field_first_name')->value ?? '?';
                // Use only first letter of last name.
                $joint_grader_names_short[$joint_grader_id] .= ' ' . mb_substr($joint_grader->get('field_last_name')->value ?? '?', 0, 1);
              }
              else {
                $joint_grader_names_short[$joint_grader_id] = 'Okänd (' . $joint_grader_id . ')';
              }
            }
            if (!empty($joint_grader_names_short)) {
              $excel_sheet->setCellValue($cell_id, $value . ' ' . implode(', ', $joint_grader_names_short));
            }
          }
        }
      }

      // Auto adjust row height.
      $excel_sheet->getRowDimension($item_row)->setRowHeight(-1);
      $item_row++;
    }

    // Set labels.
    foreach ($cols as $key => $col) {
      $col_label = $document_context['labels'][$key] ?? '';
      if (!isset($cols_in_use[$col])) {
        $col_label = '';
      }
      $excel_sheet->setCellValue($col . $label_row, $col_label);
    }

    if ($notes_row && !empty($notes_items)) {
      $excel_sheet->setCellValue('A' . $notes_row, implode(' ', $notes_items));
    }
  }

  protected function populateGradeCatalogSpreadsheet(
    Spreadsheet $spreadsheet,
    array $grades,
    string $type,
    string $invalid_absence_key,
    array &$context,
  ): void {
    $document_context = $context['ssr_document_context'];

    $label_row = $document_context['label_row'];
    $item_row = $document_context['item_row_first'];
    $item_col = $document_context['item_col_first'];
    $notes_row = $document_context['notes_row'] ?? null;
    $cols = $document_context['cols'];


    $notes_items = [];

    $configuration = $this->getConfiguration();

    $excel_sheet = $spreadsheet->getSheet(0);

    $cols_in_use = [];

    foreach ($grades as $student_id => $student_grades) {
      $student = $this->entityTypeManager->getStorage('user')->load($student_id);
      foreach ($cols as $key => $col) {
        $cell_id = $col . $item_row;

        if ($key === 'student') {
          $cols_in_use[$col] = $col;
          if ($student) {
            $student_last_name = $student->get('field_last_name')->value ?? '-';
            $student_first_name = $student->get('field_first_name')->value ?? '-';
            $excel_sheet->setCellValue($cell_id, $student_first_name . ' ' . $student_last_name);
          }
          continue;
        }

        if ($key === 'student_ssn') {
          $cols_in_use[$col] = $col;
          $ssn = $context['results']['student_ssn'][$student_id] ?? '';
          $excel_sheet->setCellValue($cell_id, $ssn);
          continue;
        }

        if ($key === 'invalid_absence' && !empty($invalid_absence_key)) {
          $cols_in_use[$col] = $col;
          $value = $context['results']['invalid_absence'][$invalid_absence_key][$student_id] ?? '';
          if ($value > 0) {
            $value .= 'h';
          }
          $excel_sheet->setCellValue($cell_id, $value);
          continue;
        }

        if (is_numeric($key)) {
          /** @var \Drupal\simple_school_reports_grade_support\Utilities\GradeInfo|null $grade_info */
          $grade_info = $student_grades[$key] ?? NULL;
          if ($grade_info) {
            $grade_label = $this->gradeService->getGradeLabel($grade_info, $configuration['grade_catalog_exclude_reason_map']);
            $excel_sheet->setCellValue($cell_id, $grade_label);
          }
          continue;
        }

        if (str_ends_with($key, '_language_code')) {
          $syllabus_id = substr($key, 0, -strlen('_language_code'));
          $syllabus_info = $this->syllabusService->getSyllabusInfo($syllabus_id);
          $language_code = $syllabus_info['language_code'] ?? '';
          $excel_sheet->setCellValue($cell_id, $language_code);
          continue;
        }
      }

      $item_row++;
    }

    // Set labels.
    foreach ($cols as $key => $col) {
      $col_label = $document_context['labels'][$key] ?? NULL;
      if (!isset($cols_in_use[$col])) {
        $col_label = NULL;
      }
      if ($col_label) {
        $excel_sheet->setCellValue($col . $label_row, $col_label);
      }
    }
  }

  protected function saveSpreadsheet(
    Spreadsheet $spreadsheet,
    UserInterface $user,
    string $type,
    array &$context,
  ): void {
    $spreadsheet->setActiveSheetIndex(0);
    $excel_sheet = $spreadsheet->getSheet(0);
    $excel_sheet->setSelectedCell('A1');

    $file_type = 'Xlsx';
    $writer = IOFactory::createWriter($spreadsheet, $file_type);

    $sub_dir = $context['ssr_document_context']['sub_dir'] ?? NULL;

    $file_name = $context['results']['destination'] . DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR;
    if ($sub_dir) {
      $file_name .= $sub_dir . DIRECTORY_SEPARATOR;
    }
    if (!empty($context['ssr_document_context']['file_name'])) {
      $file_name = $context['results']['destination'] . DIRECTORY_SEPARATOR . $context['ssr_document_context']['file_name'];
    }
    else {
      $file_name .= $type;
      $file_name .= '_' . ($user->get('field_first_name')->value ?? '') . '_' . ($user->get('field_last_name')->value ?? '');
      $file_name .= '_' . $user->id() . '.xlsx';
    }

    $file_name = $this->fileTemplateService->sanitizeFileName($file_name);
    $writer->save($file_name);

    $context['results']['generated_documents'][] = $file_name;
    $context['ssr_document_context'] = [];
  }

  protected function doMakeGradeDocument(string $type, string|int $student_id, ?string $invalid_absence_key, array &$context) {
    /** @var \Drupal\user\UserInterface $student */
    $student = $this->entityTypeManager->getStorage('user')->load($student_id);
    if (!$student) {
      return;
    }

    $is_student = $context['results']['is_student'][$student_id] ?? FALSE;
    if (!$is_student) {
      return;
    }

    if (!empty($context['ssr_document_context']['skip_replaced'])) {
      $number_of_grades = 0;
      foreach ($context['results']['student_grades_map'][$student_id] as $grade_info_id) {
        /** @var \Drupal\simple_school_reports_grade_support\Utilities\GradeInfo|null $grade_info */
        $grade_info = $context['results']['grade_info'][$grade_info_id] ?? NULL;
        if (!$grade_info || $grade_info->replaced) {
          continue;
        }
        $number_of_grades++;
      }
    }
    else {
      $number_of_grades = count($context['results']['student_grades_map'][$student_id] ?? []);
    }

    if ($number_of_grades === 0) {
      return;
    }

    $student_grade_value = $student->get('field_grade')->value;

    $info_items = [];
    $use_classes = $this->moduleHandler->moduleExists('simple_school_reports_class');
    if ($use_classes) {
      $class = $student->get('field_class')->entity;
      if ($class) {
        $info_items[] = [
          'label' => 'Klass',
          'value' => $class->label(),
        ];
      }
    }
    $info_items[] = [
      'label' => 'Årskurs',
      'value' => $student_grade_value ? SchoolGradeHelper::parseGradeValueToActualGrade($student_grade_value) : '-',
    ];

    $sign_label = 'Rektor';
    $sign_name = '';
    $sign_uid = $context['results']['form_values']['principal'] ?? NULL;

    if ($type === 'grade_document') {
      $mentor = $student->get('field_mentor')->entity;
      if ($mentor) {
        $sign_label = 'Mentor';
        $sign_uid = $mentor->id();
      }
    }

    if ($sign_uid) {
      /** @var \Drupal\user\UserInterface|null $user */
      $user = $this->entityTypeManager->getStorage('user')->load($sign_uid);
      $sign_name = $user?->getDisplayName();
    }

    $spreadsheet = $this->prepareGradeSpreadsheet(
      $context['results'][$type . '_path'],
      $type,
      $student,
      $number_of_grades,
      $info_items,
      'Betyg',
      '',
      $context['results']['form_values']['document_date'],
      $invalid_absence_key,
      $sign_label,
      $sign_name,
      $context,
    );
    if (!$spreadsheet) {
      return;
    }

    $save_type = $spreadsheet->getSheet(0)->getCell('A1')->getValueString() ?? 'betyg';
    $save_type = mb_strtolower($save_type);

    $grade_info_items = $context['results']['student_grades_map'][$student->id()] ?? [];
    $this->populateGradeSpreadsheet($spreadsheet, $student, $grade_info_items, $type, $context);
    $student_group = $context['results']['group'][$student->id()] ?? 'Okänd årskurs';
    $context['ssr_document_context']['sub_dir'] = mb_strtolower($student_group);
    $this->saveSpreadsheet($spreadsheet, $student, $save_type, $context);
  }

  protected function prepareGradeSigningSpreadsheet(
    string $template_path,
    string $type,
    GradeSigningInterface $grade_signing,
    UserInterface $grader,
    int $grade_rows,
    array $info_items,
    string $label,
    string $sublabel,
    string $document_date,
    ?string $sign_label,
    ?string $sign_name,
    array &$context
  ): ?Spreadsheet {

    $file_type = 'Xlsx';
    $reader = IOFactory::createReader($file_type);
    $reader->setLoadAllSheets();
    $spreadsheet = $reader->load($template_path);

    $excel_sheet = $spreadsheet->getSheet(0);

    $excel_sheet->setCellValue('A1', $label);
    $excel_sheet->setCellValue('A2', $sublabel);
    $excel_sheet->setCellValue('A4', $document_date);

    $context['ssr_document_context']['document_label'] = $label;

    $school_unit = $this->organizationsService->getOrganization('school_unit', $this->getSchoolType());
    $excel_sheet->setCellValue('A7', $school_unit?->label() ?? '-');
    $school_unit_code = $school_unit?->get('school_unit_code')->value ?? '';

    $excel_sheet->setCellValue('F7', $school_unit_code);

    $school_organization = $this->organizationsService->getOrganization('school_organization', $this->getSchoolType());
    $excel_sheet->setCellValue('A8', $school_organization?->label() ?? '-');

    $grader_last_name = $grader->get('field_last_name')->value ?? '-';
    $grader_first_name = $grader->get('field_first_name')->value ?? '-';

    $excel_sheet->setCellValue('A11', $grader_last_name . ', ' . $grader_first_name);
    $excel_sheet->setCellValue('F11', $grade_signing->getDocumentId());

    $student_row_first = 17;
    $student_row_to_copy = 18;

    // Prepare subject rows.
    $rows_to_copy = $grade_rows - 3;
    if ($rows_to_copy < 0) {
      $rows_to_copy = 0;
    }
    if ($rows_to_copy > 0) {
      $excel_sheet->insertNewRowBefore($student_row_to_copy, $rows_to_copy);
    }

    for ($row_to_clear = $student_row_first; $row_to_clear <= $student_row_first + 3 + $rows_to_copy; $row_to_clear++) {
      $cols_to_check = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
      foreach ($cols_to_check as $col_to_check) {
        if (!empty($excel_sheet->getCell($col_to_check . $row_to_clear)->getValueString())) {
          $excel_sheet->setCellValue($col_to_check . $row_to_clear, '');
        }
      }
    }

    $info_item_sequence = ['A13:A14', 'F13:F14'];
    foreach ($info_item_sequence as $index => $info_item_sequence_item) {
      [$label_cell_id, $value_cell_id] = explode(':', $info_item_sequence_item);
      $info_item = $info_items[$index] ?? [];
      $excel_sheet->setCellValue($label_cell_id, $info_item['label'] ?? '');
      $excel_sheet->setCellValue($value_cell_id, $info_item['value'] ?? '');
    }

    if (count($info_items) === 0) {
      // Remove row 10 + 11.
      $excel_sheet->removeRow(10, 2);

      $student_row_first -= 2;
    }

    $context['ssr_document_context']['label_row'] = $student_row_first - 1;
    $context['ssr_document_context']['item_row_first'] = $student_row_first;

    $context['ssr_document_context']['cols'] = [
      'student' => 'A',
      'student_ssn' => 'B',
      'not_in_use' => 'C',
      'grade' => 'D',
      'codes' => 'E',
      'remark' => 'F',
      'joint_graders_suffixed' => 'F',
    ];

    $context['ssr_document_context']['labels'] = [
      'student' => 'Namn',
      'student_ssn' => 'Personnummer',
      'grade' => 'Betyg',
      'codes' => 'Markering',
      'remark' => 'Övriga upplysningar',
      'joint_graders_suffixed' => 'Övriga upplysningar',
    ];

    // Setup signer.
    $footer = $excel_sheet->getHeaderFooter()->getOddFooter();
    if (!$sign_label && !$sign_name) {
      $footer = '';
    }
    else {
      $footer = str_replace(['Lärare', '[SignLabel]'], $sign_label ?? '', $footer);
      $footer = str_replace(['Namn', '[Signer]'], $sign_name ?? '', $footer);
    }
    $excel_sheet->getHeaderFooter()->setOddFooter($footer);

    return $spreadsheet;
  }

  protected function prepareGradeCatalogSpreadsheet(
    string $template_path,
    string $type,
    array $syllabus_ids,
    int $number_of_students,
    array $info_items,
    string $label,
    string $sublabel,
    string $document_date,
    ?string $sign_label,
    ?string $sign_name,
    string $invalid_absence_key,
    array &$context
  ): ?Spreadsheet {

    $file_type = 'Xlsx';
    $reader = IOFactory::createReader($file_type);
    $reader->setLoadAllSheets();
    $spreadsheet = $reader->load($template_path);

    $excel_sheet = $spreadsheet->getSheet(0);

    $excel_sheet->setCellValue('A1', $label);
    $excel_sheet->setCellValue('A2', $sublabel);
    $excel_sheet->setCellValue('A4', $document_date);

    $context['ssr_document_context']['document_label'] = $label;

    $school_unit = $this->organizationsService->getOrganization('school_unit', $this->getSchoolType());
    $excel_sheet->setCellValue('B4', $school_unit?->label() ?? '-');
    $school_unit_code = $school_unit?->get('school_unit_code')->value ?? '';

    $excel_sheet->setCellValue('F2', $school_unit_code);

    $school_organization = $this->organizationsService->getOrganization('school_organization', $this->getSchoolType());
    $excel_sheet->setCellValue('A8', $school_organization?->label() ?? '-');


    $student_row_first = 8;
    $student_row_to_copy = 9;
    $subject_col_first = 3;
    $subject_col_to_copy = 18;
    $notes_row = 13;

    // Prepare subject rows.
    $rows_to_copy = $number_of_students - 3;
    if ($rows_to_copy < 0) {
      $rows_to_copy = 0;
    }
    if ($rows_to_copy > 0) {
      $excel_sheet->insertNewRowBefore($student_row_to_copy, $rows_to_copy);
    }
    $notes_row += $rows_to_copy;

    for ($row_to_clear = $student_row_first; $row_to_clear <= $student_row_first + 3 + $rows_to_copy; $row_to_clear++) {
      $cols_to_check = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S'];
      foreach ($cols_to_check as $col_to_check) {
        if (!empty($excel_sheet->getCell($col_to_check . $row_to_clear)->getValueString())) {
          $excel_sheet->setCellValue($col_to_check . $row_to_clear, '');
        }
      }
    }

    $info_item_sequence = ['B1:B2'];
    foreach ($info_item_sequence as $index => $info_item_sequence_item) {
      [$label_cell_id, $value_cell_id] = explode(':', $info_item_sequence_item);
      $info_item = $info_items[$index] ?? [];
      $excel_sheet->setCellValue($label_cell_id, $info_item['label'] ?? '');
      $excel_sheet->setCellValue($value_cell_id, $info_item['value'] ?? '');
    }

    $needed_cols = 0;
    foreach ($syllabus_ids as $syllabus_id) {
      $needed_cols++;
      $syllabus_info = $this->syllabusService->getSyllabusInfo($syllabus_id);
      if (!empty($syllabus_info['language_code'])) {
        $needed_cols++;
      }
    }

    // Add columns.
    $cols_to_add = $needed_cols - 16;
    if (!empty($invalid_absence_key)) {
      $cols_to_add++;
    }
    if ($cols_to_add < 0) {
      $cols_to_add = 0;
    }

    if ($cols_to_add > 0) {
      $excel_sheet->insertNewColumnBeforeByIndex($subject_col_to_copy, $cols_to_add);
    }

    $context['ssr_document_context']['cols'] = [
      'student' => 'A',
      'student_ssn' => 'B',
    ];

    $context['ssr_document_context']['labels'] = [
      'student' => 'Namn',
      'student_ssn' => 'Personnummer',
    ];

    $label_row = $student_row_first - 1;

    // Clear all subject labels and adjust column widths.
    $col_width = 10;
    for ($col_to_clear = $subject_col_first; $col_to_clear <= $subject_col_first + 16 + $cols_to_add; $col_to_clear++) {
      $col_string = Coordinate::stringFromColumnIndex($col_to_clear);
      $cell_id = $col_string . $label_row;
      if ($col_to_clear === $subject_col_first) {
        $col_width = $excel_sheet->getColumnDimension($col_string)->getWidth();
      }
      else {
        $excel_sheet->getColumnDimension($col_string)->setWidth($col_width);
      }
      if (!empty($excel_sheet->getCell($cell_id)->getValueString())) {
        $excel_sheet->setCellValue($cell_id, '');
      }
    }

    // Populate subject labels.
    $col_index = $subject_col_first;
    foreach ($syllabus_ids as $syllabus_id) {
      $col = Coordinate::stringFromColumnIndex($col_index);

      $label_cell_id = $col . $label_row;

      $syllabus_info = $this->syllabusService->getSyllabusInfo($syllabus_id);
      $label = $syllabus_info['label'] ?? '-';
      $excel_sheet->setCellValue($label_cell_id, $label);

      $context['ssr_document_context']['cols'][$syllabus_id] = $col;

      if (!empty($syllabus_info['language_code'])) {
        $col_index++;
        $col = Coordinate::stringFromColumnIndex($col_index);
        $language_code_cell_id = $col . $label_row;

        $excel_sheet->mergeCells($language_code_cell_id . ':' . $label_cell_id);
        $context['ssr_document_context']['cols'][$syllabus_id . '_language_code'] = $col;
      }

      $col_index++;
    }

    // Populate invalid absence labels.
    if (!empty($invalid_absence_key)) {
      $col = Coordinate::stringFromColumnIndex($col_index);
      $context['ssr_document_context']['cols']['invalid_absence'] = $col;
      $context['ssr_document_context']['labels']['invalid_absence'] = 'Ogiltig frånvaro';
    }

    $context['ssr_document_context']['label_row'] = $label_row;
    $context['ssr_document_context']['item_row_first'] = $student_row_first;
    $context['ssr_document_context']['item_col_first'] = $student_row_first;
    $context['ssr_document_context']['notes_row'] = $notes_row;

    // Setup signer.
    $excel_sheet->setCellValue('F3', $sign_label);
    $excel_sheet->setCellValue('F4', $sign_name);

    $notes = $this->getConfiguration()['grade_catalog_info'] ?? '';
    $excel_sheet->setCellValue('A' . $notes_row, $notes);

    return $spreadsheet;
  }

  public function makeGradeDocument(string|int $student_id, ?string $invalid_absence_key, array &$context) {
    $type = 'grade_document';
    $this->doMakeGradeDocument($type, $student_id, $invalid_absence_key, $context);
  }

  public function makeFinalGradeDocument(string|int $student_id, array &$context) {
    $type = 'final_grade_document';
    $this->doMakeGradeDocument($type, $student_id, '', $context);
  }

  public function makeSignatureDocument(array &$context) {
    if (empty($context['results']['grade_signing_groups'])) {
      return;
    }
    $signing_group_key = array_key_first($context['results']['grade_signing_groups']);
    $grade_revision_ids = $context['results']['grade_signing_groups'][$signing_group_key];
    unset($context['results']['grade_signing_groups'][$signing_group_key]);

    $group_label = 'Kurs';
    $group_value = '-';
    $course_code = NULL;
    $file_name = NULL;

    try {
      [$course_id, $syllabus_id, $grader_id] = $this->parseSignatureDocumentKey($signing_group_key);
      $main_entity = $course_id ? $this->entityTypeManager->getStorage('node')->load($course_id) : NULL;
      if (!$main_entity && $syllabus_id) {
        $main_entity = $this->entityTypeManager->getStorage('ssr_syllabus')->load($syllabus_id);
      }
      $file_name = $main_entity?->label() ?? 'Okänt ämne';
      $group_value = $main_entity?->label() ?? '-';

      /** @var \Drupal\user\UserInterface|null $grader */
      $grader = $this->entityTypeManager->getStorage('user')->load($grader_id);
      if (!$grader) {
        throw new \RuntimeException('Unable to load grader.');
      }
      $file_name .= ' - ' . $grader->getDisplayName();

      $signees = [];
      $signees[$grader_id] = [
        ['target_id' => $grader_id],
      ];

      $syllabus_info = $this->syllabusService->getSyllabusInfo($syllabus_id);
      $course_code = ActivateSyllabusFormBase::parseSyllabusIdentifier($syllabus_info['identifier'] ?? '')['course_code'];

      $grades_to_sign_targets = [];
      $grade_info_items = [];
      foreach ($grade_revision_ids as $grade_revision_id) {
        /** @var \Drupal\simple_school_reports_grade_support\Utilities\GradeInfo|null $grade_info */
        $grade_info = $context['results']['grade_info'][$grade_revision_id] ?? NULL;
        if (!$grade_info) {
          continue;
        }
        $grade_info_items[] = $grade_revision_id;
        $grades_to_sign_targets[] = [
          'target_id' => $grade_info->id,
          'target_revision_id' => $grade_info->revisionId,
        ];

        $signees[$grade_info->mainGrader] = [
          'target_id' => $grade_info->mainGrader
        ];

        foreach ($grade_info->jointGraders ?? [] as $uid) {
          $signees[$uid] = [
            'target_id' => $uid,
          ];
        }
      }

      if (empty($grades_to_sign_targets)) {
        return;
      }

      // Make grade sign entity.
      /** @var \Drupal\simple_school_reports_grade_support\GradeSigningInterface $grade_signing */
      $grade_signing = $this->entityTypeManager->getStorage('ssr_grade_signing')->create([
        'label' => substr($file_name, 0, 255),
        'status' => 1,
        'signees' => array_values($signees),
        'export_document_key' => $signing_group_key,
        'syllabus' => ['target_id' => $syllabus_id ?? 0],
        'langcode' => 'sv',
      ]);
      $grade_signing->set('grades', $grades_to_sign_targets);
      $grade_signing->save();

      $number_of_grades = count($grades_to_sign_targets);

      $type = 'sign_document';

      $info_items = [
        [
          'label' => $group_label,
          'value' => $group_value,
        ],
        [
          'label' => 'Kurskod',
          'value' => $course_code ?? '-',
        ],
      ];

      $sign_label = 'Lärare';
      $sign_name = '';

      $signees_uids = array_keys($signees);
      if (!empty($signees_uids)) {
        $user_storage = $this->entityTypeManager->getStorage('user');
        $signees_users = $user_storage->loadMultiple($signees_uids);
        /** @var \Drupal\user\UserInterface $signees_user */
        $signees_names = [];
        foreach ($signees_users as $signees_user) {
          $signees_names[$signees_user->id()] = $signees_user->getDisplayName();
        }
        $sign_name = implode(', ', array_values($signees_names));
      }

      $spreadsheet = $this->prepareGradeSigningSpreadsheet(
        $context['results'][$type . '_path'],
        $type,
        $grade_signing,
        $grader,
        $number_of_grades,
        $info_items,
        'Betygssignering',
        '',
        $context['results']['form_values']['document_date'],
        $sign_label,
        $sign_name,
        $context,
      );
      if (!$spreadsheet) {
        return;
      }

      $this->populateGradeSpreadsheet($spreadsheet, $grader, $grade_info_items, $type, $context);

      $file_name = $syllabus_info['course_code'] ?? 'okänd_kurs';
      $file_name .= ' - ' . $grader->getDisplayName();

      if (count($signees_uids) > 1) {
        $file_name .= ' mfl';
      }
      $file_name .= ' ' . $grade_signing->getDocumentId();
      $context['ssr_document_context']['file_name'] = 'betygskatalog' . DIRECTORY_SEPARATOR . 'signering_' . $file_name . '.xlsx';
      $this->saveSpreadsheet($spreadsheet, $grader, 'signering', $context);
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Failed to create signing document for @name', ['@name' => $file_name ?? 'Okänt ämne']));
      \Drupal::logger('ssr_grade_export')->error($e->getMessage());
      $context['ssr_document_context'] = [];
    }
  }

  public function makeGradeCatalog(\DateTime $from, \DateTime $to, array &$context) {
    $catalog_sub_index = $from->getTimestamp() . ':' . $to->getTimestamp();
    if (empty($context['results']['grade_catalog_groups'][$catalog_sub_index])) {
      return;
    }
    $catalog_group_key = array_key_first($context['results']['grade_catalog_groups'][$catalog_sub_index]);
    $group_data = $context['results']['grade_catalog_groups'][$catalog_sub_index][$catalog_group_key];
    $group_internal_id = count($context['results']['grade_catalog_groups'][$catalog_sub_index]);
    unset($context['results']['grade_catalog_groups'][$catalog_sub_index][$catalog_group_key]);

    if (empty($group_data['student_ids'])) {
      return;
    }

    $group_name = $group_data['name'] ?? 'Okänd grupp';

    $form_values = $context['results']['form_values'] ?? [];

    $use_invalid_absence = $form_values['grade_catalog_include_invalid_absence'] ?? FALSE;
    $invalid_absence_key = '';
    if ($use_invalid_absence) {
      $invalid_absence_key = $from->getTimestamp() . ':' . $to->getTimestamp();
    }

    $syllabus_ids = [];
    $number_of_students = 0;

    $grade_references = $this->gradeService->getGradeReferencesByRegistrationDate($from, $to, $group_data['student_ids'] ?? [], $syllabus_ids);
    $grades = $this->gradeService->parseGradesFromReferences($grade_references);
    if (empty($grades)) {
      return;
    }

    $number_of_students = count($grades);
    foreach ($grades as $student_id => $grades_for_student) {
      /**
       * @var string|int $syllabus_id
       * @var \Drupal\simple_school_reports_grade_support\Utilities\GradeInfo $grade_info
       */
      foreach ($grades_for_student as $syllabus_id => $grade_info) {
        $syllabus_ids[$syllabus_id] = $syllabus_id;
      }
    }
    $syllabus_ids = array_values($syllabus_ids);

    $type = 'grade_catalog';

    $info_items = [];

    $municipality = Settings::get('ssr_school_municipality', NULL);
    if ($municipality) {
      $info_items[] = [
        'label' => 'Kommun',
        'value' => $municipality
      ];
    }

    $sign_label = 'Rektor';
    $sign_name = '';
    $sign_uid = $context['results']['form_values']['principal'] ?? NULL;

    if ($sign_uid) {
      /** @var \Drupal\user\UserInterface|null $user */
      $user = $this->entityTypeManager->getStorage('user')->load($sign_uid);
      $sign_name = $user?->getDisplayName();
    }

    $spreadsheet = $this->prepareGradeCatalogSpreadsheet(
      $context['results'][$type . '_path'],
      $type,
      $syllabus_ids,
      $number_of_students,
      $info_items,
      'Betygskatalog',
      '',
      $context['results']['form_values']['document_date'],
      $sign_label,
      $sign_name,
      $invalid_absence_key,
      $context,
    );

    $this->populateGradeCatalogSpreadsheet($spreadsheet, $grades, $type, $invalid_absence_key, $context);

    $file_name = $group_name;

    $term_index_from = $this->termService->getDefaultTermIndex($from);
    $term_from_short_name = $this->termService->parseDefaultTermIndex($term_index_from)['semester_name_short'];
    $term_index_to = $this->termService->getDefaultTermIndex($to);
    $term_to_short_name = $this->termService->parseDefaultTermIndex($term_index_to)['semester_name_short'];

    $name_suffixes = [];
    $name_suffixes[$term_index_from] = $term_from_short_name;
    $name_suffixes[$term_index_to] = $term_to_short_name;
    $name_suffixes[] = $group_internal_id;
    $file_name .= ' ' . implode(' - ', array_values($name_suffixes));

    $context['ssr_document_context']['file_name'] = 'betygskatalog' . DIRECTORY_SEPARATOR . 'betygskatalog_' . $file_name . '.xlsx';

    /** @var \Drupal\user\UserInterface $current_user */
    $current_user = $this->entityTypeManager->getStorage('user')->load($this->currentUser()->id());
    $this->saveSpreadsheet($spreadsheet, $current_user, 'betygskatalog', $context);

  }

  public function finished($success, $results) {
    if (!$success) {
      $this->messenger()->addError(t('Something went wrong'));
      return;
    }

    if (empty($results['generated_documents'])) {
      $form_values = $results['form_values'] ?? [];
      $use_grade_documents = $form_values['grade_documents'] ?? FALSE;
      $use_final_grade_document = $form_values['final_grade_documents'] ?? FALSE;
      $use_signature_documents = $form_values['signature_documents'] ?? FALSE;
      $use_grade_catalog = $form_values['grade_catalog'] ?? FALSE;

      if ($use_signature_documents && !$use_grade_documents && !$use_final_grade_document && !$use_grade_catalog) {
        $this->messenger()->addStatus(t('There is no signature document to export.'));
      }
      else {
        $this->messenger()->addError(t('Something went wrong'));
      }

      return;
    }

    if (count($results['generated_documents']) === 1) {
      $source_file = $results['generated_documents'][0];
      $source_file_name = basename($source_file);

      $target = $results['final_destination'];
      $target_uri = $results['final_destination_uri'];

      $moved_file_path = $this->fileSystem->copy($source_file, $target . DIRECTORY_SEPARATOR . $source_file_name);
      $target_file_name = basename($moved_file_path);
      /** @var FileInterface $file */
      $file = \Drupal::entityTypeManager()->getStorage('file')->create([
        'filename' => $source_file_name,
        'uri' => $target_uri . DIRECTORY_SEPARATOR . $target_file_name,
      ]);
      $file->save();
      $path = $file->createFileUrl();
      $link = Markup::create('<a href="' . $path . '" target="_blank">' . t('here') . '</a>');
    }
    else {
      $now = new \DateTime();

      $export_date_suffix = $now->format('Y-m-d-His');

      $source_dir = $results['destination'];
      $destination = $results['final_destination'];

      $destination_uri = $results['final_destination_uri'];

      $file_name = 'betyg-export-' . $export_date_suffix . '.zip';

      $result =  $this->fileTemplateService->doZip($source_dir, $destination, $file_name, TRUE);
      if (!$result) {
        $this->messenger()->addError(t('Something went wrong'));
        return;
      }

      /** @var FileInterface $file */
      $file = \Drupal::entityTypeManager()->getStorage('file')->create([
        'filename' => $file_name,
        'uri' => $destination_uri . DIRECTORY_SEPARATOR . $file_name,
      ]);
      $file->save();
      $path = $file->createFileUrl();
      $link = Markup::create('<a href="' . $path . '" target="_blank">' . t('here') . '</a>');
    }

    $this->messenger()->addMessage(t('Grade file generation complete. Save the file in a secure location. This file will shortly be removed from server. Download it now form @link', ['@link' => $link]));
    $this->fileSystem->deleteRecursive($results['destination']);
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['batchTest', 'finished'];
  }

}
