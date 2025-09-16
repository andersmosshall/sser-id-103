<?php

namespace Drupal\simple_school_reports_grade_support\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\simple_school_reports_core\SchoolTypeHelper;
use Drupal\simple_school_reports_entities\Service\SyllabusServiceInterface;
use Drupal\simple_school_reports_entities\SyllabusInterface;
use Drupal\simple_school_reports_grade_support\GradeInterface;
use Drupal\simple_school_reports_grade_support\GradeRegistrationCourseInterface;
use Drupal\simple_school_reports_grade_support\Service\GradableCourseServiceInterface;
use Drupal\simple_school_reports_grade_support\Service\GradeService;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a form base for grade registration.
 */
abstract class GradeRegistrationFormBase extends ConfirmFormBase {

  protected array $lookup = [];

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected GradableCourseServiceInterface $gradableCourseService,
    protected GradeService $gradeService,
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
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Register grades');
  }

  /**
   * @return string
   */
  abstract public function getCancelRoute(): string;

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
    return $this->t('Save');
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
  public function buildForm(
    array $form,
    FormStateInterface $form_state,
    array $syllabuses = [],
    array $students = [],
    array $grading_teachers = [],
    ?NodeInterface $course = NULL,
  ) {
    if (!$course || $course->bundle() !== 'course') {
      $course = NULL;
    }

    $filtered_syllabuses = [];
    foreach ($syllabuses as $syllabus) {
      if ($syllabus instanceof SyllabusInterface) {
        if (in_array($syllabus->get('school_type_versioned')->value, $this->getSchoolTypeVersions())) {
          $filtered_syllabuses[] = $syllabus;
        }
      }
    }

    $filtered_students = [];
    foreach ($students as $student) {
      if ($student instanceof UserInterface && $student->hasRole('student')) {
        $filtered_students[] = $student;
      }
    }

    $filtered_grading_teachers = [];
    foreach ($grading_teachers as $grading_teacher) {
      if ($grading_teacher instanceof UserInterface && $grading_teacher->hasPermission('school staff permissions')) {
        $filtered_grading_teachers[] = $grading_teacher;
      }
    }

    if (empty($filtered_syllabuses) || empty($filtered_students) || empty($filtered_grading_teachers)) {
      $this->messenger()->addError($this->t('No valid syllabuses, students or grading teachers found.'));
      return $this->redirect($this->getCancelRoute());
    }

    if (count($filtered_syllabuses) * count($filtered_students) > 130) {
      $this->messenger()->addError($this->t('Too many syllabuses, students or grading teachers selected. Please select fewer.'));
      return $this->redirect($this->getCancelRoute());
    }

    $filtered_syllabus_ids = array_map(fn(SyllabusInterface $syllabus) => $syllabus->id(), $filtered_syllabuses);
    $filtered_student_ids = array_map(fn(UserInterface $student) => $student->id(), $filtered_students);

    $form['syllabus_ids'] = [
      '#type' => 'value',
      '#value' => $filtered_syllabus_ids,
    ];

    $form['student_ids'] = [
      '#type' => 'value',
      '#value' => $filtered_student_ids,
    ];

    $form['course_id'] = [
      '#type' => 'value',
      '#value' => $course?->id(),
    ];

    if ($form_state->get('grade_references') === NULL) {
      $grade_references = $this->gradeService->getGradeReferences($filtered_student_ids, $filtered_syllabus_ids);
      $form_state->set('grade_references', $grade_references);
      $grades_info = $this->gradeService->parseGradesFromReferences($grade_references);
      $form_state->set('grades_info', $grades_info);

      // Warm up grade storage cache.
      $grade_ids = [];
      foreach ($filtered_syllabus_ids as $syllabus_id) {
        foreach ($filtered_student_ids as $student_id) {
          $grade_info = !empty($grades_info[$student_id][$syllabus_id])
            ? $grades_info[$student_id][$syllabus_id]
            : NULL;
          if (!$grade_info || !$grade_info->id) {
            continue;
          }
          $grade_ids[] = $grade_info->id;
        }
      }
      if (!empty($grade_ids)) {
        $this->entityTypeManager->getStorage('ssr_grade')->loadMultiple($grade_ids);
      }
    }

    $registration_init_state = hash('sha256', Json::encode($form_state->get('grade_references')));
    $form['registration_init_state'] = [
      '#type' => 'hidden',
      '#value' => $registration_init_state,
    ];


    foreach ($filtered_syllabuses as $key => $syllabus) {
      $form['grades_' . $syllabus->id()] = $this->makeGradeFormSection($form_state, $syllabus, $filtered_students, $filtered_grading_teachers, $course);

      if ($key < count($filtered_syllabuses) - 1) {
        $form['divider_' . $syllabus->id()] = ['#markup' => '<hr>'];
      }
    }

    $grade_registration_courses = $course
      ? $this->getGradeRegistrationCourses($course, $form_state)
      : [];

    if (!empty($grade_registration_courses)) {
      $form['grade_registration_course_status'] = [
        '#type' => 'radios',
        '#title' => $this->t('Mark as done'),
        '#description' => $this->t('Mark all grades in course @label as done.', ['@label' => $course?->label() ?? '?']),
        '#default_value' => NULL,
        '#options' => [
          GradeRegistrationCourseInterface::REGISTRATION_STATUS_DONE => $this->t('Yes'),
          GradeRegistrationCourseInterface::REGISTRATION_STATUS_STARTED => $this->t('No'),
        ],
        '#required' => TRUE,
      ];
    }

    $form['#attached']['library'][] = 'simple_school_reports_grade_support/grade_registration';
    $form['#attributes']['class'][] = 'grade-registration-form';
    return parent::buildForm($form, $form_state);
  }

  protected function getGradeOptions(SyllabusInterface $syllabus): array {
    $grade_vid = $syllabus->get('grade_vid')->value ?? 'none';

    if ($grade_vid === 'none') {
      return [];
    }

    $cid = 'grade_options:' . $grade_vid;
    $cid2 = 'grade_options_category:' . $grade_vid;
    if (array_key_exists($cid, $this->lookup)) {
      $grade_options = $this->lookup[$cid];
      $grade_options_category = $this->lookup[$cid2];
    }
    else {
      /** @var \Drupal\taxonomy\TermStorageInterface $term_storage */
      $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');

      $grade_options = [];
      $grade_items = $term_storage->loadTree($grade_vid, 0, NULL, TRUE);

      $grade_options_category = [];

      /** @var \Drupal\taxonomy\TermInterface $grade_item */
      foreach ($grade_items as $grade_item) {
        $merit = $grade_item->get('field_merit')->value ?? 0;
        $category = 'not_approved';
        if ($merit === 10) {
          $category = 'approved';
        }
        if ($merit > 10) {
          $category = 'extended_approved';
        }
        $grade_options_category[$grade_item->id()] = $category;
        $grade_options[$grade_item->id()] = $grade_item->label();
      }

      $this->lookup[$cid] = $grade_options;
      $this->lookup[$cid2] = $grade_options_category;
    }

    // Diploma project has only approved on non approved grades.
    if ($this->syllabusService->useDiplomaProject($syllabus->id())) {
      $new_grade_options = [];
      foreach ($grade_options as $grade_option_id => $grade_option_label) {
        $category = $grade_options_category[$grade_option_id] ?? '?';
        if ($category === 'not_approved' || $category === 'approved') {
          $new_grade_options[$grade_option_id] = $grade_option_label;
        }
      }
      return $new_grade_options;
    }


    return $grade_options;
  }

  protected function getFieldKey(int|string $syllabus_id, int|string $student_id, string $key): string {
    return 's_' . $syllabus_id . 'u_' . $student_id . '_' . $key;
  }

  protected function getFormValue(int|string $syllabus_id, int|string $student_id, string $key, FormStateInterface $form_state, mixed $fallback = NULL): mixed {
    return $form_state->getValue($this->getFieldKey($syllabus_id, $student_id, $key), $fallback);
  }

  /**
   * @return \Drupal\simple_school_reports_grade_support\GradeRegistrationCourseInterface[]
   */
  protected function getGradeRegistrationCourses(NodeInterface $course, ?FormStateInterface $form_state = NULL): array {
    if (!$form_state) {
      return $this->entityTypeManager->getStorage('ssr_grade_reg_course')->loadByProperties([
        'course' => $course->id(),
      ]);
    }

    if ($form_state->get('grade_registration_courses') === NULL) {
      $grade_registration_courses = $this->entityTypeManager->getStorage('ssr_grade_reg_course')->loadByProperties([
        'course' => $course->id(),
      ]);
      $form_state->set('grade_registration_courses', $grade_registration_courses);
    }

    return $form_state->get('grade_registration_courses') ?? [];
  }

  protected function useJointGrading(SyllabusInterface $syllabus): bool {
    $school_type_versioned = $syllabus->get('school_type_versioned')->value;
    if (!$school_type_versioned) {
      return FALSE;
    }

    $school_type = SchoolTypeHelper::getSchoolTypeFromSchoolTypeVersioned($school_type_versioned);
    return $this->moduleHandler->moduleExists('simple_school_reports_joint_grading_' . mb_strtolower($school_type));
  }

  public function makeGradeFormSection(
    FormStateInterface $form_state,
    SyllabusInterface $syllabus,
    array $students,
    array $grading_teachers,
    ?NodeInterface $course,
  ): array {
    $grade_registration_courses = $course
      ? $this->getGradeRegistrationCourses($course, $form_state)
      : [];

    $stashed_exclude_reasons = [];

    $grade_storage = $this->entityTypeManager->getStorage('ssr_grade');


    /** @var \Drupal\simple_school_reports_grade_support\GradeRegistrationCourseInterface $grade_registration_course */
    foreach ($grade_registration_courses as $grade_registration_course) {
      try {
        $form_data_stash = [];

        $form_data_stash_json = $grade_registration_course->get('form_data_stash')->value ?? NULL;
        if ($form_data_stash_json) {
          $form_data_stash = Json::decode($form_data_stash_json);
        }
      }
      catch (\Exception $e) {
        $form_data_stash = [];
      }

      $stashed_exclude_reasons = !empty($form_data_stash['exclude_reasons'][$syllabus->id()])
        ? $form_data_stash['exclude_reasons'][$syllabus->id()]
        : [];
    }

    $use_joint_grading = $this->useJointGrading($syllabus);

    $form = [];
    $form['label'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t('Grades in @name', ['@name' => $syllabus->label() . ' (' . $syllabus->get('course_code')->value . ')']),
    ];

    $grade_options = $this->getGradeOptions($syllabus);
    if (empty($grade_options)) {
      $form['info'] = [
        '#type' => 'html_tag',
        '#tag' => 'em',
        '#value' => $this->t('Syllabus @name does not support grading.', ['@name' => $syllabus->label()]),
      ];
      return $form;
    }

    $grades_info = $form_state->get('grades_info') ?? [];
    $syllabus_id = $syllabus->id();

    $grading_teacher_options_base = [];
    /** @var \Drupal\user\UserInterface $grading_teacher */
    foreach ($grading_teachers as $grading_teacher) {
      $grading_teacher_options_base[$grading_teacher->id()] = $grading_teacher->getDisplayName();
    }

    foreach ($students as $student) {
      $student_id = $student->id();

      /** @var \Drupal\simple_school_reports_grade_support\Utilities\GradeInfo|null $grade_info */
      $grade_info = !empty($grades_info[$student_id][$syllabus_id])
        ? $grades_info[$student_id][$syllabus_id]
        : NULL;

      $grading_teacher_options = $grading_teacher_options_base;

      $default_main_grader = NULL;
      $main_grader = $grade_info?->mainGrader ?? NULL;
      if ($main_grader) {
        $default_main_grader = $main_grader;
        if (empty($grading_teacher_options[$main_grader])) {
          $main_grader_user = $this->entityTypeManager->getStorage('user')->load($main_grader);
          $grading_teacher_options[$main_grader] = $main_grader_user?->getDisplayName() ?? '??? (' . $main_grader . ')';
        }
      }
      if (count($grading_teacher_options) === 1) {
        $default_main_grader = array_keys($grading_teacher_options)[0];
      }

      $joint_grading_teacher_options = $grading_teacher_options;
      $default_joint_grading_teachers = $grade_info?->jointGraders ?? [];
      foreach ($grade_info?->jointGraders ?? [] as $grader) {
        if (empty($grading_teacher_options[$grader])) {
          $grader_user = $this->entityTypeManager->getStorage('user')->load($grader);
          $joint_grading_teacher_options[$grader] = $grader_user?->getDisplayName() ?? '??? (' . $grader . ')';
        }
      }

      $has_grade = !empty($grade_info?->grade);

      $form['grade_registration'][$student_id]['student'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['student-row']],
      ];

      $form['grade_registration'][$student_id]['student']['info'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['student-row--info-wrapper'],
        ],
      ];

      $form['grade_registration'][$student_id]['student']['info']['name'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['student-row--info-name'],
        ],
        'value' => [
          '#prefix' => '<b>',
          '#suffix' => '</b>',
          '#markup' => $student->getDisplayName(),
        ],
      ];

      $form['grade_registration'][$student_id]['student']['grade_registration'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['student-row--report-wrapper'],
        ],
      ];

      $form['grade_registration'][$student_id]['student']['grade_registration']['grade_info'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['student-row--report-wrapper--grade-info'],
        ],
      ];

      // Make exclude reason options.
      $exclude_reason_options = [];
      if ($grade_info?->gradeTid && $this->gradeService->getGradeLabel($grade_info)) {
        $exclude_reason_options['keep'] = $this->t('No changes. Keep grade @grade by @main_grader - @registered', [
          '@grade' => $this->gradeService->getGradeLabel($grade_info),
          '@main_grader' => $grading_teacher_options[$main_grader],
          '@registered' => $grade_info?->date?->format('Y-m-d') ?? '',
        ]);
      }

      if (!$grade_info?->id) {
        $exclude_reason_options['n_a'] = !empty($grade_registration_courses)
          ? $this->t('Student does not study the course')
          : $this->t('Skip grade registration now / Not applicable');
      }
      else {
        /** @var \Drupal\simple_school_reports_grade_support\GradeInterface|null $grade */
        $grade = $grade_storage->load($grade_info?->id ?? 0);
        $allow_delete = !$grade;
        if ($this->currentUser()->hasPermission('administer simple_school_reports_grade_support')) {
          $allow_delete = TRUE;
        }
        if ((int) $this->currentUser()->id() === $main_grader) {
          $allow_delete = TRUE;
        }
        if ($allow_delete) {
          if ($grade_info->gradeTid) {
            $exclude_reason_options['n_a'] = $this->t('Delete grade @grade by @main_grader - @registered. (This action cannot be undone.)', [
              '@grade' => $this->gradeService->getGradeLabel($grade_info) ?? $this->t('Unknown grade'),
              '@main_grader' => $grading_teacher_options[$main_grader] ?? '?',
              '@registered' => $grade_info?->date?->format('Y-m-d') ?? '',
            ]);
          } else {
            $exclude_reason_options['n_a'] = $this->t('Delete grade @grade - @registered. (This action cannot be undone.)', [
              '@grade' => $this->gradeService->getGradeLabel($grade_info) ?? $this->t('Unknown grade'),
              '@registered' => $grade_info?->date?->format('Y-m-d') ?? '',
            ]);
          }
        }
      }

      $exclude_reason_options['adapted_studies'] = $this->t('Adapted studies');
      if (!empty($grade_registration_courses)) {
        $exclude_reason_options['pending'] = $this->t('Grade will be set later');
      }

      // Set default exclude_reason.
      $default_exclude_reason = isset($exclude_reason_options['keep']) ? 'keep' : NULL;

      if (isset($stashed_exclude_reasons[$student_id]) && isset($exclude_reason_options[$stashed_exclude_reasons[$student_id]])) {
        $default_exclude_reason = $stashed_exclude_reasons[$student_id];
      }

      if ($grade_info?->excludeReason && isset($exclude_reason_options[$grade_info?->excludeReason])) {
        $default_exclude_reason = $grade_info?->excludeReason;
      }

      $exclude_key = $this->getFieldKey($syllabus_id, $student_id, 'exclude');

      $form['grade_registration'][$student_id]['student']['grade_registration']['grade_info'][$exclude_key] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Exclude student / Set later'),
        '#default_value' => $default_exclude_reason !== NULL,
      ];

      $exclude_reason_key = $this->getFieldKey($syllabus_id, $student_id, 'exclude_reason');

      $form['grade_registration'][$student_id]['student']['grade_registration']['grade_info']['exclude_reason'] = [
        '#type' => 'container',
        '#states' => [
          'visible' => [
            ':input[name="' . $exclude_key . '"]' => [
              'checked' => TRUE,
            ],
          ],
        ],
        $exclude_reason_key => [
          '#type' => 'radios',
          '#title' => $this->t('Select reason for excluded student'),
          '#default_value' => $default_exclude_reason,
          '#options' => $exclude_reason_options,
        ],
      ];

      $form['grade_registration'][$student_id]['student']['grade_registration']['grade_wrapper'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['student-row--report-wrapper--grade'],
        ],
        '#states' => [
          'invisible' => [
            ':input[name="' . $exclude_key . '"]' => [
              'checked' => TRUE,
            ],
          ],
        ],
      ];


      $default_grade = NULL;
      if ($grade_info?->gradeTid && isset($grade_options[$grade_info?->gradeTid])) {
        $default_grade = $grade_info?->gradeTid;
      }

      $form['grade_registration'][$student_id]['student']['grade_registration']['grade_wrapper']['grade_select_wrapper'] = [
        '#type' => 'container',
      ];

      $form['grade_registration'][$student_id]['student']['grade_registration']['grade_wrapper']['grade_select_wrapper'][$this->getFieldKey($syllabus_id, $student_id, 'grade')] = [
        '#title' => $this->t('Grade'),
        '#type' => 'select',
        '#empty_option' => $this->t('Not set'),
        '#options' => $grade_options,
        '#default_value' => $default_grade,
      ];

      $form['grade_registration'][$student_id]['student']['grade_registration']['grade_wrapper']['grade_select_wrapper'][$this->getFieldKey($syllabus_id, $student_id, 'trial')] = [
        '#title' => $this->t('Grade from trial'),
        '#type' => 'checkbox',
        '#default_value' => $grade_info?->trial ?? FALSE,
      ];

      $form['grade_registration'][$student_id]['student']['grade_registration']['grade_wrapper'][$this->getFieldKey($syllabus_id, $student_id, 'main_grader')] = [
        '#title' => t('Grading teacher'),
        '#type' => 'select',
        '#empty_option' => t('Not set'),
        '#options' => $grading_teacher_options,
        '#default_value' => $default_main_grader,
      ];

      if (count($joint_grading_teacher_options) > 1 && $use_joint_grading) {
        $form['grade_registration'][$student_id]['student']['grade_registration']['grade_wrapper'][$this->getFieldKey($syllabus_id, $student_id, 'joint_graders')] = [
          '#title' => t('Joint grader'),
          '#type' => 'checkboxes',
          '#options' => $joint_grading_teacher_options,
          '#default_value' => $default_joint_grading_teachers,
        ];
      }

