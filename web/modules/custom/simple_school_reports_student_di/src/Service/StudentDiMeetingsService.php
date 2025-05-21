<?php

namespace Drupal\simple_school_reports_student_di\Service;

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
use Drupal\simple_school_reports_entities\SsrMeetingInterface;
use Drupal\simple_school_reports_maillog\SsrMaillogInterface;

/**
 * Class ConsentsService
 */
class StudentDiMeetingsService implements StudentDiMeetingsServiceInterface {

  /**
   * Group map cache.
   *
   * Structure:
   *   [group_id => [
   *     'group_id' = string,
   *     'locked' = bool,
   *     'locked_caregivers' = bool,
   *     'available_meetings' = [meeting_id => ['id' => meeting_id]]
   *     'booked_meetings' = [student_id => [meeting_id => meeting_id]]
   *   ]]
   *
   *
   * @var array|null
   *   Group map cache.
   */
  protected array|null $groupMap = NULL;

  /**
   * Student group map cache.
   *
   * Structure:
   *   [student_id => [group_id => group_id]]
   *
   * @var array|null
   *   Student group map cache.
   */
  protected array|null $studentGroupMap = NULL;

  /**
   * Meeting data map.
   *
   * Structure:
   *   [meeting_id =>
   *     id
   *     student
   *     attendees
   *   ]
   *
   *
   * @var array|null
   *   The meeting data map.
   */
  protected array|null $meetingDataMap = NULL;

  public function __construct(
    protected Connection $connection,
    protected CacheBackendInterface $cache,
    protected EmailServiceInterface $emailService,
  ) {}

  protected function warmUpCache() {
    if (is_array($this->groupMap) && is_array($this->studentGroupMap) && is_array($this->meetingDataMap)) {
      return;
    }

    $cid = 'ssr_meetings_data';
    $cached = $this->cache->get($cid);
    if ($cached) {
      $data = $cached->data;
    }
    else {
      $data = [];

      $data['group_map'] = [];
      $data['student_group_map'] = [];
      $data['meeting_data_map'] = [];

      $query = $this->connection->select('ssr_meeting_field_data' ,'m');
      $query->innerJoin('node__field_student_groups', 'g', 'g.field_student_groups_target_id = m.node_parent');
      $query->innerJoin('node__field_locked', 'l', 'l.entity_id = g.entity_id');
      $query->innerJoin('node__field_locked_caregivers', 'lc', 'lc.entity_id = g.entity_id');
      $query->leftJoin('ssr_meeting__field_student', 's', 's.entity_id = m.id');
      $query->condition('m.bundle', 'student_di');
      $results = $query
        ->fields('m', ['id', 'node_parent'])
        ->fields('l', ['field_locked_value', 'entity_id'])
        ->fields('lc', ['field_locked_caregivers_value'])
        ->fields('s', ['field_student_target_id'])
        ->execute();

      foreach ($results as $result) {
        $meeting_id = $result->id;
        $group_id = $result->node_parent;
        $round_id = $result->entity_id;
        $locked = (bool) $result->field_locked_value;
        $locked_caregivers = $locked || $result->field_locked_caregivers_value;
        $booked_student_id = $result->field_student_target_id ?? NULL;


        // Setup group map.
        if (!isset($data['group_map'][$group_id])) {
          $data['group_map'][$group_id] = [
            'id' => $group_id,
            'round_id' => $round_id,
            'locked' => $locked,
            'locked_caregivers' => $locked_caregivers,
            'available_meetings' => [],
            'booked_meetings' => [],
            'students' => [],
          ];
        }

        if ($booked_student_id) {
          $data['group_map'][$group_id]['booked_meetings'][$booked_student_id][$meeting_id] = $meeting_id;
        }
        else {
          $data['group_map'][$group_id]['available_meetings'][$meeting_id] = $meeting_id;
        }

        // Setup meeting data map.
        if (!isset($data['meeting_data_map'][$meeting_id])) {
          $data['meeting_data_map'][$meeting_id] = [
            'id' => $meeting_id,
            'group_id' => $group_id,
            'round_id' => $round_id,
            'student' => $booked_student_id,
            'locked' => $locked,
            'locked_caregivers' => $locked_caregivers,
            'attendees' => [],
          ];
        }
      }

      // Resolve attendees to meeting data.
      $meeting_ids = array_keys($data['meeting_data_map']);
      if (!empty($meeting_ids)) {
        $results = $this->connection->select('ssr_meeting__attending' ,'a')
          ->condition('a.entity_id', $meeting_ids, 'IN')
          ->fields('a', ['entity_id', 'attending_target_id'])
          ->execute();

        foreach ($results as $result) {
          $meeting_id = $result->entity_id;
          $attended = $result->attending_target_id;
          if (isset($data['meeting_data_map'][$meeting_id])) {
            $data['meeting_data_map'][$meeting_id]['attendees'][$attended] = $attended;
          }
        }
      }

      // Resolve student group map.
      $group_ids = array_keys($data['group_map']);
      if (!empty($group_ids)) {
        $results = $this->connection->select('node__field_student' ,'s')
          ->condition('s.entity_id', $group_ids, 'IN')
          ->fields('s', ['entity_id', 'field_student_target_id'])
          ->execute();

        foreach ($results as $result) {
          $group_id = $result->entity_id;
          $student_id = $result->field_student_target_id;

          if (!isset($data['student_group_map'][$student_id])) {
            $data['student_group_map'][$student_id] = [];
          }
          $data['student_group_map'][$student_id][$group_id] = $group_id;
          $data['group_map'][$group_id]['students'][$student_id] = $student_id;
        }
      }


      $tags = [
        'ssr_meeting_list:student_di',
        'node_list:student_development_interview',
        'node_list:di_student_group',
      ];
      $this->cache->set($cid, $data, Cache::PERMANENT, $tags);
    }

    $this->groupMap = $data['group_map'] ?? [];
    $this->studentGroupMap = $data['student_group_map'] ?? [];
    $this->meetingDataMap = $data['meeting_data_map'] ?? [];
  }

