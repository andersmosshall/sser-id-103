<?php

namespace Drupal\simple_school_reports_class_support\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\simple_school_reports_class_support\Service\SsrClassServiceInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a confirmation form for assign students to class.
 */
class AssignToClassForm extends ConfirmFormBase {

  public function __construct(
    protected PrivateTempStoreFactory $tempStoreFactory,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected SsrClassServiceInterface $ssrClassService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('entity_type.manager'),
      $container->get('simple_school_reports_class_support.class_service'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bulk_update_class_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Assign to class');
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

    // Retrieve the accounts to be assigned to class from the temp store.
    /** @var \Drupal\user\Entity\User[] $accounts */
    $accounts = $this->tempStoreFactory
      ->get('assign_to_class')
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

    $form['account']['names'] = [
      '#theme' => 'item_list',
      '#items' => $names,
    ];

    $class_options = $this->ssrClassService->getSortedClassOptions(FALSE, FALSE, TRUE);
    $form['class'] = [
      '#type' => 'select',
      '#title' => $this->t('Class'),
      '#options' => $class_options,
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $current_user_id = $this->currentUser()->id();

    // Clear out the accounts from the temp store.
    $this->tempStoreFactory->get('assign_to_class')->delete($current_user_id);

    if (!$form_state->getValue('confirm')) {
      $this->logger('confirm_form')->error('Confirm issue!');
      $this->messenger()->addError($this->t('Something went wrong. Try again.'));
      $form_state->setRebuild(TRUE);
      return;
    }

    if (!empty($form_state->getValue('accounts'))) {
      // Initialize batch (to set title).
      $batch = [
        'title' => $this->t('Bulk update class'),
        'init_message' => $this->t('Bulk update class'),
        'progress_message' => $this->t('Processed @current out of @total.'),
        'operations' => [],
        'finished' => [self::class, 'finished'],
      ];

      $class_id = $form_state->getValue('class', 'none');

      foreach ($form_state->getValue('accounts') as $uid => $value) {
        $batch['operations'][] = [[self::class, 'setStudentClass'], [$uid, $class_id]];
      }

      batch_set($batch);
    }
  }

  public static function setStudentClass(string $uid, string $class_id) {
    /** @var UserStorageInterface $user_storage */
    $user_storage = \Drupal::entityTypeManager()->getStorage('user');
    /** @var \Drupal\user\UserInterface $user */
    $user = $user_storage->load($uid);

    $class_id = is_numeric($class_id) ? $class_id : NULL;

    $class_value = $class_id ? ['target_id' => $class_id] : NULL;
    $user->set('field_class', $class_value);
    $user->save();
  }

  public static function finished($success, $results) {
    if ($success) {
      \Drupal::messenger()->addStatus(t('Class assigned'));
    }
    else {
      \Drupal::messenger()->addError(t('Something went wrong.'));
    }
  }

}
