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

/**
 * Class SchoolSubjectService
 */
class SchoolSubjectService implements SchoolSubjectServiceInterface {

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
   * SchoolSubjectService constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    CacheBackendInterface $cache
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public function getSchoolSubjectOptionList(bool $include_unpublished = FALSE): array {
    $cid = 'ssr_school_subject_options' . ($include_unpublished ? '1' : '0');

    $cache = $this->cache->get($cid);
    if ($cache) {
      return $cache->data;
    }

    $subject_options = [];

    $properties = ['vid' => 'school_subject'];
    if (!$include_unpublished) {
      $properties['status'] = 1;
    }
    $subjects = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties($properties);
    /** @var \Drupal\taxonomy\TermInterface $subject */
    foreach ($subjects as $subject) {
      $subject_options[$subject->id()] = $subject->getName();
      if ($subject->get('field_language_code')->value) {
        $subject_options[$subject->id()] .= ' (' . $subject->get('field_language_code')->value . ')';
      }
      if ($subject->get('field_subject_specify')->value) {
        $subject_options[$subject->id()] .= ' ' . $subject->get('field_subject_specify')->value;
      }
    }
    $this->cache->set($cid, $subject_options, Cache::PERMANENT, ['taxonomy_term_list:school_subject']);
    return $subject_options;
  }
}
