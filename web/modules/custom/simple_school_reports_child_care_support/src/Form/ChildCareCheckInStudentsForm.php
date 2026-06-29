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
 * Provides a confirmation form to check in multiple students.
 */
class ChildCareCheckInStudentsForm extends ConfirmFormBase implements TrustedCallbackInterface {

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
    return 'child_care_check_in_students_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    if ($this->date) {
      return $this->t('Check in students') . ' - ' . $this->date->format('Y-m-d');
    }
    return $this->t('Check in students');
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
    return $this->t('Check in');
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
        ->get('child_care_check_in_date')
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
    $default_from = ($now)->format('H:i');
    $max_from = $is_today ? $now->format('H:i') : '23:59';


    /** @var \Drupal\user\UserInterface[] $students */
    $students = [];
    if ($user) {
      if ($user->hasRole('student')) {
        $students[] = $user;
      }
    }
    else {
      $student_ids = $this->tempStoreFactory
        ->get('child_care_check_in_students')
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

      $child_care_ids = $this->childCareService->getChildCareGroups($uid);

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
          '#value' => $this->t('No available child care placement to check in to.'),
        ];
        continue;
      }

      $student_uids[] = $uid;


      foreach ($child_care_groups as $child_care) {
        $child_care_id = $child_care->id();
        $child_care_ids_in_use[$child_care_id] = $child_care_id;
        $key = $uid .  '_' . $child_care_id;

        $form['child_care_label' . $key] = [
          '#type' => 'html_tag',
          '#tag' => 'h3',
          '#value' => $child_care->label() . ' (' . $child_care->getShortLabel() . ')',
        ];

        $checked_in = $this->childCareCheckInService->getActiveCheckIn($uid, $child_care->id(), $date);

        if ($checked_in) {
          $from_date = clone $date;
          $from_date->setTimestamp($checked_in['from']);
          $from = $from_date->format('H:i');

          $form['options_' . $key] = [
            '#type' => 'radios',
            '#title' => $this->t('Already checked in, what do you want to do?'),
            '#default_value' => 'keep',
            '#options' => [
              'keep' => $this->t('Keep the current check in @from', ['@from' => $from]),
              'update' => $this->t('Update the current check in'),
              'delete' => $this->t('Revert the current check in'),
            ],
            '#required' => TRUE,
          ];
          $form['check_in_id_' . $key] = [
            '#type' => 'value',
            '#value' => $checked_in['check_in_id'],
          ];
        }
        else {
          $from = $default_from;
        }

        $form['from_' . $key] = [
          '#type' => 'time',
          '#title' => $this->t('Check in time'),
          '#default_value' => $from,
          '#attributes' => [
            'min' => '00:00',
            'max' => $max_from,
          ],
          '#required' => TRUE,
        ];
        if ($checked_in) {
          $form['from_' . $key]['#states'] = [
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
      'title' => $this->t('Checking in students'),
      'init_message' => $this->t('Checking in students'),
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
        $from = $form_state->getValue('from_' . $key);
        if (!$from) {
          continue;
        }
        $from = $base_date + $from;

        $option = $form_state->getValue('options_' . $key, 'update');
        if ($option === 'keep') {
          continue;
        }
        $check_in_id = $form_state->getValue('check_in_id_' . $key);

        if ($option === 'delete') {
          if ($check_in_id) {
            $batch['operations'][] = [[$this, 'deleteCheckIn'], [$check_in_id]];
          }
          continue;
        }

        $batch['operations'][] = [[$this, 'checkInStudent'], [$student_uid, $child_care_id, $from, $check_in_id]];
      }
    }

    if (!empty($batch['operations'])) {
      if (count($batch['operations']) < 10) {
        $batch['progressive'] = FALSE;
      }
      batch_set($batch);
    }
    else {
      $this->messenger()->addWarning($this->t('No check ins where performed.'));
    }
  }

  public function checkInStudent(string|int $student_id, string|int $child_care_id, int $from, string|int|null $check_in_to_update) {
    $check_in_storage = $this->entityTypeManager->getStorage('ssr_child_care_check_in');
    $check_in = NULL;
    if ($check_in_to_update) {
      $check_in = $check_in_storage->load($check_in_to_update);
    }
    if (!$check_in) {
      $check_in = $check_in_storage->create([
        'student' => $student_id,
        'child_care' => $child_care_id,
        'langcode' => 'sv',
      ]);
    }
    $check_in->set('from', $from);
    $check_in->save();
  }

  public function deleteCheckIn(string|int $check_in_id, array &$context) {
    $check_in = $this->entityTypeManager->getStorage('ssr_child_care_check_in')->load($check_in_id);
    $check_in?->delete();
  }

  public function finished($success, $results) {
    if (!$success) {
      $this->messenger()->addError($this->t('Something went wrong'));
      return;
    }

    $this->messenger()->addStatus($this->t('Students checked in.'));
  }

  public static function trustedCallbacks() {
    return [
      'finished',
      'checkInStudent',
      'deleteCheckIn',
    ];
  }

  public function access(AccountInterface $account, ?string $date = NULL): AccessResult {
    $date_object = NULL;
    try {
      $date = $date ?? $this->tempStoreFactory
        ->get('child_care_check_in_date')
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

    $student_access = AccessResult::allowedIf($this->childCareCheckInService->allowStudentCheckIn($user->id(), $date_object))->addCacheableDependency($user);

    return $this->access($account, $date)->andIf($student_access);
  }

}
