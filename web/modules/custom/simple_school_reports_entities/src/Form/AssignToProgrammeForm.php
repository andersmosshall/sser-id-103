<?php

namespace Drupal\simple_school_reports_entities\Form;

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
 * Provides a confirmation form for assign students to programme.
 */
class AssignToProgrammeForm extends ConfirmFormBase {

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
    return 'bulk_update_programme_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Assign to programme');
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

    // Retrieve the accounts to be assigned to programme from the temp store.
    /** @var \Drupal\user\Entity\User[] $accounts */
    $accounts = $this->tempStoreFactory
      ->get('assign_to_programme')
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

    $form['remove_programme'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remove programme'),
      '#default_value' => FALSE,
      '#description' => $this->t('If checked, the programme will be removed from the students.'),
    ];

    $form['programme'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'ssr_programme',
      '#default_value' => NULL,
      '#title' => $this->t('Programme'),
      '#selection_handler' => 'views',
      '#selection_settings' => [
        'match_limit' => 10,
        'match_operator' => 'CONTAINS',
        'view' => [
          'display_name' => 'all',
          'view_name' => 'programme_reference',
        ],
      ],
      '#description' => $this->t('Select the programme to assign the students to.'),
    ];
    $form['programme']['#states'] = [
      'invisible' => [
        ':input[name="remove_programme"]' => ['checked' => TRUE],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Check if the user has selected a programme.
    if (!$form_state->getValue('remove_programme', FALSE) && empty($form_state->getValue('programme'))) {
      $form_state->setErrorByName('programme', $this->t('You must select a programme to assign the students to.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $current_user_id = $this->currentUser()->id();

    // Clear out the accounts from the temp store.
    $this->tempStoreFactory->get('assign_to_programme')->delete($current_user_id);

    if (!$form_state->getValue('confirm')) {
      $this->logger('confirm_form')->error('Confirm issue!');
      $this->messenger()->addError($this->t('Something went wrong. Try again.'));
      $form_state->setRebuild(TRUE);
      return;
    }

    if (!empty($form_state->getValue('accounts'))) {
      // Initialize batch (to set title).
      $batch = [
        'title' => $this->t('Bulk update programme'),
        'init_message' => $this->t('Bulk update programme'),
        'progress_message' => $this->t('Processed @current out of @total.'),
        'operations' => [],
        'finished' => [self::class, 'finished'],
      ];

      $programme_id = $form_state->getValue('programme', 'none');
      if ($form_state->getValue('remove_programme', FALSE)) {
        $programme_id = 'none';
      }

      foreach ($form_state->getValue('accounts') as $uid => $value) {
        $batch['operations'][] = [[self::class, 'setStudentProgramme'], [$uid, $programme_id]];
      }

      batch_set($batch);
    }
  }

  public static function setStudentProgramme(string $uid, string $programme_id) {
    /** @var UserStorageInterface $user_storage */
    $user_storage = \Drupal::entityTypeManager()->getStorage('user');
    /** @var \Drupal\user\UserInterface|null $user */
    $user = $user_storage->load($uid);
    if (!$user) {
      return;
    }

    $programme_id = is_numeric($programme_id) ? $programme_id : NULL;

    $programme_value = $programme_id ? ['target_id' => $programme_id] : NULL;
    $user->set('field_programme', $programme_value);
    $user->save();
  }

  public static function finished($success, $results) {
    if ($success) {
      \Drupal::messenger()->addStatus(t('Programme assigned'));
    }
    else {
      \Drupal::messenger()->addError(t('Something went wrong.'));
    }
  }

}
