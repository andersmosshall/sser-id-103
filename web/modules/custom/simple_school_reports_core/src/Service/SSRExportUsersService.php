<?php

namespace Drupal\simple_school_reports_core\Service;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\UserInterface;

/**
 * Class SSRExportUsersService
 */
class SSRExportUsersService extends ExportUsersServiceBase {

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account): bool {
    return $account->hasPermission('administer modules');
  }

  public function getServiceId(): string {
    return 'simple_school_reports_core:export_users_ssr';
  }

  public function getFileExtension(): string {
    return 'json';
  }

  public function getShortDescription(): TranslatableMarkup {
    return $this->t('Simple School Reports');
  }

  public function getDescription(): TranslatableMarkup {
    return $this->t('This export will generate file with the selected users. The is compatible with other simple school reports systems. Use this if you want to import users to an other simple school reports system.');
  }

  /**
   * {@inheritdoc}
   */
  public function getOptionsForm(): array {
    $form = parent::getOptionsForm();

    $form['include_user_id'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include user id'),
      '#default_value' => TRUE,
    ];

    $form['include_caregivers'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include caregivers'),
      '#default_value' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function getOptionsWithDefaults(array $options): array {
    $options += [
      'include_user_id' => TRUE,
      'include_caregivers' => TRUE,
    ];

    if ($options['include_caregivers']) {
      $options['include_user_id'] = TRUE;
    }

    return $options;
  }

  public function getUserRow(UserInterface $user, array $options): array {
    $user_row = parent::getUserRow($user, $options);

    $options = static::getOptionsWithDefaults($options);

    if (empty($options['include_user_id'])) {
      unset($user_row['id']);
    }

    if (!empty($options['include_caregivers'])) {
      $caregivers = [];
      $caregivers_row_data = [];

      $caregiver_users = $this->userMetaDataService->getCaregivers($user);
      /** @var \Drupal\user\UserInterface $caregiver_user */
      foreach ($caregiver_users as $caregiver_user) {
        $caregivers[] = $caregiver_user->uuid();
        $caregiver_options = $options;
        $caregiver_options['include_caregivers'] = FALSE;
        $caregivers_row_data[$caregiver_user->uuid()] = $this->getUserRow($caregiver_user, $caregiver_options);
      }

      $user_row['caregivers'] = $caregivers;
      $user_row['caregivers_row_data'] = $caregivers_row_data;
    }

    return $user_row;
  }

  protected function processUserRows(array $userRows, array $options): array {
    $options = static::getOptionsWithDefaults($options);

    if (!empty($options['include_caregivers'])) {
      foreach ($userRows as $userRow) {
        if (!empty($userRow['caregivers_row_data'])) {
          $caregivers = $userRow['caregivers_row_data'];
          $userRows = $userRows + $caregivers;
        }
      }
    }

    foreach ($userRows as &$userRow) {
      if (!empty($userRow['caregivers_row_data'])) {
        unset($userRow['caregivers_row_data']);
      }
    }

    return $userRows;
  }

  /**
   * {@inheritdoc}
   */
  public function makeFileContent(array $user_rows, array $options): ?string {
    $user_rows = $this->processUserRows($user_rows, $options);
    return json_encode(array_values($user_rows));
  }

}
