<?php

namespace Drupal\simple_school_reports_consents\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\simple_school_reports_core\AbsenceDayHandler;
use Drupal\simple_school_reports_core\Service\EmailServiceInterface;

/**
 * Class ConsentsService
 */
class ConsentsService implements ConsentsServiceServiceInterface {

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

  /**
   * A cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\simple_school_reports_core\Service\EmailServiceInterface
   */
  protected $emailService;

  /**
   * Lookup array for fast access.
   */
  protected array $lookup = [];

  protected ?array $roleUidMap = NULL;

  protected ?array $caregiversUidMap = NULL;

  protected ?array $canSignUids = NULL;

  /**
   * TermService constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(
    Connection $connection,
    EntityTypeManagerInterface $entity_type_manager,
    CacheBackendInterface $cache,
    AccountInterface $current_user,
    ModuleHandlerInterface $module_handler,
    EmailServiceInterface $email_service
  ) {
    $this->connection = $connection;
    $this->entityTypeManager = $entity_type_manager;
    $this->cache = $cache;
    $this->currentUser = $current_user;
    $this->moduleHandler = $module_handler;
    $this->emailService = $email_service;
  }

  protected function warmUpRoleCache() {
    if (!$this->roleUidMap) {
      $query = $this->connection
        ->select('user__roles', 'r')
        ->condition('r.deleted', 0)
        ->fields('r', ['roles_target_id', 'entity_id']);
      $query->innerJoin('users_field_data', 'u', 'u.uid = r.entity_id');
      $query->leftJoin('user__field_allow_login', 'al', 'al.entity_id = r.entity_id');
      $query->fields('u', ['mail']);
      $query->fields('al', ['field_allow_login_value']);
      $results = $query->execute();
      $this->roleUidMap = [];
      $user_data = [];

      foreach ($results as $result) {
        $role = $result->roles_target_id;
        $this->roleUidMap[$role][] = $result->entity_id;

        // Filter role.
        $filter_role = [
          'anonymous',
          'authenticated',
        ];

        if ($role && !in_array($role, $filter_role)) {
          $user_data[$result->entity_id]['roles'][$role] = $role;

          if ($role !== 'student' && $role !== 'caregiver') {
            $user_data[$result->entity_id]['other_roles'][$role] = $role;
          }

          $user_data[$result->entity_id]['mail'] = $result->mail;
          $user_data[$result->entity_id]['allow_login'] = $result->field_allow_login_value ?? FALSE;
        }
      }

      $this->canSignUids = [];

      $caregiver_login = $this->moduleHandler->moduleExists('simple_school_reports_caregiver_login');

      foreach ($user_data as $uid => $data) {
        $is_student = in_array('student', $data['roles']);
        $is_caregiver = in_array('caregiver', $data['roles']);
        $has_other_roles = !empty($data['other_roles']);

        if ($is_student && !$is_caregiver && !$has_other_roles) {
          continue;
        }

        if ($is_caregiver && !$has_other_roles && !$caregiver_login) {
          continue;
        }

        if ($is_caregiver && empty($data['allow_login'])) {
          continue;
        }

        $email = $data['mail'] ?? NULL;
        if ($this->emailService->skipMail($email)) {
          continue;
        }

        $this->canSignUids[$uid] = $uid;
      }
    }
  }

  protected function warmUpCaregiversCache() {
    if (!$this->caregiversUidMap) {
      $query = $this->connection
        ->select('user__field_caregivers', 'c')
        ->condition('c.deleted', 0)
        ->fields('c', ['field_caregivers_target_id', 'entity_id']);
      $query->innerJoin('users', 'u', 'u.uid = c.entity_id');
      $results = $query->execute();
      $this->caregiversUidMap = [];
      foreach ($results as $result) {
        $this->caregiversUidMap[$result->entity_id][] = $result->field_caregivers_target_id;
      }
    }
  }

  protected function warmUpCanSignCache() {
    if (!$this->canSignUids) {
      $this->warmUpRoleCache();
    }
  }



  protected function warmUpCache() {
    $cid1 = 'consent_uid_map';
    $cid2 = 'uid_consent_map';

    if (isset($this->lookup[$cid1]) && isset($this->lookup[$cid2])) {
      return;
    }


    $cache1 = $this->cache->get($cid1);
    $cache2 = $this->cache->get($cid2);

    if (!empty($cache1) && !empty($cache2)) {
      $this->lookup[$cid1] = $cache1->data;
      $this->lookup[$cid2] = $cache2->data;
      return;
    }


    // [consent_id][target_uid][uid] = status_data ([status, answer_id])
    $consent_uid_map = [];

    // [uid][consent_id][target_uid] = status_id
    $uid_consent_map = [];

    $ids = $this->entityTypeManager->getStorage('user')->getQuery()->accessCheck(FALSE)->execute();

    $valid_uids = [];
    foreach ($ids as $id) {
      if ($id > 0) {
        $valid_uids[$id] = $id;
      }
    }


    $consent_ids = $this->entityTypeManager
      ->getStorage('node')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'consent')
      ->execute();

    $nids = $this->entityTypeManager
      ->getStorage('node')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'consent')
      ->condition('field_locked', 1)
      ->execute();

    $locked_consent_ids = [];
    foreach ($nids as $nid) {
      $locked_consent_ids[$nid] = $nid;
    }

    if (!empty($consent_ids)) {
      $query = $this->connection->select('paragraphs_item_field_data', 'p')
        ->condition('p.parent_field_name', 'field_consent_target_groups')
        ->condition('p.parent_type', 'node')
        ->condition('p.parent_id', $consent_ids, 'IN');
      $query->innerJoin('node__field_consent_target_groups', 'n', 'n.field_consent_target_groups_target_id = p.id');
      $query->leftJoin('paragraph__field_user_roles', 'r', 'r.entity_id = p.id');
      $query->leftJoin('paragraph__field_students', 's', 's.entity_id = p.id');
      $query->condition('n.deleted', 0);
      $query->fields('p', ['id', 'type', 'parent_id']);
      $query->fields('r', ['field_user_roles_target_id']);
      $query->fields('s', ['field_students_target_id']);
      $results = $query->execute();

      // Populate default values, e.g. not answered.
      foreach ($results as $result) {
        $consent_id = $result->parent_id;
        $target_type = $result->type;

        // Get uid for role.
        if ($target_type === 'consent_target_role') {
          $this->warmUpRoleCache();
          if ($role = $result->field_user_roles_target_id) {
            $uids = $this->roleUidMap[$role] ?? [];
            foreach ($uids as $uid) {
              if (!isset($valid_uids[$uid])) {
                continue;
              }

              $target_uid = $uid;
              $consent_uid_map[$consent_id][$target_uid][$uid] = [
                'status' => self::CONSENT_NOT_ANSWERED,
                'answer_id' => NULL,
                'locked' => isset($locked_consent_ids[$consent_id]),
              ];
              $uid_consent_map[$uid][$consent_id][$target_uid] = self::CONSENT_NOT_ANSWERED;
            }
          }
        }

        // Get uid for students.
        if ($target_type === 'consent_target_student') {
          if ($uid = $result->field_students_target_id) {
            if (!isset($valid_uids[$uid])) {
              continue;
            }

            $target_uid = $uid;
            $consent_uid_map[$consent_id][$target_uid][$uid] = [
              'status' => self::CONSENT_NOT_ANSWERED,
              'answer_id' => NULL,
              'locked' => isset($locked_consent_ids[$consent_id]),
            ];
            $uid_consent_map[$uid][$consent_id][$target_uid] = self::CONSENT_NOT_ANSWERED;
          }
        }

        // Get uid for caregivers.
        if ($target_type === 'consent_target_caregivers') {
          if ($target_uid = $result->field_students_target_id) {
            $this->warmUpCaregiversCache();
            $uids = $this->caregiversUidMap[$target_uid] ?? [];
            foreach ($uids as $uid) {
              if (!isset($valid_uids[$uid])) {
                continue;
              }

              $consent_uid_map[$consent_id][$target_uid][$uid] = [
                'status' => self::CONSENT_NOT_ANSWERED,
                'answer_id' => NULL,
                'locked' => isset($locked_consent_ids[$consent_id]),
              ];
              $uid_consent_map[$uid][$consent_id][$target_uid] = self::CONSENT_NOT_ANSWERED;
            }
          }
        }
      }

      // Populate the answers.
      $results = $this->connection->select('ssr_consent_answer', 'a')
        ->condition('a.consent', array_keys($consent_uid_map), 'IN')
        ->condition('a.uid', array_keys($uid_consent_map), 'IN')
        ->fields('a', ['label', 'id', 'consent', 'answer', 'target_uid', 'uid'])
        ->execute();

      foreach ($results as $result) {
        $uid = $result->uid;
        $consent_id = $result->consent;
        $target_uid = $result->target_uid;
        $status = $result->answer;
        $status_display = $result->label;

        $consent_uid_map[$consent_id][$target_uid][$uid] = [
          'status' => $status,
          'status_display' => $status_display,
          'answer_id' => $result->id,
          'locked' => isset($locked_consent_ids[$consent_id]),
        ];
        $uid_consent_map[$uid][$consent_id][$target_uid] = $status;
      }
    }

    $cache_tags = ['node_list:consent', 'ssr_consent_answer_list', 'user_list:roles', 'user_list:new'];

    $this->cache->set($cid1, $consent_uid_map, Cache::PERMANENT, $cache_tags);
    $this->cache->set($cid2, $uid_consent_map, Cache::PERMANENT, $cache_tags);

    $this->lookup[$cid1] = $consent_uid_map;
    $this->lookup[$cid2] = $uid_consent_map;
  }

  protected function getConsentUidMap(): array {
    $this->warmUpCache();
    // [consent_id][target_uid][uid] = status_data ([status, answer_id])
    return $this->lookup['consent_uid_map'];
  }

  protected function getUidConsentMap(): array {
    // [uid][consent_id][target_uid] = status_id
    $this->warmUpCache();
    return $this->lookup['uid_consent_map'];
  }

  /**
   * {@inheritdoc}
   */
  public function getUnHandledConsentIds(int $uid): array {
    $cid = 'unhandled_by_uid' . $uid;

    if (isset($this->lookup[$cid])) {
      return $this->lookup[$cid];
    }

    $data = [];

    $consents = $this->getUidConsentMap()[$uid] ?? [];

    foreach ($consents as $consent_id => $target_ids) {
      foreach ($target_ids as $target_uid => $status) {
        if ($status == self::CONSENT_NOT_ANSWERED) {
          $data[] = $consent_id;
        }
      }
    }

    $this->lookup[$cid] = $data;
    return $this->lookup[$cid];
  }

