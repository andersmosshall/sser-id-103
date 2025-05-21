<?php

namespace Drupal\simple_school_reports_core;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\user\AccountForm;
use Drupal\user\UserInterface;

class UserFormAlter {

  public static function userFormAlter(&$form, FormStateInterface $form_state) {
    $user = \Drupal::currentUser();

    $form['#default_mail_access'] = !empty($form['account']['mail']['#access']);

    // Disable some default account fields to be handled by the guard fields
    // instead.
    $form['account']['name']['#access'] = FALSE;
    $form['account']['name']['#required'] = FALSE;
    $form['account']['mail']['#access'] = FALSE;
    $form['account']['mail']['#required'] = FALSE;
    $form['account']['mail']['#type'] = 'textfield';

    if (!empty($form['account']['roles']) && $user->hasPermission('assign user roles')) {
      $form['account']['roles']['#access'] = TRUE;
    }

    $account = NULL;
    /** @var \Drupal\user\AccountForm $form_object */
    $form_object = $form_state->getFormObject();

    if ($form_object instanceof AccountForm) {
      /** @var \Drupal\user\Entity\User $account */
      $account = $form_object->getEntity();
    }

    // Do not set password for new users.
    if (!$account || $account->isNew()) {
      $form['account']['pass']['#required'] = FALSE;
      $form['account']['pass']['#access'] = FALSE;
      $form['field_protected_personal_data']['#access'] = FALSE;
      $form['#ssr_new_user'] = TRUE;
      $form['#entity_builders'][] = [self::class, 'generateUserPassword'];
    }

    if (!empty($form['#form_mode'])) {
      if ($form['#form_mode'] === 'register' || $form['#form_mode'] === 'default') {
        $student_fields = ['field_grade', 'field_caregivers', 'field_mentor', 'field_adapted_studies', 'field_class'];

        foreach ($student_fields as $student_field) {
          $form[$student_field]['#states']['visible'][] = [
            ':input[name="roles[student]"]' => [
              'checked' => TRUE,
            ],
          ];
          $form[$student_field]['#states']['disabled'][] = [
            ':input[name="roles[student]"]' => [
              'checked' => FALSE,
            ],
          ];
        }
      }
    }


    if (!empty($form['field_protected_personal_data'])) {
      $form['protected_personal_data_info'] = [
        '#type' => 'container',
      ];

      $form['protected_personal_data_info']['security_marking']['info'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['password-suggestions'],
        ],
        '#states' => [
          'visible' => [
            'select[name="field_protected_personal_data"]' => ['value' => 'security_marking'],
          ],
        ],
        'value' => [
          '#markup' => t('If users have privacy marking or protected population records, only administrators and principals have access to sensitive information. School staff and the user himself can still see a limited set of information about the user. For example. name and class information (for students).') .
            '<br>' .
            t('NOTE: Always store data that is essential for the purpose of your school. Do not store more data than necessary!') .
            '<br>' .
            t('NOTE: Remember that you probably want to set caregivers and siblings to this setting as well.'),
        ],
      ];
    }

