<?php

namespace Drupal\simple_school_reports_entities\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\simple_school_reports_entities\SchoolWeekInterface;
use Drupal\simple_school_reports_entities\Service\SchoolWeekServiceInterface;
use Drupal\time_field\Time;
use Drupal\user\EntityOwnerTrait;
use Drupal\views\Views;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat\Wizard\DateTime;

/**
 * Defines the school week entity class.
 *
 * @ContentEntityType(
 *   id = "school_week",
 *   label = @Translation("School Week"),
 *   label_collection = @Translation("School Weeks"),
 *   label_singular = @Translation("school week"),
 *   label_plural = @Translation("school weeks"),
 *   label_count = @PluralTranslation(
 *     singular = "@count school weeks",
 *     plural = "@count school weeks",
 *   ),
 *   handlers = {
 *     "list_builder" =
 *   "Drupal\simple_school_reports_entities\SchoolWeekListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" =
 *   "Drupal\simple_school_reports_entities\SchoolWeekAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\simple_school_reports_entities\Form\SchoolWeekForm",
 *       "edit" = "Drupal\simple_school_reports_entities\Form\SchoolWeekForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "school_week",
 *   admin_permission = "administer school week",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/school-week",
 *     "add-form" = "/school-week/add",
 *     "canonical" = "/school-week/{school_week}",
 *     "edit-form" = "/school-week/{school_week}/edit",
 *     "delete-form" = "/school-week/{school_week}/delete",
 *   },
 *   field_ui_base_route = "entity.school_week.settings",
 * )
 */
