<?php

namespace Drupal\simple_school_reports_core\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\simple_school_reports_core\AbsenceDayHandler;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a confirmation form for reseting invalid absence counter.
 */
class ResetInvalidAbsenceMultipleForm extends ConfirmFormBase {

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
    return 'reset_invalid_absence_multiple';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Reset invalid absence counter');
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
      ->get('reset_invalid_absence_counter')
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

    $from_date = new DrupalDateTime();
    $from_date->setTime(0,0);

    $form['from_date'] = [
      '#type' => 'datetime',
      '#date_date_element' => 'date',
      '#title' => $this->t('Calculate invalid absence from'),
      '#default_value' => $from_date,
      '#required' => TRUE,
      '#date_increment' => 86400,
    ];

    $from_date = new DrupalDateTime();
    $from_date->setTime(23,59);

    $form['to_date'] = [
      '#type' => 'datetime',
      '#date_date_element' => 'date',
      '#title' => $this->t('Calculate invalid absence to'),
      '#default_value' => $from_date,
      '#required' => TRUE,
      '#date_increment' => 86400,
    ];

    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  protected function normalizeDate($date_value, $fallback_date = NULL, $fallback_time = '00:00:00') {
    if (is_array($date_value) || !$date_value) {
      $time = !empty($date_value['time']) ? $date_value['time'] : $fallback_time;
      $date = !empty($date_value['date']) ? $date_value['date'] : $fallback_date;
      if (!$date) {
        return NULL;
      }
      return new DrupalDateTime($date . ' ' . $time);
    }

    if ($date_value instanceof DrupalDateTime) {
      return $date_value;
    }

    return NULL;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    /** @var DrupalDateTime $from_date */
    $from_date = $this->normalizeDate($form_state->getValue('from_date'));
    if (!$from_date) {
      $form_state->setErrorByName('from_date', $this->t('@name field is required.', ['@name' => $this->t('Absence from')]));
      return;
    }

    /** @var DrupalDateTime $from_date */
    $to_date = $this->normalizeDate($form_state->getValue('to_date'), NULL, '23:59:59');
    if (!$to_date) {
      $form_state->setErrorByName('to_date', $this->t('@name field is required.', ['@name' => $this->t('Absence to')]));
      return;
    }

    if ($to_date < $from_date) {
      $form_state->setErrorByName('to_date', $this->t('%name must be higher than or equal to %min.', ['%name' => $this->t('Absence to'), '%min' => $this->t('Absence from')]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $current_user_id = $this->currentUser()->id();

    // Clear out the accounts from the temp store.
    $this->tempStoreFactory->get('reset_invalid_absence_counter')->delete($current_user_id);

    if (!$form_state->getValue('confirm')) {
      $this->logger('confirm_form')->error('Confirm issue!');
      $this->messenger()->addError($this->t('Something went wrong. Try again.'));
      $form_state->setRebuild(TRUE);
      return;
    }

    if ($form_state->getValue('confirm')) {
      $from_date = $this->normalizeDate($form_state->getValue('from_date'));
      $to_date = $this->normalizeDate($form_state->getValue('to_date', $from_date->format('Y-m-d'), '23:59:59'));
      if (!$from_date || !$to_date) {
        $this->messenger()->addError($this->t('Something went wrong. Try again.'));
        return;
      }

      $attendance_report_nids = $this->entityTypeManager
        ->getStorage('node')
        ->getQuery()
        ->condition('type', 'course_attendance_report')
        ->condition('field_class_start', $to_date->getTimestamp(), '<')
        ->condition('field_class_end', $from_date->getTimestamp(), '>')
        ->accessCheck(FALSE)
        ->execute();

      if (!empty($form_state->getValue('accounts'))) {

        // Initialize batch (to set title).
        $batch = [
          'title' => $this->t('Calculating invalid absence'),
          'init_message' => $this->t('Calculating invalid absence'),
          'progress_message' => $this->t('Processed @current out of @total.'),
          'operations' => [],
        ];

        foreach ($form_state->getValue('accounts') as $uid => $value) {
          $batch['operations'][] = [[self::class, 'calculateInvalidAbsence'], [$uid, $attendance_report_nids]];
        }

        batch_set($batch);
      }
    }
  }

  public static function calculateInvalidAbsence(string $uid, array $attendance_report_nids) {
    $invalid_absence = 0;

    /** @var UserStorageInterface $user_storage */
    $user_storage = \Drupal::entityTypeManager()->getStorage('user');
    $user = $user_storage->load($uid);
    if ($user) {
      if (!empty($attendance_report_nids)) {
        /** @var \Drupal\Core\Database\Connection $connection */
        $connection = \Drupal::service('database');

        $query = $connection->select('paragraph__field_invalid_absence', 'ia');
        $query->innerJoin('paragraph__field_student', 's', 's.entity_id = ia.entity_id');
        $query->innerJoin('paragraphs_item_field_data', 'd', 'd.id = ia.entity_id');
        $query->condition('ia.bundle', 'student_course_attendance')
          ->condition('ia.field_invalid_absence_value', 0, '<>')
          ->condition('s.field_student_target_id', $uid)
          ->condition('d.parent_id', $attendance_report_nids, 'IN')
          ->fields('ia',['field_invalid_absence_value']);

        $results = $query->execute();
        foreach ($results as $result) {
          $invalid_absence += $result->field_invalid_absence_value;
        }
      }

      $user->set('field_invalid_absence', $invalid_absence);
      $user->save();
    }
  }



}
