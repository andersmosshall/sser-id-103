<?php

namespace Drupal\simple_school_reports_grading_gy\Form;

use Drupal\simple_school_reports_core\SchoolTypeHelper;
use Drupal\simple_school_reports_grade_support\Form\ExportsGradesFormBase;
use Drupal\simple_school_reports_grade_support\GradeSigningInterface;
use Drupal\user\UserInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Provides a form for grades export.
 */
class ExportsGradesFormGy extends ExportsGradesFormBase {

  /**
   * {@inheritdoc}
   */
  public function getConfiguration(): array {
    $config = parent::getConfiguration();

    $config['codes'] = [
      'SK' => 'Utbildning enligt specialinriktad ämnesplan enligt 11 kap. 4 § gymnasieförordningen (2010:2039).',
      'U' => 'Kursen har slutförts utöver det fullständiga programmet enligt 4 kap 24 § gymnasieförordningen.',
      'O' => 'Kursen har omvandlats enligt Skolverkets föreskrifter (SKOLFS 2011:196) om vilka kurser enligt kursplaner som motsvaras av kurser enligt ämnesplaner och får ingå i en gymnasieexamen.',
      'P' => 'Kursen har betygsatts efter prövning. Gäller för den som inte är elev i gymnasieskolan.',
    ];

    $config['final_grade_label'] = 'Examensbevis/Studiebevis';

    $extra_final_grade_confirm_items = [];

    $extra_final_grade_confirm_items['intro'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => 'Modul för studieplan och/eller individual studieplan saknas. Det är därför viktigt att genererade dokument kontrolleras att de är korrekta i avseende för markeringar, program, examensform och liknande.',
    ];

    $extra_final_grade_confirm_items['use_final_grade_calculations'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Calculate document label etc.'),
      '#description' => $this->t('Check this if you want to calculate the document label and other data that the system does not have all the information for. It is still important that any generated documents are checked for accuracy.'),
    ];

    $extra_final_grade_confirm_items['final_grade_calculations_wrapper'] = [
      '#type' => 'details',
      '#title' => $this->t('Calculations settings'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="use_final_grade_calculations"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $extra_final_grade_confirm_items['final_grade_calculations_wrapper']['examina_points_threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Threshold for examina certificate (F)'),
      '#description' => $this->t('The minimum points needed to get an examina certificate (F) otherwise study certificate is used.'),
      '#default_value' => 2500,
      '#min' => 0,
      '#step' => 1,
    ];

    $extra_final_grade_confirm_items['final_grade_calculations_wrapper']['extended_examina_points_threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Threshold for extended examina certificate (U)'),
      '#description' => $this->t('The minimum points needed to get an extended examina certificate (U) otherwise study certificate is used.'),
      '#default_value' => 2600,
      '#min' => 0,
      '#step' => 1,
    ];

    $syllabus_ids = $this->getSyllabusIds();
    $course_options = $this->syllabusService->getSyllabusLabelsInOrder($syllabus_ids);

    if (!empty($course_options)) {
      $extra_final_grade_confirm_items['final_grade_calculations_wrapper']['required_syllabuses'] = [
        '#type' => 'ssr_multi_select',
        '#title' => $this->t('Required syllabuses'),
        '#description' => $this->t('Select the list of syllabuses that are required to have at least approved grade for the examina certificate otherwise study certificate is used.'),
        '#options' => $course_options,
        '#filter_placeholder' => $this->t('Type to search for course syllabuses'),
      ];
    }

    $programme_options = [];
    $student_ids = $this->gradeService->getStudentIdsWithGrades($syllabus_ids);
    $programme_ids = $this->programmeService->getProgrammeIdsInUseBy($student_ids);

    if (!empty($programme_ids)) {
      $programmes = $this->entityTypeManager->getStorage('ssr_programme')->loadMultiple(array_values($programme_ids));
      foreach ($programmes as $programme) {
        $programme_options[$programme->id()] = $programme->label();
      }
    }

    if (!empty($programme_options)) {
      $extra_final_grade_confirm_items['final_grade_calculations_wrapper']['higher_graded_programmes'] = [
        '#type' => 'ssr_multi_select',
        '#title' => $this->t('Higher grade examina certificate'),
        '#description' => $this->t('Select the list of programmes that will result in the higher graded examina certificate.'),
        '#options' => $programme_options,
        '#filter_placeholder' => $this->t('Type to search for programmes'),
      ];
    }

    $config['final_grade_confirm_items']['disclaimer'] = $extra_final_grade_confirm_items;

    $this->config = $config;
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'register_detached_grades_gy';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute(): string {
    return 'view.ssr_grade_reg_rounds.gy';
  }

  /**
   * {@inheritdoc}
   */
  public function getSchoolType(): string {
    return 'GY';
  }

  public function doMakeGradeDocument(string $type, int|string $student_id, ?string $invalid_absence_key, array &$context) {
    $context['ssr_document_context']['skip_replaced'] = TRUE;
    parent::doMakeGradeDocument($type, $student_id, $invalid_absence_key, $context);
    $context['ssr_document_context'] = [];
  }

  protected function prepareGradeSpreadsheet(string $template_path, string $type, UserInterface $student, int $grade_rows, array $info_items, string $label, string $sublabel, string $document_date, ?string $invalid_absence_key, ?string $sign_label, ?string $sign_name, array &$context): ?Spreadsheet {
    $label = 'Betyg';
    $sublabel = 'Gymnasieskolan';

    $student_id = $student->id();

    $form_values = $context['results']['form_values'] ?? [];


    $points = 0;
    $passed_points = 0;
    $examina_type = 'Fullständigt (F)';

    if ($type === 'final_grade_document') {
      // Calculate points and document label.
      $label = 'Examensbevis';

      $use_final_grade_calculations = !empty($form_values['use_final_grade_calculations']);
      $examina_points_threshold = $form_values['examina_points_threshold'] ?? NULL;
      $extended_examina_points_threshold = $form_values['extended_examina_points_threshold'] ?? NULL;
      $required_subject_codes = $form_values['required_subject_codes'] ?? [];
      $higher_graded_programmes = $form_values['higher_graded_programmes'] ?? [];

      $passed_subject_codes = [];

      $grades = $context['results']['student_grades_map'][$student_id] ?? [];
      foreach ($grades as $syllabus_id => $grade_revision_id) {
        /** @var \Drupal\simple_school_reports_grade_support\Utilities\GradeInfo|null $grade_info */
        $grade_info = $context['results']['grade_info'][$grade_revision_id] ?? NULL;
        if (!$grade_info) {
          continue;
        }

        if ($grade_info->replaced) {
          continue;
        }

        $syllabus_info = $this->syllabusService->getSyllabusInfo($syllabus_id);
        if (!$syllabus_info) {
          continue;
        }

        $subject_code = $syllabus_info['subject_code'] ?? NULL;
        $course_points = $this->gradeService->getAggregatedPoints($grade_info) ?? 0;

        if ($this->gradeService->hasGrade($grade_info)) {
          $points += $course_points;
        }

        if ($this->gradeService->isPassed($grade_info)) {
          $passed_subject_codes[] = $subject_code;
          $passed_points += $course_points;
        }
      }

      if ($use_final_grade_calculations) {
        if ($passed_points >= $extended_examina_points_threshold) {
          $label = 'Examensbevis';
          $examina_type = 'Utökat program (U)';
        }
        elseif ($passed_points >= $examina_points_threshold) {
          $label = 'Examensbevis';
          $examina_type = 'Fullständigt program (F)';
        }
        else {
          $label = 'Studiebevis';
          $examina_type = 'Reducerat program (R)';
        }


        if (!empty($required_subject_codes)) {
          foreach ($required_subject_codes as $required_code) {
            if (!in_array($required_code, $passed_subject_codes)) {
              $label = 'Studiebevis';
              break;
            }
          }
        }


        $student_programmes = [];
        $programme = $student->get('field_programme')->entity;
        if ($programme) {
          $student_programmes[] = $programme->id();
          while ($programme?->get('parent')->entity) {
            $programme = $programme->get('parent')->entity;
            if (in_array($programme->id(), $student_programmes)) {
              break;
            }
            $student_programmes[] = $programme->id();
          }
        }

        if (!empty($higher_graded_programmes) && !empty(array_intersect($student_programmes, $higher_graded_programmes))) {
          $sublabel = 'Gymnasieskola - Studieförberedande program';
        }
        else {
          $sublabel = 'Gymnasieskola - Yrkesförberedande program';
        }
      }
    }


    $info_items = [];

    $programme_label = '-';
    $programme_focus_label = '-';
    $programme_focus_code = '-';

    // Add programme.
    /** @var \Drupal\simple_school_reports_entities\ProgrammeInterface $programme */
    $programme = $student->get('field_programme')->entity;
    if ($programme) {
      $programme_type = $programme->get('type')->value;
      if ($programme_type === 'programme_focus') {
        $programme_focus_label = $programme->label();
        $programme_focus_code = $programme->get('code')->value;
        $programme_label = $programme->get('parent')->entity?->label() ?? '-';
      }
      else {
        $programme_label = $programme->label();
      }
    }

    $info_items[] = [
      'label' => 'Program',
      'value' => $programme_label,
    ];
    $info_items[] = [
      'label' => 'Inriktning',
      'value' => $programme_focus_label,
    ];

    if ($type === 'final_grade_document') {
      $info_items[] = [
        'label' => 'Reducerad, fullständigt eller utökat program',
        'value' => $examina_type,
      ];
    }

    $info_items[] = [
      'label' => 'Elev på skolan',
      'value' => 'Ja',
    ];
    $info_items[] = [
      'label' => 'Inriktningskod',
      'value' => $programme_focus_code,
    ];

    if ($type === 'final_grade_document') {
      $info_items[] = [
        'label' => 'Omfattning',
        'value' => number_format($points, 0, ',', ' ') . 'p',
      ];
    }
    $context['ssr_document_context']['skip_replaced'] = TRUE;

    $spreadsheet = parent::prepareGradeSpreadsheet($template_path, $type, $student, $grade_rows, $info_items, $label, $sublabel, $document_date, $invalid_absence_key, $sign_label, $sign_name,$context);

    unset($context['ssr_document_context']['cols']['course_extra']);
    $context['ssr_document_context']['cols']['levels'] = 'C';
    // TODO: resolve kurskod eller nivåkod.
    $context['ssr_document_context']['labels']['course'] = 'Ämne';
    $context['ssr_document_context']['labels']['course_code'] = 'Nivåkod';
    $context['ssr_document_context']['labels']['levels'] = 'Nivåer';

    return $spreadsheet;
  }

  public function prepareGradeSigningSpreadsheet(string $template_path, string $type, GradeSigningInterface $grade_signing, UserInterface $grader, int $grade_rows, array $info_items, string $label, string $sublabel, string $document_date, ?string $sign_label, ?string $sign_name, array &$context): ?Spreadsheet {
    $sublabel = 'Gymnasieskolan';
    return parent::prepareGradeSigningSpreadsheet($template_path, $type, $grade_signing, $grader, $grade_rows, $info_items, $label, $sublabel, $document_date, $sign_label, $sign_name, $context);
  }

  public function prepareGradeCatalogSpreadsheet(string $template_path, string $type, array $syllabus_ids, int $number_of_students, array $info_items, string $label, string $sublabel, string $document_date, ?string $sign_label, ?string $sign_name, string $invalid_absence_key, array &$context): ?Spreadsheet {
    $sublabel = 'Gymnasieskolan';
    return parent::prepareGradeCatalogSpreadsheet($template_path, $type, $syllabus_ids, $number_of_students, $info_items, $label, $sublabel, $document_date, $sign_label, $sign_name, $invalid_absence_key, $context);
  }

}
