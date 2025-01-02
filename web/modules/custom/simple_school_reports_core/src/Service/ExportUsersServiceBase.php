<?php

namespace Drupal\simple_school_reports_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\simple_school_reports_core\Events\ExportUsersMethodsEvent;
use Drupal\simple_school_reports_core\Events\SsrCoreEvents;
use Drupal\simple_school_reports_core\Pnum;
use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ExportUsersServiceBase
 */
abstract class ExportUsersServiceBase implements ExportUsersServiceInterface, EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;


  protected AccountInterface $currentUser;

  protected Pnum $pnumService;

  protected EmailServiceInterface $emailService;

  protected MessengerInterface $messenger;

  protected TermServiceInterface $termService;

  protected array $lookup;


  public function __construct(
    Connection $connection,
    EntityTypeManagerInterface $entity_type_manager,
    AccountInterface $current_user,
    Pnum $pnum_service,
    EmailService $email_service,
    MessengerInterface $messenger,
    TermServiceInterface $term_service,
  ) {
    $this->connection = $connection;
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->pnumService = $pnum_service;
    $this->emailService = $email_service;
    $this->messenger = $messenger;
    $this->termService = $term_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[SsrCoreEvents::EXPORT_USERS_METHODS][] = 'onExportUsersMethods';
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  public function onExportUsersMethods(ExportUsersMethodsEvent $event) {
    if (!$this->access($this->currentUser)) {
      return;
    }

    $event->addExportMethodService($this->getServiceId());
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function getPriority(): int {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptionsForm(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getErrors(array $uids, array $options): array {
    $errors = [];

    return $errors;
  }

  public function getUuidMap(): array {
    $cid = 'uuid_uid_map';

    if (!empty($this->lookup[$cid])) {
      return $this->lookup[$cid];
    }

    $query = $this->connection->select('users_field_data', 'u');
    $query->fields('u', ['uid', 'uuid']);
    $result = $query->execute();

    $map = [];

    foreach ($result as $row) {
      $map[$row->uid] = $row->uuid;
    }

    $this->lookup[$cid] = $map;
    return $map;
  }

  protected function getUserAdress(UserInterface $user, ?int $trim_adress = NULL, ?int $trim_postal_code = NULL, ?int $trim_city = NULL): array {
    $adress_paragraph = $user->get('field_address')->entity;

    $adress = $adress_paragraph?->get('field_street_address')->value ?? '';
    $postal_code = $adress_paragraph?->get('field_zip_code')->value ?? '';
    $city = $adress_paragraph?->get('field_city')->value ?? '';


    if ($trim_adress) {
      $adress = substr($adress, 0, $trim_adress);
    }

    if ($trim_postal_code) {
      $postal_code = substr($postal_code, 0, $trim_postal_code);
    }

    if ($trim_city) {
      $city = substr($city, 0, $trim_city);
    }

    return [
      'adress' => $adress,
      'postal_code' => $postal_code,
      'city' => $city,
    ];
  }

  public function getUserRow(UserInterface $user, array $options): ?array {
    $birth_date = '';
    $ssn = '';
    $birth_date_timestamp = $user->get('field_birth_date')->value ?? NULL;

    if (!$user->get('field_birth_date_source')->isEmpty()) {
      if ($user->get('field_birth_date_source')->value === 'ssn') {
        $user_ssn = $user->get('field_ssn')->value;
        if ($user_ssn) {
          $user_ssn = $this->pnumService->normalizeIfValid($user_ssn);
          if ($user_ssn) {
            $birth_date_timestamp = $this->pnumService->getBirthDateTimestamp($user_ssn);
            $ssn = $user_ssn;
          }
        }
      }
    }

    if ($birth_date_timestamp) {
      $date = new \DateTime();
      $date->setTimestamp($birth_date_timestamp);
      $birth_date = $date->format('Y-m-d');
    }

    $roles = [];
    // Roles to export.
    $roles_to_export = [
      'student',
      'teacher',
      'caregiver',
    ];

    foreach ($user->getRoles(TRUE) as $role) {
      if (!in_array($role, $roles_to_export)) {
        continue;
      }
      $roles[] = $role;
    }

    $email = $this->emailService->getUserEmail($user) ?? '';

    return [
      'id' => $user->uuid(),
      'email' => $email,
      'first_name' => $user->get('field_first_name')->value,
      'last_name' => $user->get('field_last_name')->value,
      'grade' => $user->get('field_grade')->value,
      'gender' => $user->get('field_gender')->value,
      'birth_date' => $birth_date,
      'ssn' => $ssn,
      'roles' => $roles,
    ];
  }

}
