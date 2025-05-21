<?php

namespace Drupal\simple_school_reports_extra_adaptations\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Service for handling extra adaptations.
 */
class ExtraAdaptationsService implements ExtraAdaptationsServiceInterface {

  /**
   * @var array
   */
  protected array $lookup = [];

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getExtraAdaptationSubjectMap(): array {
    $cid = 'extra_adaptation_subject_map';
    if (is_array($this->lookup[$cid] ?? NULL)) {
      return $this->lookup[$cid];
    }
    $map = [];

    /** @var \Drupal\taxonomy\TermStorage $term_storage */
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');

    $extra_adaptation_term_ids = $term_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('vid', 'extra_adaptations')
      ->execute();

    $subject_ids = $term_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('vid', 'school_subject')
      ->execute();

    foreach ($extra_adaptation_term_ids as $extra_adaptation_term_id) {
      $map[$extra_adaptation_term_id] = array_values($subject_ids);
    }

    $this->lookup[$cid] = $map;
    return $map;
  }

}
