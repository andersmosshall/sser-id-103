<?php

namespace Drupal\simple_school_reports_core\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\simple_school_reports_core\SchoolSubjectHelper;
use Drupal\simple_school_reports_entities\SyllabusInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a confirmation form for enabling syllabuses.
 */
abstract class ActivateSyllabusFormBase extends ConfirmFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ResetInvalidAbsenceMultipleForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
    );
  }

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
    return $this->t('Activate');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
  }

  /**
   * @return string
   */
  abstract protected function getSchoolTypeVersioned(): string;

  /**
   * @return array
   *   Course data is keyed by course code, and each value is an associative
   *   array with the following keys:
   *   - 'label': The label of the course.
   *   - 'course_code': The course code.
   *   - 'subject_code': The subject code.
   *   - 'subject_name': The label of the subject.
   *   - 'link': External link for detailed course info.
   *   - 'points': The points of with the course if applicable.
   *   - 'use_langcode': If the course is associated with a language.
   *   - 'official': Whether the course is official or not.
   *   - 'group_for': The course codes this cours is a group for, if applicable.
   *   - 'levels': The course codes this course is levels for, if applicable.
   *   - 'grade_vid': The vocabulary ID for the grade taxonomy term, or 'none'.
   */
  abstract protected function getCourseData(): array;

  abstract protected function getCourseShortLabel(array $course_data): string;

  public static function getSuccessMessage(): string {
    return t('Syllabuses have been activated.');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $course_data = $this->getCourseData();
    if (empty($course_data)) {
      throw new NotFoundHttpException('No course data available.');
    }

    $course_options = [];
    $use_langcode = FALSE;

    foreach ($course_data as $course_code => $data) {
      $course_options[$course_code] = $data['label'];
      if (isset($data['use_langcode']) && $data['use_langcode']) {
        $use_langcode = TRUE;
      }
    }

    $form['courses'] = [
      '#type' => 'ssr_multi_select',
      '#title' => $this->t('Course syllabuses'),
      '#options' => $course_options,
      '#required' => TRUE,
      '#filter_placeholder' => $this->t('Type to search for course syllabuses'),
    ];

    if ($use_langcode) {
      $form['language_code'] = [
        '#type' => 'ssr_multi_select',
        '#title' => $this->t('Language'),
        '#description' => $this->t('Select language(s) to enable for courses that supports languages. For courses that do not support languages, this option will be ignored.'),
        '#options' => SchoolSubjectHelper::getSupportedLanguageCodes(),
        '#filter_placeholder' => $this->t('Type to search for languages'),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $courses = $form_state->getValue('courses');

    $language_codes = $form_state->getValue('language_code', []);

    $language_label_map = SchoolSubjectHelper::getSupportedLanguageCodes(FALSE, TRUE);

    $course_data_src = $this->getCourseData();
    $course_data = [];

    foreach ($course_data_src as $course_code => $data) {
      if (!in_array($course_code, $courses)) {
        continue;
      }

      if (!empty($data['use_langcode'])) {
        if (empty($language_codes)) {
          $this->messenger()->addWarning($this->t('Language is required for %course. Skipping.', ['%course' => $data['label']]));
          continue;
        }

        foreach ($language_codes as $langcode) {
          $course_key = $course_code . '_' . $langcode;

          $language_label = mb_strtolower($language_label_map[$langcode] ?? $langcode);

          $course_data[$course_key] = $data;
          $course_data[$course_key]['label'] = $data['label'] . ', ' . $language_label;
          $course_data[$course_key]['course_code'] = $course_code . '_' . $langcode;
          $course_data[$course_key]['language_code'] = $langcode;
          $course_data[$course_key]['short_label'] = $this->getCourseShortLabel($course_data[$course_key]);

          $course_data[$course_key]['subject_name'] = $course_data[$course_key]['subject_name'] . ', ' . $language_label;

          $levels = $course_data[$course_key]['levels'] ?? [];
          foreach ($levels as $key => $level) {
            $levels[$key] = $level . '_' . $langcode;
          }
          $course_data[$course_key]['levels'] = $levels;
        }
        continue;
      }

      $data['short_label'] = $this->getCourseShortLabel($data);
      $course_data[$course_code] = $data;
    }

    if (empty($course_data)) {
      $this->messenger()->addError($this->t('No valid courses selected.'));
      return;
    }

    // Initialize batch (to set title).
    $batch = [
      'title' => $this->t('Activate courses'),
      'init_message' => $this->t('Activate courses'),
      'progress_message' => $this->t('Processed @current out of @total.'),
      'finished' => [self::class, 'finished'],
      'operations' => [],
    ];

    foreach ($course_data as $course_code => $data) {
      $batch['operations'][] = [
        [static::class, 'activateCourse'],
        [$course_code, $data],
      ];
    }
    $this->modifyBatch($batch);

    batch_set($batch);
  }

  protected function modifyBatch(array &$batch) {
    // Override this method in child classes to modify the batch data.
  }

  public static function calculateSyllabusIdentifier(?string $course_code = NULL, ?string $language_code = NULL): string {
    if (!$course_code) {
      return 'INVALID';
    }
    $identifier = $course_code;
    if ($language_code) {
      $identifier .= ':' . $language_code;
    }
    return mb_strtoupper($identifier);
  }

  public static function activateCourse(string $course_code, array $data): bool {
    $syllabus_storage = \Drupal::entityTypeManager()->getStorage('ssr_syllabus');

    $identifier = self::calculateSyllabusIdentifier($course_code, $data['language_code'] ?? NULL);

    $syllabus = current($syllabus_storage->loadByProperties(['identifier' => $identifier]));
    if (!$syllabus) {
      // Create a new syllabus if it does not exist.
      $syllabus = $syllabus_storage->create([
        'langcode' => 'sv',
      ]);
    }
    /** @var \Drupal\simple_school_reports_entities\SyllabusInterface $syllabus */

    $syllabus->set('label', $data['label']);
    $syllabus->set('short_label', $data['short_label']);
    $syllabus->set('identifier', $identifier);
    $syllabus->set('school_type_versioned', $data['school_type_versioned']);

    if (!empty($data['levels'])) {
      $syllabus->set('levels', Json::encode($data['levels']));
    } else {
      $syllabus->set('levels', NULL);
    }

    if (!empty($data['link'])) {
      $syllabus->set('link', $data['link']);
    } else {
      $syllabus->set('link', NULL);
    }

    $group_for_identifiers = [];
    $language_code = $data['language_code'] ?? NULL;

    foreach ($data['group_for'] ?? [] as $group_course_code) {
      $group_for_identifier = self::calculateSyllabusIdentifier($group_course_code, $language_code);
      if (!$group_for_identifier || $group_for_identifier === 'INVALID') {
        continue;
      }
      $group_for_identifiers[] = $group_for_identifier;
    }

    $group_for_syllabuses = [];
    if (!empty($group_for_identifiers)) {
      $group_for_syllabuses = $syllabus_storage->loadByProperties(['identifier' => $group_for_identifiers]);
    }

    $syllabus->set('group_for', $group_for_syllabuses);
    $syllabus->set('subject_code', $data['subject_code']);
    $syllabus->set('subject_name', $data['subject_name']);
    $syllabus->set('subject_designation', $data['subject_designation'] ?? $data['subject_code']);

    $syllabus->set('course_code', $data['course_code']);
    $syllabus->set('language_code', $data['language_code'] ?? NULL);
    $syllabus->set('points', $data['points'] ?? NULL);
    $syllabus->set('official', $data['official'] ?? FALSE);
    $syllabus->set('custom', $data['custom'] ?? FALSE);

    $syllabus->set('status', TRUE);
    if (array_key_exists('status', $data)) {
      $syllabus->set('status', $data['status']);
    }

    $syllabus->set('grade_vid', $data['grade_vid'] ?? 'none');

    if (!empty($data['subject_target_id'])) {
      $syllabus->set('school_subject', ['target_id' => $data['subject_target_id']]);
    }

    $violations = $syllabus->validate();
    if (count($violations) > 0) {
      // Missing school subject can be accepted as it is then set in presave.
      if (count($violations) === 1 && count($violations->getByField('school_subject')) === 1) {
        // Do nothing if the "only" error is missing subject, proceed as
        // normal.
      } else {
        \Drupal::messenger()->addError(t('Failed to activate course %course', ['%course' => $data['label']]));
        return FALSE;
      }
    }

    $syllabus->save();
    return TRUE;
  }

  public static function finished($success, $results) {
    if ($success) {
      \Drupal::messenger()->addStatus(self::getSuccessMessage());
    }
    else {
      \Drupal::messenger()->addError(t('Something went wrong.'));
    }
  }

  public static function handleSubjectTarget(SyllabusInterface $syllabus, string $school_type) {
    if ($syllabus->get('school_type_versioned')->value !== $school_type) {
      return;
    }

    $syllabus->set('identifier', self::calculateSyllabusIdentifier($syllabus->get('course_code')->value, $syllabus->get('language_code')->value));

    /** @var \Drupal\taxonomy\TermInterface|null $school_subject */
    $school_subject = $syllabus->get('school_subject')->entity;
    if ($school_subject && $syllabus->isPublished() && !$school_subject->isPublished()) {
      $school_subject->setPublished(TRUE);
      $school_subject->save();
    }

    if (!$school_subject) {
      // Load school subject or create a new one if it does not exist.
      $subject_code = $syllabus->get('subject_code')->value;
      if (!$subject_code) {
        $subject_code = 'COA';
        $syllabus->set('subject_code', 'COA');
        $syllabus->set('subject_designation', 'COA');
        $syllabus->set('subject_name', 'Övriga ämnen');
      }

      $load_properties = [
        'vid' => 'school_subject',
        'field_subject_code_new' => $subject_code,
        'field_school_type_versioned' => $school_type,
      ];
      $language_code = $syllabus->get('language_code')->value ?? NULL;
      if ($language_code) {
        $load_properties['field_language_code'] = $language_code;
      }

      $school_subject_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
      $school_subject = NULL;
      $school_subject_alts = $school_subject_storage->loadByProperties($load_properties);
      /** @var \Drupal\taxonomy\TermInterface $school_subject_alt */
      foreach ($school_subject_alts as $school_subject_alt) {
        if (!$school_subject_alt->get('field_subject_specify')->isEmpty()) {
          continue;
        }
        if ($school_subject_alt->get('field_language_code')->value === $language_code) {
          $school_subject = $school_subject_alt;
          break;
        }
      }
      if (!$school_subject) {
        // Create a new school subject if it does not exist.
        $school_subject = $school_subject_storage->create([
          'vid' => 'school_subject',
          'name' => $syllabus->get('subject_name')->value,
          'field_subject_code_new' => $syllabus->get('subject_code')->value,
          'field_school_type_versioned' => $school_type,
        ]);
        if ($language_code) {
          $school_subject->set('field_language_code', $language_code);
        }
      }

      $school_subject->setPublished(TRUE);
      $school_subject->save();
      $syllabus->set('school_subject', $school_subject);
    }
  }
}
