<?php

namespace Drupal\simple_school_reports_leave_application\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Drupal\simple_school_reports_core\Service\UserMetaDataServiceInterface;

/**
 * Class LeaveApplicationService
 */
class LeaveApplicationService implements LeaveApplicationServiceInterface {

  public function __construct(
    protected StateInterface $state,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountInterface $currentUser,
    protected UserMetaDataServiceInterface $userMetaDataService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getStudentLeaveApplicationIdsToHandle(?string $uid = NULL): array {
    if (!$uid) {
      $uid = $this->currentUser->id();
    }
    /** @var \Drupal\user\UserInterface $user */
    $user = $this->entityTypeManager->getStorage('user')->load($uid);
    if (!$user) {
      return [];
    }

    $handle_long = $user->hasPermission('handle long ssr_student_leave_application');
    $mentoring_uids = $this->userMetaDataService->getMentorStudents($uid);

    if (empty($mentoring_uids) && !$handle_long) {
      return [];
    }

    $query = $this->entityTypeManager->getStorage('ssr_student_leave_application')->getQuery()->accessCheck(FALSE);
    $query->exists('student');
    $query->condition('state', 'pending');
    $query->condition('status', 1);

    if (!$handle_long) {
      $query->condition('leave_days', $this->getSetting('long_leave'), '<=');
    }

    $or_condition = $query->orConditionGroup();
    if ($handle_long) {
      $or_condition->condition('leave_days', $this->getSetting('long_leave'), '>');
    }

    if (!empty($mentoring_uids)) {
      $or_condition->condition('student', $mentoring_uids, 'IN');
    }

    $query->condition($or_condition);
    return $query->execute();
  }

  public function getSettings(): array {
    $settings = $this->state->get('ssr_student_leave_application_settings', []);

    $settings += [
      'long_leave' => 10,
      'max_application_days' => 30,
      'max_application_days_ago' => 7,
      'max_application_days_future' => 365,
    ];

    return $settings;
  }

  public function getSetting(string $key, int $default = 1): int {
    $settings = $this->getSettings();
    return $settings[$key] ?? $default;
  }

  public function setSettings(array $settings): void {
    $to_store = [];
    foreach ($settings as $key => $value) {
      if (is_numeric($value) && $value > 0) {
        $to_store[$key] = (int) $value;
      }
    }
    $this->state->set('ssr_student_leave_application_settings', $to_store);
  }

}
