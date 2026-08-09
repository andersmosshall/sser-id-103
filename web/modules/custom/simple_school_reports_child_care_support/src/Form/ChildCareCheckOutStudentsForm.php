<?php

namespace Drupal\simple_school_reports_child_care_support\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\simple_school_reports_child_care_support\ChildCareInterface;
use Drupal\simple_school_reports_child_care_support\Service\ChildCareCheckInServiceInterface;
use Drupal\simple_school_reports_child_care_support\Service\ChildCareServiceInterface;
use Drupal\simple_school_reports_core\AbsenceDayHandler;
use Drupal\simple_school_reports_core\Service\EmailService;
use Drupal\simple_school_reports_core\Service\EmailServiceInterface;
use Drupal\simple_school_reports_core\Service\ReplaceTokenServiceInterface;
use Drupal\simple_school_reports_core\Traits\MultiValueElementTrait;
use Drupal\simple_school_reports_core\Traits\PreventDoublePostTrait;
use Drupal\simple_school_reports_maillog\SsrMaillogInterface;
use Drupal\time_field\Time;
use Drupal\user\UserInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a confirmation form to check out multiple students.
 */
class ChildCareCheckOutStudentsForm extends ConfirmFormBase implements TrustedCallbackInterface {

  protected \DateTimeInterface|null $date = NULL;

