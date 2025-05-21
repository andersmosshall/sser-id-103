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
use Drupal\taxonomy\TermInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a confirmation form for activating school subject.
 */
class ActivateSchoolSubjectForm extends ConfirmFormBase {

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
    return 'activate_school_subject_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to activate school subject?');
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
    return $this->t('Activate');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
  }

  /**
   * @param \Drupal\taxonomy\TermInterface $school_subject
   */
  public function setStatus(TermInterface $school_subject) {
    $school_subject->set('status', 1);
  }

  /**
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  public function getSuccessMessage() {
    return $this->t('School subject updated.');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?TermInterface $taxonomy_term = NULL) {

    if (!$taxonomy_term || $taxonomy_term->bundle() !== 'school_subject') {
      throw new AccessDeniedHttpException();
    }

    $form['term_id'] = [
      '#type' => 'value',
      '#value' => $taxonomy_term->id(),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect($this->getCancelRoute());
    $term_id = $form_state->getValue('term_id');
    /** @var TermInterface $school_subject */
    $school_subject = $this->entityTypeManager->getStorage('taxonomy_term')->load($term_id);
    if ($school_subject && $school_subject->bundle() === 'school_subject') {
      $this->setStatus($school_subject);
      $school_subject->save();
      $this->messenger()->addStatus($this->getSuccessMessage());
    }
    else {
      $this->messenger()->addError('Something went wrong. Try again.');
    }

  }

}