//      $default_remark = $grade_info?->remark ?? '';;
//      $form['grade_registration'][$student_id]['student']['grade_registration']['grade_wrapper'][$this->getFieldKey($syllabus_id, $student_id, 'remark')] = [
//        '#title' => t('Short comment'),
//        '#description' => t('The short comment will be shown in generated grade document'),
//        '#type' => 'textfield',
//        '#default_value' => $default_remark,
//        '#maxlength' => 10,
//      ];
      // TODO: Replace with remark above when/if we need it. For now use a filler.
      $form['grade_registration'][$student_id]['student']['grade_registration']['grade_wrapper'][$this->getFieldKey($syllabus_id, $student_id, 'remark')] = [
        '#type' => 'container',
      ];


      // TODO: Handle diploma project label/description.
      if ($this->syllabusService->useDiplomaProject($syllabus_id)) {
        $form['grade_registration'][$student_id]['student']['grade_registration']['diploma_project_wrapper'] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['student-row--report-wrapper--diploma-project'],
          ],
          '#states' => [
            'invisible' => [
              ':input[name="' . $exclude_key . '"]' => [
                'checked' => TRUE,
              ],
            ],
          ],
        ];

        /** @var \Drupal\simple_school_reports_grade_support\GradeInterface|null $grade */
        $grade = $grade_info?->id
          ? $grade_storage->load($grade_info->id)
          : NULL;

        $default_diploma_project_label = $grade?->get('diploma_project_label')->value ?? NULL;
        $default_diploma_project_description = $grade?->get('diploma_project_description')->value ?? NULL;

        $form['grade_registration'][$student_id]['student']['grade_registration']['diploma_project_wrapper'][$this->getFieldKey($syllabus_id, $student_id, 'diploma_project_label')] = [
          '#type' => 'textfield',
          '#title' => t('Diploma project label'),
          '#default_value' => $default_diploma_project_label,
          '#maxlength' => 250,
        ];

        $form['grade_registration'][$student_id]['student']['grade_registration']['diploma_project_wrapper'][$this->getFieldKey($syllabus_id, $student_id, 'diploma_project_description')] = [
          '#type' => 'textarea',
          '#title' => t('Diploma project description'),
          '#default_value' => $default_diploma_project_description,
          '#maxlength' => 250,
        ];
      }




      if ($grade_info) {
        $exclude_reason_key_lookup = $exclude_reason_key;
        if ($grade_info?->excludeReason === 'adapted_studies') {
          $exclude_reason_key_lookup = 'no_adapted_studies_change';
        }


        $form['grade_registration'][$student_id]['student']['grade_registration']['update_reason_wrapper'] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['student-row--update-reason-wrapper'],
          ],
          '#states' => [
            'visible' => [
              ':radio[name="' . $exclude_reason_key_lookup . '"]' => ['value' => 'adapted_studies'],
            ],
            'invisible' => [
              ':input[name="' . $exclude_key . '"]' => ['checked' => TRUE],
            ],
          ],
        ];

        $update_reason_options = [
          GradeInterface::CORRECTION_TYPE_CORRECTED => t('Correction, (e.g. wrong registration, updated with correct value.)'),
          GradeInterface::CORRECTION_TYPE_CHANGED => t('Change, (e.g. the grade has changed for example due to a new examination)'),
        ];
        $default_update_reason = GradeInterface::CORRECTION_TYPE_CORRECTED;
        $form['grade_registration'][$student_id]['student']['grade_registration']['update_reason_wrapper'][$this->getFieldKey($syllabus_id, $student_id, 'correction_type')] = [
          '#type' => 'radios',
          '#title' => t('Update reason'),
          '#default_value' => $default_update_reason,
          '#options' => $update_reason_options,
        ];
      }
    }

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $step = $form_state->get('step') ?? NULL;
    if ($step === 1) {
      return;
    }

    $student_ids = $form_state->getValue('student_ids', []);
    $syllabus_ids = $form_state->getValue('syllabus_ids', []);
    $grade_references = $this->gradeService->getGradeReferences($student_ids, $syllabus_ids);
    $registration_init_state = hash('sha256', Json::encode($grade_references));

    if ($registration_init_state !== $form_state->getValue('registration_init_state')) {
      $form_state->setError($form, $this->t('Someone has made changes to one or more grades. Please refresh the page and try again.'));
      return;
    }

    $is_done = $form_state->getValue('grade_registration_course_status', GradeRegistrationCourseInterface::REGISTRATION_STATUS_DONE) === GradeRegistrationCourseInterface::REGISTRATION_STATUS_DONE;

    foreach ($syllabus_ids as $syllabus_id) {
      foreach ($student_ids as $student_id) {
        $excluded = (bool) $this->getFormValue($syllabus_id, $student_id, 'exclude', $form_state, FALSE);
        $exclude_reason = $excluded
          ? $this->getFormValue($syllabus_id, $student_id, 'exclude_reason', $form_state)
          : NULL;

        if ($excluded && $is_done && $exclude_reason === 'pending') {
          $form_state->setErrorByName($this->getFieldKey($syllabus_id, $student_id, 'exclude_reason'), $this->t('You can not mark registration as done if there are pending grades to set.'));
          $form_state->setErrorByName('grade_registration_course_status', $this->t('You can not mark registration as done if there are pending grades to set.'));
          continue;
        }

        if ($excluded && empty($exclude_reason)) {
          $form_state->setErrorByName($this->getFieldKey($syllabus_id, $student_id, 'exclude_reason'), $this->t('You must select a reason for excluding the grade registraion for this student.'));
        }

        if ($excluded) {
          continue;
        }

        $grade_tid = $this->getFormValue($syllabus_id, $student_id, 'grade', $form_state);
        if (!$grade_tid) {
          $form_state->setErrorByName($this->getFieldKey($syllabus_id, $student_id, 'grade'), t('@name field is required.', ['@name' => $this->t('Grade')]));
        }

        $main_grader = $this->getFormValue($syllabus_id, $student_id, 'main_grader', $form_state);
        if (!$main_grader) {
          $form_state->setErrorByName($this->getFieldKey($syllabus_id, $student_id, 'main_grader'), t('@name field is required.', ['@name' => $this->t('Grading teacher')]));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $transaction = $this->database->startTransaction();
    try {
      $student_ids = $form_state->getValue('student_ids', []);
      $syllabus_ids = $form_state->getValue('syllabus_ids', []);
      $course_id = $form_state->getValue('course_id');
      $grades_info = $form_state->get('grades_info') ?? [];

      $grade_storage = $this->entityTypeManager->getStorage('ssr_grade');

      /** @var \Drupal\node\NodeInterface|null $course */
      $course = $course_id
        ? $this->entityTypeManager->getStorage('node')->load($course_id)
        : NULL;

      $form_data_stash = [];
      foreach ($syllabus_ids as $syllabus_id) {
        foreach ($student_ids as $student_id) {
          $excluded = (bool) $this->getFormValue($syllabus_id, $student_id, 'exclude', $form_state, FALSE);
          $exclude_reason = $excluded
            ? $this->getFormValue($syllabus_id, $student_id, 'exclude_reason', $form_state)
            : NULL;

          // If set to be kept, don't do anything.
          if ($excluded && $exclude_reason === 'keep') {
            continue;
          }

          /** @var \Drupal\simple_school_reports_grade_support\Utilities\GradeInfo|null $grade_info */
          $grade_info = !empty($grades_info[$student_id][$syllabus_id])
            ? $grades_info[$student_id][$syllabus_id]
            : NULL;


          /** @var \Drupal\simple_school_reports_grade_support\GradeInterface|null $grade */
          $grade = $grade_info?->id
            ? $grade_storage->load($grade_info->id)
            : NULL;

          $grade_valid_exclude_reasons = [
            GradeInterface::EXCLUDE_REASON_ADAPTED_STUDIES,
          ];

          if ($excluded && !in_array($exclude_reason, $grade_valid_exclude_reasons)) {
            $form_data_stash['exclude_reasons'][$syllabus_id][$student_id] = $exclude_reason;

            if ($exclude_reason === 'n_a') {
              $grade?->delete();
            }

            continue;
          }

          $original_grade = $grade ? clone $grade : NULL;
          if (!$grade) {
            $grade = $grade_storage->create([
              'langcode' => 'sv',
            ]);
          }
          /** @var \Drupal\simple_school_reports_grade_support\GradeInterface $grade */


          $grade->set('status', TRUE);
          $grade->set('syllabus', ['target_id' => $syllabus_id]);
          $grade->set('student', ['target_id' => $student_id]);

          $grade->set('main_grader', ['target_id' => $this->getFormValue($syllabus_id, $student_id, 'main_grader', $form_state)]);

          $joint_graders = $this->getFormValue($syllabus_id, $student_id, 'joint_graders', $form_state, []);
          $joint_grading_by_value = [];
          foreach ($joint_graders as $uid => $value) {
            if (!$value) {
              continue;
            }
            $joint_grading_by_value[] = ['target_id' => $uid];
          }
          $grade->set('joint_grading_by', $joint_grading_by_value);

          $grade->set('course', $course);

          $grade_tid = $this->getFormValue($syllabus_id, $student_id, 'grade', $form_state);
          $grade->set('grade', $grade_tid ? ['target_id' => $grade_tid] : NULL);
          $grade->set('trial', $this->getFormValue($syllabus_id, $student_id, 'trial', $form_state, FALSE));
          $grade->set('exclude_reason', $exclude_reason);

          $grade->set('remark', $this->getFormValue($syllabus_id, $student_id, 'remark', $form_state));

          $correction_type = $grade->isNew()
            ? NULL
            : $this->getFormValue($syllabus_id, $student_id, 'correction_type', $form_state, GradeInterface::CORRECTION_TYPE_CORRECTED);
          $grade->set('correction_type', $correction_type);

          $grade->set('diploma_project_label', $this->getFormValue($syllabus_id, $student_id, 'diploma_project_label', $form_state));
          $grade->set('diploma_project_description', $this->getFormValue($syllabus_id, $student_id, 'diploma_project_description', $form_state));

          $grade->sanitizeFields();
          if (!$grade->hasChanges($original_grade)) {
            continue;
          }

          $violations = $grade->validate();
          if (count($violations) > 0) {
            $violation_messages = [];
            foreach ($violations as $violation) {
              $property_path = $violation->getPropertyPath();
              $violation_messages[$property_path] = $violation->getMessage();
            }

            $log_message = 'Grade validation failed for student @student_id and syllabus @syllabus_id, @violations';
            $tokens = [
              '@student_id' => $student_id,
              '@syllabus_id' => $syllabus_id,
              '@violations' => Json::encode($violation_messages),
            ];
            // Replace tokens in log message.
            foreach ($tokens as $token => $value) {
              $log_message = str_replace($token, $value, $log_message);
            }

            throw new \Exception($log_message);
          }

          $grade->save();
        }
      }

      $grade_registration_courses = $course
        ? $this->getGradeRegistrationCourses($course)
        : [];

      $new_grade_registration_courses_status = $form_state->getValue('grade_registration_course_status', GradeRegistrationCourseInterface::REGISTRATION_STATUS_NOT_STARTED);

      foreach ($grade_registration_courses as $grade_registration_course) {
        if ($grade_registration_course->get('registration_status')->value === GradeRegistrationCourseInterface::REGISTRATION_STATUS_DONE) {
          $grade_registration_course->set('form_data_stash', NULL);
          $grade_registration_course->save();
          continue;
        }

        $is_done = $new_grade_registration_courses_status === GradeRegistrationCourseInterface::REGISTRATION_STATUS_DONE;

        $grade_registration_course->set('registration_status', $new_grade_registration_courses_status);
        $grade_registration_course->set('form_data_stash', $is_done ? NULL : Json::encode($form_data_stash));
        $grade_registration_course->save();
      }

    } catch (\Exception $e) {
      $transaction->rollback();
      $this->messenger()->addError($this->t('Something went wrong. Try again.'));
      \Drupal::logger('simple_school_reports_grade_support')->error('Something went wrong registering grades. ' . $e->getMessage());
      $form_state->setRebuild(TRUE);
      return;
    }

    $this->messenger()->addStatus($this->t('Grades has been registered.'));
    $form_state->setRedirect($this->getCancelRoute());
  }
}