  /**
   * Constructs a new CheckInStudentsForm.
   */
  public function __construct(
    protected PrivateTempStoreFactory $tempStoreFactory,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ChildCareServiceInterface $childCareService,
    protected ChildCareCheckInServiceInterface $childCareCheckInService,
    protected Request $currentRequest,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('entity_type.manager'),
      $container->get('simple_school_reports_child_care_support.child_care'),
      $container->get('simple_school_reports_child_care_support.child_care_check_in'),
      $container->get('request_stack')->getCurrentRequest(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'child_care_check_out_students_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    if ($this->date) {
      return $this->t('Check out students') . ' - ' . $this->date->format('Y-m-d');
    }
    return $this->t('Check out students');
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
    return $this->t('Check out');
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
  public function buildForm(array $form, FormStateInterface $form_state, ?string $date = NULL, ?UserInterface $user = NULL) {
    $groups = $this->currentRequest->query->get('groups');
    if (is_string($groups) && $groups !== '') {
      $groups = explode(',', $groups);
    }

    try {
      $date = $date ?? $this->tempStoreFactory
        ->get('child_care_check_out_date')
        ->get($this->currentUser()->id());

      if (!empty($date)) {
        $date = new \DateTime($date . ' 00:00:00');
      }
    }
    catch (\Exception $e) {
      $date = NULL;
    }

    if (empty($date)) {
      throw new NotFoundHttpException();
    }

    $this->date = $date;
    $form['date'] = [
      '#type' => 'value',
      '#value' => $date->format('Y-m-d H:i:s'),
    ];

    $now = new \DateTime();
    $is_today = $date->format('Y-m-d') === ($now)->format('Y-m-d');
    $default_to = ($now)->format('H:i');
    $max_to = $is_today ? $now->format('H:i') : '23:59';

    /** @var \Drupal\user\UserInterface[] $students */
    $students = [];
    if ($user) {
      if ($user->hasRole('student')) {
        $students[] = $user;
      }
    }
    else {
      $student_ids = $this->tempStoreFactory
        ->get('child_care_check_out_students')
        ->get($this->currentUser()->id());
      $students = !empty($student_ids) ? $this->entityTypeManager->getStorage('user')->loadMultiple($student_ids) : [];
      $students = array_values($students);
      $students = array_filter($students, fn(UserInterface $student) => $student->hasRole('student'));
    }

    if (empty($students)) {
      throw new AccessDeniedHttpException();
    }

    $student_uids = [];
    $child_care_ids_in_use = [];

    foreach ($students as $index => $student) {
      $uid = $student->id();


      $form['label_' . $uid] = [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $student->getDisplayName(),
      ];

      $check_ins = [];
      foreach ($this->childCareCheckInService->getChildCareCheckIns($uid, $date) as $check_in) {
        $child_care_id = $check_in['child_care_id'];
        $check_ins[$child_care_id] = $check_in;
      }

      $child_care_ids = array_keys($check_ins);

      /** @var \Drupal\simple_school_reports_child_care_support\ChildCareInterface[] $child_care_groups */
      $child_care_groups = !empty($child_care_ids)
        ? $this->entityTypeManager->getStorage('ssr_child_care')->loadMultiple($child_care_ids)
        : [];

      $child_care_groups = array_filter($child_care_groups, fn(ChildCareInterface $child_care) =>
        (!is_array($groups) || in_array($child_care->id(), $groups)) &&
        $this->childCareCheckInService->allowStudentCheckIn($uid, $date, [$child_care->id()])
      );

      if (empty($child_care_groups)) {
        $form['info_' . $uid] = [
          '#type' => 'html_tag',
          '#tag' => 'em',
          '#value' => $this->t('No available child care placement to check out from.'),
        ];
        continue;
      }

      $student_uids[] = $uid;


      foreach ($child_care_groups as $child_care) {
        $child_care_id = $child_care->id();
        $checked_in = $this->childCareCheckInService->getActiveCheckIn($uid, $child_care->id(), $date);
        if (!$checked_in) {
          $checked_in = $this->childCareCheckInService->getLatestCheckIn($uid, $child_care->id(), $date);
        }
        if (!$checked_in) {
          continue;
        }

        $from = new \DateTime();
        $from->setTimestamp($checked_in['from']);

        $child_care_ids_in_use[$child_care_id] = $child_care_id;
        $key = $uid .  '_' . $child_care_id;

        $form['check_in_id_' . $key] = [
          '#type' => 'value',
          '#value' => $checked_in['check_in_id'],
        ];

        $form['child_care_label' . $key] = [
          '#type' => 'html_tag',
          '#tag' => 'h3',
          '#value' => $child_care->label() . ' (' . $child_care->getShortLabel() . ') - ' . $this->t('Checked in @from', ['@from' => $from->format('H:i')])
        ];

        if ($checked_in['to']) {
          $to_date = clone $date;
          $to_date->setTimestamp($checked_in['to']);
          $to = $to_date->format('H:i');

          $form['options_' . $key] = [
            '#type' => 'radios',
            '#title' => $this->t('Already checked out, what do you want to do?'),
            '#default_value' => 'keep',
            '#options' => [
              'keep' => $this->t('Keep the current check out @to', ['@to' => $to]),
              'update' => $this->t('Update the current check out'),
              'delete' => $this->t('Revert the current check out'),
            ],
            '#required' => TRUE,
          ];

        }
        else {
          $to = $default_to;
        }

        $form['to_' . $key] = [
          '#type' => 'time',
          '#title' => $this->t('Check out time'),
          '#default_value' => $to,
          '#min' => '00:00',
          '#max' => $max_to,
          '#required' => TRUE,
        ];
        if ($checked_in['to']) {
          $form['to_' . $key]['#states'] = [
            'visible' => [
              ':input[name="options_' . $key . '"]' => ['value' => 'update'],
            ]
          ];
        }
      }

      if ($index !== 0 && $index < count($students) - 1) {
        $form['divider_' . $uid] = [
          '#markup' => '<hr/>',
        ];
      }
    }

    $form['student_uids'] = [
      '#type' => 'value',
      '#value' => $student_uids,
    ];

    $form['child_care_ids'] = [
      '#type' => 'value',
      '#value' => array_values($child_care_ids_in_use),
    ];

    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Initialize batch (to set title).
    $batch = [
      'title' => $this->t('Checking out students'),
      'init_message' => $this->t('Checking out students'),
      'progress_message' => $this->t('Processed @current out of @total.'),
      'finished' => [$this, 'finished'],
      'operations' => [],
    ];

    $student_uids = $form_state->getValue('student_uids');
    $child_care_ids = $form_state->getValue('child_care_ids');
    $date = new \DateTime($form_state->getValue('date'));
    $date->setTime(0,0,0);
    $base_date = $date->getTimestamp();

    foreach ($student_uids as $index => $student_uid) {
      foreach ($child_care_ids as $child_care_id) {
        $key = $student_uid . '_' . $child_care_id;
        $to = $form_state->getValue('to_' . $key);
        $check_in_id = $form_state->getValue('check_in_id_' . $key);
        if (!$to || !$check_in_id) {
          continue;
        }
        $to = $base_date + $to;

        $option = $form_state->getValue('options_' . $key, 'update');
        if ($option === 'keep') {
          continue;
        }
        if ($option === 'delete') {
          $to = NULL;
        }

        $batch['operations'][] = [[$this, 'checkOutStudent'], [$student_uid, $check_in_id, $to]];
      }
    }

    if (!empty($batch['operations'])) {
      if (count($batch['operations']) < 10) {
        $batch['progressive'] = FALSE;
      }
      batch_set($batch);
    }
    else {
      $this->messenger()->addWarning($this->t('No check outs where performed.'));
    }
  }

  public function checkOutStudent(string|int $student_id, string|int $check_in_id, int|null $to) {
    $check_in_storage = $this->entityTypeManager->getStorage('ssr_child_care_check_in');
    $check_in = $check_in_storage->load($check_in_id);
    if (!$check_in) {
      return;
    }
    if ($to === NULL) {
      $check_in->delete();
      return;
    }

    $from = $check_in->get('from')->value;
    // Delete check in if check in length is shorter than 10 minutes.
    $length = $to - $from;
    if ($length < 10 * 60) {
      $check_in->delete();
      return;
    }

    $check_in->set('to', $to);
    $check_in->save();
  }

  public function finished($success, $results) {
    if (!$success) {
      $this->messenger()->addError($this->t('Something went wrong'));
      return;
    }

    $this->messenger()->addStatus($this->t('Students checked out.'));
  }

  public static function trustedCallbacks() {
    return [
      'finished',
      'checkOutStudent',
    ];
  }

  public function access(AccountInterface $account, ?string $date = NULL): AccessResult {
    $date_object = NULL;
    try {
      $date = $date ?? $this->tempStoreFactory
        ->get('child_care_check_out_date')
        ->get($this->currentUser()->id());
      $date_object = $date ? new \DateTime($date) : NULL;

    }
    catch (\Exception $e) {
      $date_object = NULL;
    }

    if (empty($date_object)) {
      return AccessResult::forbidden()->addCacheContexts(['route']);
    }

    $today = (new \DateTime())->format('Y-m-d');
    if ($date_object->format('Y-m-d') !== $today && !$account->hasPermission('administer simple school reports settings')) {
      return AccessResult::forbidden()->addCacheContexts(['route', 'user', 'current_day']);
    }

    return AccessResult::allowedIfHasPermission($account, 'school staff permissions')->addCacheContexts(['route', 'user', 'current_day']);
  }

  public function accessWithStudent(string $date, UserInterface $user, AccountInterface $account): AccessResult {
    if (!$user->hasRole('student')) {
      return AccessResult::forbidden()->addCacheContexts(['route'])->addCacheableDependency($user);
    }

    $date_object = NULL;
    try {
      $date_object = $date ? new \DateTime($date) : NULL;
    }
    catch (\Exception $e) {
      $date_object = NULL;
    }
    if (empty($date_object)) {
      return AccessResult::forbidden()->addCacheContexts(['route']);
    }

    $student_access = AccessResult::allowedIf($this->childCareCheckInService->allowStudentCheckOut($user->id(), $date_object))->addCacheableDependency($user);

    return $this->access($account, $date)->andIf($student_access);
  }

}
