<?php

namespace Drupal\simple_school_reports_core_fth\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\simple_school_reports_child_care_support\ChildCareInterface;
use Drupal\simple_school_reports_core\AbsenceDayHandler;
use Drupal\simple_school_reports_core\Service\EmailService;
use Drupal\simple_school_reports_core\Service\EmailServiceInterface;
use Drupal\simple_school_reports_core\Service\ReplaceTokenServiceInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a confirmation form for adding multiple child care schema deviations.
 */
class AddSchemaDeviationsForm extends ConfirmFormBase implements TrustedCallbackInterface {

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'add_schema_deviations_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Add schema deviations');
  }

  public function getCancelRoute() {
    return '<front>';
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
  public function buildForm(array $form, FormStateInterface $form_state, ChildCareInterface $ssr_child_care = NULL) {

    $form['from_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Deviation from'),
      '#required' => TRUE,
    ];

    $form['to_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Deviation to'),
      '#required' => TRUE,
    ];

    if (!$this->currentUser()->hasPermission('administer ssr_child_care')) {
      $today = (new \DateTime())->format('Y-m-d');
      $form['from_date']['#min'] = $today;
      $form['to_date']['#min'] = $today;
    }

    $form['day_off'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Day off'),
      '#default_value' => FALSE,
    ];

    $form['time_from'] = [
      '#type' => 'time',
      '#title' => $this->t('From'),
      '#states' => [
        'visible' => [
          ':input[name="day_off"]' => ['checked' => FALSE],
        ]
      ]
    ];

    $form['time_to'] = [
      '#type' => 'time',
      '#title' => $this->t('To'),
      '#states' => [
        'visible' => [
          ':input[name="day_off"]' => ['checked' => FALSE],
        ]
      ]
    ];

    $form['divider_1'] = [
      '#type' => 'html_tag',
      '#tag' => 'hr',
    ];


    $child_care_default_values = [];
    if ($ssr_child_care?->id()) {
      $child_care_default_values[] = $ssr_child_care->id();
    }
    $child_care_entities = $this->entityTypeManager->getStorage('ssr_child_care')
      ->loadByProperties(['status' => TRUE, 'bundle' => 'fth']);
    $child_care_options = array_map(function($child_care) {
      return $child_care->label();
    }, $child_care_entities);

    // Sort options.
    asort($child_care_options);


    $form['child_care_wrapper'] = [
      '#type' => 'details',
      '#title' => $this->t('Child care groups'),
      '#open' => TRUE,
    ];

    $form['child_care_wrapper'] ['child_care_ids'] = [
      '#type' => 'ssr_multi_select',
      '#title' => $this->t('Child care groups'),
      '#description' => $this->t('Select what child care groups to add schema deviations for.'),
      '#options' => $child_care_options,
      '#default_value' => $child_care_default_values,
      '#filter_placeholder' => $this->t('Type to search for course child care group'),
    ];

