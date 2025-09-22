<?php

namespace Drupal\simple_school_reports_core\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\simple_school_reports_entities\ProgrammeInterface;
use Drupal\simple_school_reports_entities\SyllabusInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a confirmation form for activating or deactivating single programme.
 */
class ActivateDeactivateProgrammeForm extends ConfirmFormBase {

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
    return 'activate_deactivate_school_programme_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->status
      ? $this->t('Are you sure you want to activate programme?')
      : $this->t('Are you sure you want to deactivate programme?');
  }

  public function getCancelRoute() {
    return 'simple_school_reports_core.config_programmes';
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
    return $this->t('Programme updated.');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?ProgrammeInterface $ssr_programme = NULL, string $status = 'activate') {

    if (!$ssr_programme) {
      throw new AccessDeniedHttpException();
    }

    $this->status = $status === 'activate';

    $form['ssr_programme_id'] = [
      '#type' => 'value',
      '#value' => $ssr_programme->id(),
    ];

    $form['ssr_programme_status'] = [
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
    $ssr_programme_id = $form_state->getValue('ssr_programme_id');
    /** @var \Drupal\simple_school_reports_entities\ProgrammeInterface $srr_programme */
    $srr_programme = $this->entityTypeManager->getStorage('ssr_programme')->load($ssr_programme_id);
    if ($srr_programme) {
      $srr_programme->set('status', $form_state->getValue('ssr_programme_status'));
      $srr_programme->save();
      $this->messenger()->addStatus($this->getSuccessMessage());
    }
    else {
      $this->messenger()->addError('Something went wrong. Try again.');
    }

  }

}
