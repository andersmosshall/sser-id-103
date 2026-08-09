<?php

namespace Drupal\simple_school_reports_child_care_support\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\simple_school_reports_child_care_support\Service\ChildCareServiceInterface;
use Drupal\time_field\Time;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a confirmation form for adding multiple child care schema deviations.
 */
class AddStudentSchemaDeviationForm extends ConfirmFormBase {

  protected string|int|null $userId = NULL;

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  protected ChildCareServiceInterface $childCareService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->childCareService = $container->get('simple_school_reports_child_care_support.child_care');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'add_student_schema_deviation_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Add schema deviation');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return '<front>';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    if ($this->userId) {
      return new Url('simple_school_reports_child_care_support.student_child_care', ['user' => $this->userId]);
    }

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
  public function buildForm(array $form, FormStateInterface $form_state, ?UserInterface $user = NULL, ?string $date = NULL) {
    if (!$user || !$date) {
      throw new NotFoundHttpException();
    }
    $date = new \DateTime($date . ' 00:00:00');

    $this->userId = $user->id();

    $form['student_id'] = [
      '#type' => 'value',
      '#value' => $user->id(),
    ];
    $form['date'] = [
      '#type' => 'value',
      '#value' => $date->format('Y-m-d'),
    ];


    $form['#title'] = $this->t('Add schema deviation for @name - @date', ['@date' => $date->format('Y-m-d'), '@name' => $user->getDisplayName()]);

    $default_from = $this->getRequest()->query->get('from') ?? NULL;
    $default_to = $this->getRequest()->query->get('to') ?? NULL;
    $default_comment = NULL;

    $deviation_id = $this->getRequest()->query->get('deviation_id');
    if ($deviation_id) {
      /** @var \Drupal\simple_school_reports_child_care_support\ChildCareSchemaDeviationStudentInterface|null $deviation */
      $deviation = $this->entityTypeManager->getStorage('ssr_cc_deviation_student')->load($deviation_id);
      if ($deviation && $deviation->get('student')->target_id == $user->id()) {
        $default_from = $deviation->get('from')->value;
        $default_to = $deviation->get('to')->value;
        $default_comment = $deviation->get('field_comment')->value;
      }
    }

    $default_from_time = NULL;
    $default_to_time = NULL;
    if (is_numeric($default_from)) {
      $default_from_time = Time::createFromTimestamp($default_from);
    }
    if (is_numeric($default_to)) {
      $default_to_time = Time::createFromTimestamp($default_to);
    }

    $threshold = new \DateTime();
    $threshold->setTime(0, 0, 0);

    $future_min_limit = $this->childCareService->getSettings()['future_min_limit'];
    $threshold->add(new \DateInterval('P' . $future_min_limit . 'D'));

    $allow_change_time = $date >= $threshold;

    $default_day_off = !$default_from_time || !$default_to_time;

    if ($allow_change_time) {
      $form['day_off'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Day off'),
        '#default_value' => $default_day_off,
      ];

      $form['time_from'] = [
        '#type' => 'time',
        '#title' => $this->t('From'),
        '#default_value' => $default_from_time?->format('H:i:s'),
        '#states' => [
          'visible' => [
            ':input[name="day_off"]' => ['checked' => FALSE],
          ]
        ]
      ];

      $form['time_to'] = [
        '#type' => 'time',
        '#title' => $this->t('To'),
        '#default_value' => $default_to_time?->format('H:i:s'),
        '#states' => [
          'visible' => [
            ':input[name="day_off"]' => ['checked' => FALSE],
          ]
        ]
      ];
    }
    else {
      $form['day_off'] = [
        '#type' => 'value',
        '#value' => $default_day_off,
      ];
      $form['time_from'] = [
        '#type' => 'value',
        '#value' => $default_from,
      ];
      $form['time_to'] = [
        '#type' => 'value',
        '#value' => $default_to,
      ];

      if ($default_day_off) {
        $form['day_off_visual_only'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Day off'),
          '#default_value' => TRUE,
          '#disabled' => TRUE,
        ];
      }
      else {
        $form['time_from_visual_only'] = [
          '#type' => 'time',
          '#title' => $this->t('From'),
          '#default_value' => $default_from_time?->format('H:i:s'),
          '#disabled' => TRUE,
        ];
        $form['time_to_visual_only'] = [
          '#type' => 'time',
          '#title' => $this->t('To'),
          '#default_value' => $default_to_time?->format('H:i:s'),
          '#disabled' => TRUE,
        ];
      }

      $form['not_allowed_info'] = [
        '#type' => 'html_tag',
        '#tag' => 'em',
        '#value' => $this->t('You are not allowed change time but you can add a comment.'),
      ];
    }

    $form['comment'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Comment'),
      '#default_value' => $default_comment,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
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
    try {
      $date = $form_state->getValue('date');

      $day_off = $form_state->getValue('day_off');
      $time_from = $day_off ? NULL : $form_state->getValue('time_from');
      $time_to = $day_off ? NULL : $form_state->getValue('time_to');

      $from_date_object = new \DateTime($date . ' 00:00:00');
      $to_date_object = new \DateTime($date . ' 23:59:59');

      $student_id = $form_state->getValue('student_id');
      $comment = $form_state->getValue('comment');

      /** @var \Drupal\user\UserInterface $student */
      $student = $this->entityTypeManager->getStorage('user')->load($student_id);
      if (!$student) {
        return;
      }

      $child_care_deviation = $this->entityTypeManager->getStorage('ssr_cc_deviation_student')->create([
        'label' => 'Avvikelse för ' . $student->getDisplayName(),
        'student' => ['target_id' => $student->id()],
        'from_date' => $from_date_object->getTimestamp(),
        'to_date' => $to_date_object->getTimestamp(),
        'from' => $time_from,
        'to' => $time_to,
        'field_comment' => $comment,
        'langcode' => 'sv',
        'status' => TRUE,
      ]);
      $child_care_deviation->save();
      $this->messenger()->addStatus(t('Schema deviation registered'));
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Something went wrong. Try again.'));
    }
  }

  public function access(UserInterface $user, string $date, AccountInterface $account): AccessResultInterface {
    if (!ssr_use_child_care() || !$user) {
      return AccessResult::forbidden();
    }

    try {
      $date_object = new \DateTime($date . ' 00:00:00');
    }
    catch (\Exception $e) {
      return AccessResult::forbidden()->addCacheContexts(['route', 'current_day'])->addCacheableDependency($user);
    }

    $today = new \DateTime();
    $today->setTime(0, 0, 0);

    if ($date_object < $today) {
      return AccessResult::forbidden()->addCacheContexts(['route', 'current_day'])->addCacheableDependency($user);
    }

    $future_max_limit = $this->childCareService->getSettings()['future_max_limit'];
    $threshold = new \DateTime();
    $threshold->setTime(0, 0, 0);
    $threshold->add(new \DateInterval('P' . $future_max_limit . 'D'));

    if ($date_object >= $threshold) {
      return AccessResult::forbidden()->addCacheContexts(['route', 'current_day'])->addCacheableDependency($user);
    }

    if (!$user->hasRole('student') || !$user->access('caregiver_access', $account)) {
      return AccessResult::forbidden()->addCacheContexts(['route', 'current_day'])->addCacheableDependency($user);
    }

    $group_ids = $this->childCareService->getChildCareGroups($user->id());
    return AccessResult::allowedIf(!empty($group_ids))->addCacheContexts(['route', 'current_day'])->addCacheableDependency($user);
  }

}
