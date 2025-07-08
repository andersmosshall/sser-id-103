<?php

namespace Drupal\simple_school_reports_pmo_export\Service;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\simple_school_reports_core\SchoolGradeHelper;
use Drupal\simple_school_reports_core\Service\ExportUsersServiceBase;
use Drupal\user\UserInterface;

/**
 * Class PMOExportUsersService
 */
class PMOExportUsersService extends ExportUsersServiceBase {



  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account): bool {
    return $account->hasPermission('administer simple school reports settings');
  }

  public function getServiceId(): string {
    return 'simple_school_reports_pmo_export:export_users_pmo';
  }

  public function getFileExtension(): string {
    return 'txt';
  }

  public function getShortDescription(): TranslatableMarkup {
    return $this->t('PMO');
  }

  public function getDescription(): TranslatableMarkup {
    return $this->t('This export will generate file with the selected users to be compatible with PMO systems. Use this if you want to import users to a PMO system.');
  }

  /**
   * {@inheritdoc}
   */
  public function getOptionsForm(): array {
    $form = parent::getOptionsForm();

    $form['include_caregivers'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include caregivers'),
      '#default_value' => TRUE,
    ];

    $form['ssn_caregivers_behavior'] = [
      '#type' => 'radios',
      '#title' => $this->t('Behavior if caregiver is missing personal number'),
      '#options' => [
        'ignore' => $this->t('Ignore caregiver'),
        'include_with_warning' => $this->t('Include without personal number but show me a warning'),
        'include_silent' => $this->t('Include without personal number without a warning'),
        'abort' => $this->t('Abort the export with an error'),
      ],
      '#default_value' => 'include_with_warning',
      '#states' => [
        'visible' => [
          ':input[name="export_method[simple_school_reports_pmo_export:export_users_pmo][options][include_caregivers]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['general_info'] = [
      '#type' => 'details',
      '#title' => $this->t('General information'),
    ];

    $form['general_info']['school_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('School name'),
      '#default_value' => Settings::get('ssr_school_name', ''),
      '#max' => 50,
      '#min' => 5,
      '#required' => TRUE,
    ];

    $default_school_year = $this->termService->getDefaultSchoolYearStart()->format('Y') . '-' . $this->termService->getDefaultSchoolYearEnd()->format('Y');

    $form['general_info']['school_year'] = [
      '#type' => 'textfield',
      '#title' => $this->t('School year'),
      '#default_value' => $default_school_year,
      '#max' => 50,
      '#min' => 9,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function getOptionsWithDefaults(array $options): array {
    $options += [
      'include_caregivers' => TRUE,
      'ssn_caregivers_behavior' => 'include_with_warning',
      'school_name' => '',
      'school_year' => '',
    ];


    // Copy values from $options['general_info'] is set.
    if (!empty($options['general_info'])) {
      $options['school_name'] = $options['general_info']['school_name'] ?? $options['school_name'];
      $options['school_year'] = $options['general_info']['school_year'] ?? $options['school_year'];
    }

    // Trim school name and year to max 50 characters.
    $options['school_name'] = substr($options['school_name'], 0, 50);
    $options['school_year'] = substr($options['school_year'], 0, 50);

    return $options;
  }

  protected function getSsnIfValid(UserInterface $user): string|null {
    $ssn = $user->get('field_ssn')->value;
    if ($ssn) {
      $ssn = $this->pnumService->normalizeIfValid($ssn, TRUE);
      if ($ssn) {
        $ssn = str_replace(['-', '+'], '', $ssn);
      }
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

    $grades = SchoolGradeHelper::getSchoolGradesMap(['FKLASS', 'GR']);

    /** @var \Drupal\user\UserInterface $student */
    foreach ($users as $student) {
      $uid = $student->id();

      if (!$student->isActive() || !$student->hasRole('student')) {
        $errors[$uid] = $this->t('User @name is not a student', [
          '@name' => $student->getDisplayName(),
        ]);
        continue;
      }

      $grade = $student->get('field_grade')->value;
      if ($grade === NULL || !isset($grades[$grade])) {
        $errors[$uid] = $this->t('Student @name is missing school grade or has an invalid value', [
          '@name' => $student->getDisplayName(),
        ]);
        continue;
      }

      $ssn = $this->getSsnIfValid($student);
      if (!$ssn) {
        $errors[$uid] = $this->t('Student @name is missing a valid personal number', [
          '@name' => $student->getDisplayName(),
        ]);
        continue;
      }
      $ssn_map[$ssn][] = $student->getDisplayName();

      if ($options['include_caregivers']) {
        $caregiver_users = $this->userMetaDataService->getCaregivers($student);
        $caregivers_missing_ssn = [];
        $behavior = $options['ssn_caregivers_behavior'];
        foreach ($caregiver_users as $caregiver_user) {
          $caregiver_ssn = $this->getSsnIfValid($caregiver_user);
          if ($caregiver_ssn) {
            $ssn_map[$caregiver_ssn][] = $caregiver_user->uuid();
          }
          else {
            $caregivers_missing_ssn[] = $caregiver_user->getDisplayName();
          }
        }

        if (!empty($caregivers_missing_ssn)) {
          if ($behavior === 'abort') {
            $errors[$uid] = $this->t('Student @name has caregivers missing a valid personal number: @caregivers', [
              '@name' => $student->getDisplayName(),
              '@caregivers' => implode(', ', $caregivers_missing_ssn),
            ]);
            continue;
          }
        }
      }
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

  protected function getUserName(UserInterface $user): string {
    $name = $user->getDisplayName();
    $first_name = $user->get('field_first_name')->value;
    $last_name = $user->get('field_last_name')->value;
    if ($last_name && $first_name) {
      $name = $last_name . ', ' . $first_name;
    }

    // Trim to max 100 characters
    return substr($name, 0, 100);
  }

  protected function getCaregiverRowData(UserInterface $user, string $prefix, array $options, UserInterface $child): ?array {
    $include_caregiver = $options['include_caregivers'];
    if (!$include_caregiver) {
      return NULL;
    }

    $ssn = $this->getSsnIfValid($user);
    if (!$ssn) {
      $behavior = $options['ssn_caregivers_behavior'];
      if ($behavior === 'abort') {
        $this->messenger->addError($this->t('Skipping @name due to unknown error. Try again.', ['@name' => $user->getDisplayName()]));
        return NULL;
      }

      if ($behavior === 'ignore') {
        return NULL;
      }

      if ($behavior === 'include_with_warning') {
        $this->messenger->addWarning($this->t('Student @name has caregivers missing a valid personal number: @caregivers', ['@name' => $child->getDisplayName(), '@caregivers' => $user->getDisplayName()]));
      }

    }

    $century = $ssn ? substr($ssn, 0, 2) : '';
    $ssn = $ssn ? substr($ssn, 2) : '';

    $adress = $this->getUserAdress($user, 50, 10, 50);

    return [
      $prefix . 'century' => $century,
      $prefix . 'ssn' => $ssn,
      $prefix . 'name' => $this->getUserName($user),
      $prefix . 'adress' => $adress['adress'],
      $prefix . 'postal_code' => $adress['postal_code'],
      $prefix . 'city' => $adress['city'],
      $prefix . 'phone_number_home' => substr($user->get('field_telephone_number')->value ?? '', 0, 100),
      $prefix . 'phone_number_workplace' => substr($user->get('field_telephone_number')->value ?? '', 0, 100),
      $prefix . 'caregiver' => 'J',
      $prefix . 'relation' => '',
    ];
  }

  public function getUserRow(UserInterface $user, array $options): ?array {

    // Sekel;Personnummer (utan sekel);Namn;Patient address;Postnummer;Stad;Patient telefonnummer;Skola;Klass;Skolår;Sekel anhörig;Födelsedatum anhörig;Namn anhörig;Address anhörig;Postnummer anhörig;Stad anhörig;Hemtelefon anhörig;jobbtelefon anhörig;Vårdnadshavare anhörig(J/N);Typ av anhörig(Moder(M) Fader(F) osv)
    // 19;XXXXXXXXXX ;Nilsson, Daniel;Löftets Gränd 32;75148;UPPSALA; 018-123 45;Granbackens skola;6 B;2008-2009;19;XXXXXXXXXX;Nilsson, Maria;Löftets Gränd 32;75148;UPPSALA;018-123 45;;J;;19; XXXXXXXXXX;Nilsson, Tomas;Löftets Gränd 32;75148;UPPSALA;018-123 45;;J;;

    $options = static::getOptionsWithDefaults($options);
    $this->getErrors([$user->id()], $options);

    $ssn = $this->getSsnIfValid($user);
    if (!$ssn || !empty($this->getErrors([$user->id()], []))) {
      $this->messenger->addError($this->t('Skipping @name due to unknown error. Try again.', ['@name' => $user->getDisplayName()]));
      return NULL;
    }

    $century = substr($ssn, 0, 2);
    $ssn = substr($ssn, 2);

    $grade_value = $user->get('field_grade')->value;
    $grades = SchoolGradeHelper::getSchoolGradesMap(['FKLASS', 'GR']);

    $grade = $grades[$grade_value] ?? 'Okänd';
    if ($grade_value > 0) {
      $grade = 'Årskurs ' . $grade_value;
    }

    $adress = $this->getUserAdress($user, 50, 10, 50);

    $user_row = [
      'century' => $century,
      'ssn' => $ssn,
      'name' => $this->getUserName($user),
      'adress' => $adress['adress'],
      'postal_code' => $adress['postal_code'],
      'city' => $adress['city'],
      'phone_number' => substr($user->get('field_telephone_number')->value ?? '', 0, 100),
      'school' => $options['school_name'],
      'grade' => (string) $grade,
      'school_year' => $options['school_year'],
    ];

    if ($options['include_caregivers']) {
      $caregiver_users = $this->userMetaDataService->getCaregivers($user);

      // Limit to max 5 caregives.
      $caregiver_users = array_slice($caregiver_users, 0, 5);

      foreach (array_values($caregiver_users) as $caregiver_user) {
        $caregiver_data = $this->getCaregiverRowData($caregiver_user, $caregiver_user->id(), $options, $user);
        if ($caregiver_data) {
          $user_row = $user_row + $caregiver_data;
        }
      }
    }

    // Clean up the data.
    foreach ($user_row as $key => $value) {
      $value = $value ?? '';
      $value= str_replace(';', '', $value);
      $value = trim($value);
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

    $formatted_user_rows = [];

    foreach ($user_rows as $key => $user_row) {
      $ssn = $user_row['century'] . $user_row['ssn'];
      $ssn_map[$ssn][] = $key;

      for($i = 0; $i < 5; $i++) {
        if (empty($user_row['century' . $i]) || empty($user_row['ssn' . $i])) {
          continue;
        }
        $caregiver_ssn = $user_row['century' . $i] . $user_row['ssn' . $i];
        if ($caregiver_ssn) {
          $ssn_map[$caregiver_ssn][] = $key;
        }
      }

      $formatted_user_rows[] = implode(';', array_values($user_row)) . ';';
    }

    // Final validation of unique ssn.
    foreach ($ssn_map as $items) {
      if (count($items) > 1) {
        return NULL;
      }
    }

    $file_content = implode(PHP_EOL, $formatted_user_rows);

    $file_content = mb_convert_encoding($file_content, 'ISO-8859-1');

    return $file_content;
  }

}
