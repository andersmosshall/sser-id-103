<?php

namespace Drupal\simple_school_reports_user_import_support\Form;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\simple_school_reports_core\Pnum;
use Drupal\simple_school_reports_core\SchoolGradeHelper;
use Drupal\simple_school_reports_core\Service\EmailServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\Session;

abstract class ImportUsersFormBase extends FormBase {

  protected bool $acceptUsersToImport = FALSE;

  protected array $usersToImport = [];

  protected array $preValidationErrors = [];

  protected array $addedPnums = [];

  protected array $addedEmails = [];

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected UuidInterface $uuid,
    protected Session $session,
    protected Pnum $pnum,
    protected EmailServiceInterface $emailService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('uuid'),
      $container->get('session'),
      $container->get('simple_school_reports_core.pnum'),
      $container->get('simple_school_reports_core.email_service'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $step = $form_state->get('step') ?: 'init';

    if ($step === 'preview') {
      $form['step'] = $this->buildPreviewFormStep($form, $form_state);
    }
    elseif ($step === 'pre_validation_failure') {
      $form['step'] = $this->buildPrevalidationFailureFormStep($form, $form_state);
    }
    else {
      $form['step'] = $this->buildIntiFormStep($form, $form_state);
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $submit_label = $this->t('Preview');
    if ($step === 'pre_validation_failure') {
      $submit_label = $this->t('Next');
    }
    if ($step === 'preview') {
      $submit_label = $this->t('Import');
    }

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $submit_label,
      '#button_type' => 'primary',
    ];

    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#attributes' => ['class' => ['button', 'dialog-cancel']],
      '#url' => Url::fromRoute('simple_school_reports_user_import_support.import_methods'),
      '#cache' => [
        'contexts' => [
          'url.query_args:destination',
        ],
      ],
    ];

    if ($step === 'preview') {
      $form['actions']['submit']['#submit'] = ['::finalSubmit'];
    }
    elseif ($step === 'pre_validation_failure') {
      $form['actions']['submit']['#submit'] = ['::preValidationSubmit'];
    }
    else {
      $form['actions']['submit']['#submit'] = ['::initSubmit'];
      $form['#validate'][] = '::validateInit';
    }

