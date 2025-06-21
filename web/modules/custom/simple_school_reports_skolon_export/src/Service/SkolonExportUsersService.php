<?php

namespace Drupal\simple_school_reports_skolon_export\Service;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\simple_school_reports_core\Service\CourseServiceInterface;
use Drupal\simple_school_reports_core\Service\ExportUsersServiceBase;
use Drupal\user\UserInterface;

/**
 * Class PMOExportUsersService
 */
class SkolonExportUsersService extends ExportUsersServiceBase {

  protected CourseServiceInterface $courseService;


  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account): bool {
    return $account->hasPermission('administer simple school reports settings');
  }

  public function getServiceId(): string {
    return 'simple_school_reports_skolon_export.export_users_skolon';
  }

  public function getFileExtension(): string {
    return 'csv';
  }

  public function getShortDescription(): TranslatableMarkup {
    return $this->t('Skolon');
  }

  public function supportedRoles(): array {
    return [
      'student' => $this->t('Student'),
      'teacher' => $this->t('Teacher'),
    ];
  }

  protected function rolesMap(): array {
    return [
      'student' => 'Elev',
      'teacher' => 'Lärare',
    ];
  }

  public function getDescription(): TranslatableMarkup {
    return $this->t('This export will generate file with the selected users to be bulk imported to Skolon. NOTE: Only users with roles @roles will be included. Any other users will be ignored.', [
      '@roles' => implode(', ', $this->supportedRoles()),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getOptionsForm(): array {
    $form = parent::getOptionsForm();

    $form['auto_include_mentors'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically include mentors'),
      '#description' => $this->t('If any of the exported user are students and have mentors, those mentors will be included in the export.'),
      '#default_value' => FALSE,
    ];

    $form['ssn_behavior'] = [
      '#type' => 'radios',
      '#title' => $this->t('Behavior if user is missing personal number'),
      '#options' => [
        'ignore' => $this->t('Ignore user with warning'),
        'abort' => $this->t('Abort the export with an error'),
      ],
      '#default_value' => 'ignore',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function getOptionsWithDefaults(array $options): array {
    $options += [
      'auto_include_mentors' => FALSE,
      'ssn_behavior' => 'ignore',
    ];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function modifyUidsList(array $uids, array $options): array {
    $uids = parent::modifyUidsList($uids, $options);

    $options = static::getOptionsWithDefaults($options);
    if (!$options['auto_include_mentors'] || empty($uids)) {
      return $uids;
    }

    $results = $this->connection->select('user__field_mentor', 'm')
      ->condition('m.entity_id', $uids, 'IN')
      ->fields('m', ['entity_id', 'field_mentor_target_id'])
      ->execute();

    $allowed_mentor_ids = $this->entityTypeManager->getStorage('user')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('roles', 'teacher')
      ->condition('status', 1)
      ->execute();

    foreach ($results as $result) {
      // Only include mentors that are teachers and active.
      if (!in_array($result->field_mentor_target_id, $allowed_mentor_ids)) {
        continue;
      }
      $uids[] = $result->field_mentor_target_id;
    }
    return array_unique($uids);
  }

  protected function getSsnIfValid(UserInterface $user): string|null {
    $ssn = $user->get('field_ssn')->value;
    if ($ssn) {
      $ssn = $this->pnumService->normalizeIfValid($ssn, FALSE);
    }
    return $ssn ?? NULL;
  }

  public function getErrors(array $uids, array $options): array {
    $options = static::getOptionsWithDefaults($options);

    $errors = parent::getErrors($uids, $options);
    if (empty($uids)) {
      $errors['general'] = $this->t('No users selected.');
    }

    $users = $this->entityTypeManager->getStorage('user')->loadMultiple($uids);

    if (empty($users)) {
      $errors['general'] = $this->t('No users selected.');
    }

    $ssn_map = [];

    $grades = simple_school_reports_core_allowed_user_grade();
    unset($grades[-99]);
    unset($grades[99]);

    /** @var \Drupal\user\UserInterface $user */
    foreach ($users as $user) {
      $uid = $user->id();

      if (!$user->isActive()) {
        $errors[$uid] = $this->t('User @name is not a active', [
          '@name' => $user->getDisplayName(),
        ]);
        continue;
      }

      $has_any_supported_role = FALSE;
      foreach (array_keys($this->supportedRoles()) as $supported_role) {
        if ($user->hasRole($supported_role)) {
          $has_any_supported_role = TRUE;
          break;
        }
      }
      if (!$has_any_supported_role) {
        $errors[$uid] = $this->t('User @name does not have any supported roles @roles', [
          '@name' => $user->getDisplayName(),
          '@roles' => implode(', ', $this->supportedRoles()),
        ]);
        continue;
      }

      $ssn = $this->getSsnIfValid($user);
      $ssn_behavior = $options['ssn_behavior'];
      if (!$ssn && $ssn_behavior === 'abort') {
        $errors[$uid] = $this->t('User @name is missing a valid personal number', [
          '@name' => $user->getDisplayName(),
        ]);
        continue;
      }
      $ssn_map[$ssn][] = $user->getDisplayName();
    }

    // Check that all ssn are unique.
    foreach ($ssn_map as $ssn => $users) {
      if (count($users) > 1) {
        $errors['ssn_unique'] = $this->t('Personal number @ssn is used by multiple users: @users', [
          '@ssn' => $ssn,
          '@users' => implode(', ', $users),
        ]);
      }
    }

    return $errors;
  }

  public function getUserRow(UserInterface $user, array $options): ?array {
    $options = static::getOptionsWithDefaults($options);
    $this->getErrors([$user->id()], $options);

    $ssn = $this->getSsnIfValid($user);

    if (!$ssn && !empty($options['enforce_pnum'])) {
      $ssn = $options['enforce_pnum'];
    }

    if (!empty($this->getErrors([$user->id()], []))) {
      if (empty($options['skip_message'])) {
        $this->messenger->addError($this->t('Skipping @name due to unknown error. Try again.', ['@name' => $user->getDisplayName()]));
      }
      return NULL;
    }

    if (!$ssn) {
      if (empty($options['skip_message'])) {
        $this->messenger->addWarning($this->t('Skipping @name due to missing personal number.', ['@name' => $user->getDisplayName()]));
      }
      return NULL;
    }

    $is_student = $user->hasRole('student');
    $is_teacher = $user->hasRole('teacher');
    [$grade, $school_type] = $this->userMetaDataService->getUserSchoolGradeAndType($user->id());

    $adress = $this->getUserAdress($user, 50, 10, 50);
    $phone_number = $user->get('field_telephone_number')->value ?? '';
    $mobile_start_pattern = [
      '+467',
      '00467',
      '07',
    ];
    $is_mobile = FALSE;
    foreach ($mobile_start_pattern as $pattern) {
      if (str_starts_with($phone_number, $pattern)) {
        $is_mobile = TRUE;
        break;
      }
    }

    $course_ids = [];
    if ($is_student) {
      $course_ids = $this->courseService->getStudentActiveCourseIds($user->id());
    }
    if ($is_teacher) {
      $course_ids = $this->courseService->getTeacherActiveCourseIds($user->id());
    }

    if (!empty($options['only_ids'])) {
      $courses = $course_ids;
    }
    else {
      $courses = [];
      $course_nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($course_ids);
      foreach ($course_nodes as $course_node) {
        $courses[$course_node->id()] = str_replace('|', '', $course_node->label());
      }
    }


    $class_target = $is_student ? $user->get('field_class')->target_id : NULL;
    $class = '';
    if ($class_target) {
      $class = $class_target;
      if (empty($options['only_ids'])) {
        $class_entity = $user->get('field_class')->entity;
        $class = $class_entity ? $class_entity->label() : '';
        $class = str_replace('|', '', $class);
      }
    }

    $role_names = [];
    foreach ($user->getRoles() as $role) {
      if (isset($this->rolesMap()[$role])) {
        $role_names[] = $this->rolesMap()[$role];
      }
    }

    $birth_date_ts = $user->get('field_birth_date')->value;
    $birth_date = $birth_date_ts ? date('Y-m-d', $birth_date_ts) : '';

    // Reference: Förnamn;Efternamn;E-post;Användartyp;Användarnamn;EPPN;Idp identifierare;Hemtelefonnummer;Mobiltelefonnummer;Adress;Stad;Postnummer;Födelsedatum;Studievägskod;Personnummer;Skolform;Årskurs;Undervisningsgrupper;Klasser
    $user_row = [
      'first_name' => $user->get('field_first_name')->value ?? '',
      'last_name' => $user->get('field_last_name')->value ?? '',
      'email' => $this->emailService->getUserEmail($user) ?? '',
      'user_type' => $role_names[0] ?? '',
      'username' => $this->emailService->getUserEmail($user) ?? '',
      'eppn' => '',
      'idp_identifier' => '',
      'home_phone' => $is_mobile ? '' : $phone_number,
      'mobile_phone' => $is_mobile ? $phone_number : '',
      'address' => $adress['adress'] ?? '',
      'city' => $adress['city'] ?? '',
      'postal_code' => $adress['postal_code'] ?? '',
      'birth_date' => $birth_date,
      'study_path_code' => '',
      'ssn' => $ssn,
      'school_type' => $school_type,
      'grade' => $is_student && $grade > 0 ? $grade : '',
      'courses' => implode('|', $courses),
      'classes' => $class,
    ];

    // Clean up the data.
    foreach ($user_row as $key => $value) {
      $value = $value ?? '';
      $value = str_replace(';', '', $value);
      $value = str_replace('"', '""', $value);
      $value = trim($value);
      $value = '"' . $value . '"';
      $user_row[$key] = $value;
    }

    return $user_row;
  }

  /**
   * {@inheritdoc}
   */
  public function makeFileContent(array $user_rows, array $options): ?string {
    if (empty($user_rows)) {
      return NULL;
    }

    $ssn_map = [];

    $formatted_rows = [];
    $formatted_rows[] = 'Förnamn;Efternamn;E-post;Användartyp;Användarnamn;EPPN;Idp identifierare;Hemtelefonnummer;Mobiltelefonnummer;Adress;Stad;Postnummer;Födelsedatum;Studievägskod;Personnummer;Skolform;Årskurs;Undervisningsgrupper;Klasser';

    foreach ($user_rows as $key => $user_row) {
      $ssn = $user_row['ssn'];
      $ssn_map[$ssn][] = $key;

      $formatted_rows[] = implode(';', array_values($user_row));
    }

    // Final validation of unique ssn.
    foreach ($ssn_map as $items) {
      if (count($items) > 1) {
        return NULL;
      }
    }

    $file_content = implode(PHP_EOL, $formatted_rows);

    return $file_content;
  }

}