    self::addGuards($form, $account, TRUE);
    $form['#after_build'][] = [self::class, 'userFormAfterBuild'];
  }

  public static function userFormAfterBuild($form, FormStateInterface $form_state) {
    $pass_weight = !empty($form['account']['pass']['#weight']) ? $form['account']['pass']['#weight'] : 0.002;
    $password_policy_status_weight = $pass_weight - 0.0001;

    if (!empty($form['account']['password_policy_status'])) {
      $form['account']['password_policy_status']['#weight'] = $password_policy_status_weight;
      // Make sure attached is set to not break ajax.
      if (empty($form['account']['password_policy_status']['#attached'])) {
        $form['account']['password_policy_status']['#attached'] = [];
      }
      $form['#attached']['library'][] = 'simple_school_reports_core/password_policy';
    }

    if (!empty($form['field_protected_personal_data'])) {
      $secrecy_info_weight_base = $form['field_protected_personal_data']['#weight'] ?? 6;
      $secrecy_info_weight_base += 0.01;

      $form['protected_personal_data_info']['#weight'] = $secrecy_info_weight_base;

      if (array_key_exists('#access', $form['field_protected_personal_data']) &&  $form['field_protected_personal_data']['#access'] === FALSE) {
        $form['protected_personal_data_info']['#access'] = FALSE;
      }
    }
    else {
      $form['protected_personal_data_info']['#access'] = FALSE;
    }

    return $form;
  }

  public static function generatePassword($length = 32) {
    // Define character pools
    $lowercase = 'abcdefghijklmnopqrstuvwxyz';
    $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $numbers = '0123456789';
    $specialChars = '!@#$%^&*()-_=+[]{}|;:,.<>?';

    // Ensure the password contains at least one character from each pool
    $password = $lowercase[random_int(0, strlen($lowercase) - 1)] .
      $uppercase[random_int(0, strlen($uppercase) - 1)] .
      $numbers[random_int(0, strlen($numbers) - 1)] .
      $specialChars[random_int(0, strlen($specialChars) - 1)];

    // Combine all pools
    $allChars = $lowercase . $uppercase . $numbers . $specialChars;

    // Fill the rest of the password length with random characters from the combined pool
    for ($i = 4; $i < $length; $i++) {
      $password .= $allChars[random_int(0, strlen($allChars) - 1)];
    }

    // Shuffle the password to ensure randomness
    return str_shuffle($password);
  }

  public static function addGuards(&$form, $account = NULL, $use_field_to_set = FALSE) {
    if (!empty($form['#form_mode'])) {
      if ($form['#form_mode'] === 'student' || $form['#form_mode'] === 'caregiver' || $form['#form_mode'] === 'caregiver_ief') {
        if ($account->isNew() && !empty($form['account']['pass'])) {
          $default_pass = self::generatePassword();
          $form['account']['pass']['#default_value'] = ['pass1' => $default_pass, 'pass2' => $default_pass];
        }
      }
    }

    $use_constrained_user_list = \Drupal::moduleHandler()->moduleExists('simple_school_reports_constrained_user_list');
    if (!$use_constrained_user_list && !empty($form['field_birth_date_source']) && !empty($form['field_ssn']) && !empty($form['field_birth_date'])) {
      $birth_date_source_name = 'field_birth_date_source';
      if (!empty($form['#parents']) && count($form['#parents']) > 1) {
        $parents_copy = $form['#parents'];
        $first_element = array_shift($parents_copy);
        $parents_copy[] = $birth_date_source_name;
        $birth_date_source_name = $first_element . '[' . implode('][', $parents_copy) . ']';
      }

      if ($birth_date_source_name) {
        $form['field_ssn']['#states']['visible'][] = [
          ':input[name="' . $birth_date_source_name . '"]' => [
            'value' => 'ssn',
          ],
        ];

        $form['field_birth_date']['#states']['visible'][] = [
          ':input[name="' . $birth_date_source_name . '"]' => [
            'value' => 'birth_date',
          ],
        ];
      }
    }

    $form['name_guard'] = [
      '#type' => 'value',
      '#required' => FALSE,
      '#element_validate' => [[self::class, 'userNameValidate']],
      '#default_value' => $account && $account->getAccountName() ? $account->getAccountName() : '',
      '#weight' => -15,
    ];

    $default_mail_access = array_key_exists('#default_mail_access', $form) ? $form['#default_mail_access'] : TRUE;

    if ($default_mail_access) {
      $form['mail_guard'] = [
        '#type' => 'email',
        '#title' => t('Email address'),
        '#required' => FALSE,
        '#element_validate' => [[self::class, 'userMailValidate']],
        '#uid' => $account && $account->id() ? $account->id() : NULL,
        '#default_value' => $account && $account->getEmail() ? $account->getEmail() : '',
        '#weight' => -10,
      ];
    }

    if ($use_field_to_set) {
      $form['name_guard']['#field_to_set'] = 'name';
      if ($default_mail_access) {
        $form['mail_guard']['#field_to_set'] = 'mail';
      }
    }
  }

  public static function resolveRolesAutoSet(&$form, FormStateInterface $form_state) {
    $form_display_mode = NULL;

    if (!empty($form['#form_mode'])) {
      $form_display_mode = $form['#form_mode'];
    }
    else {
      if ($form_object = $form_state->getFormObject()) {
        if ($form_object instanceof AccountForm) {
          $form_display_mode = $form_object->getOperation();
          $form['#form_mode'] = $form_display_mode;
        }
      }
    }

    if ($form_display_mode === 'caregiver' || $form_display_mode === 'caregiver_ief') {
      $form['#entity_builders'][] = [self::class, 'caregiverUserBuild'];
    }
    if ($form_display_mode === 'student') {
      $form['#entity_builders'][] = [self::class, 'studentUserBuild'];
    }
  }

  public static function userNameValidate(&$element, FormStateInterface $form_state) {
    if (empty($element['#field_to_set'])) {
      return;
    }
    $form_state->setValue($element['#field_to_set'], self::resolveNameValue($form_state->getValue($element['#parents'])));
  }

  public static function userMailValidate(&$element, FormStateInterface $form_state) {
    $value = self::resolveMailValue($form_state->getValue($element['#parents']));
    $query = \Drupal::entityTypeManager()->getStorage('user')->getQuery()->accessCheck(FALSE);

    if (!empty($element['#uid'])) {
      $query->condition('uid', $element['#uid'], '<>');
    }
    $query->accessCheck(FALSE);
    $value_taken = (bool) $query
      ->condition('mail', $value)
      ->range(0, 1)
      ->count()
      ->execute();

    if ($value_taken) {
      $form_state->setError($element, t('The email address %value is already taken.', ['%value' => $value]));
    }

    if (empty($element['#field_to_set'])) {
      return;
    }
    $form_state->setValue($element['#field_to_set'], $value);
  }

  public static function resolveNameValue($value) : string {
    if (!$value) {
      /** @var \Drupal\Component\Uuid\UuidInterface $uuid_service */
      $uuid_service = \Drupal::service('uuid');
      $value = $uuid_service->generate();
    }
    return $value;
  }

  public static function resolveMailValue($value) : string {
    if (!$value) {
      /** @var \Drupal\Component\Uuid\UuidInterface $uuid_service */
      $uuid_service = \Drupal::service('uuid');
      $host = \Drupal::request()->getHost();
      $host = str_replace('www.', '', $host);
      $host = str_replace('https', '', $host);
      $host = str_replace('http', '', $host);
      $host = str_replace('://', '', $host);

      if ($host === 'default') {
        $school_name = Settings::get('ssr_school_name', 'stage');
        $school_name = mb_strtolower($school_name);
        $host = $school_name . '.simpleschoolreports.se';
      }

      $value = 'no-reply-' . $uuid_service->generate() . '@' . $host;
    }
    return $value;
  }


  public static function iefUserBuilder($entity_type, UserInterface $user,  &$form, FormStateInterface $form_state) {
    $delta = isset($form['#ief_row_delta']) ? $form['#ief_row_delta'] : NULL;
    if ($delta === NULL) {
      return;
    }
    $values = $form_state->getValue(['field_caregivers', 'form', $delta]);
    if ($values === NULL) {
      $values = $form_state->getValue(['field_caregivers', 'form', 'inline_entity_form', 'entities', $delta, 'form']);
      if ($values === NULL) {
        return;
      }
    }

    // Resolve name.
    $name_guard = isset($values['name_guard']) ? $values['name_guard'] : NULL;
    $name = self::resolveNameValue($name_guard);
    $user->set('name', $name);

    // Resolve mail.
    $mail_guard = isset($values['mail_guard']) ? $values['mail_guard'] : NULL;
    $mail = self::resolveMailValue($mail_guard);
    $user->set('mail', $mail);
  }

  public static function caregiverUserBuild($entity_type, UserInterface $user, &$form, FormStateInterface $form_state) {
    $user->addRole('caregiver');
    // Activate automatically on add.
    if ($user->isNew()) {
      $user->set('status', 1);
    }
  }

  public static function studentUserBuild($entity_type, UserInterface $user, &$form, FormStateInterface $form_state) {
    $user->addRole('student');
    // Activate automatically on add.
    if ($user->isNew()) {
      $user->set('status', 1);
    }
  }

  public static function generateUserPassword($entity_type, UserInterface $user, &$form, FormStateInterface $form_state) {
    if ($user->isNew()) {
      $password = $form_state->getValue('pass');
      if (!$password) {
        $password = self::generatePassword();
        $user->setPassword($password);
        $form_state->setValue('pass', $password);
      }
    }
  }

  public static function handleAbsenceField(ParagraphInterface $paragraph, bool $deleted = FALSE) {
    if ($paragraph->hasField('field_invalid_absence') && $paragraph->hasField('field_student') && !$paragraph->get('field_student')->isEmpty()) {
      $invalid_absence_change = 0;

      if ($paragraph->isNew() || !$paragraph->original || $deleted) {
        $invalid_absence_change = $paragraph->get('field_invalid_absence')->value;
        if ($deleted && is_numeric($invalid_absence_change)) {
          $invalid_absence_change *= -1;
        }
      }
      else {
        $new_invalid_absence = $paragraph->get('field_invalid_absence')->value;
        $old_invalid_absence = $paragraph->original->get('field_invalid_absence')->value;

        $invalid_absence_change = $new_invalid_absence - $old_invalid_absence;
      }

      $invalid_absence_change = is_numeric($invalid_absence_change) ? (int) $invalid_absence_change : 0;

      if (is_numeric($invalid_absence_change) && $invalid_absence_change !== 0) {
        $user_storage = \Drupal::entityTypeManager()->getStorage('user');
        $user = $user_storage->load($paragraph->get('field_student')->target_id);

        if ($user) {
          $current_invalid_absence = $user->get('field_invalid_absence')->value ?? 0;
          $user->set('field_invalid_absence', $current_invalid_absence + $invalid_absence_change);
          $user->save();
        }
      }

    }
  }

  public static function exposedFilterByTeacher(&$form, FormStateInterface $form_state, bool $use_me_option = FALSE) {
    if (!isset($form['field_teacher_target_id'])) {
      return;
    }

    $teachers = [
      '' => t('All'),
    ];
    if (in_array('teacher', \Drupal::currentUser()->getRoles())) {
      $teachers['my_uid'] = t('Me (@name)', ['@name' => \Drupal::currentUser()->getDisplayName()]);
      $form['#cache']['contexts'][] = 'user';
      $form['#cache']['tags'][] = 'user:' . \Drupal::currentUser()->id();
    }

    /** @var \Drupal\user\UserStorageInterface $user_storage */
    $user_storage = \Drupal::entityTypeManager()->getStorage('user');

    $uids = $user_storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('roles', 'teacher')
      ->sort('field_first_name')
      ->sort('field_last_name')
      ->execute();

    if (!empty($uids)) {
      /** @var UserInterface $user */
      foreach ($user_storage->loadMultiple($uids) as $user) {
        $teachers[$user->id()] = $user->getDisplayName();
      }
    }
    unset($form['field_teacher_target_id']['#size']);
    $form['field_teacher_target_id']['#type'] = 'select';
    $form['field_teacher_target_id']['#options'] = $teachers;
  }

  public static function exposedFilterByMentor(&$form, FormStateInterface $form_state) {
    if (!isset($form['field_mentor_target_id'])) {
      return;
    }

    $teachers = [
      '' => t('All'),
    ];
    if (in_array('teacher', \Drupal::currentUser()->getRoles())) {
      $teachers['my_uid'] = t('Me (@name)', ['@name' => \Drupal::currentUser()->getDisplayName()]);
      $form['#cache']['contexts'][] = 'user';
      $form['#cache']['tags'][] = 'user:' . \Drupal::currentUser()->id();
    }

    /** @var \Drupal\user\UserStorageInterface $user_storage */
    $user_storage = \Drupal::entityTypeManager()->getStorage('user');

    $uids = $user_storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('roles', 'teacher')
      ->sort('field_first_name')
      ->sort('field_last_name')
      ->execute();

    if (!empty($uids)) {
      /** @var UserInterface $user */
      foreach ($user_storage->loadMultiple($uids) as $user) {
        $teachers[$user->id()] = $user->getDisplayName();
      }
    }
    unset($form['field_mentor_target_id']['#size']);
    $form['field_mentor_target_id']['#type'] = 'select';
    $form['field_mentor_target_id']['#options'] = $teachers;
  }

  public static function exposedFilterByClass(&$form, FormStateInterface $form_state) {
    if (!isset($form['field_class_target_id'])) {
      return;
    }

    /** @var \Drupal\Core\Extension\ModuleHandlerInterface $module_handler */
    $module_handler = \Drupal::service('module_handler');

    if (!$module_handler->moduleExists('simple_school_reports_class')) {
      $form['field_class_target_id']['#access'] = FALSE;
      return;
    }

    /** @var \Drupal\simple_school_reports_class_support\Service\SsrClassServiceInterface $class_service */
    $class_service = \Drupal::service('simple_school_reports_class_support.class_service');
    $classes = $class_service->getSortedClassOptions();

    unset($form['field_class_target_id']['#size']);
    $form['field_class_target_id']['#type'] = 'select';
    $form['field_class_target_id']['#options'] = $classes;
  }

  public static function exposedFilterAutocomplete(&$form, FormStateInterface $form_state, $field_name) {
    if (!isset($form[$field_name])) {
      return;
    }

    $form[$field_name]['#type'] = 'entity_autocomplete';
    $form[$field_name]['#target_type'] = 'user';
    $form[$field_name]['#selection_settings'] =  [
      'include_anonymous' => FALSE,
    ];
    $form[$field_name]['#validate_reference'] = FALSE;
  }

  public static function getUidFromAutocompleteString(null|string|AccountInterface $value): ?string {
    if (!$value) {
      return NULL;
    }

    if ($value instanceof AccountInterface) {
      return (string) $value->id();
    }

    if (is_numeric($value)) {
      return $value;
    }

    preg_match_all('#\((.*?)\)#', $value, $match);
    if (empty($match[1])) {
      return NULL;
    }

    return $match[1][count($match[1]) - 1];
  }


  public static function cancelFormAlter(&$form, FormStateInterface $form_state) {
    $full_permission = \Drupal::currentUser()->hasPermission('administer modules');

    if (!$full_permission) {
      $form['description']['#access'] = FALSE;
      $form['user_cancel_method']['#access'] = FALSE;
      $form['user_cancel_method']['#default_value'] = 'user_cancel_reassign';

      $form['user_cancel_notify']['#access'] = FALSE;
      $form['user_cancel_notify']['#default_value'] = FALSE;

      $form['user_cancel_confirm']['#access'] = FALSE;
      $form['user_cancel_confirm']['#default_value'] = FALSE;
    }

  }
}
