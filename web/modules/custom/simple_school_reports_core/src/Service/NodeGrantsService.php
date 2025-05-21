<?php

namespace Drupal\simple_school_reports_core\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\node_access_grants\NodeAccessGrantsInterface;

/**
 * Class NodeGrantsService
 *
 * @see https://www.drupal.org/project/node_access_grants
 *
 * @package Drupal\simple_school_reports_grade_registration
 */
class NodeGrantsService implements NodeAccessGrantsInterface {

  /**
   * Grant id for administrator grant.
   */
  const SSR_ADMIN_GRANT = 1;

  /**
   * Grant id for principle grant.
   */
  const SSR_PRINCIPLE_GRANT = 1;

  const SSR_SCHOOL_STAFF = 1;

  const SSR_BUDGET_ACCESS = 1;

  /**
   * Roles that should have editor access.
   *
   * @var array
   */
  protected static $adminRoles = ['principle', 'administrator'];


  /**
   * @var \Drupal\simple_school_reports_core\Service\UserMetaDataServiceInterface
   */
  protected $userMetaData;

  protected $calculatedGrants = [];


  public function __construct(
    UserMetaDataServiceInterface $user_meta_data
  ) {
    $this->userMetaData = $user_meta_data;
  }

  /**
   * Sets access records nodes.
   *
   * @param \Drupal\node\NodeInterface $node
   *
   * @return array
   */
  public function accessRecords(NodeInterface $node) {
    $grants = [];
    $node_type = $node->getType();

    if ($node_type === 'day_absence') {
      $grants['ssr_school_staff_view' . self::SSR_SCHOOL_STAFF] = [
        'realm' => 'ssr_school_staff_view',
        'gid' => self::SSR_SCHOOL_STAFF,
        'grant_view' => 1,
        'grant_update' => 0,
        'grant_delete' => 1,
      ];

      $this->giveStudentsViewAccess($grants, $node);
    }

    if ($node_type === 'course') {
      $grants['ssr_course_admin'.self::SSR_ADMIN_GRANT] = [
        'realm' => 'ssr_course_admin',
        'gid' => self::SSR_ADMIN_GRANT,
        'grant_view' => 1,
        'grant_update' => 1,
        'grant_delete' => 1,
      ];

      $grants['ssr_course_admin'.self::SSR_PRINCIPLE_GRANT] = [
        'realm' => 'ssr_course_admin',
        'gid' => self::SSR_PRINCIPLE_GRANT,
        'grant_view' => 1,
        'grant_update' => 1,
        'grant_delete' => 1,
      ];

      if (!$node->get('field_teacher')->isEmpty()) {
        $teacher_uids = array_column($node->get('field_teacher')->getValue(), 'target_id');;

        foreach ($teacher_uids as $teacher_uid) {
          $grants['ssr_course_admin'.$teacher_uid] = [
            'realm' => 'ssr_course_admin',
            'gid' => $teacher_uid,
            'grant_view' => 1,
            'grant_update' => 1,
            'grant_delete' => 1,
          ];
        }
      }

      $this->giveStudentsViewAccess($grants, $node);
    }

    if ($node_type === 'course_attendance_report') {
      $grants['ssr_attendance_report_admin'.self::SSR_ADMIN_GRANT] = [
        'realm' => 'ssr_attendance_report_admin',
        'gid' => self::SSR_ADMIN_GRANT,
        'grant_view' => 1,
        'grant_update' => 1,
        'grant_delete' => 1,
      ];

      $grants['ssr_attendance_report_admin'.self::SSR_PRINCIPLE_GRANT] = [
        'realm' => 'ssr_attendance_report_admin',
        'gid' => self::SSR_PRINCIPLE_GRANT,
        'grant_view' => 1,
        'grant_update' => 1,
        'grant_delete' => 1,
      ];

      if (!$node->get('field_course')->isEmpty()) {
        $course_id = $node->get('field_course')->target_id;

        $grants['ssr_attendance_report_admin'.$course_id] = [
          'realm' => 'ssr_attendance_report_admin',
          'gid' => $course_id,
          'grant_view' => 1,
          'grant_update' => 1,
          'grant_delete' => 1,
        ];

        $grants['ssr_attendance_report_view'.$course_id] = [
          'realm' => 'ssr_attendance_report_view',
          'gid' => $course_id,
          'grant_view' => $node->isPublished() ? 1 : 0,
          'grant_update' => 0,
          'grant_delete' => 0,
        ];

        $grants['ssr_attendance_report_view'.self::SSR_SCHOOL_STAFF] = [
          'realm' => 'ssr_attendance_report_view',
          'gid' => self::SSR_SCHOOL_STAFF,
          'grant_view' => $node->isPublished() ? 1 : 0,
          'grant_update' => 0,
          'grant_delete' => 0,
        ];
      }

      foreach ($node->get('field_student_course_attendance')->referencedEntities() as $report_card) {
        if ($report_card->get('field_student')->target_id) {
          $grants['ssr_student_view' . $report_card->get('field_student')->target_id] = [
            'realm' => 'ssr_student_view',
            'gid' => $report_card->get('field_student')->target_id,
            'grant_view' => $node->isPublished() ? 1 : 0,
            'grant_update' => 0,
            'grant_delete' => 0,
          ];
        }
      }
    }

    if ($node_type === 'grade_subject') {
      $grants['ssr_grade_reg_admin'.self::SSR_ADMIN_GRANT] = [
        'realm' => 'ssr_grade_reg_admin',
        'gid' => self::SSR_ADMIN_GRANT,
        'grant_view' => 1,
        'grant_update' => 1,
        'grant_delete' => 1,
      ];

      $grants['ssr_grade_reg_allow'.self::SSR_PRINCIPLE_GRANT] = [
        'realm' => 'ssr_grade_reg_allow',
        'gid' => self::SSR_PRINCIPLE_GRANT,
        'grant_view' => 1,
        'grant_update' => 1,
        'grant_delete' => 0,
      ];

      if (!$node->get('field_teacher')->isEmpty()) {
        $teacher_uids = array_column($node->get('field_teacher')->getValue(), 'target_id');;
        foreach ($teacher_uids as $teacher_uid) {
          $grants['ssr_grade_reg_allow'.$teacher_uid] = [
            'realm' => 'ssr_grade_reg_allow',
            'gid' => $teacher_uid,
            'grant_view' => 1,
            'grant_update' => 1,
            'grant_delete' => 0,
          ];
        }
      }
    }

    if ($node_type === 'iup' || $node_type === 'iup_goal') {
      $grants['ssr_school_staff_full_access' . self::SSR_SCHOOL_STAFF] = [
        'realm' => 'ssr_school_staff_full_access',
        'gid' => self::SSR_SCHOOL_STAFF,
        'grant_view' => 1,
        'grant_update' => 1,
        'grant_delete' => 1,
      ];

      $this->giveStudentsViewAccess($grants, $node);
    }

    if ($node_type === 'list_template') {
      $grants['ssr_school_staff_view' . self::SSR_SCHOOL_STAFF] = [
        'realm' => 'ssr_school_staff_view',
        'gid' => self::SSR_SCHOOL_STAFF,
        'grant_view' => $node->get('field_public')->value ? 1 : 0,
        'grant_update' => 0,
        'grant_delete' => 0,
      ];

      $grants['node_owner' . $node->getOwnerId()] = [
        'realm' => 'node_owner',
        'gid' => $node->getOwnerId(),
        'grant_view' => 1,
        'grant_update' => 1,
        'grant_delete' => 1,
      ];

    }

    if ($node_type === 'budget') {
      $grants['budget_reviewer' . self::SSR_BUDGET_ACCESS] = [
        'realm' => 'budget_reviewer',
        'gid' => self::SSR_BUDGET_ACCESS,
        'grant_view' => 1,
        'grant_update' => 0,
        'grant_delete' => 0,
      ];

      $grants['budget_administrator' . self::SSR_BUDGET_ACCESS] = [
        'realm' => 'budget_administrator',
        'gid' => self::SSR_BUDGET_ACCESS,
        'grant_view' => 1,
        'grant_update' => 1,
        'grant_delete' => 1,
      ];

    }

    if ($node_type === 'help_page') {
      $grants['help_page_admin' . self::SSR_ADMIN_GRANT] = [
        'realm' => 'help_page_admin',
        'gid' => self::SSR_ADMIN_GRANT,
        'grant_view' => 1,
        'grant_update' => 1,
        'grant_delete' => 1,
      ];

      if ($node->get('field_target_group')->isEmpty()) {
        $grants['help_page_view_all' . 1] = [
          'realm' => 'help_page_view_all',
          'gid' => 1,
          'grant_view' => 1,
          'grant_update' => 0,
          'grant_delete' => 0,
        ];
      }
      else {

        $grants['help_page_view_school_staff' . self::SSR_SCHOOL_STAFF] = [
          'realm' => 'help_page_view_school_staff',
          'gid' => self::SSR_SCHOOL_STAFF,
          'grant_view' => 1,
          'grant_update' => 0,
          'grant_delete' => 0,
        ];

        $roles = array_column($node->get('field_target_group')->getValue(), 'target_id');
        foreach ($roles as $role) {
          $rid = $this->getRoleId($role);
          if (!$rid) {
            continue;
          }

          $grants['help_page_view_role' . $rid] = [
            'realm' => 'help_page_view_role',
            'gid' => $rid,
            'grant_view' => 1,
            'grant_update' => 0,
            'grant_delete' => 0,
          ];
        }
      }
    }

    return array_values($grants);
  }