//    $form['child_care_wrapper'] ['child_care_comment'] = [
//      '#type' => 'textarea',
//      '#title' => $this->t('Comment on child care group schema deviations'),
//    ];

    $student_options = [];
    $user_storage = $this->entityTypeManager->getStorage('user');
    $uids = $user_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 1)
      ->condition('roles', 'student')
      ->sort('field_grade')
      ->sort('field_first_name')
      ->sort('field_last_name')
      ->execute();
    if (!empty($uids)) {
      /** @var \Drupal\user\UserInterface[] $users */
      $users = $user_storage->loadMultiple($uids);
      foreach ($users as $user) {
        $student_options[$user->id()] = $user->getDisplayName();
      }
    }

    $form['students_wrapper'] = [
      '#type' => 'details',
      '#title' => $this->t('Students'),
      '#open' => TRUE,
    ];

    $form['students_wrapper']['student_ids'] = [
      '#type' => 'ssr_multi_select',
      '#title' => $this->t('Students'),
      '#description' => $this->t('Select students to add schema deviations for.'),
      '#options' => $student_options,
      '#default_value' => [],
      '#filter_placeholder' => $this->t('Enter name or grade/class to filter'),
    ];

    $form['students_wrapper']['student_comment'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Comment on student schema deviations'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Verify to date is after from date.
    $from_date = $form_state->getValue('from_date');
    $to_date = $form_state->getValue('to_date');
    if ($from_date > $to_date) {
      $form_state->setErrorByName('to_date', $this->t('To date must be after from date.'));
    }

    $day_off = $form_state->getValue('day_off');
    if ($day_off) {
      return;
    }

    $time_from = $form_state->getValue('time_from', NULL);
    $time_to = $form_state->getValue('time_to', NULL);
    // If day_off is not checked, verify that time from and time to is set.
    if (!is_numeric($time_from) || !is_numeric($time_to)) {
      if (!is_numeric($time_from)) {
        $form_state->setErrorByName('time_from', $this->t('Time from must be set.'));
      }
      if (!is_numeric($time_to)) {
        $form_state->setErrorByName('time_to', $this->t('Time to must be set.'));
      }
      return;
    }

    // Verify that time to is after time from.
    if ($time_from > $time_to) {
      $form_state->setErrorByName('time_to', $this->t('To time must be after from time.'));
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

    $from_date = $form_state->getValue('from_date');
    $to_date = $form_state->getValue('to_date');

    if ($from_date > $to_date) {
      throw new \RuntimeException('From date must be before to date.');
    }

    $day_off = $form_state->getValue('day_off');
    $time_from = $day_off ? NULL : $form_state->getValue('time_from');
    $time_to = $day_off ? NULL : $form_state->getValue('time_to');

    $from_date_object = new \DateTime($from_date);
    $from_date_object->setTime(0, 0, 0);

    $to_date_object = new \DateTime($to_date);
    $to_date_object->setTime(23, 59, 59);

    $child_care_ids = $form_state->getValue('child_care_ids');
    $student_ids = $form_state->getValue('student_ids');

    $child_care_comment = $form_state->getValue('child_care_comment');
    $student_comment = $form_state->getValue('student_comment');

    if (empty($child_care_ids) && empty($student_ids)) {
      $this->messenger()->addError($this->t('No child care groups or students selected.'));
      return;
    }

    $batch = [
      'title' => $this->t('Register deviations'),
      'init_message' => $this->t('Register deviations'),
      'progress_message' => $this->t('Processed @current out of @total.'),
      'finished' => [$this, 'finished'],
      'operations' => [],
    ];

    // Do a date walk between from and to date.
    $current_date = $from_date_object;
    $current_date_object = clone $current_date;

    while ($current_date_object <= $to_date_object) {
      $current_from_date = clone $current_date_object;
      $current_from_date->setTime(0, 0, 0);

      $current_to_date = clone $current_date_object;
      $current_to_date->setTime(23, 59, 59);

      foreach ($child_care_ids as $child_care_id) {
        $batch['operations'][] = [[$this, 'addSchemaDeviation'], [$child_care_id, $current_from_date->getTimestamp(), $current_to_date->getTimestamp(), $time_from, $time_to, $child_care_comment]];
      }

      foreach ($student_ids as $student_id) {
        $batch['operations'][] = [[$this, 'addSchemaDeviationStudent'], [$student_id, $current_from_date->getTimestamp(), $current_to_date->getTimestamp(), $time_from, $time_to, $student_comment]];
      }

      $current_date_object->modify('+1 day');
    }

    if (!empty($batch['operations'])) {
      batch_set($batch);
    }
    else {
      $this->messenger()->addWarning($this->t('Something went wrong'));
    }

  }

  public function addSchemaDeviation(int $child_care_id, int $from_date, int $to_date, ?int $from, ?int $to, ?string $comment, array &$context) {
    /** @var \Drupal\simple_school_reports_child_care_support\ChildCareInterface|null $child_care */
    $child_care = $this->entityTypeManager->getStorage('ssr_child_care')->load($child_care_id);
    if (!$child_care) {
      return;
    }

    $child_care_deviation = $this->entityTypeManager->getStorage('ssr_cc_deviation')->create([
      'label' => 'Avvikelse för ' . $child_care->label(),
      'child_care' => ['target_id' => $child_care->id()],
      'from_date' => $from_date,
      'to_date' => $to_date,
      'from' => $from,
      'to' => $to,
      'field_comment' => $comment,
      'langcode' => 'sv',
      'status' => TRUE,
    ]);
    $child_care_deviation->save();
  }

  public function addSchemaDeviationStudent(int $student_id, int $from_date, int $to_date, ?int $from, ?int $to,  ?string $comment, array &$context) {
    /** @var \Drupal\user\UserInterface $student */
    $student = $this->entityTypeManager->getStorage('ssr_child_care')->load($student_id);
    if (!$student) {
      return;
    }

    $child_care_deviation = $this->entityTypeManager->getStorage('ssr_cc_deviation_student')->create([
      'label' => 'Avvikelse för ' . $student->getDisplayName(),
      'student' => ['target_id' => $student->id()],
      'from_date' => $from_date,
      'to_date' => $to_date,
      'from' => $from,
      'to' => $to,
      'field_comment' => $comment,
      'langcode' => 'sv',
      'status' => TRUE,
    ]);
    $child_care_deviation->save();
  }

  public function finished($success, $results) {
    if (!$success) {
      $this->messenger()->addError(t('Something went wrong'));
      return;
    }
    $this->messenger()->addStatus(t('Schema deviations registered'));
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['addSchemaDeviation', 'addSchemaDeviationStudent', 'finished'];
  }

}
