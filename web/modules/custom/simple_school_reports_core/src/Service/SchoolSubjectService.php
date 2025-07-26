<?php

namespace Drupal\simple_school_reports_core\Service;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use Drupal\simple_school_reports_core\Form\ResetInvalidAbsenceMultipleForm;
use Drupal\simple_school_reports_core\SchoolTypeHelper;

/**
 * Class SchoolSubjectService
 */
class SchoolSubjectService implements SchoolSubjectServiceInterface {

  protected array $lookup = [];

  /**
   * SchoolSubjectService constructor.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Connection $connection,
    protected CacheBackendInterface $cache
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getSchoolSubjectOptionList(?array $school_types_filter = NULL, bool $include_unpublished = FALSE): array {
    $cid = 'ssr_school_subject_options' . ($include_unpublished ? '1' : '0');
    if ($school_types_filter) {
      $cid .= ':' . implode(':', $school_types_filter);
    }

    $cache = $this->cache->get($cid);
    if ($cache) {
      return $cache->data;
    }

    $subject_options = [];

    $school_types = [];
    if (empty($school_types_filter)) {
      $school_types = array_keys(SchoolTypeHelper::getSchoolTypesVersioned());
    }
    else {
      foreach ($school_types_filter as $school_type) {
        $school_types = array_merge($school_types, SchoolTypeHelper::getSchoolTypeVersions($school_type));
      }
      $school_types = array_unique($school_types);
    }
    if (empty($school_types)) {
      $this->cache->set($cid, $subject_options, Cache::PERMANENT, ['taxonomy_term_list:school_subject']);
      return $subject_options;
    }

    $vid = 'school_subject';
    $query = $this->connection->select('taxonomy_term_field_data', 't');
    $query->leftJoin('taxonomy_term__field_language_code', 'lc', 'lc.entity_id = t.tid');
    $query->leftJoin('taxonomy_term__field_subject_specify', 'ss', 'ss.entity_id = t.tid');
    $query->leftJoin('taxonomy_term__field_school_type_versioned', 'v', 'v.entity_id = t.tid');
    $query->condition('t.vid', $vid);
    $query->condition('v.field_school_type_versioned_value', $school_types, 'IN');
    if (!$include_unpublished) {
      $query->condition('t.status', 1);
    }

    $query->fields('t', ['tid', 'name']);
    $query->fields('lc', ['field_language_code_value']);
    $query->fields('ss', ['field_subject_specify_value']);
    $query->fields('v', ['field_school_type_versioned_value']);

    $query->orderBy('v.field_school_type_versioned_value');
    $query->orderBy('t.name');

    $results = $query->execute();

    $school_type_map = SchoolTypeHelper::getSchoolTypesVersioned();

    foreach ($results as $result) {
      if (empty($result->tid) || empty($result->name)) {
        continue;
      }

      $subject_options[$result->tid] = $result->name;
      if (!empty($result->field_language_code_value)) {
        $subject_options[$result->tid] .= ' (' . $result->field_language_code_value . ')';
      }
      if (!empty($result->field_subject_specify_value)) {
        $subject_options[$result->tid] .= ' ' . $result->field_subject_specify_value;
      }

      $school_type = $result->field_school_type_versioned_value;
      if ($school_type && isset($school_type_map[$result->field_school_type_versioned_value])) {
        $subject_options[$result->tid] .= ' | ' . $school_type_map[$result->field_school_type_versioned_value];
      }
    }

    $this->cache->set($cid, $subject_options, Cache::PERMANENT, ['taxonomy_term_list:school_subject']);
    return $subject_options;
  }

  public function getSubjectShortNames(): array {
    $cid = 'short_name_map';
    if (isset($this->lookup[$cid]) && is_array($this->lookup[$cid])) {
      return $this->lookup[$cid];
    }

    $map = [];

    $vid = 'school_subject';

    $connection = \Drupal::database();

    $query = $connection->select('taxonomy_term_field_data', 't');
    $query->leftJoin('taxonomy_term__field_subject_code_new', 'sc', 'sc.entity_id = t.tid');
    $query->leftJoin('taxonomy_term__field_language_code', 'lc', 'lc.entity_id = t.tid');
    $query->condition('t.vid', $vid);
    $query->fields('t', ['tid', 'name']);
    $query->fields('sc', ['field_subject_code_new_value']);
    $query->fields('lc', ['field_language_code_value']);
    $results = $query->execute();

    foreach ($results as $result) {
      $short_name = NULL;
      $subject_id = $result->tid;
      if (!$subject_id) {
        continue;
      }
      $subject_code = $result->field_subject_code_new_value;

      if ($subject_code && str_starts_with($subject_code, 'C')) {
        // Remove 'C' prefix.
        $subject_code = mb_substr($subject_code, 1);
      }

      if ($subject_code) {
        $short_name = $subject_code;
        if ($language_code = $result->field_language_code_value) {
          $short_name .= ':' . $language_code;
        }
      }

      if (!$short_name) {
        $subject_name = $result->name ?? '';
        // Explode nameparts by ' ' or '/'.
        $name_parts = preg_split('/[ \/]/', $subject_name);
        if (!$name_parts || count($name_parts) === 1) {
          $name_parts = [$subject_name];
        }

        if (count($name_parts) > 1) {
          $short_name = '';
          foreach ($name_parts as $name_part) {
            $short_name .= mb_substr($name_part, 0, 1);
            if (mb_strlen($short_name) >= 3) {
              break;
            }
          }
        }
        else {
          $short_name = mb_substr($name_parts[0], 0, 2);
        }
        $short_name = mb_strtoupper($short_name);
      }

      $map[$subject_id] = $short_name;
    }

    $this->lookup[$cid] = $map;
    return $map;
  }

  public function getSubjectShortName(?string $subject_tid): string {
    if (!$subject_tid) {
      return 'n/a';
    }
    return $this->getSubjectShortNames()[$subject_tid] ?? 'n/a';
  }
}