  protected function getRoleId(string $role): int {
    $map = [
      'anonymous' => 1,
      'authenticated' => 2,
      'student' => 3,
      'caregiver' => 4,
      'teacher' => 5,
      'administrator' => 6,
      'principle' => 7,
      'budget_reviewer' => 8,
      'budget_administrator' => 9,
    ];

    if (isset($map[$role])) {
      return $map[$role];
    }
    return 0;
  }

  protected function giveStudentsViewAccess(array &$grants, NodeInterface $node) {
    if (!$node->get('field_student')->isEmpty()) {
      $students_uid = array_column($node->get('field_student')->getValue(), 'target_id');
      foreach ($students_uid as $student_uid) {
        $grants['ssr_student_view' . $student_uid] = [
          'realm' => 'ssr_student_view',
          'gid' => $student_uid,
          'grant_view' => $node->isPublished() ? 1 : 0,
          'grant_update' => 0,
          'grant_delete' => 0,
        ];
      }
    }
  }

  /**
   * OO version of hook_node_grants().
   *
   * Remember in node_grants the function should
   * *NOT* have to know anything about the node :)
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   * @param string $op
   *
   * @return array
   *
   * @throws \InvalidArgumentException
   */
  public function grants(AccountInterface $account, $op) {
    $uid = $account->id();

    if (!empty($this->calculatedGrants[$uid][$op])) {
      return $this->calculatedGrants[$uid][$op];
    }

    $grants = [];

    $roles = $account->getRoles();
    $is_principle = in_array('principle', $roles);
    $is_school_staff = $account->hasPermission('school staff permissions');
    $is_admin = $account->hasPermission('administer simple school reports settings');

    $mentor_student_uids = $this->userMetaData->getMentorStudents($uid);
    $caregiver_student_uids = $this->userMetaData->getCaregiverStudents($uid);

    $grants['node_owner'][] = $uid;

    if ($is_school_staff) {
      $grants['ssr_school_staff_full_access'][] = self::SSR_SCHOOL_STAFF;
      $grants['ssr_school_staff_view'][] = self::SSR_SCHOOL_STAFF;
    }

    if ($is_admin) {
      $grants['ssr_grade_reg_admin'][] = self::SSR_ADMIN_GRANT;
      $grants['ssr_course_admin'][] = self::SSR_ADMIN_GRANT;
    }

    if ($is_principle) {
      $grants['ssr_grade_reg_allow'][] = self::SSR_PRINCIPLE_GRANT;
      $grants['ssr_course_admin'][] = self::SSR_PRINCIPLE_GRANT;
    }
    $grants['ssr_grade_reg_allow'][] = $uid;
    $grants['ssr_course_admin'][] = $uid;
    $grants['ssr_student_view'][] = $uid;
    $grants['help_page_admin'][] = $uid;
    $grants['help_page_view_all'][] = 1;

    foreach ($mentor_student_uids as $mentor_student_uid) {
      $grants['ssr_student_view'][] = (string) $mentor_student_uid;
    }

    foreach ($caregiver_student_uids as $caregiver_student_uid) {
      $grants['ssr_student_view'][] = (string) $caregiver_student_uid;
    }

    $teacher_course_ids = $this->userMetaData->getTeacherCourses($uid);
    foreach ($teacher_course_ids as $teacher_course_id) {
      $grants['ssr_attendance_report_admin'][] = $teacher_course_id;
    }

    $student_course_ids = $this->userMetaData->getStudentCourses(array_merge([$uid], $mentor_student_uids));
    foreach ($student_course_ids as $student_course_id) {
      $grants['ssr_attendance_report_view'][] = $student_course_id;
    }

    if ($is_school_staff) {
      $grants['ssr_attendance_report_view'][] = self::SSR_SCHOOL_STAFF;
    }

    // Budget stuff...
    $is_budget_reviewer = $uid == 1 || in_array('budget_reviewer', $roles);
    $is_budget_administrator = $uid == 1 || in_array('budget_administrator', $roles);
    if ($is_budget_reviewer) {
      $grants['budget_reviewer'][] = self::SSR_BUDGET_ACCESS;
    }
    if ($is_budget_administrator) {
      $grants['budget_administrator'][] = self::SSR_BUDGET_ACCESS;
    }

    // Help pages specific.
    if ($is_school_staff) {
      $grants['help_page_view_school_staff'][] = self::SSR_SCHOOL_STAFF;
    }
    else {
      foreach ($roles as $role) {
        $rid = $this->getRoleId($role);
        if ($rid) {
          $grants['help_page_view_role'][] = $role;
        }
      }
    }


    $this->calculatedGrants[$uid][$op] = $grants;
    return $grants;
  }

}
