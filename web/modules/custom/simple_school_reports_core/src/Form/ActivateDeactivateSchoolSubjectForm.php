<?php

namespace Drupal\simple_school_reports_core\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\simple_school_reports_entities\SyllabusInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a confirmation form for activating or deactivating single syllabus.
 */
class ActivateDeactivateSchoolSubjectForm extends ConfirmFormBase {

  protected bool $status = TRUE;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager
  ) {}

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
    return 'activate_deactivate_school_subject_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->status
      ? $this->t('Are you sure you want to activate syllabus?')
      : $this->t('Are you sure you want to deactivate syllabus?');
  }

  public function getCancelRoute() {
    return 'simple_school_reports_core.config_syllabuses';
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
    return $this->status
      ? $this->t('Activate')
      : $this->t('Deactivate');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
  }

  /**
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  public function getSuccessMessage() {
    return $this->t('Syllabus updated.');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?SyllabusInterface $ssr_syllabus = NULL, string $status = 'activate') {

    if (!$ssr_syllabus) {
      throw new AccessDeniedHttpException();
    }

    $this->status = $status === 'activate';

    $form['ssr_syllabus_id'] = [
      '#type' => 'value',
      '#value' => $ssr_syllabus->id(),
    ];

    $form['ssr_syllabus_status'] = [
      '#type' => 'value',
      '#value' => $status === 'activate',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect($this->getCancelRoute());
    $ssr_syllabus_id = $form_state->getValue('ssr_syllabus_id');
    /** @var \Drupal\simple_school_reports_entities\SyllabusInterface $srr_syllabus */
    $srr_syllabus = $this->entityTypeManager->getStorage('ssr_syllabus')->load($ssr_syllabus_id);
    if ($srr_syllabus) {
      $srr_syllabus->set('status', $form_state->getValue('ssr_syllabus_status'));
      $srr_syllabus->save();
      $this->messenger()->addStatus($this->getSuccessMessage());
    }
    else {
      $this->messenger()->addError('Something went wrong. Try again.');
    }

  }

}
