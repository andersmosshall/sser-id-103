<?php

namespace Drupal\simple_school_reports_extension_proxy\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\simple_school_reports_core\SchoolGradeHelper;
use Drupal\simple_school_reports_entities\SchoolWeekInterface;

/**
 * Controller for School week settings.
 */
class SchoolWeekSettingsController extends ControllerBase {

  public function schoolWeekSettings() {
    $build = [];

    $state = $this->state()->get('ssr_school_week_per_grade', []);

    $grades = SchoolGradeHelper::getSchoolGradesMap();

    $build['info_1'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Here you can set the school week time for each grade/class. This will be used to calculate attendance statistics for students for specific days.'),
    ];

    $build['info_2'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('You may set start and end time for each school day. If not set a school day will be assumed to have its middle at 12:00 in the analyse, adding one hour in each direction. For example for a 300 min long school day, start time will be assumed at 08:30 and end at 15:30.'),
    ];

    $use_classes = $this->moduleHandler()->moduleExists('simple_school_reports_class');

    if ($use_classes) {
      /** @var \Drupal\simple_school_reports_class_support\Service\SsrClassServiceInterface $class_service */
      $class_service = \Drupal::service('simple_school_reports_class_support.class_service');
      $classes = $class_service->getSortedClasses();

      if (empty($classes)) {
        $use_classes = FALSE;
      }
    }

    if ($use_classes) {
      $build['info_3'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('If school week is set for class and grade. The school week for the class will be used.'),
      ];
    }

    $build['info_4'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('You can edit adapted studies as school week for a specific student. That school week setting will take precedence over the grade/class setting.'),
    ];

    $build['grade_wrapper'] = [
      '#type' => 'container',
    ];

    if ($use_classes) {
      $build['grade_wrapper'] = [
        '#type' => 'details',
        '#title' => $this->t('School week for grades'),
        '#open' => FALSE,
      ];
    }

    foreach ($grades as $grade => $label) {
      $school_week_id = $state[$grade] ?? NULL;

      /** @var \Drupal\simple_school_reports_entities\SchoolWeekInterface|null $school_week */
      $school_week = $school_week_id ? $this->entityTypeManager()->getStorage('school_week')->load($school_week_id) : NULL;
      $label = $grade > 0 ? $this->t('Grade @grade', ['@grade' => $grade]) : $label;

      $build['grade_wrapper'][$grade] = $this->buildSchoolWeekRow('grade', $grade, $label, $school_week);
    }

    if ($use_classes) {
      $build['class_wrapper'] = [
        '#type' => 'details',
        '#title' => $this->t('School week for classes'),
        '#open' => FALSE,
      ];

      foreach ($classes as $class) {
        $school_week = $class->get('school_week')->entity ?? NULL;
        $build['class_wrapper'][$class->id()] = $this->buildSchoolWeekRow('class', $class->id(), $class->label(), $school_week);
      }
    }

    return $build;
  }

  protected function buildSchoolWeekRow(string $type, string $key, string $label, ?SchoolWeekInterface $school_week): array {
    $build = [
      '#type' => 'details',
      '#title' => $this->t('School week for @label', ['@label' => $label]),
      '#open' => TRUE,
    ];

    $build['school_week'] = [];
    $build['actions'] = [
      '#type' => 'actions',
    ];
    $destination = Url::fromRoute('simple_school_reports_extension_proxy.school_week_settings')->toString();

    if ($school_week) {
      $build['school_week'] = $school_week->toTable(TRUE);

      $build['actions']['remove'] = [
        '#type' => 'link',
        '#title' => $this->t('Remove'),
        '#url' => Url::fromRoute('entity.school_week.delete_form', ['school_week' => $school_week->id()], ['query' => ['destination' => $destination]]),
        '#attributes' => [
          'class' => ['button', 'button--danger'],
        ],
      ];

      $build['actions']['edit'] = [
        '#type' => 'link',
        '#title' => $this->t('Edit'),
        '#url' => Url::fromRoute('entity.school_week.edit_form', ['school_week' => $school_week->id()], ['query' => ['destination' => $destination]]),
        '#attributes' => [
          'class' => ['button'],
        ],
      ];
    }
    else {
      $build['school_week'] = [
        '#type' => 'html_tag',
        '#tag' => 'em',
        '#value' => $this->t('No school week set for @label', ['@label' => $label]),
      ];

      $build['actions']['add'] = [
        '#type' => 'link',
        '#title' => $this->t('Add'),
        '#url' => Url::fromRoute('entity.school_week.add_form', ['school_week_' . $type . '_set' => $key], ['query' => ['destination' => $destination]]),
        '#attributes' => [
          'class' => ['button'],
        ],
      ];
    }

    return $build;
  }

  public function accessSchoolWeekSettings(AccountInterface $account) {
    if (!ssr_use_schema() && !\Drupal::moduleHandler()->moduleExists('simple_school_reports_attendance_analyse')) {
      return AccessResult::forbidden();
    }

    return AccessResult::allowedIfHasPermission($account, 'administer simple school reports settings');
  }

}
