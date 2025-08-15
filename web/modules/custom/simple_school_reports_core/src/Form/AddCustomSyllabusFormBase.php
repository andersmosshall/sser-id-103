<?php

namespace Drupal\simple_school_reports_core\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\link\LinkItemInterface;
use Drupal\simple_school_reports_core\SchoolSubjectHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a confirmation form for adding custom syllabus.
 */
abstract class AddCustomSyllabusFormBase extends ConfirmFormBase {

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

  public function getQuestion() {
    return $this->t('Add custom syllabus');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Create syllabus');
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
   *   Subject data is keyed by subject code, and each value is an associative
   *   array with the following keys:
   *   - 'subject_code': The subject code.
   *   - 'subject_name': The label of the subject.
   */
  abstract protected function getSubjectsData(): array;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $subjects_data = $this->getSubjectsData();
    if (empty($subjects_data)) {
      throw new NotFoundHttpException('No subjects data available.');
    }

    $subject_options = [];

    foreach ($subjects_data as $subject_code => $data) {
      $subject_options[$subject_code] = $data['subject_name'];
    }

    $form['subject'] = [
      '#type' => 'select',
      '#title' => $this->t('Subject'),
      '#options' => $subject_options,
      '#required' => TRUE,
    ];

    $form['course_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Course label'),
      '#required' => TRUE,
    ];

    $form['course_short_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Course short label'),
      '#maxlength' => 4,
      '#required' => TRUE,
    ];

    $form['course_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Course code'),
      '#required' => TRUE,
    ];

    $form['link'] = [
      '#type' => 'url',
      '#title' => $this->t('Link to detailed course information'),
    ];

    $form['points'] = [
      '#type' => 'number',
      '#title' => $this->t('Points'),
      '#min' => 0,
      '#max' => 999999,
      '#step' => 1,
    ];

    $form['grade_vid'] = [
      '#type' => 'select',
      '#title' => $this->t('Grade system'),
      '#options' => simple_school_reports_entities_grade_vid_options(),
      '#default' => 'none',
    ];

    $form['language_codes'] = [
      '#type' => 'ssr_multi_select',
      '#title' => $this->t('Language'),
      '#description' => $this->t('Select language(s) to enable for the course that supports language. Ignore if this course does not support different languages.'),
      '#options' => SchoolSubjectHelper::getSupportedLanguageCodes(),
      '#filter_placeholder' => $this->t('Type to search for languages'),
    ];

    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Check for existance.
    $course_code = $form_state->getValue('course_code');
    $language_codes = $form_state->getValue('language_codes', []);
    $has_languages = !empty($language_codes);

    if (!$has_languages) {
      $language_codes[] = 'no_language';
    }

    $syllabus_identifiers = [];
    foreach ($language_codes as $language_code) {
      if ($language_code === 'no_language') {
        $language_code = NULL;
      }
      $course_code_instance = $course_code;
      if ($language_code) {
        $course_code_instance = $course_code . '_' . $language_code;
      }

      $syllabus_identifiers[] = ActivateSyllabusFormBase::calculateSyllabusIdentifier($course_code_instance, $language_code);
    }

    $syllabuses = $this->entityTypeManager->getStorage('ssr_syllabus')->loadByProperties(['identifier' => $syllabus_identifiers]);
    if (!empty($syllabuses)) {
      $message = $has_languages
        ? $this->t('The course code is already occupied with one or more of the selected languages.')
        : $this->t('The course code is already occupied.');
      $form_state->setErrorByName('course_code', $message);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $subject = $form_state->getValue('subject');
    $subjects_data = $this->getSubjectsData()[$subject] ?? NULL;
    if (empty($subjects_data)) {
      $this->messenger()->addError($this->t('Something went wrong.'));
      return;
    }

    $language_codes = $form_state->getValue('language_codes', []);
    $has_languages = !empty($language_codes);

    if (!$has_languages) {
      $language_codes[] = 'no_language';
    }

    $language_label_map = SchoolSubjectHelper::getSupportedLanguageCodes(FALSE, TRUE);

    foreach ($language_codes as $language_code) {
      if ($language_code === 'no_language') {
        $language_code = NULL;
      }

      $label = $form_state->getValue('course_label');
      $short_label = $form_state->getValue('course_short_label');
      $course_code = $form_state->getValue('course_code');

      if ($language_code) {
        $language_label = mb_strtolower($language_label_map[$language_code] ?? '');
        $label .= ', ' . $language_label;
        $short_label .= ':' . $language_code;
        $course_code .= '_' . $language_code;
      }

      $course_data = [
        'label' => $label,
        'short_label' => $short_label,
        'course_code' => $course_code,
        'subject_code' => $subject,
        'subject_name' => $subjects_data['subject_name'],
        'link' => $form_state->getValue('link'),
        'use_langcode' => $has_languages,
        'language_code' => $language_code,
        'official' => FALSE,
        'custom' => TRUE,
        'grade_vid' => $form_state->getValue('grade_vid'),
        'group_for' => [],
        'levels' => [],
        'school_type_versioned' => $this->getSchoolTypeVersioned(),
      ];

      $stored = ActivateSyllabusFormBase::activateCourse($course_code, $course_data);
      if ($stored) {
        $this->messenger()->addStatus($this->t('Syllabus @name created.', ['@name' => $label]));
      }
    }
  }
}
