<?php

namespace Drupal\simple_school_reports_grade_registration\Form;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\Event\FileUploadSanitizeNameEvent;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Drupal\node\NodeInterface;
use Drupal\simple_school_reports_core\Pnum;
use Drupal\simple_school_reports_core\SchoolGradeHelper;
use Drupal\simple_school_reports_core\Service\FileTemplateServiceInterface;
use Drupal\simple_school_reports_core\Service\OrganizationsService;
use Drupal\simple_school_reports_core\Service\SchoolSubjectServiceInterface;
use Drupal\simple_school_reports_grade_registration\GradeRoundFormAlter;
use Drupal\simple_school_reports_grade_registration\GroupGradeExportInterface;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a confirmation form for generating grade catalog.
 */
class GenerateGradeCatalogForm extends ConfirmFormBase {

  /**
   * @var \Drupal\simple_school_reports_core\Service\FileTemplateServiceInterface
   */
  protected $fileTemplateService;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * @var UuidInterface
   */
  protected $uuid;

  protected SchoolSubjectServiceInterface $schoolSubjectService;

  /**
   * @var \Drupal\simple_school_reports_core\Pnum
   */
  protected $pnum;

  protected bool $useExtentExport;

  protected bool $useSCBExport;

  protected $calculatedData;

  const LETTER_INDEX = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'X', 'Y', ];



  public function __construct(FileTemplateServiceInterface $file_template_service, EntityTypeManagerInterface $entity_type_manager, Connection $connection,  UuidInterface $uuid, Pnum $pnum, ModuleHandlerInterface $module_handler, SchoolSubjectServiceInterface $school_subject_service) {
    $this->fileTemplateService = $file_template_service;
    $this->entityTypeManager = $entity_type_manager;
    $this->connection = $connection;
    $this->uuid = $uuid;
    $this->schoolSubjectService = $school_subject_service;
    $this->pnum = $pnum;
    $this->useExtentExport = $module_handler->moduleExists('simple_school_reports_extens_grade_export');
    $this->useSCBExport = $module_handler->moduleExists('simple_school_reports_scb_grade_export');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('simple_school_reports_core.file_template_service'),
      $container->get('entity_type.manager'),
      $container->get('database'),
      $container->get('uuid'),
      $container->get('simple_school_reports_core.pnum'),
      $container->get('module_handler'),
      $container->get('simple_school_reports_core.school_subjects')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'generate_grade_catalog_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Generate grade catalog');
  }

  public function getCancelRoute() {
    return 'view.grade_registration_rounds.active';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url($this->getCancelRoute());
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Generate');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?NodeInterface $node = NULL) {
    if (!$node || $node->bundle() !== 'grade_round') {
      throw new AccessDeniedHttpException();
    }

    $form['grade_round_nid'] = [
      '#type' => 'value',
      '#value' => $node->id(),
    ];
    $form = parent::buildForm($form, $form_state);

    if ($form_state->get('ssn_key_errors')) {
      // Rewrite submit button.
      $form['actions']['submit']['#skip_pnum_validation'] = TRUE;
      $form['actions']['submit']['#value'] = t('Generate anyway');
    }


    if ($node->get('field_document_date')->value && $node->get('field_locked')->value) {
      $doc_date = new \DateTime();
      $doc_date->setTimestamp($node->get('field_document_date')->value);
      $doc_date->add(new \DateInterval('P1M'));

      $limit = new \DateTime();
      $limit->setTime(0,0,0);

      if ($doc_date < $limit) {
        $messages = [
          'warning' => [
            $this->t('Note. Information in documents is regenerated. I.e. information that may have changed since the regular time of the round, such as student name or mentor etc., may therefore be out of date. For documents current at the time of the round please download from the school\'s local archives.'),
          ],
        ];

        $form['message'] = [
          '#theme' => 'status_messages',
          '#message_list' => $messages,
          '#status_headings' => [
            'status' => $this->t('Status message'),
            'error' => $this->t('Error message'),
            'warning' => $this->t('Warning message'),
          ],
        ];
      }
    }

    if ($this->getFormId() === 'generate_grade_catalog_form' && !$node->get('field_locked')->value) {
      $form['lock'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Lock'),
        '#description' => $this->t('Lock this round for future registrations, can be unlocked by editing the round later.'),
        '#default_value' => FALSE,
      ];
    }

    if ($this->getFormId() === 'generate_grade_catalog_form') {
      if ($this->useExtentExport) {
        $extens_export_grades = [6, 7, 8, 9];

        $grade_options = SchoolGradeHelper::getSchoolGradesMap(['GR']);
        $extens_export_grade_options = [];
        $extens_default_value = [];

        foreach ($grade_options as $grade_option => $label) {
          if (in_array($grade_option, $extens_export_grades)) {
            $extens_export_grade_options[$grade_option] = $label;
            if ($grade_option === 9) {
              $extens_default_value[] = $grade_option;
            }
          }
        }

        $form['extend_export_wrapper'] = [
          '#type' => 'details',
          '#title' => $this->t('Extens export (MGBETYG)'),
          '#open' => TRUE,
        ];
        $form['extend_export_wrapper']['extens_export_grades'] = [
          '#type' => 'checkboxes',
          '#title' => $this->t('Grades to include in export file'),
          '#description' => $this->t('The grade refers to the grade that has been set in the corresponding student groups. Only students in these student groups will be included in the export.'),
          '#options' => $extens_export_grade_options,
          '#default_value' => $extens_default_value,
        ];

        $form['extend_export_wrapper']['extens_include_contact_details'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Include student contact details'),
          '#description' => $this->t('NOTE: Contact details for students with protected personal data will not be included.'),
          '#default_value' => TRUE,
        ];
      }

      if ($this->useSCBExport) {
        $grade_options = SchoolGradeHelper::getSchoolGradesMap(['GR']);
        if (!empty($grade_options[6]) || !empty($grade_options[9])) {
          $form['scb_export_wrapper'] = [
            '#type' => 'details',
            '#title' => $this->t('SCB export'),
            '#open' => TRUE,
          ];

          if (!empty($grade_options[6])) {
            $form['scb_export_wrapper']['scb_export_6'] = [
              '#type' => 'checkbox',
              '#title' => $this->t('Include students in grade 6'),
              '#default_value' => FALSE,
            ];
          }

          if (!empty($grade_options[9])) {
            $form['scb_export_wrapper']['scb_export_9_final'] = [
              '#type' => 'checkbox',
              '#title' => $this->t('Include students in grade 9'),
              '#description' => $this->t('NOTE: Only students with the final grade in grade 9 will be included.'),
              '#default_value' => FALSE,
            ];
          }

          $form['scb_export_wrapper']['disclaimer'] = [
            '#type' => 'html_tag',
            '#tag' => 'em',
            '#value' => $this->t('NOTE: SCB do not support partial imports. It is important that the student list and the grade registration is complete before using the SCB export file.'),
          ];
        }
      }
    }

    $form['#title'] = t('Generate grade catalog') . ' - ' . $node->label();

    $form['actions']['submit']['#gen_submit'] = TRUE;

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $form_state->set('ssn_key_errors', FALSE);

    if (empty($form_state->getTriggeringElement()['#gen_submit'])) {
      return;
    }


    // Validate file exists.
    $required_templates = ['student_grade_term', 'student_grade_final', 'student_group_grade', 'teacher_grade_sign', 'doc_logo_right'];
    foreach ($required_templates as $required_template) {
      if (!$this->fileTemplateService->getFileTemplateRealPath($required_template)) {
        $form_state->setError($form, $this->t('File template missing.'));
        return;
      }
    }

    if (!empty($form_state->getTriggeringElement()['#skip_pnum_validation'])) {
      return;
    }

    $valid_ssn_map = [];
  }

  protected function getCalculatedData(FormStateInterface $form_state) {
    if (!is_array($this->calculatedData)) {
      $batch = [
        'title' => $this->t('Generating grade documents'),
        'init_message' => $this->t('Generating grade documents'),
        'progress_message' => $this->t('Processed @current out of @total.'),
        'operations' => [],
        'finished' => [self::class, 'finished'],
      ];


      $students = [];
      $teachers = [];
      $student_groups_data = [];

      $ordered_student_uids = $this->entityTypeManager->getStorage('user')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('roles', 'student')
        ->sort('field_first_name')
        ->sort('field_last_name')
        ->execute();

      $ordered_student_uids = array_values($ordered_student_uids);

      $subjects = [];
      $catalog_ids = Settings::get('ssr_catalog_id');
      $excluded_catalog_label = Settings::get('ssr_excluded_catalog_label');
      $code_options = _simple_school_reports_core_school_subject_codes();

      foreach ($code_options as $code => &$label) {
        $label = preg_replace('/\(.+\)\s/', '', $label);
      }

      $subject_ids = array_keys($this->schoolSubjectService->getSchoolSubjectOptionList(['GR'], TRUE));
      $weight = 1;

      if (!empty($subject_ids)) {
        foreach ($this->entityTypeManager->getStorage('taxonomy_term')->loadMultiple($subject_ids) as $subject) {
          $code = $subject->get('field_subject_code_new')->value;
          if (!$code || !isset($catalog_ids[$code]) || !isset($code_options[$code])) {
            continue;
          }
          $block_parent = $subject->get('field_block_parent')->target_id;
          if ($block_parent) {
            $subjects[$block_parent]['children'][] = $subject->id();
          }
          $subjects[$subject->id()]['weight'] = $weight;
          $subjects[$subject->id()]['id'] = $subject->id();
          $subjects[$subject->id()]['name'] = $code_options[$code];
          $subjects[$subject->id()]['full_name'] = $subject->label();
          $subjects[$subject->id()]['catalog_id'] = $catalog_ids[$code];
          $subjects[$subject->id()]['catalog_com_id'] = isset($catalog_ids[$code. '_COM']) ? $catalog_ids[$code. '_COM'] : NULL;
          $subjects[$subject->id()]['code'] = $code;
          $subjects[$subject->id()]['parent'] = $block_parent;
          $subjects[$subject->id()]['excluded_label'] = isset($excluded_catalog_label[$code]) ? $excluded_catalog_label[$code] : '-';
          $weight++;
        }

        foreach ($subjects as $subject_id => $subject) {
          if (!isset($subject['id'])) {
            unset($subjects[$subject_id]);
          }
        }
      }

      /** @var \Drupal\node\NodeStorage $node_storage */
      $node_storage = $this->entityTypeManager->getStorage('node');


      /** @var NodeInterface $grade_round */
      $grade_round = $node_storage->load($form_state->getValue('grade_round_nid'));

      if (!$grade_round) {
        $this->messenger()->addError(t('Something went wrong'));
        return;
      }

      $student_groups = $grade_round->get('field_student_groups')->referencedEntities();

      /** @var NodeInterface $student_group */
      foreach ($student_groups as $student_group) {
        $student_uids = array_column($student_group->get('field_student')->getValue(), 'target_id');
        foreach ($student_uids as $student_uid) {
          $students[$student_uid]['groups'][$student_group->id()] = $student_group->id();
        }
        $student_groups_data[$student_group->id()] = [
          'name' => $student_group->label(),
          'students' => $student_uids,
          'principle' => $student_group->get('field_principle')->target_id,
          'document_type' => $student_group->get('field_document_type')->value ?? '',
          'grade_registrations' => [],
          'grade_system' => $student_group->get('field_grade_system')->value,
        ];

        $grade_subject_nids = array_column($student_group->get('field_grade_subject')->getValue(), 'target_id');

        if (empty($grade_subject_nids)) {
          continue;
        }

        $query = $this->connection->select('node__field_grade_registration', 'g');
        $query->fields('g', ['field_grade_registration_target_id']);
        $query->condition('g.entity_id', $grade_subject_nids, 'IN');
        $results = $query->execute();

        foreach ($results as $result) {
          $student_groups_data[$student_group->id()]['grade_registrations'][] = $result->field_grade_registration_target_id;
          $batch['operations'][] = [[self::class, 'resolveGrade'], [$result->field_grade_registration_target_id, $student_group->id(), $subjects]];
        }

        foreach ($grade_subject_nids as $grade_subject_nid) {
          $batch['operations'][] = [[self::class, 'resolveDefaultGrades'], [$grade_subject_nid, $student_group->id(), $student_groups_data[$student_group->id()], $subjects]];
        }

        if (!empty($student_groups_data[$student_group->id()]['grade_registrations'])) {
          $query = $this->connection->select('paragraph__field_teacher', 't');
          $query->fields('t', ['field_teacher_target_id']);
          $query->condition('t.entity_id', $student_groups_data[$student_group->id()]['grade_registrations'], 'IN');
          $results = $query->execute();

          foreach ($results as $result) {
            $teachers[$result->field_teacher_target_id]['groups'][$student_group->id()] = $student_group->id();
          }
        }

      }

      $calculated_data = [
        'students' => $students,
        'students_uids' => array_keys($students),
        'ordered_student_uids' => $ordered_student_uids,
        'teachers' => $teachers,
        'student_groups_data' => $student_groups_data,
        'subjects' => $subjects,
        'batch' => $batch,
      ];
      $this->calculatedData = $calculated_data;
    }

    return $this->calculatedData;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->get('ssn_key_errors')) {
      $form_state->setRebuild(TRUE);
      return;
    }


    $form_state->setRedirect($this->getCancelRoute());

    if (!empty($form_state->getValue('ssn_key')) && $file = $this->entityTypeManager->getStorage('file')->load($form_state->getValue('ssn_key')[0])) {
      $file->delete();
    }

    $calculated_data = $this->getCalculatedData($form_state);

    $students = $calculated_data['students'];
    $ordered_student_uids = $calculated_data['ordered_student_uids'];
    $teachers = $calculated_data['teachers'];
    $student_groups_data = $calculated_data['student_groups_data'];
    $subjects = $calculated_data['subjects'];
    $batch = $calculated_data['batch'];

    /** @var \Drupal\node\NodeStorageInterface $node_storage */
    $node_storage = $this->entityTypeManager->getStorage('node');

    /** @var NodeInterface $grade_round */
    $grade_round = $node_storage->load($form_state->getValue('grade_round_nid'));

    $invalid_absence_from = $grade_round->get('field_invalid_absence_from')->value;
    $invalid_absence_to = $grade_round->get('field_invalid_absence_to')->value;


    if (!empty($batch['operations'])) {
      // Setup callbacks for file generation.
      $grade_options = SchoolGradeHelper::getSchoolGradesMap();

      $document_date = '';
      $timestamp = $grade_round->get('field_document_date')->value;

      if ($timestamp) {
        $date = new DrupalDateTime();
        $date->setTimestamp($timestamp);
        $document_date = $date->format('Y-m-d');
      }

      $scb_export_types = [];
      if ($form_state->getValue('scb_export_6', FALSE)) {
        $scb_export_types[] = 'scb_export_6';
      }
      if ($form_state->getValue('scb_export_9_final', FALSE)) {
        $scb_export_types[] = 'scb_export_9_final';
      }

      $references = [
        'students' => $students,
        'ordered_student_uids' => $ordered_student_uids,
        'teachers' => $teachers,
        'student_groups_data' => $student_groups_data,
        'subjects' => $subjects,
        'term_type' => $grade_round->get('field_term_type')->value,
        'term_type_full' => GradeRoundFormAlter::getFullTermStamp($grade_round),
        'document_date' => $document_date,
        'grade_options' => array_keys($grade_options),
        'base_destination' => $this->uuid->generate(),
        'grade_round_name' => $grade_round->label(),
        'extens_export_grades' => $form_state->getValue('extens_export_grades', []),
        'extens_include_contact_details' => $form_state->getValue('extens_include_contact_details', FALSE),
        'scb_export_types' => $scb_export_types,
      ];

      $external_services = [];
      if ($this->getFormId() === 'generate_grade_catalog_form') {
        if ($this->useExtentExport && !empty($references['extens_export_grades'])) {
          $external_services[] = 'simple_school_reports_extens_grade_export.export_service';
        }
        if ($this->useSCBExport && !empty($references['scb_export_types'])) {
          $external_services[] = 'simple_school_reports_scb_grade_export.export_service';
        }
      }

      if (is_numeric($invalid_absence_from) && is_numeric($invalid_absence_to) && $invalid_absence_to > $invalid_absence_from) {
        $attendance_report_nids = $this->entityTypeManager
          ->getStorage('node')
          ->getQuery()
          ->condition('type', 'course_attendance_report')
          ->condition('field_class_start', $invalid_absence_to, '<')
          ->condition('field_class_end', $invalid_absence_from, '>')
          ->accessCheck(FALSE)
          ->execute();

        if (!empty($attendance_report_nids)) {
          foreach ($students as $student_uid => $data) {
            $batch['operations'][] = [[self::class, 'calculateInvalidAbsence'], [$student_uid, $attendance_report_nids]];
          }
        }
      }

      foreach ($students as $student_uid => $data) {
        $batch['operations'][] = [[self::class, 'generateStudentGradeDoc'], [$student_uid, $references]];
      }

      foreach ($student_groups_data as $student_group_nid => $data) {
        $batch['operations'][] = [[self::class, 'generateStudentGroupCatalog'], [$student_group_nid, $references]];

        foreach ($external_services as $external_service) {
          $batch['operations'][] = [[self::class, 'handleExternalGroupExport'], [$external_service, $student_group_nid, $references]];
        }
      }

      foreach ($teachers as $teacher_uid => $data) {
        foreach ($data['groups'] as $student_group_nid => $g_data) {
          $batch['operations'][] = [[self::class, 'generateTeacherSignDoc'], [$teacher_uid, $student_group_nid, $references]];
        }
      }

      foreach ($external_services as $external_service) {
        $batch['operations'][] = [[self::class, 'handleBeforeFinnishExport'], [$external_service, $references]];
      }

      if ($form_state->getValue('lock', FALSE)) {
        $grade_round->set('field_locked', TRUE);
        $grade_round->save();
      }

      batch_set($batch);
    }
    else {
      $this->messenger()->addWarning($this->t('No grade has been registered.'));
    }
  }

  public static function resolveGrade($pargraph_id, $student_group_nid, $subjects, &$context) {
    self::doResolveGrade($pargraph_id, $student_group_nid, $subjects,$context);
  }

  public static function doResolveGrade($pargraph_id, $student_group_nid, $subjects, &$context, $comment_suffix = '', $skip_sign = FALSE) {
    /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
    $paragraph = \Drupal::entityTypeManager()->getStorage('paragraph')->load($pargraph_id);
    if (!$paragraph) {
      return;
    }

    $student_uid = $paragraph->get('field_student')->target_id;
    $grade_subject = $paragraph->getParentEntity();
    if (!$student_uid || !$grade_subject) {
      return;
    }

    $subject_id = $grade_subject->get('field_school_subject')->target_id;

    if (!$subject_id || empty($subjects[$subject_id])) {
      return;
    }

    $subject = $subjects[$subject_id];

    $exclude_reason = $paragraph->get('field_exclude_reason')->value;
    $excluded_label = $exclude_reason === 'adapted_studies' ? '3' : $subject['excluded_label'];

    if ($exclude_reason === 'pending') {
      return;
    }

    /** @var TermInterface $grade */
    $grade = current($paragraph->get('field_grade')->referencedEntities());
    $grade_name = $grade ? $grade->label() : '-';
    $clean_comment = $paragraph->get('field_comment')->value ?? '';

    $grade_comment = $clean_comment ? $clean_comment . $comment_suffix : $comment_suffix;
    if (strlen($grade_comment) > 10) {
      $truncated_grade_comment = substr($grade_comment,0,10 - 3 - strlen($comment_suffix)) . '...';
      $grade_comment = $truncated_grade_comment . $comment_suffix;
    }

    if ($exclude_reason === NULL) {
      $student_grade_name = $grade_name;

      if (!empty($subject['children'])) {
        if ($grade_name === '-') {
          $grade_name = '2';
        }
        else {
          foreach ($subject['children'] as $child_subject_id) {
            if (isset($subjects[$child_subject_id])) {
              $context['results']['catalog'][$student_group_nid][$student_uid][$subjects[$child_subject_id]['catalog_id']] = '2';
              unset($context['results']['student'][$student_uid][$student_group_nid][$subject_id]);
            }
          }
        }
      }


      $set_grade = !empty($context['results']['catalog'][$student_group_nid][$student_uid][$subject['catalog_id']]) ? $context['results']['catalog'][$student_group_nid][$student_uid][$subject['catalog_id']] : NULL;

      // Check for existing grade.
      if ($set_grade && $set_grade !== '3' && $set_grade !== $excluded_label) {
        // Show a warning about this.
        /** @var \Drupal\user\UserInterface $student */
        $student = \Drupal::entityTypeManager()->getStorage('user')->load($student_uid);
        if ($student && $student->hasRole('student') && isset($subject['name'])) {
          $name = '';
          _simple_school_reports_core_resolve_name($name, $student, TRUE);
          \Drupal::messenger()->addWarning(t('@name has multiple grades set in @subject', ['@name' => $name, '@subject' => $subject['name']]));
        }
      }

      $context['results']['catalog'][$student_group_nid][$student_uid][$subject['catalog_id']] = $grade_name;
      if ($subject['catalog_com_id']) {
        $context['results']['catalog'][$student_group_nid][$student_uid][$subject['catalog_com_id']] = $clean_comment ?? '2';
      }
      $context['results']['student'][$student_uid][$student_group_nid][$subject_id]['grade'] = $student_grade_name;
      $context['results']['student'][$student_uid][$student_group_nid][$subject_id]['comment'] = $grade_comment;
    }
    else {
      // This subject is already handled, skip to not overwrite this with empty
      // grades!
      if (!empty($context['results']['catalog'][$student_group_nid][$student_uid][$subject['catalog_id']])) {
        return;
      }

      $context['results']['catalog'][$student_group_nid][$student_uid][$subject['catalog_id']] = $excluded_label;

      if ($exclude_reason === 'adapted_studies') {
        $context['results']['student'][$student_uid][$student_group_nid][$subject_id]['grade'] = '**';
        $context['results']['student'][$student_uid][$student_group_nid][$subject_id]['comment'] = $grade_comment;
      }

    }

    $teacher_uid = $paragraph->get('field_teacher')->target_id;
    if (!$teacher_uid) {
      return;
    }

    $joint_grading_uids = [];

    foreach (array_column($paragraph->get('field_joint_grading')->getValue(), 'target_id') as $joint_grading_uid) {
      if ($joint_grading_uid === $teacher_uid) {
        continue;
      }
      $joint_grading_uids[$joint_grading_uid] = $joint_grading_uid;
    }

    // Put the teacher as first in the list in case of joint grading.
    if (!empty($joint_grading_uids)) {
      $joint_grading_uids = array_merge([$teacher_uid], array_values($joint_grading_uids));
    }

    if ($exclude_reason === NULL && !$skip_sign) {
      $context['results']['sign'][$teacher_uid][$student_group_nid][$subject_id][$student_uid]['grade'] = $student_grade_name;
      $context['results']['sign'][$teacher_uid][$student_group_nid][$subject_id][$student_uid]['comment'] = $grade_comment;
      $context['results']['sign'][$teacher_uid][$student_group_nid][$subject_id][$student_uid]['joint_grading'] = array_values($joint_grading_uids);
    }
  }

  public static function resolveDefaultGrades($grade_subject_nid, $student_group_nid, $student_groups_data, $subjects, &$context) {
    /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
    $grade_subject = \Drupal::entityTypeManager()->getStorage('node')->load($grade_subject_nid);
    if (!$grade_subject || $grade_subject->get('field_default_grade_round')->isEmpty()) {
      return;
    }
    $default_grade_round_nid = $grade_subject->get('field_default_grade_round')->target_id;
    $subject_id = $grade_subject->get('field_school_subject')->target_id;
    $grade_system = $student_groups_data['grade_system'] ?? NULL;

    if (!$subject_id || empty($subjects[$subject_id]) || !$grade_system) {
      return;
    }

    if (empty($student_groups_data['students'])) {
      return;
    }

    $unresolved_uids = [];


    foreach ($student_groups_data['students'] as $student_uid) {
      if (!isset($context['results']['student'][$student_uid][$student_group_nid][$subject_id])) {
        $unresolved_uids[] = $student_uid;
      }
    }

    if (!empty($unresolved_uids)) {
      /** @var \Drupal\simple_school_reports_extension_proxy\Service\GradeSupportServiceInterface $grade_support_service */
      $grade_support_service = \Drupal::service('simple_school_reports_extension_proxy.grade_support');

      $default_grade_data = $grade_support_service->getDefaultGradeRoundData($default_grade_round_nid, $subject_id, $grade_system, $unresolved_uids);
      foreach ($default_grade_data as $data) {
        self::doResolveGrade($data['paragraph_id'], $student_group_nid, $subjects, $context, $data['comment_suffix'], TRUE);
      }
    }
  }

  public static function calculateInvalidAbsence($student_uid, $attendance_report_nids, &$context) {
    $invalid_absence = 0;

    /** @var \Drupal\Core\Database\Connection $connection */
    $connection = \Drupal::service('database');
    $query = $connection->select('paragraph__field_invalid_absence', 'ia');
    $query->innerJoin('paragraph__field_student', 's', 's.entity_id = ia.entity_id');
    $query->innerJoin('paragraphs_item_field_data', 'd', 'd.id = ia.entity_id');
    $query->condition('ia.bundle', 'student_course_attendance')
      ->condition('ia.field_invalid_absence_value', 0, '<>')
      ->condition('s.field_student_target_id', $student_uid)
      ->condition('d.parent_id', $attendance_report_nids, 'IN')
      ->fields('ia',['field_invalid_absence_value']);

    $results = $query->execute();
    foreach ($results as $result) {
      $invalid_absence += $result->field_invalid_absence_value;
    }

    $invalid_absence = (int) ($invalid_absence / 60);
    if ($invalid_absence > 0) {
      $context['results']['invalid_absence'][$student_uid] = $invalid_absence;
    }
  }

  public static function generateStudentGradeDoc($student_uid, $references, &$context) {
    $has_grades = TRUE;
    if (empty($context['results']['student'][$student_uid])) {
      $has_grades = FALSE;
    }

    /** @var \Drupal\user\UserInterface $student */
    $student = \Drupal::entityTypeManager()->getStorage('user')->load($student_uid);

    if (!$student || !$student->hasRole('student')) {
      return;
    }

    /** @var FileTemplateServiceInterface $file_template_service */
    $file_template_service = \Drupal::service('simple_school_reports_core.file_template_service');

    $ssn = 'ååmmdd-nnnn';
    $has_valid = FALSE;

    if (!$student->get('field_birth_date_source')->isEmpty()) {
      if ($student->get('field_birth_date_source')->value === 'ssn') {
        $student_ssn = $student->get('field_ssn')->value;
        if ($student_ssn) {
          /** @var \Drupal\simple_school_reports_core\Pnum $pnum_serivice */
          $pnum_serivice = \Drupal::service('simple_school_reports_core.pnum');
          $student_ssn = $pnum_serivice->normalizeIfValid($student_ssn);
          if ($student_ssn) {
            $ssn = $student_ssn;
            $has_valid = TRUE;
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

    if (!$has_valid && $has_grades) {
      $name = '';
      _simple_school_reports_core_resolve_name($name, $student, TRUE);
      \Drupal::messenger()->addWarning(t('@name misses a valid personal number, document is generated anyway with value @value', ['@name' => $name, '@value' => $ssn]));
    }


    $search_replace_map = [];
    $search_replace_map['!UA!'] = 'Underskrift';
    $search_replace_map['!datum!'] = $references['document_date'];
    $search_replace_map['!tl!'] = 'Termin';
    $search_replace_map['!ti!'] = '';

    if (!empty($references['document_date'])) {
      $year = substr($references['document_date'], 0, 4);
      $search_replace_map['!ti!'] = $references['term_type'] === 'ht' ? 'Höstterminen ' . $year : 'Vårterminen ' . $year;
    }
    else {
      $search_replace_map['!ti!'] = $references['term_type'] === 'ht' ? 'Hösttermin' : 'Vårtermin';
    }

    $use_classes = \Drupal::moduleHandler()->moduleExists('simple_school_reports_class');

    /** @var \Drupal\simple_school_reports_core\Service\OrganizationsServiceInterface $organization_service */
    $organization_service = \Drupal::service('simple_school_reports_core.organizations_service');
    $organizer = $organization_service->getOrganization('school_organiser', 'GR');
    $school = $organization_service->getOrganization('school', 'GR');

    $search_replace_map['!fornamn!'] = $student->get('field_first_name')->value ?? '';
    $search_replace_map['!efternamn!'] = $student->get('field_last_name')->value ?? '';
    $search_replace_map['!sgl!'] = 'Årskurs';
    $search_replace_map['!personnummer!'] = $ssn;
    $search_replace_map['!noteringar!'] = '';
    $search_replace_map['!bsl!'] = 'Antal timmar med ogiltig frånvaro';
    $search_replace_map['!bs!'] = !empty($context['results']['invalid_absence'][$student_uid]) ? $context['results']['invalid_absence'][$student_uid] . ' h' : '0 h';

    $search_replace_map['!huvudman!'] = $organizer?->label() ?? '';
    $search_replace_map['!skola!'] = ($school?->label() ?? '') . ', ' . ($organizer?->label() ?? '');
    $search_replace_map['!kommun!'] = $school?->get('municipality')->value ?? '';
    $search_replace_map['!sc!'] = OrganizationsService::getStaticSchoolUnitCode('GR');

    $search_replace_map['!gi!'] = '';

    $search_replace_map['!gfp1!'] = 'Som betyg ska någon av beteckningarna A, B, C, D, E eller F användas. Betyg för godkända resultat betecknas med A, B, C, D eller E. Högsta betyg betecknas med A och lägsta betyg med E. Betyg för icke godkänt betyg betecknas med F. Om det saknas underlag för bedömning av en elevs kunskaper i ett ämne på grund av elevens frånvaro, ska betyg inte sättas i ämnet. Detta ska markeras med ett horisontellt streck i terminsbetyget.';
    $search_replace_map['!gfp2!'] = '* Ämnet har avslutats.';
    $search_replace_map['!gfp3!'] = '** Betyg har inte satts i ämnet på grund av anpassad studiegång.';

    $context['results']['students_name'][$student_uid] = $search_replace_map['!fornamn!'] . ' ' . $search_replace_map['!efternamn!'];
    $context['results']['students_first_name'][$student_uid] = $search_replace_map['!fornamn!'];
    $context['results']['students_last_name'][$student_uid] = $search_replace_map['!efternamn!'];
    $context['results']['students_ssn'][$student_uid] = $ssn;

    // No need for more if there is no grades.
    if (!$has_grades) {
      return;
    }

    $mentor_name = '';
    /** @var \Drupal\user\UserInterface $mentor */
    $mentor = current($student->get('field_mentor')->referencedEntities());
    if ($mentor) {
      $mentor_name = $mentor->getDisplayName();
    }
    $search_replace_map['!mentor!'] = $mentor_name;

    $file_name = str_replace(' ', '_', $references['term_type_full'] . '_' . $context['results']['students_name'][$student_uid] . '_' . $student->id());
    $file_name = str_replace('/', '-', $file_name);
    $file_name = str_replace('\\', '-', $file_name);
    $file_name = mb_strtolower($file_name);

    $event = new FileUploadSanitizeNameEvent($file_name, '');
    $dispatcher = \Drupal::service('event_dispatcher');
    $dispatcher->dispatch($event);
    $file_name = $event->getFilename();

    foreach ($context['results']['student'][$student_uid] as $student_group_id => $grade_data) {

      $student_group_node = \Drupal::entityTypeManager()->getStorage('node')->load($student_group_id);

      if (!$student_group_node) {
        continue;
      }

      $search_replace_map['!sg!'] = '';
      $context['results']['ssr_student_doc_grade_value'][$student_uid] = NULL;
      $student_grade = $student_group_node->get('field_grade')->value
        ? (int) $student_group_node->get('field_grade')->value
        : NULL;
      if ($student_grade >= 0) {
        $grade_options = SchoolGradeHelper::getSchoolGradesMap(['FKLASS', 'GR']);
        if (isset($grade_options[$student_grade])) {
          if ($student_grade === 0) {
            $search_replace_map['!sg!'] = 'Förskoleklass';
          }
          if ($student_grade > 0) {
            $search_replace_map['!sg!'] = 'Årskurs ' . $student_grade;
            $context['results']['ssr_student_doc_grade_value'][$student_uid] = $student_grade;
          }
        }
      }

      $student_class = $use_classes ? $student_group_node->get('field_class')->entity : NULL;
      $context['results']['ssr_student_doc_class_value'][$student_uid] = $student_class?->label() ?? NULL;
      if ($student_class) {
        $search_replace_map['!sgl!'] = 'Årskurs/Klass';
        $search_replace_map['!sg!'] = !empty($search_replace_map['!sg!'])
          ? $search_replace_map['!sg!'] . ' ' . $student_class->label()
          : $student_class->label();
      }

      foreach ($references['grade_options'] as $student_grade_option) {
        $search_replace_map['!c' . $student_grade_option . '!'] = $student_grade == $student_grade_option ? FileTemplateServiceInterface::WORD_CHECKBOX_CHECKED : FileTemplateServiceInterface::WORD_CHECKBOX_UNCHECKED;
      }

      $type = $student_group_node->get('field_document_type')->value ?? '';

      $template_file = 'student_grade_' . $type;
      if (!$file_template_service->getFileTemplateRealPath($template_file)) {
        return;
      }

      if ($type === 'term') {
        $file_name = 'terminsbetyg_' . $file_name;
      }

      if ($type === 'final') {
        $file_name = 'slutbetyg_' . $file_name;
      }

      $principle_name = '';
      if (!empty($references['student_groups_data'][$student_group_id]['principle'])) {
        /** @var \Drupal\user\UserInterface $principle */
        $principle = \Drupal::entityTypeManager()->getStorage('user')->load($references['student_groups_data'][$student_group_id]['principle']);
        if ($principle) {
          $principle_name = $principle->getDisplayName();
        }
      }
      $search_replace_map['!rektor!'] = $principle_name;

      $search_replace_map['!dokument_sign_label!'] = $type === 'term' ? 'Mentors underskrift' : 'Rektorns underskrift';
      $search_replace_map['!dokument_signer!'] = $type === 'term' ? $search_replace_map['!mentor!'] : $search_replace_map['!rektor!'];

      if ($type === 'final') {
        $search_replace_map['!gi!'] = 'Eleven har avslutat grundskolan enligt förordningen (SKOLFS 2010:37) om läroplan för grundskolan, förskoleklassen och fritidshemmet och därvid fått följande betyg.';
      }

      // Set defaults;
      for ($i = 1; $i <= 24; $i++) {
        $grade_sr_key = self::LETTER_INDEX[$i - 1];
        $search_replace_map['!' . $grade_sr_key . '!'] = '';
        $search_replace_map['!k' . $i . '!'] = '';
        $search_replace_map['!a' . $i . '!'] = '';
      }

      $subject_ordered_ids = [];

      foreach ($references['subjects'] as $subject_id => $subject_data) {
        $subject_ordered_ids[$subject_data['weight']] = $subject_id;
      }
      ksort($subject_ordered_ids);

      $subject_ordered_ids = array_values($subject_ordered_ids);
      $grade_subject_ids = array_keys($grade_data);
      $subject_ids = [];

      $temp  = [];

      foreach ($subject_ordered_ids as $ordered_id) {
        if (in_array($ordered_id, $grade_subject_ids)) {
          $subject_ids[] = $ordered_id;
          $temp[] = $references['subjects'][$ordered_id]['name'];
        }
      }
      $grade_count = count($subject_ids);

      $col_break_index = (int) ceil($grade_count / 2);

      // Print first column.
      for ($i = 0; $i < $col_break_index; $i++) {
        $grade_sr_key = self::LETTER_INDEX[$i];
        $search_replace_map['!a' . ($i + 1) .  '!'] = !empty($references['subjects'][$subject_ids[$i]]['name']) ? $references['subjects'][$subject_ids[$i]]['name'] : '';
        $search_replace_map['!' . $grade_sr_key . '!'] = !empty($grade_data[$subject_ids[$i]]['grade']) ? $grade_data[$subject_ids[$i]]['grade'] : '';
        $search_replace_map['!k' . ($i + 1) . '!'] = !empty($grade_data[$subject_ids[$i]]['comment']) ? $grade_data[$subject_ids[$i]]['comment'] : '';
      }

      $real_index = 13;
      // Print second column.
      for ($i = $col_break_index; $i < $grade_count; $i++) {
        $grade_sr_key = self::LETTER_INDEX[$real_index - 1];
        $search_replace_map['!a' . $real_index .  '!'] = !empty($references['subjects'][$subject_ids[$i]]['name']) ? $references['subjects'][$subject_ids[$i]]['name'] : '';
        $search_replace_map['!' . $grade_sr_key . '!'] = !empty($grade_data[$subject_ids[$i]]['grade']) ? $grade_data[$subject_ids[$i]]['grade'] : '';
        $search_replace_map['!k' . $real_index . '!'] = !empty($grade_data[$subject_ids[$i]]['comment']) ? $grade_data[$subject_ids[$i]]['comment'] : '';
        $real_index++;
      }

      $student_group_dest = str_replace(' ', '_', $references['student_groups_data'][$student_group_id]['name']);
      $student_group_dest = mb_strtolower($student_group_dest);
      $event = new FileUploadSanitizeNameEvent($student_group_dest, '');
      $dispatcher->dispatch($event);
      $student_group_dest = $event->getFilename();


      $file_dest = $references['base_destination'] . DIRECTORY_SEPARATOR . $student_group_dest . DIRECTORY_SEPARATOR;
      if ($file_template_service->generateDocxFile($template_file, $file_dest, $file_name, $search_replace_map, 'doc_logo_right')) {
        self::makeSureMetaDataInContext($references, $context);
        $context['results']['latest_file_destination'] = 'ssr_tmp' . DIRECTORY_SEPARATOR .  $file_dest;
        $context['results']['latest_file_name'] = $file_name . '.docx';
      }
    }
  }

  public static function makeSureMetaDataInContext($references, &$context) {
    $context['results']['base_destination'] = $references['base_destination'];
    $context['results']['grade_round_name'] = $references['grade_round_name'];
  }

  public static function generateStudentGroupCatalog($student_group_nid, $references, &$context) {
    if (empty($context['results']['catalog'][$student_group_nid])) {
      return;
    }

    /** @var FileTemplateServiceInterface $file_template_service */
    $file_template_service = \Drupal::service('simple_school_reports_core.file_template_service');

    $template_file = 'student_group_grade';
    if (!$file_template_service->getFileTemplateRealPath($template_file)) {
      return;
    }

    /** @var \Drupal\simple_school_reports_core\Service\OrganizationsServiceInterface $organization_service */
    $organization_service = \Drupal::service('simple_school_reports_core.organizations_service');
    $organizer = $organization_service->getOrganization('school_organiser', 'GR');
    $school = $organization_service->getOrganization('school', 'GR');

    $search_replace_map = [];
    $search_replace_map['!grupp!'] = $references['student_groups_data'][$student_group_nid]['name'];
    $search_replace_map['!datum!'] = $references['document_date'];
    $search_replace_map['!r-datum!'] = '- ' . $references['document_date'];
    $search_replace_map['!itg!'] = 'Betyg ska sättas enligt skolförordningen (2011:185)';
    $search_replace_map['!sts!'] = '';
    $search_replace_map['!gspec!'] = 'Lgr11. Betygsbeteckningarna som används är A, B, C, D, E eller F. (F, eleven har icke godkänt resultat för betyg), (-, Underlag för bedömning av elevens kunskaper saknas pga elevens frånvaro) Skollagen (2010:800) 10 kap 17-18 §. Siffran 2 anges i NO och SO för respektive ämne när eleven fått sammanfattande betyg för ämnesblocket eller i ämnesblocket när eleven fått betyg i de enskilda ämnena Skollagen (2010:800) 10 kap. 18 §, SkolFS (2011:123) 10 §. Siffran 2 anges även för svenska eller svenska som andraspråk samt i ämnena moderna språk (elevens val eller språkval), modersmål och teckenspråk när eleven inte läst dessa ämnen. SkolFS (2011:123) 10 §. Siffran 3 anges när ett ämne inte lästs på grund av anpassad studiegång enligt Skollagen (2010:800) 3 kap. 12 §. Anmärkningskolumnen används för rättelse av betyg (anges med siffran 4) och prövning (anges med siffran 5). Efter siffran används förkortning av ämnet.';

    $search_replace_map['!huvudman!'] = $organizer?->label() ?? '';
    $search_replace_map['!skola!'] = $school?->label() ?? '';;
    $search_replace_map['!kommun!'] = $school?->get('municipality')->value ?? '';
    $search_replace_map['!sc!'] = OrganizationsService::getStaticSchoolUnitCode('GR');

    $principle_name = '';
    if (!empty($references['student_groups_data'][$student_group_nid]['principle'])) {
      /** @var \Drupal\user\UserInterface $principle */
      $principle = \Drupal::entityTypeManager()->getStorage('user')->load($references['student_groups_data'][$student_group_nid]['principle']);
      if ($principle) {
        $principle_name = $principle->getDisplayName();
      }
    }
    $search_replace_map['!rektor!'] = $principle_name;

    $file_name = str_replace(' ', '_', 'betygskatalog_' . $references['student_groups_data'][$student_group_nid]['name']) . '_' . $references['term_type_full'];
    $file_name = str_replace('/', '-', $file_name);
    $file_name = str_replace('\\', '-', $file_name);
    $file_name = mb_strtolower($file_name);
    $event = new FileUploadSanitizeNameEvent($file_name, '');
    $dispatcher = \Drupal::service('event_dispatcher');
    $dispatcher->dispatch($event);
    $file_name_base = $event->getFilename();

    $student_grades = $context['results']['catalog'][$student_group_nid];

    $student_uids = [];

    $ordered_student_uids = $references['ordered_student_uids'];
    foreach ($ordered_student_uids as $ordered_student_uid) {
      if (isset($student_grades[$ordered_student_uid])) {
        $student_uids[] = $ordered_student_uid;
      }
    }

    $max_size = Settings::get('ssr_max_grade_student_group_size', 32);
    $parts = ceil(count($student_uids) / $max_size);

    $col_default_values = [];
    $excluded_catalog_label = Settings::get('ssr_excluded_catalog_label', []);
    foreach (Settings::get('ssr_catalog_id', []) as $code => $col) {
      if (isset($excluded_catalog_label[$code])) {
        $col_default_values[$col] = $excluded_catalog_label[$code];
      }
    }

    for ($part = 1; $part <= $parts; $part++) {
      if ($parts > 1) {
        $file_name = $file_name_base . '_' . $part;
      }
      else {
        $file_name = $file_name_base;
      }



      // Set default.
      for($i = 1; $i <= $max_size; $i++) {
        $search_replace_map['!bs' . $i . '!'] = '';
        $search_replace_map['!anm' . $i . '!'] = '';
        $search_replace_map['!p' . $i . '!'] = '';
        $search_replace_map['!e' . $i . '!'] = '';
        for ($col = 1; $col <= 27; $col++) {
          $search_replace_map['!' . $i . '!' . $col . '!'] = '';
        }
      }

      for ($col = 1; $col <= 27; $col++) {
        $search_replace_map['!s' . $col . '!'] = '';
      }



      $row_id = 1;
      foreach (array_slice($student_uids, ($part - 1) * $max_size, $max_size) as $student_uid) {

        $search_replace_map['!e' . $row_id . '!'] = $context['results']['students_name'][$student_uid] ?? '';
        $search_replace_map['!p' . $row_id . '!'] = $context['results']['students_ssn'][$student_uid] ?? 'ååmmdd-nnnn';
        $search_replace_map['!bs'. $row_id . '!'] = !empty($context['results']['invalid_absence'][$student_uid]) ? $context['results']['invalid_absence'][$student_uid] : '';

        // Set default.
        for ($col = 1; $col <= 27; $col++) {
          if (isset($col_default_values[$col])) {
            $search_replace_map['!' . $row_id . '!' . $col . '!'] = $col_default_values[$col];
          }
        }

        foreach ($student_grades[$student_uid] as $catalog_id => $value) {
          $search_replace_map['!' . $row_id . '!' . $catalog_id . '!'] = $value;
        }

        $row_id++;
      }

      if ($file_template_service->generateXlsxFile($template_file, $references['base_destination'] . DIRECTORY_SEPARATOR . 'betygskalatog' . DIRECTORY_SEPARATOR, $file_name, $search_replace_map)) {
        self::makeSureMetaDataInContext($references, $context);
      };

    }
  }

  public static function handleExternalGroupExport($service, $student_group_nid, $references, &$context) {
    try {
      $service = \Drupal::service($service);
      if ($service instanceof GroupGradeExportInterface) {
        $service->handleExport($student_group_nid, $references, $context);
      }
    }
    catch (\Exception $e) {
      // Ignore errors.
    }
  }

  public static function generateTeacherSignDoc($teacher_uid, $student_group_nid, $references, &$context) {
    if (empty($context['results']['sign'][$teacher_uid][$student_group_nid])) {
      return;
    }

    /** @var FileTemplateServiceInterface $file_template_service */
    $file_template_service = \Drupal::service('simple_school_reports_core.file_template_service');

    $template_file = 'teacher_grade_sign';
    if (!$file_template_service->getFileTemplateRealPath($template_file)) {
      return;
    }

    /** @var \Drupal\user\UserInterface $teacher */
    $teacher = \Drupal::entityTypeManager()->getStorage('user')->load($teacher_uid);
    if (!$teacher) {
      return;
    }

    /** @var \Drupal\simple_school_reports_core\Service\OrganizationsServiceInterface $organization_service */
    $organization_service = \Drupal::service('simple_school_reports_core.organizations_service');
    $organizer = $organization_service->getOrganization('school_organiser', 'GR');
    $school = $organization_service->getOrganization('school', 'GR');

    $search_replace_map = [];
    $search_replace_map['!grupp!'] = $references['student_groups_data'][$student_group_nid]['name'];
    $search_replace_map['!datum!'] = $references['document_date'];
    $search_replace_map['!r-datum!'] = '- ' . $references['document_date'];
    $search_replace_map['!UA!'] = 'Underskrift';
    $search_replace_map['!al!'] = 'Ämne';
    $search_replace_map['!grupplabel!'] = 'Klass/Gruppbeteckning';

    $search_replace_map['!huvudman!'] = $organizer?->label() ?? '';
    $search_replace_map['!skola!'] = $school?->label() ?? '';;
    $search_replace_map['!kommun!'] = $school?->get('municipality')->value ?? '';
    $search_replace_map['!sc!'] = OrganizationsService::getStaticSchoolUnitCode('GR');



    foreach ($context['results']['sign'][$teacher_uid][$student_group_nid] as $subject_id => $student_grades) {
      $search_replace_map['!a!'] = !empty($references['subjects'][$subject_id]['name']) ? $references['subjects'][$subject_id]['name'] : '';
      if (!$search_replace_map['!a!']) {
        continue;
      }

      $joint_teacher_map = [];
      $joint_teacher_map_short = [];

      $student_uids = [];
      $ordered_student_uids = $references['ordered_student_uids'];
      foreach ($ordered_student_uids as $ordered_student_uid) {
        if (isset($student_grades[$ordered_student_uid])) {
          $student_uids[] = $ordered_student_uid;
          if (!empty($student_grades[$ordered_student_uid]['joint_grading'])) {
            foreach ($student_grades[$ordered_student_uid]['joint_grading'] as $joint_teacher_uid) {
              $joint_teacher_map[$joint_teacher_uid] = '??? (' . $joint_teacher_uid . ')';
              $joint_teacher_map_short[$joint_teacher_uid] = '??? (' . $joint_teacher_uid . ')';
              /** @var \Drupal\user\Entity\User|null $joint_teacher */
              $joint_teacher = \Drupal::entityTypeManager()->getStorage('user')->load($joint_teacher_uid);
              if ($joint_teacher) {
                $joint_teacher_map[$joint_teacher_uid] = $joint_teacher->getDisplayName();
                $joint_teacher_map_short[$joint_teacher_uid] = ($joint_teacher->get('field_first_name')->value ?? '?');
                // Use only first letter of last name.
                $joint_teacher_map_short[$joint_teacher_uid] .= ' ' . mb_substr($joint_teacher->get('field_last_name')->value ?? '?', 0, 1);
              }
            }
          }
        }
      }

      $max_size = Settings::get('ssr_max_grade_student_group_size', 32);
      $parts = ceil(count($student_uids) / $max_size);

      for ($part = 1; $part <= $parts; $part++) {
        $grading_teachers = [];
        $grading_teachers[$teacher->id()] = $teacher->getDisplayName();

        // Set default.
        for ($i = 1; $i <= $max_size; $i++) {
          $search_replace_map['!ef' . $i . '!'] = '';
          $search_replace_map['!ee' . $i . '!'] = '';
          $search_replace_map['!p' . $i . '!'] = '';
          $search_replace_map['!k' . $i . '!'] = '';
          $search_replace_map['!b' . $i . '!'] = '';
          $search_replace_map['!Anm' . $i . '!'] = '';
        }

        $row_id = 1;
        foreach (array_slice($student_uids, ($part - 1) * $max_size, $max_size) as $student_uid) {
          $search_replace_map['!ef' . $row_id . '!'] = $context['results']['students_first_name'][$student_uid];
          $search_replace_map['!ee' . $row_id . '!'] = $context['results']['students_last_name'][$student_uid];
          $search_replace_map['!p' . $row_id . '!'] = $context['results']['students_ssn'][$student_uid] ?? 'ååmmdd-nnnn';

          $sign_comment = NULL;
          if (!empty($student_grades[$student_uid]['joint_grading'])) {
            $joint_teacher_names_short = [];
            foreach ($student_grades[$student_uid]['joint_grading'] as $joint_teacher_uid) {
              $joint_teacher_name = $joint_teacher_map[$joint_teacher_uid] ?? '??? (' . $joint_teacher_uid . ')';
              $joint_teacher_name_short = $joint_teacher_map_short[$joint_teacher_uid] ?? $joint_teacher_name;
              $joint_teacher_names_short[] = $joint_teacher_name_short;

              if (!isset($grading_teachers[$joint_teacher_uid])) {
                $grading_teachers[$joint_teacher_uid] = $joint_teacher_name;
              }
            }
            $sign_comment = 'Samb. ' .  implode(', ', $joint_teacher_names_short);
          }

          $search_replace_map['!k' . $row_id . '!'] = $sign_comment ?? '';
          $search_replace_map['!b' . $row_id . '!'] = !empty($student_grades[$student_uid]['grade']) ? $student_grades[$student_uid]['grade'] : '';
          $search_replace_map['!Anm' . $row_id . '!'] = !empty($student_grades[$student_uid]['comment']) ? $student_grades[$student_uid]['comment'] : '';
          $row_id++;
        }

        $grading_teachers = array_values($grading_teachers);
        $search_replace_map['!nf1!'] = implode(', ', $grading_teachers);
        $teacher_suffix = count($grading_teachers) > 1 ? 'mfl_' : '';
        $file_name = str_replace(' ', '_', 'sign_' . $references['student_groups_data'][$student_group_nid]['name'] . '_' . $references['subjects'][$subject_id]['full_name'] . '_' . $teacher->getDisplayName() . '_' . $teacher_suffix . $teacher->id());
        $file_name = str_replace('/', '-', $file_name);
        $file_name = str_replace('\\', '-', $file_name);
        $file_name = mb_strtolower($file_name);
        $event = new FileUploadSanitizeNameEvent($file_name, '');
        $dispatcher = \Drupal::service('event_dispatcher');
        $dispatcher->dispatch($event);
        $file_name_base = $event->getFilename();

        if ($parts > 1) {
          $file_name = $file_name_base . '_' . $part;
        }
        else {
          $file_name = $file_name_base;
        }

        if ($file_template_service->generateXlsxFile($template_file, $references['base_destination'] . DIRECTORY_SEPARATOR . 'betygskalatog' . DIRECTORY_SEPARATOR, $file_name, $search_replace_map, 'doc_logo_right')) {
          self::makeSureMetaDataInContext($references, $context);
        }
      }
    }
  }

  public static function handleBeforeFinnishExport($service, $references, &$context) {
    try {
      $service = \Drupal::service($service);
      if ($service instanceof GroupGradeExportInterface) {
        $service->beforeFinishExport($references, $context);
      }
    }
    catch (\Exception $e) {
      // Ignore errors.
    }
  }

  public static function finished($success, $results) {
    if (!$success || empty($results['base_destination'])) {
      \Drupal::messenger()->addError(t('Something went wrong'));
      return;
    }

    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    $source_dir = 'public://ssr_tmp' . DIRECTORY_SEPARATOR . $results['base_destination'] . DIRECTORY_SEPARATOR;
    $source_dir = $file_system->realpath($source_dir);
    /** @var UuidInterface $uuid_service */
    $uuid_service = \Drupal::service('uuid');
    $destination = 'ssr_generated' . DIRECTORY_SEPARATOR . $uuid_service->generate() . DIRECTORY_SEPARATOR;

    $now = new DrupalDateTime();
    $file_name = str_replace(' ', '_', $results['grade_round_name']) . '_' . \Drupal::service('uuid')->generate() . '.zip';
    $event = new FileUploadSanitizeNameEvent($file_name, 'zip');
    $dispatcher = \Drupal::service('event_dispatcher');
    $dispatcher->dispatch($event);
    $file_name = $event->getFilename();

    /** @var FileTemplateServiceInterface $file_template_service */
    $file_template_service = \Drupal::service('simple_school_reports_core.file_template_service');

    if ($file_template_service->doZip($source_dir, $destination, $file_name)) {
      /** @var FileInterface $file */
      $file = \Drupal::entityTypeManager()->getStorage('file')->create([
        'filename' => $file_name,
        'uri' => 'public://' . $destination . $file_name,
      ]);
      $file->save();
      $path = $file->createFileUrl();
      $link = Markup::create('<a href="' . $path . '" target="_blank">' . t('here') . '</a>');
      \Drupal::messenger()->addMessage(t('Grade file generation complete. Save the file in a secure location. This file will shortly be removed from server. Download it now form @link', ['@link' => $link]));
      $file_system->deleteRecursive($source_dir);
      return;
    };
    \Drupal::messenger()->addError(t('Something went wrong'));
  }
}