  /**
   * {@inheritdoc}
   */
  public function getHandledConsentsIds(int $uid): array {
    $cid = 'handled_by_uid' . $uid;

    if (isset($this->lookup[$cid])) {
      return $this->lookup[$cid];
    }

    $data = [];

    $consents = $this->getUidConsentMap()[$uid] ?? [];

    foreach ($consents as $consent_id => $target_ids) {
      foreach ($target_ids as $target_uid => $status) {
        if ($status != self::CONSENT_NOT_ANSWERED) {
          $data[] = $consent_id;
        }
      }
    }

    $this->lookup[$cid] = $data;
    return $this->lookup[$cid];
  }

  /**
   * {@inheritdoc}
   */
  public function getConsentCompletion(int $consent_id): string {
    $cid = 'consent_completion_' . $consent_id;

    if (isset($this->lookup[$cid])) {
      return $this->lookup[$cid];
    }

    $total = 0;
    $handled = 0;

    $consent_uid_map = $this->getConsentUidMap()[$consent_id] ?? [];

    foreach ($consent_uid_map as $target_id => $answers) {
      foreach ($answers as $uid => $status_data) {
        $status = $status_data['status'] ?? self::CONSENT_NOT_ANSWERED;
        $total++;
        if ($status !== self::CONSENT_NOT_ANSWERED) {
          $handled++;
        }
      }
    }

    $value = '0';
    if ($total > 0) {
      $value = round(($handled / $total) * 100, 1);
    }

    $this->lookup[$cid] = $value;
    return $this->lookup[$cid];
  }