class SchoolWeek extends ContentEntityBase implements SchoolWeekInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;
  use StringTranslationTrait;

  protected array $lookup = [];

  protected SchoolWeekInterface|null $parentSchoolWeek = NULL;

  protected SchoolWeekServiceInterface|null $schoolWeekService = NULL;

  protected function getSchoolWeekService(): SchoolWeekServiceInterface {
    if (!$this->schoolWeekService) {
      $this->schoolWeekService = \Drupal::service('simple_school_reports_entities.school_week_service');
    }
    return $this->schoolWeekService;
  }

  /**
   * {@inheritdoc}
   */
  public function getType(): string {
    return $this->get('school_week_type')->value ?? 'default';
  }

  /**
   * {@inheritdoc}
   */
  public function isStudentSchema(): bool {
    return $this->getType() === 'student_schema';
  }

  /**
   * {@inheritdoc}
   */
  public function calculateFromSchema(): bool {
    return (bool) $this->get('calculate_from_schema')->value;
  }

  /**
   * @param \Drupal\simple_school_reports_entities\SchoolWeekInterface $school_week
   *
   * @return self
   */
  public function setParentSchoolWeek(SchoolWeekInterface|null $school_week): self {
    if ($this->parentSchoolWeek && $this->parentSchoolWeek->id() === $school_week->id()) {
      return $this;
    }

    if ($this->isStudentSchema()) {
      for ($day_index = 1; $day_index <= 7; $day_index++) {
        $this->calculateLengthFromLessons($day_index);
      }
    }

    $this->parentSchoolWeek = $school_week;
    return $this;
  }

  /**
   * @return \Drupal\simple_school_reports_entities\SchoolWeekInterface|null
   */
  public function getParentSchoolWeek(): ?SchoolWeekInterface {
    return $this->parentSchoolWeek;
  }

  protected function makeSchoolWeekLessonFromSchemaEntryDataItem(array $schema_entry_data_item, string $day_index): ?array {
    if ($schema_entry_data_item['week_day'] !== $day_index) {
      return NULL;
    }

    $lesson_start = $schema_entry_data_item['from'];
    $lesson_end = $schema_entry_data_item['to'];

    if ($lesson_start === NULL || $lesson_end === NULL) {
      return NULL;
    }

    if ($lesson_start > $lesson_end) {
      $t = $lesson_start;
      $lesson_start = $lesson_end;
      $lesson_end = $t;
    }

    $length = $lesson_end - $lesson_start;
    $length = floor($length / $schema_entry_data_item['periodicity_week']);

    if ($schema_entry_data_item['subject'] === 'CBT') {
      $length = 0;
    }

    $base_time = new \DateTime('2001-01-0' . $day_index . ' 00:00:00');
    return [
      'id' => $schema_entry_data_item['id'],
      'from' => $base_time->getTimestamp() + $lesson_start,
      'to' => $base_time->getTimestamp() + $lesson_end,
      'type' => 'student_schema',
      'subject' => $schema_entry_data_item['subject'] ?? 'n/a',
      'diverged_periodicity' => $schema_entry_data_item['periodicity_week'] !== 1,
      'diverged_student_list' => $schema_entry_data_item['sub_group_id'] !== 'default',
      'length' => $length,
      'attended' => $length,
      'reported_absence' => 0,
      'leave_absence' => 0,
      'valid_absence' => 0,
      'invalid_absence' => 0,
    ];
  }

  protected function sortLessons(array $lessons): array {
    usort($lessons, function ($a, $b) {
      return $a['from'] <=> $b['from'];
    });
    return $lessons;
  }

  protected function getBaseLessons(string $day_index) {
    $cid = 'base_lessons:' . $this->id() . ':' . $day_index;

    if (array_key_exists($cid, $this->lookup)) {
      return $this->lookup[$cid];
    }

    $lessons = [];
    if ($this->isStudentSchema()) {
      $identifier = $this->get('identifier')->value;
      if ($identifier && \Drupal::hasService('simple_school_reports_core.course_service')) {
        /** @var \Drupal\simple_school_reports_core\Service\CourseServiceInterface $course_service */
        $course_service = \Drupal::service('simple_school_reports_core.course_service');
        foreach ($course_service->getStudentSchemaEntryDataByHash($identifier) as $schema_entry_data_item) {
          $lesson = $this->makeSchoolWeekLessonFromSchemaEntryDataItem($schema_entry_data_item, $day_index);
          if ($lesson) {
            $lessons[] = $lesson;
          }
        }
      }

      $this->lookup[$cid] = $lessons;
      return $lessons;
    }

    if ($this->calculateFromSchema() && ssr_use_schema()) {
      if (\Drupal::hasService('simple_school_reports_core.course_service')) {
        /** @var \Drupal\simple_school_reports_core\Service\CourseServiceInterface $course_service */
        $course_service = \Drupal::service('simple_school_reports_core.course_service');
        $school_week_service = $this->getSchoolWeekService();
        $uids = $school_week_service->getStudentIdsRelevantForSchoolWeek($this->id());

        $identifiers = [];
        foreach ($uids as $uid) {
          $identifier = $course_service->getStudentSchemaEntryDataIdentifiersHash($uid);
          if (!$identifier) {
            continue;
          }
          $identifiers[$identifier] = $identifier;
        }

        foreach ($identifiers as $identifier) {
          foreach ($course_service->getStudentSchemaEntryDataByHash($identifier) as $schema_entry_data_item) {
            if (isset($lessons[$schema_entry_data_item['id']])) {
              continue;
            }

            $lesson = $this->makeSchoolWeekLessonFromSchemaEntryDataItem($schema_entry_data_item, $day_index);
            if ($lesson) {
              $lessons[$lesson['id']] = $lesson;
            }
          };
        }
      }

      $lessons = array_values($lessons);
      $lessons = $this->sortLessons($lessons);
      $this->lookup[$cid] = $lessons;
      return $lessons;
    }

    $lessons = $this->getSchoolDayInfo(new \DateTime('2001-01-0' . $day_index))['lessons'] ?? [];
    $this->lookup[$cid] = $lessons;
    return $lessons;
  }

  protected function calculateLengthFromLessons(string $day_index): void {
    $length = 0;
    $from = NULL;
    $to = NULL;
    foreach ($this->getBaseLessons($day_index) as $lesson) {
      if ($lesson['subject'] === 'CBT') {
        continue;
      }

      $length += floor($lesson['length'] / 60);
      if ($from === NULL || $lesson['from'] < $from) {
        $from = $lesson['from'];
      }
      if ($to === NULL || $lesson['to'] > $to) {
        $to = $lesson['to'];
      }
    }

    $from_value = $from
      ? Time::createFromHtml5Format((new \DateTime())->setTimestamp($from)->format('H:i'))
      : NULL;
    $to_value = $to
      ? Time::createFromHtml5Format((new \DateTime())->setTimestamp($to)->format('H:i'))
      : NULL;

    $this->set('length_' . $day_index, $length);
    $this->set('from_' . $day_index, $from_value?->getTimestamp());
    $this->set('to_' . $day_index, $to_value?->getTimestamp());
  }

  protected function getSchoolDayData(string $date_string, string $day_index, bool $include_deviation = TRUE): array {
    $length = $this->get('length_' . $day_index)->value ?? 0;
    $length *= 60;
    $from = $this->get('from_' . $day_index)->value ?? NULL;
    $to = $this->get('to_' . $day_index)->value ?? NULL;

    $map = $include_deviation
      ? $this->getSchoolWeekService()->getSchoolWeekDeviationMap($this)
      : [];
    if (!empty($map[$date_string])) {
      $deviation_data = $map[$date_string];
      if ($deviation_data['no_teaching']) {
        $length = 0;
        $from = NULL;
        $to = NULL;
      }
      if ($deviation_data['from'] && $deviation_data['to']) {
        $length = self::CALCULATE_LENGTH;
        $from = $deviation_data['from'];
        $to = $deviation_data['to'];
      }
    }

    // Sanity check $from and $to.
    if (!$from || !$to) {
      $from = NULL;
      $to = NULL;
    }

    if ($from && $to && $from > $to) {
      $t = $from;
      $from = $to;
      $to = $t;
    }

    if ($from && $to && $to - $from < $length) {
      $from = NULL;
      $to = NULL;
    }

    $base_time = new \DateTime($date_string . ' 00:00:00');

    if (!$from) {
      $from_object = new \DateTime($date_string . ' 12:00:00');
      $from = $from_object->getTimestamp() - ($length / 2) - 60 * 60;
    }
    else {
      $from = $base_time->getTimestamp() + $from;
    }

    if (!$to) {
      $to_object = new \DateTime($date_string . ' 12:00:00');
      $to = $to_object->getTimestamp() + ($length / 2) + 60 * 60;
    }
    else {
      $to = $base_time->getTimestamp() + $to;
    }

    return [
      $length,
      $from,
      $to,
    ];

  }

  protected function getParentIds(): array {
    $ids = [];
    $parent = $this->getParentSchoolWeek();
    while ($parent) {
      if (isset($ids[$parent->id()])) {
        break;
      }
      $ids[$parent->id()] = $parent->id();
      $parent = $parent->getParentSchoolWeek();
    }
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function getSchoolDayInfo(?\DateTimeInterface $date_time = NULL, bool $include_day_lessons = TRUE): array {
    if (!$date_time) {
      $date_time = new \DateTime();
    }
    $day_index = $date_time->format('N');
    $date = $date_time->format('Y-m-d');
    $cid = 'school_day_info:' . $this->id() . ':' . $date;
    $cid .= ':' . implode(':', $this->getParentIds());

    if (array_key_exists($cid, $this->lookup)) {
      return $this->lookup[$cid];
    }

    $is_base_schema = $date_time->format('Y-m') === '2001-01';
    $include_deviation = $include_day_lessons || !$is_base_schema;

    [
      $length,
      $from,
      $to,
    ] = $this->getSchoolDayData($date, $day_index, $include_deviation);

    if ($length === 0) {
      $this->lookup[$cid] = [
        'length' => 0,
        'from' => NULL,
        'to' => NULL,
        'lessons' => [],
      ];
      return $this->lookup[$cid];
    }

    if ($length === self::CALCULATE_LENGTH) {
      [
        $original_length,
        $original_from,
        $original_to,
      ] = $this->getSchoolDayData($date, $day_index, FALSE);
      $original_lessons = $this->makeLessons($original_from, $original_to, $original_length);
      $lessons = [];

      $length = 0;

      foreach ($original_lessons as $lesson) {
        if ($lesson['to'] <= $from || $lesson['from'] >= $to) {
          continue;
        }

        $lesson['from'] = max($lesson['from'], $from);
        $lesson['to'] = min($lesson['to'], $to);
        $lesson['length'] = $lesson['to'] - $lesson['from'];
        $length += $lesson['length'];
        $lessons[] = $lesson;
      }
    }
    else {
      $lessons = $include_day_lessons ? $this->makeLessons($from, $to, $length) : [];
    }

    $lessons = $this->sortLessons($lessons);
    $info = [
      'length' => $length,
      'from' => $from,
      'to' => $to,
      'lessons' => $lessons,
    ];

    $this->lookup[$cid] = $info;
    return $info;
  }

  protected function makeLessons(int $from, int $to, int $length): array {
    if ($length <= 0) {
      return [];
    }

    $length = min($length, $to - $from);
    $break = abs(floor(($to - $from - $length) / 3));
    $lesson_duration = floor($length / 4);

    $lessons = [];

    // Loop through each segment and calculate the start and end timestamps
    for ($i = 0; $i < 4; $i++) {
      $lesson_start = $from + $i * ($lesson_duration + $break);
      $lesson_end = $lesson_start + $lesson_duration;

      $lessons[] = [
        'from' => $lesson_start,
        'to' => $lesson_end,
        'type' => 'dynamic',
        'subject' => 'n/a',
        'length' => $lesson_end - $lesson_start,
        'attended' => $lesson_end - $lesson_start,
        'reported_absence' => 0,
        'leave_absence' => 0,
        'valid_absence' => 0,
        'invalid_absence' => 0,
      ];
    }

    return $lessons;
  }

  /**
   * {@inheritdoc}
   */
  public function toTable(bool $show_lessons = FALSE, bool $show_deviations = TRUE): array {
    if ($this->isStudentSchema() && $this->getParentSchoolWeek() && !$this->getParentSchoolWeek()->calculateFromSchema()) {
      return $this->getParentSchoolWeek()->toTable($show_lessons, $show_deviations);
    }

    $day_map = [
      1 => t('Monday'),
      2 => t('Tuesday'),
      3 => t('Wednesday'),
      4 => t('Thursday'),
      5 => t('Friday'),
      6 => t('Saturday'),
      7 => t('Sunday'),
    ];

    $headers = [];

    foreach ($day_map as $day_index => $day_label) {
      $field = $this->get('length_' . $day_index);
      if ($field->access('view')) {
        $headers[$day_index] = $day_label;
      }
    }


    if (empty($headers)) {
      return [];
    }

    $headers = [0 => ''] + $headers;
    // Make render array with days as headers and rows with from/to and length.
    $table = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => [],
    ];

    $row_length = [];
    $row_from = [];
    $row_to = [];

    foreach ($headers as $day_index => $day_label) {

      if ($day_index === 0) {
        $row_length[0] = $this->t('School day');;
        $row_from[0] = $this->t('Day start');
        $row_to[0] = $this->t('Day end');
        continue;
      }

      if ($this->calculateFromSchema() && ssr_use_schema()) {
        $this->calculateLengthFromLessons($day_index);
      }

      $field = $this->get('length_' . $day_index)->value ?? 0;
      $row_length[$day_index] = number_format($field, 0, ',', ' ') . ' min';

      $from = $this->get('from_' . $day_index)->value ?? NULL;
      if ($from) {
        $time = Time::createFromTimestamp($from);
        $from = $time->format('H:i');
      }
      else {
        $from = '-';
      }
      $row_from[$day_index] = $from;


      $to = $this->get('to_' . $day_index)->value ?? NULL;
      if ($to) {
        $time = Time::createFromTimestamp($to);
        $to = $time->format('H:i');
      }
      else {
        $to = '-';
      }
      $row_to[$day_index] = $to;
    }

    $table['#rows'][] = $row_length;
    $table['#rows'][] = $row_from;
    $table['#rows'][] = $row_to;

    $has_lessons = FALSE;
    $row_lessons = [];

    $show_footnote_1 = FALSE;
    $show_footnote_2 = FALSE;
    $show_footnote_3 = FALSE;

    if ($show_lessons) {
      foreach ($headers as $day_index => $day_label) {
        if ($day_index === 0) {
          $row_lessons[0] = $this->t('Lessons');
          continue;
        }

        $lessons = $this->getBaseLessons($day_index);

        if (empty($lessons)) {
          $row_lessons[$day_index]['data'][] = [
            '#markup' => '-',
          ];
          continue;
        }

        foreach ($lessons as $lesson) {
          $has_lessons = TRUE;
          $time_from = date('H:i', $lesson['from']);
          $time_to = date('H:i', $lesson['to']);

          $suffix = '';
          if ($lesson['subject'] !== 'n/a') {
            $name = $lesson['subject'];

            if ($name === 'CBT') {
              $name = 'BT';
            }

            if (str_contains($name, ':')) {
              $name = explode(':', $name);
              $name = array_pop($name);
            }
            $suffix .= ' ' . $name;
          }

          if ($lesson['type'] === 'dynamic') {
            $suffix .= '<sup>1</sup>';
            $show_footnote_1 = TRUE;
          }

          if (!empty($lesson['diverged_student_list'])) {
            $suffix .= '<sup>2</sup>';
            $show_footnote_2 = TRUE;
          }
          elseif (!empty($lesson['diverged_periodicity'])) {
            $suffix .= '<sup>3</sup>';
            $show_footnote_3 = TRUE;
          }

          $row_lessons[$day_index]['data'][] = [
            '#type' => 'container',
            'value' => [
              '#markup' => $time_from . ' - ' . $time_to . $suffix,
            ],
          ];
        }
      }
    }

    if ($has_lessons) {
      $table['#rows'][] = $row_lessons;

      $table['#suffix'] = '';
      if ($show_footnote_1) {
        $table['#suffix'] .= '<div><sup>1</sup><em>' . $this->t('The lessons that are assumed unless there are attendance reports defining the lesson time and length.') . '</em></div>';
      }
      if ($show_footnote_2) {
        $table['#suffix'] .= '<div><sup>2</sup><em> ' . $this->t('Lesson with diverged student list or periodicity that might not occur every week for all students.') . '</em></div>';
      }
      if ($show_footnote_3) {
        $table['#suffix'] .= '<div><sup>2</sup><em> ' . $this->t('Lesson with diverged periodicity that might not occur every week.') . '</em></div>';
      }
    }

    if (!$show_deviations) {
      return $table;
    }
    $deviation_ids = $this->getSchoolWeekService()->getSchoolWeekDeviationIds($this);

    if (empty($deviation_ids)) {
      return $table;
    }

    $build = [
      'table' => $table,
      'deviations' => [],
    ];

    $display_id = $this->getSchoolWeekService()->getDeviationViewsDisplay();

    // Build view of deviations.
    $deviations_view = Views::getView('deviations_for_school_week');
    $deviations_view->setDisplay($display_id);
    $deviations_view->setArguments([json_encode($deviation_ids)]);
    $deviations_view->preExecute();
    $deviations_view->execute();
    $deviations_view = $deviations_view->buildRenderable($display_id);
    $build['deviations'] = $deviations_view;

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    if (!$this->getOwnerId()) {
      // If no owner has been set explicitly, make the anonymous user the owner.
      $this->setOwnerId(0);
    }

    if (!$this->label()) {
      $this->set('label', 'Skolvecka');
    }

    if (!$this->get('school_week_type')->value) {
      $this->set('school_week_type', 'default');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Label'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Author'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(static::class . '::getDefaultEntityOwner')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the school week was created.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the school week was last edited.'));

    $day_map = [
      1 => t('Monday'),
      2 => t('Tuesday'),
      3 => t('Wednesday'),
      4 => t('Thursday'),
      5 => t('Friday'),
      6 => t('Saturday'),
      7 => t('Sunday'),
    ];
    for ($day_index = 1; $day_index <= 7; $day_index++) {

      $day_label = $day_map[$day_index];

      $fields['length_' . $day_index] = BaseFieldDefinition::create('integer')
        ->setLabel(t('School day length of @day_label', ['@day_label' => $day_label]))
        ->setRequired(TRUE)
        ->setDefaultValue(0)
        ->setSetting('min', 0)
        ->setSetting('max', 1200)
        ->setDescription(t('The length of the school day for @day_label in minutes.', ['@day_label' => $day_label]))
        ->setSetting('unsigned', TRUE)
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayConfigurable('view', TRUE);

      $fields['from_' . $day_index] = BaseFieldDefinition::create('time')
        ->setLabel(t('School day start of @day_label', ['@day_label' => $day_label]))
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayConfigurable('view', TRUE);

      $fields['to_' . $day_index] = BaseFieldDefinition::create('time')
        ->setLabel(t('School day end of @day_label', ['@day_label' => $day_label]))
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayConfigurable('view', TRUE);
    }

    $fields['deviation'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Specific deviation'))
      ->setDescription(t('Specific deviation for this school week only.'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSetting('target_type', 'school_week_deviation')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['school_week_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('School week type'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values_function', 'simple_school_reports_entities_school_week_types')
      ->setDefaultValue('default')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['calculate_from_schema'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Calculate school days from schema'))
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['identifier'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Identifier'))
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
