<?php

namespace Drupal\simple_school_reports_core\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\simple_school_reports_core\AbsenceDayHandler;
use Drupal\user\UserInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a confirmation form for cancelling multiple user accounts.
 */
class RegisterMultipleAbsenceForm extends ConfirmFormBase {

  /**
   * The temp store factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * Constructs a new RegisterMultipleAbsenceForm.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The temp store factory.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory) {
    $this->tempStoreFactory = $temp_store_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'register_multiple_absence_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Register absence');
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
  public function buildForm(array $form, FormStateInterface $form_state, ?UserInterface $user = NULL) {
    if ($user) {
      /** @var \Drupal\user\Entity\User[] $accounts */
      $accounts = [$user];
    }
    else {
      // Retrieve the accounts to be canceled from the temp store.
      /** @var \Drupal\user\Entity\User[] $accounts */
      $accounts = $this->tempStoreFactory
        ->get('register_absence_form')
        ->get($this->currentUser()->id());
    }


    if (empty($accounts)) {
      $this->logger('register_absence_form')->error('No accounts to register absence for - throw exception.');
      $this->messenger()->addError($this->t('Something went wrong. Try again.'));
      return throw new AccessDeniedHttpException();
    }

    $current_user = $this->currentUser();
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

    $form['accounts_debug'] = [
      '#type' => 'hidden',
      '#value' => json_encode(array_keys($form['accounts'])),
    ];

    if (empty($names)) {
      throw new AccessDeniedHttpException();
    }

    $form['account']['names'] = [
      '#theme' => 'item_list',
      '#items' => $names,
    ];

    $date = new DrupalDateTime();
    $today_string = $date->format('Y-m-d');
    $date->add(new \DateInterval('P1D'));
    $tomorrow_string = $date->format('Y-m-d');

    $form['interval_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Choose absence'),
      '#default_value' => 'today',
      '#description' => $this->t('Select "other period" to set an other date or part of a day.'),
      '#options' => [
        'today' => $this->t('Today (Whole day @date)', ['@date' => $today_string]),
        'tomorrow' => $this->t('Tomorrow (Whole day @date)', ['@date' => $tomorrow_string]),
        'custom' => $this->t('Other period'),
      ],
    ];


    $form['custom_interval'] = [
      '#type' => 'container',
    ];

    $form['custom_interval']['#states']['visible'][] = [
      ':input[name="interval_type"]' => [
        'value' => 'custom',
      ],
    ];

    $from_date = new DrupalDateTime();
    $from_date->setTime(0,0);

    $form['custom_interval']['from_date'] = [
      '#type' => 'datetime',
      '#date_date_element' => 'date',
      '#title' => $this->t('Absence from'),
      '#default_value' => $from_date,
      '#required' => TRUE,
      '#date_increment' => 60,
    ];

    $from_date = new DrupalDateTime();
    $from_date->setTime(23,59);

    $form['custom_interval']['field_absence_to'] = [
      '#type' => 'datetime',
      '#date_date_element' => 'date',
      '#title' => $this->t('Absence to'),
      '#default_value' => $from_date,
      '#required' => TRUE,
      '#date_increment' => 60,
    ];

    if ($current_user->hasPermission('school staff permissions')) {
      $options = [
        'reported' => $this->t('Reported absence'),
        'leave' => $this->t('Leave absence'),
      ];

      $form['absence_type'] = [
        '#type' => 'select',
        '#title' => $this->t('Absence type'),
        '#default_value' => 'reported',
        '#options' => $options,
      ];
    }
    else {
      $form['absence_type'] = [
        '#type' => 'value',
        '#value' => 'reported',
      ];
    }

    $form = parent::buildForm($form, $form_state);

    if ($current_user->hasPermission('school staff permissions')) {
      $form['notice'] = [
        '#type' => 'html_tag',
        '#tag' => 'em',
        '#value' => $this->t('Note! Any reported attendance of type invalid absence to whole class in any course will be converted to valid absence if the class session completely or partly interferes with the reported absence.'),
      ];
    }

    if (!empty($form['actions']['cancel']['#url'])) {
      /** @var \Drupal\Core\Url $url */
      $url = $form['actions']['cancel']['#url'];
      $query = $url->getOption('query');
      $query['cancel'] = '1';
      $url->setOption('query', $query);
      $form['actions']['cancel']['#url'] = $url;
    }

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

  protected function resolveFromToDate(&$from_date, &$to_date, FormStateInterface $form_state) {
    $interval_type = $form_state->getValue('interval_type', 'today');

    if ($interval_type === 'today' || $interval_type === 'tomorrow') {
      $from_date = new DrupalDateTime();
      $from_date->setTime(0,0,0);

      $to_date = new DrupalDateTime();
      $to_date->setTime(23,59,59);

      if ($interval_type === 'tomorrow') {
        $from_date->add(new \DateInterval('P1D'));
        $to_date->add(new \DateInterval('P1D'));
      }
    }
    else {
      /** @var DrupalDateTime $from_date */
      $from_date = $this->normalizeDate($form_state->getValue('from_date'));
      // Adjust seconds to 0.
      $from_date->setTime($from_date->format('H'), $from_date->format('i'), 0);
      /** @var DrupalDateTime $to_date */
      $to_date = $this->normalizeDate($form_state->getValue('field_absence_to'), NULL, '23:59:59');
      // Adjust seconds to 59.
      $to_date->setTime($to_date->format('H'), $to_date->format('i'), 59);
    }
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {

    $this->resolveFromToDate($from_date, $to_date, $form_state);
    if (!$from_date) {
      $form_state->setErrorByName('from_date', $this->t('@name field is required.', ['@name' => $this->t('Absence from')]));
      return;
    }
    if (!$to_date) {
      $form_state->setErrorByName('field_absence_to', $this->t('@name field is required.', ['@name' => $this->t('Absence to')]));
      return;
    }
    $interval = in_array('caregiver', $this->currentUser()->getRoles()) && !$this->currentUser()->hasPermission('school staff permissions') ? 'P7D' : 'P1Y';

    $message_line = $interval === 'P7D' ? '@name must be within +- 1 week.' : '@name must be within +- 1 year.';

    // Allow +/- 1 year of absence registration.
    $lower_limit = new DrupalDateTime();
    $lower_limit->setTime(0, 0);
    $lower_limit->sub(new \DateInterval($interval));

    $upper_limit = new DrupalDateTime();
    $upper_limit->setTime(23, 59, 59);
    $upper_limit->add(new \DateInterval($interval));

    if ($from_date < $lower_limit || $from_date > $upper_limit) {

      $form_state->setErrorByName('from_date', $this->t($message_line, ['@name' => $this->t('Absence from')]));
    }

    if ($to_date < $lower_limit || $to_date > $upper_limit) {
      $form_state->setErrorByName('field_absence_to', $this->t($message_line, ['@name' => $this->t('Absence to')]));
      return;
    }

    if ($to_date < $from_date) {
      $form_state->setErrorByName('field_absence_to', $this->t('%name must be higher than or equal to %min.', ['%name' => $this->t('Absence to'), '%min' => $this->t('Absence from')]));
      return;
    }

    // 30 days = 2592000 sec.
    if ($to_date->getTimestamp() - $from_date->getTimestamp() > 2592000) {
      $form_state->setErrorByName('field_absence_to', $this->t('@name can not be registered more then @limit days in a row.', ['@name' => $this->t('Absence'), '@limit' => 30]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $current_user_id = $this->currentUser()->id();

    // Clear out the accounts from the temp store.
    $this->tempStoreFactory->get('register_absence_form')->delete($current_user_id);

    if (!$form_state->getValue('confirm')) {
      $this->logger('confirm_form')->error('Confirm issue!');
      $this->messenger()->addError($this->t('Something went wrong. Try again.'));
      $form_state->setRebuild(TRUE);
      return;
    }

    if ($form_state->getValue('confirm')) {

      $this->resolveFromToDate($from_date, $to_date, $form_state);

      if (!$from_date || !$to_date) {
        $this->messenger()->addError($this->t('Something went wrong. Try again.'));
        return;
      }
      $absence_type = $form_state->getValue('absence_type', 'reported');
      $values = [];

      $values[] = [
        'field_absence_from' => $from_date->getTimestamp(),
        'date_string' => $from_date->format('Y-m-d'),
        'field_absence_type' => $absence_type,
      ];

      $date_walk_from = clone $from_date;
      $date_walk_to = clone $from_date;

      $date_walk_from->setTime(0,0);
      $date_walk_to->setTime(23, 59, 59);

      $i = 1;


      while ($date_walk_to < $to_date) {
        $values[$i - 1]['field_absence_to'] = $date_walk_to->getTimestamp();
        $values[$i - 1]['field_absence_to_debug'] = $date_walk_to->format('Y-m-d H:i:s');

        $date_walk_from->add(new \DateInterval('P1D'));
        $date_walk_to->add(new \DateInterval('P1D'));
        $values[] = [
          'field_absence_from' => $date_walk_from->getTimestamp(),
          'date_string' => $date_walk_from->format('Y-m-d'),
          'field_absence_type' => $absence_type,
        ];
        $i++;
      }

      unset($values[$i]);
      $values[$i - 1]['field_absence_to'] = $to_date->getTimestamp();

      if (!empty($form_state->getValue('accounts'))) {

        // Initialize batch (to set title).
        $batch = [
          'title' => $this->t('Register absence'),
          'init_message' => $this->t('Register absence'),
          'progress_message' => $this->t('Processed @current out of @total.'),
          'operations' => [],
        ];

        foreach ($form_state->getValue('accounts') as $uid => $value) {
          foreach ($values as $field_values) {
            $field_values['field_student'] = ['target_id' => $uid];
            $field_values['title'] = 'Dagsfrånvaro ' . $value . ' ' . $field_values['date_string'];
            $field_values['uid'] = $current_user_id;
            // Finish the batch and actually cancel the account.
            $batch['operations'][] = [[AbsenceDayHandler::class, 'createAbsenceDayNode'], [$field_values]];
          }
        }

        if (count($batch['operations']) < 20) {
          $batch['progressive'] = FALSE;
        }

        batch_set($batch);
        $this->logger('register_absence_form')->info('Submit batch @operations', ['@operations' => json_encode($batch['operations'])]);
        $this->messenger()->addStatus($this->t('Absence registered'));
      }
      else {
        $this->logger('register_absence_form')->error('Missing accounts in submit');
        $this->messenger()->addError($this->t('Something went wrong. Try again.'));
      }

    }
  }



}
