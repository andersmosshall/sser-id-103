<?php

namespace Drupal\simple_school_reports_core\Service;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Class ExportUsersServiceBase
 */
class CsvExportUsersService extends SSRExportUsersService {

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account): bool {
    return $account->hasPermission('administer simple school reports settings');
  }

  public function getServiceId(): string {
    return 'simple_school_reports_core:export_users_csv';
  }

  public function getFileExtension(): string {
    return 'csv';
  }

  public static function getPriority(): int {
    return 100;
  }

  public function getShortDescription(): TranslatableMarkup {
    return $this->t('Other system (csv)');
  }

  public function getDescription(): TranslatableMarkup {
    return $this->t('This export will generate CSV file with the selected users. CSV files can be read by many other systems and can also be opened in spreadsheet applications like Excel. Use this if you want to import users to an other system.');
  }

  protected function getUserRowHeader($options): array {
    $options = $this->getOptionsWithDefaults($options);

    $header = [
      'id' => $this->t('ID'),
      'email' => $this->t('Email'),
      'first_name' => $this->t('First name'),
      'last_name' => $this->t('Last name'),
      'grade' => $this->t('School grade'),
      'gender' => $this->t('Gender'),
      'birth_date' => $this->t('Birth date'),
      'ssn' => $this->t('Personal number'),
      'roles' => $this->t('Roles'),
    ];

    if (!empty($options['include_caregivers'])) {
      $header['caregivers'] = $this->t('Caregivers');
    }

    if (empty($options['include_user_id'])) {
      unset($header['id']);
    }

    return $header;
  }

  protected function processUserRows(array $userRows, array $options): array {
    $userRows = parent::processUserRows($userRows, $options);

    foreach ($userRows as $key => $userRow) {
      foreach ($userRow as $fieldKey => $fieldValue) {
        if (is_array($fieldValue)) {
          $userRows[$key][$fieldKey] = implode(', ', $fieldValue);
        }
      }
    }

    return $userRows;
  }

  /**
   * {@inheritdoc}
   */
  public function makeFileContent(array $user_rows, array $options): ?string {
    $user_rows = $this->processUserRows($user_rows, $options);

    $header = $this->getUserRowHeader($options);

    $csv_data = [];
    $csv_data[] = array_values($this->getUserRowHeader($options));

    foreach ($user_rows as $user_row) {
      $row = [];
      foreach ($header as $key => $label) {
        $row[] = $user_row[$key] ?? '';
      }
      $csv_data[] = $row;
    }

    $fp = fopen('php://memory', 'w+');
    foreach ($csv_data as $row) {
      fputcsv($fp, $row);
    }

    // Read it back into a string, if desired.
    // Rewind the stream to start.
    fseek($fp, 0);

    return stream_get_contents($fp);
  }

}
