<?php

namespace Drupal\simple_school_reports_core\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\simple_school_reports_core\AbsenceDayHandler;
use Drupal\simple_school_reports_core\SchoolGradeHelper;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a confirmation form for reseting invalid absence counter.
 */
class BulkUpdateGradeForm extends ConfirmFormBase {

  /**
   * The temp store factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ResetInvalidAbsenceMultipleForm.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bulk_update_grade_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Bulk update grade');
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

    // Retrieve the accounts to be canceled from the temp store.
    /** @var \Drupal\user\Entity\User[] $accounts */
    $accounts = $this->tempStoreFactory
      ->get('bulk_update_grade')
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

    $form['update_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Choose what to do with selected students above'),
      '#options' => [
        'push_up' => $this->t('Increase grade one level'),
        'pull_down' => $this->t('Decrease grade one level'),
        'set_specific' => $this->t('Set specific grade'),
      ],
      '#required' => TRUE,
    ];

    $form['specific_grade'] = [
      '#type' => 'select',
      '#options' => SchoolGradeHelper::getSchoolGradesMapAll(),
    ];

    $form['specific_grade']['#states']['visible'][] = [
      ':input[name="update_type"]' => [
        'value' => 'set_specific',
      ],
    ];

    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $current_user_id = $this->currentUser()->id();

    // Clear out the accounts from the temp store.
    $this->tempStoreFactory->get('bulk_update_grade')->delete($current_user_id);

    if (!$form_state->getValue('confirm')) {
      $this->logger('confirm_form')->error('Confirm issue!');
      $this->messenger()->addError($this->t('Something went wrong. Try again.'));
      $form_state->setRebuild(TRUE);
      return;
    }

    if ($form_state->getValue('confirm')) {
      if (!empty($form_state->getValue('accounts'))) {

        // Initialize batch (to set title).
        $batch = [
          'title' => $this->t('Bulk update grade'),
          'init_message' => $this->t('Bulk update grade'),
          'progress_message' => $this->t('Processed @current out of @total.'),
          'operations' => [],
          'finished' => [self::class, 'finished'],
        ];

        $grade = $form_state->getValue('update_type') === 'set_specific' ? $form_state->getValue('specific_grade') : $form_state->getValue('update_type');

        foreach ($form_state->getValue('accounts') as $uid => $value) {
          $batch['operations'][] = [[self::class, 'setStudentGrade'], [$uid, $grade]];
        }

        batch_set($batch);
      }
    }
  }

  public static function setStudentGrade(string $uid, string $grade) {
    /** @var UserStorageInterface $user_storage */
    $user_storage = \Drupal::entityTypeManager()->getStorage('user');
    /** @var \Drupal\user\UserInterface $user */
    $user = $user_storage->load($uid);
    if ($user) {
      $grade_to_set = SchoolGradeHelper::UNKNOWN_GRADE;

      $grade_values = array_keys(SchoolGradeHelper::getSchoolGradesMapAll());
      $user_grade = $user->get('field_grade')->value;
      if ($user_grade === NULL) {
        $user_grade = SchoolGradeHelper::UNKNOWN_GRADE;
      }

      $current_grade_values_key = array_search($user_grade, $grade_values) ?? 0;

      if ($grade === 'push_up') {
        $new_grade_values_key = $current_grade_values_key + 1;
        if (isset($grade_values[$new_grade_values_key])) {
          $grade_to_set = $grade_values[$new_grade_values_key];
        }
        else {
          $grade_to_set = SchoolGradeHelper::QUITED_GRADE;
        }
      }
      elseif ($grade === 'pull_down') {
        $new_grade_values_key = $current_grade_values_key - 1;
        if (isset($grade_values[$new_grade_values_key])) {
          $grade_to_set = $grade_values[$new_grade_values_key];
        }
        else {
          $grade_to_set = SchoolGradeHelper::UNKNOWN_GRADE;
        }
      }
      elseif (isset($grade_options[$grade])) {
        $grade_to_set = $grade;
      }

      $user->set('field_grade', $grade_to_set);
      $user->save();
    }
  }

  public static function finished($success, $results) {
    if ($success) {
      \Drupal::messenger()->addStatus('Grade set');
    }
    else {
      \Drupal::messenger()->addError('Something went wrong.');
    }

  }



}
