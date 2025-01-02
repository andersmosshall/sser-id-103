<?php

namespace Drupal\simple_school_reports_core\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\simple_school_reports_core\AbsenceDayHandler;
use Drupal\simple_school_reports_core\SchoolSubjectHelper;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a confirmation form for adding school subject.
 */
class AddLanguageSchoolSubjectForm extends ConfirmFormBase {

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

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'add_language_school_subject_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Add language school subject');
  }

  public function getCancelRoute() {
    return 'view.school_subjects.school_subjects';
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
  public function buildForm(array $form, FormStateInterface $form_state) {
    $subject_options = SchoolSubjectHelper::getSupportedLanguageSubjectCodes();
    $form['subject_code'] = [
      '#type' => 'select',
      '#title' => $this->t('Subject code'),
      '#empty_option' => $this->t('none'),
      '#options' => $subject_options,
      '#required' => TRUE,
    ];

    $language_options = SchoolSubjectHelper::getSupportedLanguageCodes();
    $form['language_code'] = [
      '#type' => 'select',
      '#title' => $this->t('Language code'),
      '#empty_option' => $this->t('none'),
      '#options' => $language_options,
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $subject_code = $form_state->getValue('subject_code');
    $language_code = $form_state->getValue('language_code');

    $exist = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties(['vid' => 'school_subject', 'field_subject_code' => $subject_code, 'field_language_code' => $language_code,]);
    if ($exist) {
      $form_state->setError($form, $this->t('There is already a school subject with this settings.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect($this->getCancelRoute());

    $subject_options = SchoolSubjectHelper::getSupportedLanguageSubjectCodes();
    $raw_name = $subject_options[$form_state->getValue('subject_code')] ?? '() ?';
    $parts = explode(') ', $raw_name);
    $name = count($parts) >= 2 ? $parts[1] : $raw_name;

    $subject_code = $form_state->getValue('subject_code');
    $language_code = $form_state->getValue('language_code');

    $language_options = SchoolSubjectHelper::getSupportedLanguageCodes();
    $raw_name = $language_options[$form_state->getValue('language_code')] ?? '() ?';
    $parts = explode(' ', $raw_name);
    // First part is the code.
    array_shift($parts);
    $language_name = implode(' ', $parts);
    $language_name = mb_strtolower($language_name);
    $name .= ', ' . $language_name;

    $school_subject = $this->entityTypeManager->getStorage('taxonomy_term')->create([
      'vid' => 'school_subject',
      'name' => $name,
      'field_subject_code' => $subject_code,
      'field_language_code' => $language_code,
      'langcode' => 'sv',
    ]);
    $school_subject->save();
    $this->messenger()->addStatus($this->t('School subject added.'));
  }

}