  public function getStudentGroupIds(string $student_id): array {
    $this->warmUpCache();
    return $this->studentGroupMap[$student_id] ?? [];
  }

  public function getBookedMeetingIds(string $student_id, string $group_id): array {
    $this->warmUpCache();

    if (!empty($this->groupMap[$group_id]['booked_meetings'][$student_id])) {
      return array_keys($this->groupMap[$group_id]['booked_meetings'][$student_id]);
    }

    return [];
  }

  public function getAvailableMeetingIds(string $student_id, string $group_id, bool $check_locked = TRUE, bool $check_locked_caregivers = FALSE): array {
    $this->warmUpCache();

    if (!isset($this->groupMap[$group_id])) {
      return [];
    }

    $group_data = $this->groupMap[$group_id];
    if ($check_locked) {
      if ($group_data['locked']) {
        return [];
      }
    }

    if ($check_locked_caregivers) {
      if ($group_data['locked_caregivers']) {
        return [];
      }
    }

    if (!isset($this->groupMap[$group_id]['students'][$student_id])) {
      return [];
    }

    if (!empty($this->groupMap[$group_id]['available_meetings'])) {
      return array_keys($this->groupMap[$group_id]['available_meetings']);
    }

    return [];

  }

  public function getMeetingData(string $meeting_id): array {
    $this->warmUpCache();
    return $this->meetingDataMap[$meeting_id] ?? [
      'id' => $meeting_id,
      'group_id' => NULL,
      'round_id' => NULL,
      'student' => NULL,
      'locked' => TRUE,
      'locked_caregivers' => TRUE,
      'attendees' => [],
    ];

  }

