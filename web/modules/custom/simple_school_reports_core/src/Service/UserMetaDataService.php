<?php

namespace Drupal\simple_school_reports_core\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\simple_school_reports_core\SchoolGradeHelper;
use Drupal\simple_school_reports_core\SchoolTypeHelper;
use Drupal\user\UserInterface;

/**
 * Class TermService
 *
 * @package Drupal\simple_school_reports_core\Service
 */
class UserMetaDataService implements UserMetaDataServiceInterface {

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
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  protected $calculatedData = [];


  /**
   * @param \Drupal\Core\Database\Connection $connection
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   */
  public function __construct(
    Connection $connection,
    EntityTypeManagerInterface $entity_type_manager,
    CacheBackendInterface $cache,
    TimeInterface $time
  ) {
    $this->connection = $connection;
    $this->entityTypeManager = $entity_type_manager;
    $this->cache = $cache;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public function getMentorStudents(string $uid): array {
    if (!isset($this->calculatedData['mentor_students_' . $uid])) {
      $cid = 'mentor_students:' . $uid;
      $cache = $this->cache->get($cid);
      if ($cache && is_array($cache->data)) {
        $student_uids = $cache->data;
      }
      else {
        $student_uids = $this->entityTypeManager->getStorage('user')->getQuery()
          ->condition('field_mentor', $uid)
          ->accessCheck(FALSE)
          ->execute();
        $this->cache->set($cid, $student_uids, Cache::PERMANENT, ['user_list']);
      }
      $this->calculatedData['mentor_students_' . $uid] = $student_uids;
    }
    return $this->calculatedData['mentor_students_' . $uid];
  }

  /**
   * Get all caregiver students map.
   *
   * @return array
   */
  protected function getCaregiverStudentsMap(): array {
    $cid = 'caregiver_students';
    $cache = $this->cache->get($cid);
    if ($cache && is_array($cache->data)) {
      return $cache->data;
    }

    $caregiver_students_map = [];

    $query = $this->connection->select('user__field_caregivers', 'c');
    $query->innerJoin('user__field_first_name', 'fn', 'fn.entity_id = c.entity_id');
    $query->innerJoin('user__field_last_name', 'ln', 'ln.entity_id = c.entity_id');
    $query->leftJoin('user__field_grade', 'g', 'g.entity_id = c.entity_id');
    $results = $query->fields('c', ['entity_id', 'field_caregivers_target_id'])
      ->fields('fn', ['field_first_name_value'])
      ->fields('ln', ['field_last_name_value'])
      ->fields('g', ['field_grade_value'])
      ->orderBy('g.field_grade_value')
      ->orderBy('fn.field_first_name_value')
      ->orderBy('ln.field_last_name_value')
      ->execute();

    foreach ($results as $result) {
      $caregiver_uid = $result->field_caregivers_target_id;
      $student_uid = $result->entity_id;

      if (!empty($caregiver_students_map[$caregiver_uid][$student_uid])) {
        continue;
      }

      $name = strip_tags($result->field_first_name_value . ' ' . $result->field_last_name_value);
      $grade_name_suffix_map = SchoolGradeHelper::getSchoolGradesShortName(['FKLASS', 'GR', 'GY']);
      if ($grade = $result->field_grade_value) {
        $grade = (int) $grade;
        if (isset($grade_name_suffix_map[$grade])) {
          $name .= ' (' . $this->t($grade_name_suffix_map[$grade]) . ')';
        }
      }
      $url = Url::fromRoute('entity.user.canonical', ['user' => $student_uid]);
      $link = Link::fromTextAndUrl($name, $url);

      $caregiver_students_map[$caregiver_uid][$student_uid] = [
        'name' => $name,
        'link' => $link,
      ];
    }

    $this->cache->set($cid, $caregiver_students_map, Cache::PERMANENT, ['user_list']);

    return $caregiver_students_map;
  }

  /**
   * {@inheritdoc}
   */
  public function getCaregiverStudentsData(string $uid, bool $check_caregiver_access = FALSE): array {
    if (!isset($this->calculatedData['caregiver_students_data' . $uid])) {
      $caregiver_students_map = $this->getCaregiverStudentsMap();
      $students_data = $caregiver_students_map[$uid] ?? [];

      $this->calculatedData['caregiver_students_data' . $uid] = $students_data;
    }

    if ($check_caregiver_access) {
      $students_data = $this->calculatedData['caregiver_students_data' . $uid];
      foreach ($students_data as $student_uid => $data) {
        if (!$this->caregiversHasAccess($student_uid)) {
          unset($students_data[$student_uid]);
        }
      }
      return $students_data;
    }

    return $this->calculatedData['caregiver_students_data' . $uid];
  }

  /**
   * {@inheritdoc}
   */
  public function getCaregiverStudents(string $uid, bool $check_caregiver_access = FALSE): array {
    if (!isset($this->calculatedData['caregiver_students_' . $uid])) {
      $this->calculatedData['caregiver_students_' . $uid] = array_keys($this->getCaregiverStudentsData($uid));
    }

    if ($check_caregiver_access) {
      return array_filter($this->calculatedData['caregiver_students_' . $uid], function ($student_uid) {
        return $this->caregiversHasAccess($student_uid);
      });
    }

    return $this->calculatedData['caregiver_students_' . $uid];
  }

  public function getCaregiverUids(UserInterface $child, bool $only_caregivers_with_access = FALSE): array {
    if ($only_caregivers_with_access && !$this->caregiversHasAccess($child->id())) {
      return [];
    }
    return array_column($child->get('field_caregivers')->getValue(), 'target_id');
  }

  public function getCaregivers(UserInterface $child, bool $only_caregivers_with_access = FALSE): array {
    $caregivers_uid = $this->getCaregiverUids($child, $only_caregivers_with_access);
    if (empty($caregivers_uid)) {
      return [];
    }
    return $this->entityTypeManager->getStorage('user')->loadMultiple($caregivers_uid);
  }

  /**
   * {@inheritdoc}
   */
  public function getTeacherCourses(string $uid): array {
    if (!isset($this->calculatedData['teacher_courses_' . $uid])) {
      $cid = 'teacher_courses:' . $uid;
      $cache = $this->cache->get($cid);
      if ($cache && is_array($cache->data)) {
        $course_ids = $cache->data;
      }
      else {
        $course_ids = $this->entityTypeManager->getStorage('node')->getQuery()
          ->condition('field_teacher', $uid)
          ->condition('type', 'course')
          ->accessCheck(FALSE)
          ->execute();
        $this->cache->set($cid, $course_ids, Cache::PERMANENT, ['node_list:course']);
      }
      $this->calculatedData['teacher_courses_' . $uid] = $course_ids;
    }
    return $this->calculatedData['teacher_courses_' . $uid];
  }

  public function getStudentCourses(array $uids): array {
    if (empty($uids)) {
      return [];
    }
    $key = implode('_', $uids);
    if (!isset($this->calculatedData['student_courses_' . $key])) {
      $cid = 'student_courses:' . $key;
      $cache = $this->cache->get($cid);
      if ($cache && is_array($cache->data)) {
        $course_ids = $cache->data;
      }
      else {
        $course_ids = $this->entityTypeManager->getStorage('node')->getQuery()
          ->condition('field_student', $uids, 'IN')
          ->condition('type', 'course')
          ->accessCheck(FALSE)
          ->execute();
        $this->cache->set($cid, $course_ids, Cache::PERMANENT, ['node_list:course']);
      }
      $this->calculatedData['student_courses_' . $key] = $course_ids;
    }
    return $this->calculatedData['student_courses_' . $key];
  }

  public function getStudentGradesAgeData(bool $skip_ended = FALSE): array {
    $cid = 'students_age_grade';
    if ($skip_ended) {
      $cid .= '_no_ended';
    }
    $cache = $this->cache->get($cid);
    if ($cache && is_array($cache->data)) {
      return $cache->data;
    }

    $expire = Cache::PERMANENT;

    $raw_grades = [];
    $raw_ages = [];
    $uids = [];

    $now = new \DateTime('now', new \DateTimeZone('utc'));

    $this_year = $now->format('Y');
    $next_year = $this_year + 1;

    $query = $this->connection->select('users_field_data', 'u');
    $query->innerJoin('user__roles', 'r', 'r.entity_id = u.uid');
    $query->leftJoin('user__field_grade', 'g', 'g.entity_id = u.uid');
    $query->leftJoin('user__field_birth_date', 'b', 'b.entity_id = u.uid');
    $query->condition('r.roles_target_id', 'student');
    $query->condition('u.status', 1);
    $query->fields('u', ['uid']);
    $query->fields('g', ['field_grade_value']);
    $query->fields('b', ['field_birth_date_value']);
    $results = $query->execute();

    foreach ($results as $result) {
      if (!$result->uid) {
        continue;
      }
      $uid = $result->uid;

      if ($result->field_grade_value !== NULL) {
        $raw_grades[$uid] = (int) $result->field_grade_value;
      }
      $uids[$uid] = $uid;

      if ($result->field_birth_date_value !== NULL && !isset($raw_ages[$uid])) {
        $date = new \DateTime('now', new \DateTimeZone('utc'));
        $date->setTimestamp($result->field_birth_date_value);
        $age = $now->diff($date)->y;
        if ($age > 0 && $age <= 999) {
          $raw_ages[$uid] = (int) $age;

          $next_birth_date = new \DateTime($this_year . '-' . $date->format('m-d H:i:s'), new \DateTimeZone('utc'));
          if ($next_birth_date < $now) {
            $next_birth_date = new \DateTime($next_year . '-' . $date->format('m-d H:i:s'), new \DateTimeZone('utc'));
          }

          $age_expire = $next_birth_date->getTimestamp() + 1;
          if ($expire === Cache::PERMANENT) {
            $expire = $age_expire;
          }
          else {
            $expire = min($expire, $age_expire);
          }
        }
      }
    }

    $grades = [
      SchoolGradeHelper::UNKNOWN_GRADE => 0,
    ];
    $ages = [
      self::UNKNOWN_AGE => 0,
      'total' => 0,
    ];

    $grade_map = SchoolGradeHelper::getSchoolGradeValues(NULL, TRUE, !$skip_ended);

    foreach ($uids as $uid) {
      if (isset($raw_grades[$uid]) && isset($grade_map[$raw_grades[$uid]])) {
        if ($skip_ended && $raw_grades[$uid] == 99) {
          continue;
        }

        if (!isset($grades[$raw_grades[$uid]])) {
          $grades[$raw_grades[$uid]] = 0;
        }
        $grades[$raw_grades[$uid]]++;
      }
      else {
        $grades[SchoolGradeHelper::UNKNOWN_GRADE]++;
      }

      if (isset($raw_ages[$uid])) {
        if (!isset($ages[$raw_ages[$uid]])) {
          $ages[$raw_ages[$uid]] = 0;
        }
        $ages[$raw_ages[$uid]]++;
      }
      else {
        $ages[self::UNKNOWN_AGE]++;
      }
      $ages['total']++;
    }

    $grades_total = $grades['total'] ?? 0;
    unset($grades['total']);
    $ages_total = $ages['total'] ?? 0;
    unset($ages['total']);

    ksort($grades);
    ksort($ages);

    $grades['total'] = $grades_total;
    $ages['total'] = $ages_total;

    $data = [
      'ages' => $ages,
      'grades' => $grades,
    ];

    $this->cache->set($cid, $data, $expire, ['user_list:student']);

    return $data;

  }

  public function getAgeGroupsFromBudgetNode(NodeInterface $budget): array {
    if ($budget->bundle() !== 'budget') {
      return [];
    }

    $data = $this->getStudentGradesAgeData(TRUE);
    $raw_ages = $data['ages'] ?? [];

    $ages = [
      -99 => $raw_ages[-99] ?? 0,
      'total' => $raw_ages['total'] ?? 0,
    ];

    $age_groups = [];

    // Combine ages...
    $rows = $budget->get('field_budget_row')->referencedEntities();
    /** @var \Drupal\paragraphs\ParagraphInterface $row */
    foreach ($rows as $row) {
      if ($row->bundle() === 'budget_row') {
        $row_type = $row->get('field_row_type')->value;
        if ($row_type === 'per_student' || $row_type === 'annual_worker') {
          $age_from = $row->get('field_age_limit_from')->value ?? '';
          $age_to = $row->get('field_age_limit_to')->value ?? '';

          if ($age_from || $age_to) {


            if ($age_from && $age_to) {
              $key = $age_from . '-' . $age_to;
            }
            elseif ($age_from) {
              $key = '>=' . $age_from;
            }
            elseif ($age_to) {
              $key = '<=' . $age_to;
            }

            $age_groups[$key] = [
              'from' => $age_from ?? NULL,
              'to' => $age_to ?? NULL,
            ];
          }
        }
      }
    }


    foreach ($age_groups as $key => $age_group) {
      $age_from = $age_group['from'];
      $age_to = $age_group['to'];

      $value = $ages[$key] ?? 0;
      if ($age_to >= $age_from) {
        for ($age = $age_from; $age <= $age_to; $age++ ) {
          $part_age = $raw_ages[$age] ?? 0;
          $value += $part_age;
        }
      }
      $ages[$key] = $value;
    }

    $ages_total = $ages['total'] ?? 0;
    unset($ages['total']);
    ksort($ages);
    $ages['total'] = $ages_total;

    return [
      'ages' => $ages,
    ];
  }

  public function getStudentCacheAgeMax($skip_ended = FALSE): int {
    $cid = 'students_age_grade';
    if ($skip_ended) {
      $cid .= '_no_ended';
    }
    $cache = $this->cache->get($cid);
    if (!$cache) {
      return 0;
    }

    $expire = $cache->expire;

    if ($expire == Cache::PERMANENT) {
      return Cache::PERMANENT;
    }

    $max_age = $expire - $this->time->getRequestTime();

    return max(0, $max_age);
  }

  /**
   * {@inheritdoc}
   */
  public function getUserWeights(bool $only_active = TRUE): array {
    $cid = 'ssr_user_weights_' . ($only_active ? 'active' : 'all');
    if (!isset($this->calculatedData[$cid])) {
      $cache = $this->cache->get($cid);
      if ($cache && is_array($cache->data)) {
        $user_weights = $cache->data;
      }
      else {
        $query = $this->entityTypeManager->getStorage('user')->getQuery()
          ->condition('uid', 0, '<>')
          ->sort('field_grade')
          ->sort('field_first_name')
          ->sort('field_last_name')
          ->sort('uid');

        if ($only_active) {
          $query->condition('status', 1);
        }
        $uids = $query->accessCheck(FALSE)->execute();
        $weight = 0;
        $user_weights = [];

        foreach ($uids as $uid) {
          $weight++;
          $user_weights[$uid] = $weight;
        }

        $this->cache->set($cid, $user_weights, Cache::PERMANENT, ['user_list']);
      }
      $this->calculatedData['user_weights'] = $user_weights;
    }
    return $this->calculatedData['user_weights'];
  }

  public function getUserGrade(string $uid, ?\DateTime $date = NULL): ?int {
    $grade_diff = $this->getUserRelativeGrade($date);
    $cid = 'ssr_user_grade_' . $uid . ':' . $grade_diff;

    $grades_map = [];
    if (isset($this->calculatedData[$cid])) {
      $grades_map = $this->calculatedData[$cid];
    }
    else {
      // Resolve grades map.
      $supported_grades = SchoolGradeHelper::getSchoolGradesMap();

      $results = $this->connection->select('user__field_grade', 'g')
        ->fields('g', ['entity_id', 'field_grade_value'])
        ->execute();
      foreach ($results as $result) {
        $grade = $result->field_grade_value;
        if ($grade !== NULL) {
          $grade = (int) $grade;
          $grade += $grade_diff;
          if (isset($supported_grades[$grade])) {
            $grades_map[$result->entity_id] = $grade;
          }
        }
      }
    }

    return $grades_map[$uid] ?? NULL;
  }


  public function getUserSchoolGradeAndType(string $uid): array {
    $grade = $this->getUserGrade($uid);
    $school_type_grade = NULL;

    if ($grade === NULL) {
      // Default school type.
      $default_school_type = 'AU';
      $school_types = SchoolTypeHelper::getSchoolTypes();
      if (!empty($school_types)) {
        $default_school_type = array_pop($school_types);
      }

      return [NULL, $default_school_type];
    }

    $school_type_grade = $grade % 100;
    $school_type = SchoolGradeHelper::getSchoolTypeByGrade($grade) ?? 'AU';

    return [$school_type_grade, $school_type];
  }

  public function getUserRelativeGrade(?\DateTime $date = NULL): int {
    $grade_diff = 0;
    if ($date) {
      // Adjust new year to be set in the middle of july.
      $grade_year_now = date('Y', \strtotime('-182 days'));
      $grade_year_request = date('Y', \strtotime('-182 days', $date->getTimestamp()));
      $grade_diff = $grade_year_request - $grade_year_now;
    }

    return $grade_diff;
  }

  protected function getAdultMap(): array {
    $cid = 'adult_user_map';

    if (is_array($this->calculatedData[$cid] ?? NULL)) {
      return $this->calculatedData[$cid];
    }

    $adult_map = [];

    $adult_roles = ['caregiver', 'teacher', 'administrator', 'principle', 'super_admin', 'budget_administrator', 'budget_reviewer'];
    $adult_birth_date = new \DateTime();
    $adult_birth_date->setTimestamp(strtotime('-18 years'));
    $adult_birth_date->setTime(23, 59, 59);

    $query = $this->entityTypeManager->getStorage('user')->getQuery()
      ->accessCheck(FALSE);

    $or_condition = $query->orConditionGroup();
    $or_condition->condition('roles', $adult_roles, 'IN');
    $or_condition->condition('field_birth_date', $adult_birth_date->getTimestamp(), '<=');

    $adult_uids = $query
      ->condition($or_condition)
      ->execute();

    foreach ($adult_uids as $adult_uid) {
      $adult_map[$adult_uid] = $adult_uid;
    }

    $this->calculatedData[$cid] = $adult_map;
    return $adult_map;
  }

  public function isAdult(string $uid): bool {
    $adult_map = $this->getAdultMap();
    return array_key_exists($uid, $adult_map) ? $adult_map[$uid] : FALSE;
  }

  public function getAdultUids(): array {
    return array_values($this->getAdultMap());
  }

  public function caregiversHasAccess(string $uid): bool {
    $cid = 'caregivers_has_access_map';

    if (is_array($this->calculatedData[$cid] ?? NULL)) {
      $caregivers_has_access_map = $this->calculatedData[$cid];
    }
    else {
      $caregivers_has_access_map = [];

      $adult_birth_date = new \DateTime();
      $adult_birth_date->setTimestamp(strtotime('-18 years'));
      $adult_birth_date->setTime(23, 59, 59);

      $query = $this->entityTypeManager->getStorage('user')->getQuery()
        ->accessCheck(FALSE)
        ->condition('roles', 'student');

      $or_condition = $query->orConditionGroup();

      $or_condition->condition('field_birth_date', NULL);
      $or_condition->notExists('field_birth_date');
      $or_condition->condition('field_birth_date', $adult_birth_date->getTimestamp(), '>');

      $adult_allowed_caregiver_condition = $query->andConditionGroup();
      $adult_allowed_caregiver_condition->condition('field_birth_date', $adult_birth_date->getTimestamp(), '<=');
      $adult_allowed_caregiver_condition->condition('field_adult_student_settings', 'caregiver_continued_access');

      $or_condition->condition($adult_allowed_caregiver_condition);

      $query->condition($or_condition);
      $caregiver_access_uids = $query->execute();

      foreach ($caregiver_access_uids as $caregiver_access_uid) {
        $caregivers_has_access_map[$caregiver_access_uid] = TRUE;
      }

      $this->calculatedData[$cid] = $caregivers_has_access_map;
    }

    return array_key_exists($uid, $caregivers_has_access_map) ? $caregivers_has_access_map[$uid] : FALSE;
  }

}
