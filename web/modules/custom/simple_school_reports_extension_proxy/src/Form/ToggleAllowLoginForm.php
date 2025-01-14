<?php

namespace Drupal\simple_school_reports_extension_proxy\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\simple_school_reports_core\AbsenceDayHandler;
use Drupal\simple_school_reports_core\Form\MailMultipleCaregiversForm;
use Drupal\simple_school_reports_core\Service\EmailService;
use Drupal\simple_school_reports_core\Service\EmailServiceInterface;
use Drupal\simple_school_reports_core\Service\ReplaceTokenServiceInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a confirmation form for cancelling multiple user accounts.
 */
class ToggleAllowLoginForm extends ConfirmFormBase {

  /**
   * The temp store factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;


  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->moduleHandler = $container->get('module_handler');
    $instance->tempStoreFactory = $container->get('tempstore.private');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'toggle_allow_login_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Set allow login');
  }

  public function getCancelRoute() {
    return 'view.students.students';
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
    if (!$this->moduleHandler->moduleExists('simple_school_reports_absence_make_up')) {
      throw new AccessDeniedHttpException();
    }

    // Retrieve the accounts to be canceled from the temp store.
    /** @var \Drupal\user\Entity\User[] $accounts */
    $accounts = $this->tempStoreFactory
      ->get('toggle_allow_login')
      ->get($this->currentUser()->id());
    if (empty($accounts)) {
      return $this->redirect($this->getCancelRoute());
    }

    $names = [];

    $form['accounts'] = ['#tree' => TRUE];
    foreach ($accounts as $account) {
      $uid = $account->id();
      // Prevent user 1 from being canceled.
      if ($uid <= 1) {
        continue;
      }

      $names[$uid] = $account->label();
      $form['accounts'][$uid] = [
        '#type' => 'value',
        '#value' => $names[$uid],
      ];
    }

    if (empty($names)) {
      throw new AccessDeniedHttpException();
    }

    $form = parent::buildForm($form, $form_state);

    $form['allow_login'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow login'),
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $allow_login = $form_state->getValue('allow_login');
    if ($allow_login) {
      if (!$this->moduleHandler->moduleExists('simple_school_reports_caregiver_login')) {
        $form_state->setErrorByName('allow_login', $this->t('The caregiver login module needs to be active to allow login'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->getValue('confirm')) {
      $this->logger('confirm_form')->error('Confirm issue!');
      $this->messenger()->addError($this->t('Something went wrong. Try again.'));
      $form_state->setRebuild(TRUE);
      return;
    }

    if ($form_state->getValue('confirm')) {
      $allow_login = $form_state->getValue('allow_login', FALSE);

      // Initialize batch (to set title).
      $batch = [
        'title' => $this->t('Update users'),
        'init_message' => $this->t('Update users'),
        'progress_message' => $this->t('Processed @current out of @total.'),
        'operations' => [],
      ];


      foreach ($form_state->getValue('accounts') as $uid => $value) {
        $batch['operations'][] = [[self::class, 'setAllowLogin'], [$uid, (bool) $allow_login]];
      }

      if (!empty($batch['operations'])) {
        batch_set($batch);
      }
    }
  }

  public static function setAllowLogin(string $uid, bool $allow_login) {
    $user = \Drupal::entityTypeManager()->getStorage('user')->load($uid);
    if ($user) {
      $user->set('field_allow_login', $allow_login);
      $user->save();
    }
  }
}