  /**
   * {@inheritdoc}
   */
  public function getExpectedUids(int $filter = self::VIEWS_FILTER_NONE): array {
    $cid = 'expected_uid_' . ':' . $filter;

    if (isset($this->lookup[$cid])) {
      return $this->lookup[$cid];
    }

    $cache = $this->cache->get($cid);

    if (!empty($cache)) {
      $this->lookup[$cid] = $cache->data;

      return $this->lookup[$cid];
    }

    $data = [];

    // Resolve uids.
    $uid_consent_map = $this->getUidConsentMap();

    if ($filter === self::VIEWS_FILTER_NONE) {
      $data = array_keys($uid_consent_map);
    }
    elseif ($filter === self::VIEWS_FILTER_FULLY_ACCEPTED) {
      $valid = TRUE;
      foreach ($uid_consent_map as $uid => $consents) {
        foreach ($consents as $consent_id => $target_ids) {
          foreach ($target_ids as $target_uid => $status) {
            if ($status != self::CONSENT_ACCEPTED) {
              $valid = FALSE;
              break 2;
            }
          }
        }
        if ($valid) {
          $data[] = $uid;
        }
      }
    }
    elseif ($filter === self::VIEWS_FILTER_SOME_ACCEPTED) {
      foreach ($uid_consent_map as $uid => $consents) {
        $valid = FALSE;
        foreach ($consents as $consent_id => $target_ids) {
          foreach ($target_ids as $target_uid => $status) {
            if ($status == self::CONSENT_ACCEPTED) {
              $valid = TRUE;
              break 2;
            }
          }
        }
        if ($valid) {
          $data[] = $uid;
        }
      }
    }
    elseif ($filter === self::VIEWS_FILTER_SOME_REJECTED) {
      foreach ($uid_consent_map as $uid => $consents) {
        $valid = FALSE;
        foreach ($consents as $consent_id => $target_ids) {
          foreach ($target_ids as $target_uid => $status) {
            if ($status == self::CONSENT_REJECTED) {
              $valid = TRUE;
              break 2;
            }
          }
        }
        if ($valid) {
          $data[] = $uid;
        }
      }
    }
    elseif ($filter === self::VIEWS_FILTER_FULL_REJECTED) {
      foreach ($uid_consent_map as $uid => $consents) {
        $valid = TRUE;
        foreach ($consents as $consent_id => $target_ids) {
          foreach ($target_ids as $target_uid => $status) {
            if ($status != self::CONSENT_REJECTED) {
              $valid = FALSE;
              break 2;
            }
          }
        }
        if ($valid) {
          $data[] = $uid;
        }
      }
    }
    elseif ($filter === self::VIEWS_FILTER_MISSING_ANSWERS) {
      foreach ($uid_consent_map as $uid => $consents) {
        $valid = FALSE;
        foreach ($consents as $consent_id => $target_ids) {
          foreach ($target_ids as $target_uid => $status) {
            if ($status == self::CONSENT_NOT_ANSWERED) {
              $valid = TRUE;
              break 2;
            }
          }
        }
        if ($valid) {
          $data[] = $uid;
        }
      }
    }
    elseif ($filter === self::VIEWS_FILTER_HANDLE_BY_STAFF) {
      $this->warmUpCanSignCache();
      foreach ($uid_consent_map as $uid => $consents) {
        $valid = FALSE;
        foreach ($consents as $consent_id => $target_ids) {
          foreach ($target_ids as $target_uid => $status) {
            if ($status == self::CONSENT_NOT_ANSWERED && !isset($this->canSignUids[$uid])) {
              $valid = TRUE;
              break 2;
            }
          }
        }
        if ($valid) {
          $data[] = $uid;
        }
      }
    }
    elseif ($filter === self::VIEWS_FILTER_TO_REMIND) {
      $this->warmUpCanSignCache();
      foreach ($uid_consent_map as $uid => $consents) {
        $valid = FALSE;
        foreach ($consents as $consent_id => $target_ids) {
          foreach ($target_ids as $target_uid => $status) {
            if ($status == self::CONSENT_NOT_ANSWERED && isset($this->canSignUids[$uid])) {
              $valid = TRUE;
              break 2;
            }
          }
        }
        if ($valid) {
          $data[] = $uid;
        }
      }
    }

    $data = array_unique($data);
    $cache_tags = ['node_list:consent', 'ssr_consent_answer_list', 'user_list:roles', 'user_list:new'];
    $this->cache->set($cid, $data, Cache::PERMANENT, $cache_tags);

    $this->lookup[$cid] = $data;
    return $this->lookup[$cid];
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetUids(int $consent_id, int $filter = self::VIEWS_FILTER_NONE): array {
    $cid = 'target_uids_' . $consent_id . ':' . $filter;

    if (isset($this->lookup[$cid])) {
      return $this->lookup[$cid];
    }

    $cache = $this->cache->get($cid);

    if (!empty($cache)) {
      $this->lookup[$cid] = $cache->data;

      return $this->lookup[$cid];
    }

    $data = [];


    // Resolve target uids.

    $consent_uid_map = $this->getConsentUidMap()[$consent_id] ?? [];

    if ($filter === self::VIEWS_FILTER_NONE) {
      $data = array_keys($consent_uid_map);
    }
    elseif ($filter === self::VIEWS_FILTER_FULLY_ACCEPTED) {
      foreach ($consent_uid_map as $target_uid => $user_data) {
        $valid = TRUE;
        foreach ($user_data as $uid => $status_data) {
          if ($status_data['status'] != self::CONSENT_ACCEPTED) {
            $valid = FALSE;
            break;
          }
        }
        if ($valid) {
          $data[] = $target_uid;
        }
      }
    }
    elseif ($filter === self::VIEWS_FILTER_SOME_ACCEPTED) {
      foreach ($consent_uid_map as $target_uid => $user_data) {
        $valid = FALSE;
        foreach ($user_data as $uid => $status_data) {
          if ($status_data['status'] == self::CONSENT_ACCEPTED) {
            $valid = TRUE;
            break;
          }
        }
        if ($valid) {
          $data[] = $target_uid;
        }
      }
    }
    elseif ($filter === self::VIEWS_FILTER_SOME_REJECTED) {
      foreach ($consent_uid_map as $target_uid => $user_data) {
        $valid = FALSE;
        foreach ($user_data as $uid => $status_data) {
          if ($status_data['status'] == self::CONSENT_REJECTED) {
            $valid = TRUE;
            break;
          }
        }
        if ($valid) {
          $data[] = $target_uid;
        }
      }
    }
    elseif ($filter === self::VIEWS_FILTER_FULL_REJECTED) {
      foreach ($consent_uid_map as $target_uid => $user_data) {
        $valid = TRUE;
        foreach ($user_data as $uid => $status_data) {
          if ($status_data['status'] != self::CONSENT_REJECTED) {
            $valid = FALSE;
            break;
          }
        }
        if ($valid) {
          $data[] = $target_uid;
        }
      }
    }
    elseif ($filter === self::VIEWS_FILTER_MISSING_ANSWERS) {
      foreach ($consent_uid_map as $target_uid => $user_data) {
        $valid = FALSE;
        foreach ($user_data as $uid => $status_data) {
          if ($status_data['status'] == self::CONSENT_NOT_ANSWERED) {
            $valid = TRUE;
            break;
          }
        }
        if ($valid) {
          $data[] = $target_uid;
        }
      }
    }
    elseif ($filter === self::VIEWS_FILTER_HANDLE_BY_STAFF) {
      $this->warmUpCanSignCache();
      foreach ($consent_uid_map as $target_uid => $user_data) {
        $valid = FALSE;
        foreach ($user_data as $uid => $status_data) {
          if ($status_data['status'] == self::CONSENT_NOT_ANSWERED && !isset($this->canSignUids[$uid])) {
            $valid = TRUE;
            break;
          }
        }
        if ($valid) {
          $data[] = $target_uid;
        }
      }
    }

    $data = array_unique($data);

    $this->cache->set($cid, $data, Cache::PERMANENT, ['node:' . $consent_id]);

    $this->lookup[$cid] = $data;
    return $this->lookup[$cid];
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetUidsByUidWithData(int $uid): array {
    $consent_map = $this->getUidConsentMap()[$uid] ?? [];
    $consent_uid_map = $this->getConsentUidMap();

    $target_uids = [];
    foreach ($consent_map as $consent_id => $target_id_data) {
      foreach (array_keys($target_id_data) as $target_uid) {
        $target_uids[$target_uid][$consent_id] = $consent_uid_map[$consent_id][$target_uid][$uid];
      }
    }

    return $target_uids;
  }

  /**
   * {@inheritdoc}
   */
  public function getConsentStatus(int $consent_id, int $target_uid): array {
    $cid = 'consent_status_' . $consent_id . '_' . $target_uid;

    if (isset($this->lookup[$cid])) {
      return $this->lookup[$cid];
    }

    $data = [];

    $consent_data = $this->getConsentUidMap()[$consent_id] ?? [];
    $consent_data = $consent_data[$target_uid] ?? [];
    $name_map = [];

    if (!empty($consent_data)) {
      $uids_to_map = array_keys($consent_data);
      $uids_to_map[] = $target_uid;

      $users = $this->entityTypeManager->getStorage('user')->loadMultiple(array_unique($uids_to_map));
      /** @var \Drupal\user\UserInterface $user */
      foreach ($users as $user) {
        $name_map[$user->id()] = $user->getDisplayName();
      }
    }

    if (!empty($name_map)) {
      foreach ($consent_data as $uid => $status_data) {
        if (isset($name_map[$uid])) {
          $status = $status_data['status'] ?? self::CONSENT_NOT_ANSWERED;
          $status_display = $status_data['status_display'] ?? NULL;

          if (!$status_display) {
            $status_display = match($status) {
              self::CONSENT_NOT_ANSWERED => $this->t('Not answered'),
              self::CONSENT_REJECTED => $this->t('Rejected'),
              self::CONSENT_ACCEPTED => $this->t('Accepted'),
              default => $this->t('Not answered'),
            };
          }

          $data[$uid] = [
            'name' => $name_map[$uid],
            'target_name' => $name_map[$target_uid],
            'status' => $status_display,
            'value' => $status,
            'answer_id' => $status_data['answer_id'] ?? NULL,
            'locked' => $status_data['locked'] ?? FALSE,
          ];
        }
      }
    }
    else {
      $data[0] = [
        'name' => '',
        'target_name' => '',
        'status' => $this->t('No consent expected'),
        'value' => self::CONSENT_NOT_ANSWERED,
        'answer_id' => NULL,
        'locked' => $status_data['locked'] ?? FALSE,
      ];
    }

    $this->lookup[$cid] = $data;
    return $this->lookup[$cid];
  }

  /**
   * {@inheritdoc}
   */
  public function allowConsentHandling(int $consent_id, int $target_uid, ?int $consent_uid = NULL): bool {
    $consent_uid_map = $this->getConsentUidMap();

    if (empty($consent_uid_map[$consent_id][$target_uid])) {
      return FALSE;
    }

    // Check for locked consent.
    foreach ($consent_uid_map[$consent_id][$target_uid] as $uid => $status_data) {
      if ($status_data['locked']) {
        return FALSE;
      }
      break;
    }

    if (!$consent_uid) {
      if ($this->currentUser->hasPermission('administer any consents')) {
        return TRUE;
      }
      $uids = array_keys($consent_uid_map[$consent_id][$target_uid]);
    }
    else {
      if (empty($consent_uid_map[$consent_id][$target_uid][$consent_uid])) {
        return FALSE;
      }
      if ($this->currentUser->hasPermission('administer any consents')) {
        return TRUE;
      }
      $uids = [$consent_uid];
    }

    $users = $this->entityTypeManager->getStorage('user')->loadMultiple($uids);
    foreach ($users as $user) {
      if ($user->access('update', $this->currentUser)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  public function getConsentNames(): array {
    $cid = 'consent_name_map';

    if (isset($this->lookup[$cid])) {
      return $this->lookup[$cid];
    }

    $data = [];
    $consents = $this->entityTypeManager->getStorage('node')->loadByProperties(['type' => 'consent']);
    /** @var \Drupal\node\NodeInterface $consent */
    foreach ($consents as $consent) {
      $data[$consent->id()] = $consent->get('field_title')->value;
    }

    $this->lookup[$cid] = $data;
    return $this->lookup[$cid];
  }

}
