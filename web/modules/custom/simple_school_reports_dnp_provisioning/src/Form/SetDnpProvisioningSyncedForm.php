<?php

namespace Drupal\simple_school_reports_dnp_provisioning\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\simple_school_reports_dnp_support\DnpProvisioningInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a confirmation form for setting DNP provisioning synced.
 */
class SetDnpProvisioningSyncedForm extends ConfirmFormBase {

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
    return 'set_dnp_provisioning_synced_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to mark this provisioning file as synced?');
  }

  public function getCancelRoute() {
    return 'view.dnp_provisioning.list';
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
    return $this->t('Only set synced if you have confirmed that this provisioning file has been successfully uploaded to Skolverket.');
  }


  public function setSynced(DnpProvisioningInterface $dnp_provisioning) {
    $dnp_provisioning->set('synced', 1);
  }

  /**
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  public function getSuccessMessage() {
    return $this->t('DNP provisioning is marked as synced');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?DnpProvisioningInterface $dnp_provisioning = NULL) {

    if (!$dnp_provisioning) {
      throw new AccessDeniedHttpException();
    }

    $form['dnp_provisioning_id'] = [
      '#type' => 'value',
      '#value' => $dnp_provisioning->id(),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect($this->getCancelRoute());
    $dnp_provisioning_id = $form_state->getValue('dnp_provisioning_id');

    /** @var \Drupal\simple_school_reports_dnp_support\DnpProvisioningInterface $dnp_provisioning */
    $dnp_provisioning = $dnp_provisioning_id ? $this->entityTypeManager->getStorage('dnp_provisioning')->load($dnp_provisioning_id) : NULL;

    if (!$dnp_provisioning_id || !$dnp_provisioning->access('view')) {
      $this->messenger()->addError('Something went wrong. Try again.');
      return;
    }

    $this->setSynced($dnp_provisioning);
    $dnp_provisioning->save();
    $this->messenger()->addStatus($this->getSuccessMessage());
  }

  public function access(AccountInterface $account, ?DnpProvisioningInterface $dnp_provisioning = NULL): AccessResult {
    if (!$dnp_provisioning) {
      return AccessResult::forbidden()->addCacheContexts(['route']);
    }

    $access = $dnp_provisioning->access('view', $account, TRUE);
    $downloaded = !!$dnp_provisioning->get('downloaded')->value;
    $synced = !!$dnp_provisioning->get('synced')->value;
    if (!$downloaded || $synced) {
      $access = AccessResult::forbidden();
    }

    $access->addCacheableDependency($dnp_provisioning);

    return $access;
  }

}