    return $form;
  }

  abstract protected function buildIntiFormStep(array $form, FormStateInterface $form_state): array;

  public static function encryptSsn(?string $ssn): ?string {
    if (!$ssn) {
      return NULL;
    }

    $encryption_profile = \Drupal::config('field_encrypt.settings')->get('encryption_profile');
    /** @var \Drupal\encrypt\EncryptionProfileManagerInterface $encryption_profile_service */
    $encryption_profile_service = \Drupal::service('encrypt.encryption_profile.manager');

    return base64_encode(
      \Drupal::service('encryption')->encrypt(
        $ssn,
        $encryption_profile_service->getEncryptionProfile($encryption_profile)
      )
    );
  }

  public static function decryptSsn(?string $ssn): ?string {
    if (!$ssn) {
      return NULL;
    }

    $encryption_profile = \Drupal::config('field_encrypt.settings')->get('encryption_profile');
    /** @var \Drupal\encrypt\EncryptionProfileManagerInterface $encryption_profile_service */
    $encryption_profile_service = \Drupal::service('encrypt.encryption_profile.manager');

    return \Drupal::service('encryption')->decrypt(
      base64_decode($ssn),
      $encryption_profile_service->getEncryptionProfile($encryption_profile)
    );
  }

  public static function userDisplayNameFromValues(array $user_values): string {
    if (empty($user_values)) {
      return t('Unknown user');
    }

    if (empty($user_values['field_first_name']) && empty($user_values['field_last_name']) && empty($user_values['field_ssn']) && empty($user_values['mail'])) {
      return t('Unknown user');
    }

    $ssn = self::decryptSsn($user_values['field_ssn']);

    $name = $user_values['field_first_name'] . ' ' . $user_values['field_last_name'] . ',  ' . $ssn . ',  ' . $user_values['mail'];
    if (trim($name) === '') {
      return t('Unknown user');
    }
    return $name;
  }

  protected function userDisplayName(string $uuid, FormStateInterface $form_state): string {
    $user_values = ($form_state->get('users_to_import') ?? [])[$uuid] ?? [];
    return self::userDisplayNameFromValues($user_values);
  }

  protected function addUserToImport(
    string $email,
    string $ssn,
    string $first_name,
    string $last_name,
    array $roles,
    ?string $gender = NULL,
    ?int $grade = NULL,
    ?array $address = NULL,
    ?string $mentor = NULL,
    ?array $caregivers = NULL,
    ?string $telephone_number = NULL,
  ): ?string {
    if (!$this->acceptUsersToImport) {
      throw new \RuntimeException('Cannot add user to import when not in the right state.');
    }

    $uuid = \Drupal::service('uuid')->generate();

    // Validate ssn.
    $ssn = $this->pnum->normalizeIfValid($ssn, TRUE);
    if (!$ssn) {
      $this->preValidationErrors[$uuid]['invalid_ssn'] = $this->t('Missing valid personal number, user will not be imported.');
    }
    else {
      $this->addedPnums[$ssn][] = $uuid;
    }
    if ($email) {
      $this->addedEmails[$email][] = $uuid;
    }

    if (!empty($address)) {
      if (empty($address['field_street_address']) && empty($address['field_zip_code']) && empty($address['field_city'])) {
        $this->preValidationErrors[$uuid]['invalid_address'] = $this->t('Invalid address, user will be imported without address.');
        $address = NULL;
      }
    }

    // Filter roles.
    $roles = array_filter($roles, function ($role) {
      return in_array($role, ['student', 'teacher', 'caregiver', 'administrator', 'principle']);
    });

    $grades = SchoolGradeHelper::getSchoolGradesMapAll();
    $grade = isset($grades[$grade]) ? $grade : NULL;

    $import_priority = 1000000000;
    if (in_array('caregiver', $roles)) {
      $import_priority = 100000000;
    }
    if (in_array('student', $roles)) {
      $import_priority = 10000000;
      if ($grade) {
        $import_priority = 10000000 - $grade;
      }
    }

    if ($gender !== 'male' && $gender !== 'female') {
      $gender = NULL;
    }

    $this->usersToImport[$uuid] = [
      'field_first_name' => $first_name,
      'field_last_name' => $last_name,
      'field_ssn' => self::encryptSsn($ssn),
      'field_gender' => $gender,
      'field_address' => $address,
      'field_mentor' => $mentor,
      'field_caregivers' => $caregivers,
      'field_telephone_number' => $telephone_number,
      'field_grade' => $grade,
      'mail' => $email,
      'roles' => $roles,
      'uuid' => $uuid,
      'import_priority' => $import_priority,
    ];

    return $uuid;
  }

  abstract protected function resolveUsersToImport(FormStateInterface $form_state): void;

  /**
   * {@inheritdoc}
   */
  public function validateInit(array &$form, FormStateInterface $form_state) {
    // No real use for this method in this class.
    $this->acceptUsersToImport = TRUE;
    $this->resolveUsersToImport($form_state);
    $this->acceptUsersToImport = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function initSubmit(array &$form, FormStateInterface $form_state) {
    if (empty($this->usersToImport)) {
      $this->messenger()->addWarning($this->t('No users to import.'));
      $form_state->setRedirect('simple_school_reports_user_import_support.import_methods');
      return;
    }

    $form_state->set('import_id', $this->uuid->generate());

    foreach ($this->addedPnums as $ssn => $uuids) {
      if (count($uuids) > 1) {
        foreach ($uuids as $uuid) {
          $this->preValidationErrors[$uuid]['duplicate_ssn'] = $this->t('There are multiple users with this personal number in the import source, user will not be imported.');
        }
      }
    }

    foreach ($this->addedEmails as $email => $uuids) {
      if (count($uuids) > 1) {
        foreach ($uuids as $uuid) {
          $this->preValidationErrors[$uuid]['duplicate_email'] = $this->t('There are multiple users with this email in the import source, user will not be imported.');
        }
      }
    }

    $form_state->set('users_to_import', $this->usersToImport);
    $this->usersToImport = [];
    if (empty($this->preValidationErrors)) {
      $this->preValidationSubmit($form, $form_state);
      return;
    }

    $form_state->set('pre_validation_errors', $this->preValidationErrors);
    $this->preValidationErrors = [];
    $form_state->setRebuild();
    $form_state->set('step', 'pre_validation_failure');
  }

  protected function buildPrevalidationFailureFormStep(array $form, FormStateInterface $form_state): array {
    $pre_validation_errors = $form_state->get('pre_validation_errors') ?? [];

    if (empty($pre_validation_errors)) {
      $form['info'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('No issues were found in the import source.'),
      ];
      return $form;
    }

    $form['info'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('The following issues were found in the import source:'),
    ];

    $error_items = [];
    $uuids_to_skip = [];
    foreach ($pre_validation_errors as $uuid => $errors) {
      $display_name = $this->userDisplayName($uuid, $form_state);
      foreach ($errors as $type => $error) {
        $error_items[] = $display_name . ': ' . $error;
        $skip_types = ['invalid_ssn', 'duplicate_ssn', 'duplicate_email'];
        if (in_array($type, $skip_types)) {
          $uuids_to_skip[] = $uuid;
        }
      }
    }

    $form['errors'] = [
      '#theme' => 'item_list',
      '#items' => $error_items,
    ];

    $form['uuids_to_skip'] = [
      '#type' => 'value',
      '#value' => $uuids_to_skip,
    ];

    return $form;
  }

  public function preValidationSubmit(array &$form, FormStateInterface $form_state) {
    $form_state->set('pre_validation_errors', []);

    $users_to_import = $form_state->get('users_to_import') ?? [];
    $uuids_to_skip = $form_state->getValue('uuids_to_skip', []);
    foreach ($uuids_to_skip as $uuid) {
      unset($users_to_import[$uuid]);
    }

    $form_state->set('users_to_import', $users_to_import);

    // Sort by import priority.
    uasort($users_to_import, function ($a, $b) {
      return $a['import_priority'] <=> $b['import_priority'];
    });

    // Initialize batch (to set title).
    $batch = [
      'title' => $this->t('Validate users'),
      'init_message' => $this->t('Validate users'),
      'progress_message' => $this->t('Processed @current out of @total.'),
      'operations' => [],
      'finished' => [self::class, 'finishedValidation'],
    ];

    $import_id = $form_state->get('import_id');

    foreach ($users_to_import as $uuid => $values) {
      $batch['operations'][] = [[self::class, 'importUser'], [$uuid, $values, TRUE, $import_id]];
    }

    if (!empty($batch['operations'])) {
      batch_set($batch);
      $form_state->set('step', 'preview');
      $form_state->setRebuild();
    }
    else {
      $this->messenger()->addWarning($this->t('No users to import.'));
      $form_state->setRedirect('simple_school_reports_user_import_support.import_methods');
    }
  }

  protected function buildPreviewFormStep(array $form, FormStateInterface $form_state): array {
    $validation_errors = $this->session->get('import:errors:' . $form_state->get('import_id')) ?? [];
    $users_to_display = $form_state->get('users_to_import') ?? [];

    // First list users with validation errors.
    if (!empty($validation_errors)) {
      $form['user_with_errors'] = [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $this->t('Users with validation errors'),
      ];

      foreach ($validation_errors as $uuid => $errors) {
        if (!isset($users_to_display[$uuid])) {
          continue;
        }
        unset($users_to_display[$uuid]);
        $display_name = $this->userDisplayName($uuid, $form_state);

        $actions = [
          'skip' => $this->t('Skip @name', ['@name' => $display_name]),
        ];

        foreach ($errors as $type => $error) {
          if ($type === 'exists') {
            $actions['import'] = $this->t('Import and overwrite with @name', ['@name' => $display_name]);
            break;
          }
        }
        $default_action = NULL;
        if (count($actions) === 1) {
          $default_action = array_key_first($actions);
        }

        $form['label_' . $uuid] = [
          '#type' => 'html_tag',
          '#tag' => 'strong',
          '#value' => $display_name,
        ];
        $form['error_' . $uuid] = [
          '#markup' => implode('<br>', $errors),
          '#prefix' => '<div>',
          '#suffix' => '</div>',
        ];
        $form['import_status_' . $uuid] = [
          '#type' => 'radios',
          '#options' => $actions,
          '#default_value' => $default_action,
          '#required' => TRUE,
        ];
        $form['separator' . $uuid] = [
          '#markup' => '<hr>',
        ];
      }
    }

    // Then list students.
    $form['students'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t('Students and caregivers to import'),
    ];
    $form['students_table'] = [
      '#type' => 'table',
      '#empty' => $this->t('No students to import'),
      '#header' => [
        'user' => $this->t('User'),
        'grade' => $this->t('School grade'),
      ],
    ];

    $school_grades = SchoolGradeHelper::getSchoolGradesMap();

    foreach ($users_to_display as $uuid => $values) {
      if (!in_array('student', $values['roles'])) {
        continue;
      }
      unset($users_to_display[$uuid]);
      $display_name = $this->userDisplayName($uuid, $form_state);


      $form['students_table']['#rows'][$uuid] = [
        'user' => $display_name,
        'grade' => $school_grades[$values['field_grade'] ?? ''] ?? '-',
      ];

      if (!empty($values['field_caregivers'])) {
        foreach ($values['field_caregivers'] as $caregiver) {
          unset($users_to_display[$caregiver]);
          $form['students_table']['#rows'][$caregiver] = [
            'user' => $this->t('Caregiver') . ': ' . $this->userDisplayName($caregiver, $form_state),
            'grade' => '-',
          ];
        }
      }
    }

    // Then list the rest.
    $form['others'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t('Other users to import'),
    ];
    $form['others_table'] = [
      '#type' => 'table',
      '#empty' => $this->t('No other users to import'),
      '#header' => [
        'user' => $this->t('User'),
        'roles' => $this->t('Roles'),
      ],
    ];

    foreach ($users_to_display as $uuid => $values) {
      $display_name = $this->userDisplayName($uuid, $form_state);
      $form['others_table']['#rows'][$uuid] = [
        'user' => $display_name,
        'roles' => implode(', ', $values['roles']),
      ];
    }

    return $form;
  }

  public function finalSubmit(array &$form, FormStateInterface $form_state) {
    $users_to_import = [];
    foreach ($form_state->get('users_to_import') ?? [] as $uuid => $values) {
      if ($form_state->getValue('import_status_' . $uuid, 'import') === 'import') {
        $users_to_import[$uuid] = $values;
      }
    }
    // Sort by import priority.
    uasort($users_to_import, function ($a, $b) {
      return $b['import_priority'] <=> $a['import_priority'];
    });

    // Initialize batch (to set title).
    $batch = [
      'title' => $this->t('Import users'),
      'init_message' => $this->t('Import users'),
      'progress_message' => $this->t('Processed @current out of @total.'),
      'operations' => [],
      'finished' => [self::class, 'finishedImport'],
    ];

    $import_id = $form_state->get('import_id');

    foreach ($users_to_import as $uuid => $values) {
      $batch['operations'][] = [[self::class, 'importUser'], [$uuid, $values, FALSE, $import_id]];
    }

    if (!empty($batch['operations'])) {
      $form_state->setRedirect('simple_school_reports_user_import_support.import_methods');
      batch_set($batch);
    }
    else {
      $this->messenger()->addWarning($this->t('No users to import.'));
      $form_state->setRedirect('simple_school_reports_user_import_support.import_methods');
    }
  }

  public static function importUser(string $uuid, array $values, bool $dry_run, string $import_id, &$context) {
    $user_storage = \Drupal::entityTypeManager()->getStorage('user');

    // ToDo check if user exists by email or ssn.

    /** @var \Drupal\user\UserInterface $user */
    $user = $user_storage->create([
      'name' => $values['uuid'],
      'mail' => $values['mail'],
      'status' => 1,
      'langcode' => 'sv',
      'field_birth_date_source' => 'ssn',
    ]);

    foreach ($values as $field_name => $field_value) {
      if ($field_name === 'field_ssn') {
        $field_value = self::decryptSsn($field_value);
      }

      if ($field_name === 'field_caregivers') {
        $caregivers_values = [];
        foreach ($field_value as $key => $caregiver) {
          $target_id = $context['results']['imported'][$caregiver] ?? NULL;
          if ($target_id) {
            $caregivers_values[] = ['target_id' => $target_id];
          }
        }
        $field_value = $caregivers_values;
      }

      if ($field_name === 'field_mentor') {
        $target_id = $context['results']['imported'][$field_value] ?? NULL;
        $field_value = NULL;
        if ($target_id) {
          $field_value = ['target_id' => $target_id];
        }
      }

      if ($field_name === 'field_address') {
        $paragraph = \Drupal::entityTypeManager()->getStorage('paragraph')->create([
          'type' => 'address',
          'langcode' => 'sv',
          'field_street_address' => $field_value['field_street_address'],
          'field_zip_code' => $field_value['field_zip_code'],
          'field_city' => $field_value['field_city'],
        ]);
        $field_value = $paragraph;
      }

      if ($user->hasField($field_name)) {
        $user->set($field_name, $field_value);
      }
    }

    if ($dry_run) {
      $violations = $user->validate();
      if ($violations->count() > 0) {
        foreach ($violations as $violation) {
          $property_path = $violation->getPropertyPath();
          if ($property_path && $user->getFieldDefinition($property_path)) {
            $context['results']['validation_errors'][$uuid][] = $user->getFieldDefinition($property_path)->getLabel() . ': ' . $violation->getMessage();
          }
          else {
            $context['results']['validation_errors'][$uuid][] = t('Unknown validation error') . ': ' . $violation->getMessage();
          }
        }
      }
    }
    else {
      if (!empty($context['results']['validation_errors'][$uuid])) {
        $user_display_name = self::userDisplayNameFromValues($values);
        foreach ($context['results']['validation_errors'][$uuid] as $key => $error) {
          $context['results']['validation_errors'][$uuid][$key] = $user_display_name . ': ' . $error;
        }
        return;
      }

      try {
        $user->setSyncing(TRUE);
        $user->save();
        $user->setSyncing(FALSE);
      } catch (\Exception $e) {
        $user_display_name = self::userDisplayNameFromValues($values);
        $context['results']['validation_errors'][$uuid][] = $user_display_name . ': ' . t('Unknown error occurred');
        \Drupal::logger('simple_school_reports_user_import_support')->error('Error importing user: @error', ['@error' => $e->getMessage()]);
        return;
      }

      $context['results']['imported'][$uuid] = $user->id();
    }

    $context['results']['import_id'] = $import_id;
  }

  public static function finishedValidation($success, $results) {
    if (!$success || empty($results['import_id'])) {
      \Drupal::messenger()->addError(t('Something went wrong'));
      return;
    }

    $import_id = $results['import_id'];
    if (!empty($results['validation_errors'])) {
      /** @var \Symfony\Component\HttpFoundation\Session\SessionInterface $session */
      $session = \Drupal::service('session');
      $session->set('import:errors:' . $import_id, $results['validation_errors']);
    }
  }

  public static function finishedImport($success, $results) {
    if (!$success) {
      \Drupal::messenger()->addError(t('Something went wrong'));
      return;
    }

    foreach ($results['validation_errors'] ?? [] as $errors) {
      foreach ($errors as $error) {
        \Drupal::messenger()->addError($error);
      }
    }

    if (!empty($results['imported'])) {
      \Drupal::messenger()->addMessage(t('Imported @count users', ['@count' => count($results['imported'])]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // No real use for this method in this class.
  }

  public static function access(AccountInterface $account) {
    if (!ssr_use_user_import()) {
      return AccessResult::forbidden();
    }

    return AccessResult::allowedIfHasPermission($account, 'administer simple school reports settings');
  }
}