  public function getMeetingReminderMessage(SsrMeetingInterface $meeting): string {
    $start_time = $meeting->get('from')->value;
    $end_time = $meeting->get('to')->value;

    /** @var \Drupal\user\UserInterface|null $student */
    $student = $meeting->get('field_student')->entity;


    $message = 'Utvecklingssamtal ' . date('Y-m-d \k\l. H:i', $start_time) . ' - ' . date('H:i', $end_time) . PHP_EOL;
    if ($student) {
      $message = 'Utvecklingssamtal med ' . $student->getDisplayName() . ': ' . date('Y-m-d \k\l. H:i', $start_time) . ' - ' . date('H:i', $end_time) . PHP_EOL;
    }

    if ($location = $meeting->get('location')->value) {
      $message .= 'Plats: ' . $location . PHP_EOL;
    }

    if ($link = $meeting->get('meeting_link')->uri) {
      $message .= 'Möteslänk: ' . $link . PHP_EOL;
    }

    $attending = [];
    /** @var \Drupal\user\UserInterface $user */
    foreach ($meeting->get('attending')->referencedEntities() as $user) {
      $attending[] = $user->getDisplayName();
    }

    if (!empty($attending)) {
      $message .= 'Deltagare:' . PHP_EOL;
      $message .= implode(PHP_EOL, $attending);
    }

    return $message;
  }

  protected function sendMeetingInfo(SsrMeetingInterface $meeting, string $subject_prefix, ?string $message = NULL) {
    $student_id = $meeting->get('field_student')->target_id;
    if (!$student_id) {
      return;
    }

    $subject = $subject_prefix;
    $recipients = [];
    $teachers_ids = array_column($meeting->get('field_teachers')->getValue(), 'target_id');
    /** @var \Drupal\user\UserInterface $user */
    foreach ($meeting->get('attending')->referencedEntities() as $user) {
      if ($user->id() == $student_id) {
        $subject = $subject_prefix . ' - ' . $user->getDisplayName();
      }

      if (in_array($user->id(), $teachers_ids)) {
        continue;
      }

      if ($email = $this->emailService->getUserEmail($user)) {
        $recipients[] = $email;
      }
    }

    if (empty($recipients)) {
      return;
    }

    $options = [
      'maillog_mail_type' => SsrMaillogInterface::MAILLOG_TYPE_MEETING_REMINDER,
      'no_reply_to' => TRUE,
    ];

    $message = $message ?? $this->getMeetingReminderMessage($meeting);

    foreach ($recipients as $recipient) {
      try {
        $this->emailService->sendMail($recipient, $subject, $message, $options);
      }
      catch (\Exception $e) {
        // Ignore.
      }
    }
  }

  public function handleMeetingChanged(SsrMeetingInterface $meeting, bool $deleted = FALSE): void {
    $now = (new DrupalDateTime())->getTimestamp() + 60 * 20;
    $start_time = $meeting->get('from')->value;

    // Ignore meetings in the past.
    if ($start_time < $now) {
      return;
    }


    if ($deleted) {
      $subject = 'Utvecklingssamtal avbokat';
      $message = explode(PHP_EOL, $this->getMeetingReminderMessage($meeting))[0];
      $this->sendMeetingInfo($meeting, $subject, $message);
      return;
    }

    if ($meeting->isNew() || !$meeting->original instanceof SsrMeetingInterface) {
      return;
    }

    $new_student = $meeting->get('field_student')->target_id;
    $old_student = $meeting->original->get('field_student')->target_id;
    if ($new_student != $old_student) {
      if ($new_student) {
        $subject = 'Utvecklingssamtal bokat';
        $this->sendMeetingInfo($meeting, $subject);
      }
      if ($old_student) {
        $subject = 'Utvecklingssamtal avbokat';
        $message = explode(PHP_EOL, $this->getMeetingReminderMessage($meeting->original))[0];
        $this->sendMeetingInfo($meeting->original, $subject, $message);
      }
      return;
    }

    $new_start_time = $meeting->get('from')->value;
    $old_start_time = $meeting->original->get('from')->value;

    if ($new_start_time != $old_start_time) {
      $subject = 'Ny tid för utvecklingssamtal';
      $this->sendMeetingInfo($meeting, $subject);
    }
  }

}
