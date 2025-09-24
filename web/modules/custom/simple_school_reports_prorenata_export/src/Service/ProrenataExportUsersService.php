<?php

namespace Drupal\simple_school_reports_prorenata_export\Service;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\simple_school_reports_core\SchoolGradeHelper;
use Drupal\simple_school_reports_core\Service\ExportUsersServiceBase;
use Drupal\user\UserInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Class ProrenataExportUsersService
 */
class ProrenataExportUsersService extends ExportUsersServiceBase {

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account): bool {
    return $account->hasPermission('administer simple school reports settings');
  }

  public function getServiceId(): string {
    return 'simple_school_reports_prorenata_export:export_users_prorenata';
  }

  public function getFileExtension(): string {
    return 'prorenta.xlsx';
  }

  public function getShortDescription(): TranslatableMarkup {
    return $this->t('Prorenata');
  }

  public function getDescription(): TranslatableMarkup {
    return $this->t('This export will generate file with the selected users to be compatible with Prorenata systems. Use this if you want to import users to a Prorenata system.');
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
          ':input[name="export_method[simple_school_reports_prorenata_export:export_users_prorenata][options][include_caregivers]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['skip_user_with_protected_data'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Skip users with protected data'),
      '#description' => $this->t('If checked, users with protected data will be skipped.'),
      '#default_value' => FALSE,
    ];

    $form['include_protected_data'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include protected data'),
      '#description' => $this->t('If checked, protected data will be included even if the user has secrecy marking.'),
      '#default_value' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="export_method[simple_school_reports_prorenata_export:export_users_prorenata][options][skip_user_with_protected_data]"]' => ['checked' => FALSE],
        ]
      ]
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
      'include_protected_data' => TRUE,
      'skip_user_with_protected_data' => FALSE,
    ];

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

    $ssr_id = Settings::get('ssr_id');
    if (!$ssr_id) {
      $errors['general'] = $this->t('Something went wrong.');;
    }

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

  protected function getCaregiverRowData(UserInterface $user, array $options, UserInterface $child): ?array {
    $include_caregiver = $options['include_caregivers'];
    if (!$include_caregiver) {
      return NULL;
    }

    $include_protected_data = TRUE;
    $protected_data_value = $user->get('field_protected_personal_data')->value ?? NULL;
    $has_protected_data = $protected_data_value !== NULL && $protected_data_value !== 'none';

    if ($has_protected_data && $options['skip_user_with_protected_data']) {
      return null;
    }

    if ($has_protected_data && !$options['include_protected_data']) {
      $include_protected_data = FALSE;
    }

    $first_name = $user->get('field_first_name')->value ?? '';
    $last_name = $user->get('field_last_name')->value ?? '';

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
    $is_caregiver = 'Ja';

    $adress = $this->getUserAdress($user, 50, 10, 50);
    $adress1 = $adress['adress'];
    $adress2 = '';
    $postal_code = $adress['postal_code'];
    $city = $adress['city'];
    $country = $adress['country'] ?? '';

    $phone_number_home = $this->getUserPhoneNumber($user, 'home');
    $phone_number_work = '';
    $phone_number_mobile = $this->getUserPhoneNumber($user, 'mobile');
    $email = $this->emailService->getUserEmail($user) ?? '';

    return [
      'first_name' => $first_name,
      'last_name' => $last_name,
      'ssn' => $ssn,
      'caregiver' => $is_caregiver,
      'adress1' => $include_protected_data ? $adress1 : '',
      'adress2' => $include_protected_data ? $adress2 : '',
      'postal_code' => $include_protected_data ? $postal_code : '',
      'city' => $include_protected_data ? $city : '',
      'country' => $include_protected_data ? $country : '',
      'phone_number_home' => $include_protected_data ? $phone_number_home : '',
      'phone_number_work' => $include_protected_data ? $phone_number_work : '',
      'phone_number_mobile' => $include_protected_data ? $phone_number_mobile : '',
      'email' => $include_protected_data ? $email : '',
    ];
  }

  public function getUserRow(UserInterface $user, array $options): ?array {

    // external_orgunitid	external_patientid	socialnumber	fname	lname	year	class	address1	address2	pocode	city	country	homephone	mobile	email	deceased	custodian1_fname	custodian1_lname	custodian1_socialnumber	custodian1_is_custodian	custodian1_address1	custodian1_address2	custodian1_pocode	custodian1_city	custodian1_country	custodian1_homephone	custodian1_workphone	custodian1_mobile	custodian1_email	custodian2_fname	custodian2_lname	custodian2_socialnumber	custodian2_is_custodian	custodian2_address1	custodian2_address2	custodian2_pocode	custodian2_city	custodian2_country	custodian2_homephone	custodian2_workphone	custodian2_mobile	custodian2_email
    // externt id	externt patient id	Personnr	Förnamn	Efternamn	Årskurs	Skolklass	Gatuadress (Folkbokföring)	Adress2	Postnummer (Folkbokföring)	Postort (Folkbokföring)	Land (Folkbokföring)	Hemtelefon (Folkbokföring)	Mobiltelefon (Elevens)	E-post (Elevens)	Avliden	Förnamn (Kontaktperson 1)	Efternamn (Kontaktperson 1)	Personnummer (Kontaktperson 1)	Är vårdnadshavare (Kontaktperson 1)	Adress (Kontaktperson 1)	Adress2 (Kontaktperson 1)	Postnummer (Kontaktperson 1)	Postort (Kontaktperson 1)	Land (Kontaktperson 1)	Hemtelefon (Kontaktperson 1)	Arbetstelefon (Kontaktperson 1)	Mobiltelefon (Kontaktperson 1)	E-post (Kontaktperson 1)	Förnamn (Kontaktperson 2)	Efternamn (Kontaktperson 2)	Personnummer (Kontaktperson 2)	Är vårdnadshavare (Kontaktperson 2)	Adress (Kontaktperson 2)	Adress2 (Kontaktperson 2)	Postnummer (Kontaktperson 2)	Postort (Kontaktperson 2)	Land (Kontaktperson 2)	Hemtelefon (Kontaktperson 2)	Arbetstelefon (Kontaktperson 2)	Mobiltelefon (Kontaktperson 2)	E-post (Kontaktperson 2)

    $options = static::getOptionsWithDefaults($options);
    $this->getErrors([$user->id()], $options);

    $ssr_id = Settings::get('ssr_id', '');
    if ($ssr_id === '') {
      throw new \RuntimeException('Missing ssr id');
    }

    $include_protected_data = TRUE;
    $protected_data_value = $user->get('field_protected_personal_data')->value ?? NULL;
    $has_protected_data = $protected_data_value !== NULL && $protected_data_value !== 'none';

    if ($has_protected_data && $options['skip_user_with_protected_data']) {
      return null;
    }

    if ($has_protected_data && !$options['include_protected_data']) {
      $include_protected_data = FALSE;
    }

    $external_id = sha1('SSR.' . $ssr_id);
    $user_uuid = $user->uuid();

    $ssn = $this->getSsnIfValid($user);
    if (!$ssn || !empty($this->getErrors([$user->id()], []))) {
      $this->messenger->addError($this->t('Skipping @name due to unknown error. Try again.', ['@name' => $user->getDisplayName()]));
      return NULL;
    }

    $first_name = $user->get('field_first_name')->value ?? '';
    $last_name = $user->get('field_last_name')->value ?? '';

    $grade_value = $user->get('field_grade')->value;
    $grade = '';
    $default_class = '';
    if ($grade_value !== NULL) {
      $grade = SchoolGradeHelper::parseGradeValueToActualGrade($grade_value);
      if ($grade === 0) {
        $grade = '0';
      }
      $default_class = SchoolGradeHelper::getSchoolGradesLongName()[$grade_value];
    }

    $use_classes = \Drupal::moduleHandler()->moduleExists('simple_school_reports_class');
    $class = $default_class;
    if ($use_classes) {
      $class = $user->get('field_class')->entity?->label() ?? $default_class;
    }

    $adress = $this->getUserAdress($user, 50, 10, 50);
    $adress1 = $adress['adress'];
    $adress2 = '';
    $postal_code = $adress['postal_code'];
    $city = $adress['city'];
    $country = $adress['country'] ?? '';

    $phone_number_home = $this->getUserPhoneNumber($user, 'home');
    $phone_number_mobile = $this->getUserPhoneNumber($user, 'mobile');
    $email = $this->emailService->getUserEmail($user) ?? '';
    $deceased = '';

    $caregivers = [
      0 => [],
      1 => [],
    ];

    if ($options['include_caregivers']) {
      $caregiver_users = $this->userMetaDataService->getCaregivers($user);

      // Limit to max 2 caregives.
      $caregiver_users = array_slice($caregiver_users, 0, 2);

      foreach (array_values($caregiver_users) as $key => $caregiver_user) {
        $caregiver_data = $this->getCaregiverRowData($caregiver_user, $options, $user);
        if ($caregiver_data) {
          $caregivers[$key] = $caregiver_data;
        }
      }
    }


    // Map column names to values.
    $user_row = [
      'A' => $external_id,
      'B' => $user_uuid,
      'C' => $ssn,
      'D' => $first_name,
      'E' => $last_name,
      'F' => $grade,
      'G' => $class,
      'H' => $include_protected_data ? $adress1 : '',
      'I' => $include_protected_data ? $adress2 : '',
      'J' => $include_protected_data ? $postal_code : '',
      'K' => $include_protected_data ? $city : '',
      'L' => $include_protected_data ? $country : '',
      'M' => $include_protected_data ? $phone_number_home : '',
      'N' => $include_protected_data ? $phone_number_mobile : '',
      'O' => $include_protected_data ? $email : '',
      'P' => $deceased,

      // Caregiver 1.
      'Q' => $caregivers[0]['first_name'] ?? '',
      'R' => $caregivers[0]['last_name'] ?? '',
      'S' => $caregivers[0]['ssn'] ?? '',
      'T' => $caregivers[0]['caregiver'] ?? '',
      'U' => $caregivers[0]['adress1'] ?? '',
      'V' => $caregivers[0]['adress2'] ?? '',
      'W' => $caregivers[0]['postal_code'] ?? '',
      'X' => $caregivers[0]['city'] ?? '',
      'Y' => $caregivers[0]['country'] ?? '',
      'Z' => $caregivers[0]['phone_number_home'] ?? '',
      'AA' => $caregivers[0]['phone_number_work'] ?? '',
      'AB' => $caregivers[0]['phone_number_mobile'] ?? '',
      'AC' => $caregivers[0]['email'] ?? '',

      // Caregiver 2.
      'AD' => $caregivers[1]['first_name'] ?? '',
      'AE' => $caregivers[1]['last_name'] ?? '',
      'AF' => $caregivers[1]['ssn'] ?? '',
      'AG' => $caregivers[1]['caregiver'] ?? '',
      'AH' => $caregivers[1]['adress1'] ?? '',
      'AI' => $caregivers[1]['adress2'] ?? '',
      'AJ' => $caregivers[1]['postal_code'] ?? '',
      'AK' => $caregivers[1]['city'] ?? '',
      'AL' => $caregivers[1]['country'] ?? '',
      'AM' => $caregivers[1]['phone_number_home'] ?? '',
      'AN' => $caregivers[1]['phone_number_work'] ?? '',
      'AO' => $caregivers[1]['phone_number_mobile'] ?? '',
      'AP' => $caregivers[1]['email'] ?? '',
    ];

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

    // TODO: Make xlsx file instead.
    $template_file = 'prorenta_export_empty';
    $empty_file = $this->fileTemplateService->getFileTemplateRealPath($template_file);
    if (!$empty_file) {
      return NULL;
    }


    $file_type = 'Xlsx';
    $reader = IOFactory::createReader($file_type);
    $reader->setLoadAllSheets();
    $spreadsheet = $reader->load($empty_file);

    $sheet_names = $spreadsheet->getSheetNames();
    $found_sheet = FALSE;

    foreach ($sheet_names as $sheet_index => $sheet_name) {
      // Only sheet 0 is used.
      if ($sheet_index !== 0) {
        continue;
      }
      $found_sheet = TRUE;

      $excel_sheet = $spreadsheet->getSheet($sheet_index);

      // Prepare rows by copy them get correct formats etc.
      foreach (array_values($user_rows) as $key => $user_row) {
        if ($key === 0) {
          continue;
        }
        $original_row = 2;
        $new_row = 2 + $key;

        // Copy the cells to get correct formats etc.
        foreach (array_keys($user_row) as $column) {
          $original_cell_id = $column . $original_row;
          $new_cell_id = $column . $new_row;
          $excel_sheet->copyCells($original_cell_id, $new_cell_id);
        }
      }

      foreach (array_values($user_rows) as $key => $user_row) {
        $row_id = 2 + $key;
        $ssn = $user_row['C'];
        $ssn_map[$ssn][] = $key;
        foreach ($user_row as $column => $value) {
          $cell_id = $column . $row_id;
          $excel_sheet->setCellValue($cell_id, $value);
        }
      }
    }

    if (!$found_sheet) {
      return NULL;
    }

    // Final validation of unique ssn.
    foreach ($ssn_map as $items) {
      if (count($items) > 1) {
        return NULL;
      }
    }

    ob_start();
    $writer = IOFactory::createWriter($spreadsheet, $file_type);
    $writer->save('php://output');
    return ob_get_clean() ?? NULL;
  }

}
