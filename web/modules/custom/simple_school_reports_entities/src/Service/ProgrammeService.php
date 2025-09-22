<?php

namespace Drupal\simple_school_reports_entities\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Support methods for programme stuff.
 */
class ProgrammeService implements ProgrammeServiceInterface {
  use StringTranslationTrait;

  private array $lookup = [];

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Connection $database,
    protected StateInterface $state,
    protected QueueFactory $queueFactory,
    protected AccountInterface $currentUser,
    protected MessengerInterface $messenger,
    protected ModuleHandlerInterface $moduleHandler,
  ) {}

  protected function moduleEnabled(): bool {
    return $this->moduleHandler->moduleExists('simple_school_reports_core_gy');
  }

  /**
   * {@inheritdoc}
   */
  public function getStudentIdsByProgrammeId(string $programme_id): array {
    $cid = 'students_programme_' . $programme_id;

    if (is_array($this->lookup[$cid] ?? NULL)) {
      return $this->lookup[$cid];
    }

    if (!$this->moduleEnabled()) {
      $this->lookup[$cid] = [];
      return [];
    }

    $uids = $this->entityTypeManager->getStorage('user')->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 1)
      ->condition('roles', 'student')
      ->condition('field_programme', $programme_id)
      ->sort('field_grade')
      ->sort('field_first_name')
      ->sort('field_last_name')
      ->execute();

    $this->lookup[$cid] = array_values($uids);
    return $uids;
  }

  /**
   * {@inheritdoc}
   */
  public function getProgrammeIdsByGrade(string $grade): array {
    $cid = 'programmes_grade_' . $grade;
    if (is_array($this->lookup[$cid] ?? NULL)) {
      return $this->lookup[$cid];
    }

    if (!$this->moduleEnabled()) {
      $this->lookup[$cid] = [];
      return [];
    }

    $programme_ids = [];

    $uids = $this->entityTypeManager->getStorage('user')->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 1)
      ->condition('roles', 'student')
      ->condition('field_grade', $grade)
      ->execute();

    if (!empty($uids)) {
      $query = $this->database->select('user__field_programme', 'up');
      $query->innerJoin('ssr_school_programme_field_data', 'c', 'up.field_programme_target_id = c.id');
      $query->condition('up.entity_id', array_values($uids), 'IN');
      $query->condition('c.status', 1);
      $query->fields('c', ['id']);
      $results = $query->execute();

      foreach ($results as $result) {
        $programme_ids[$result->id] = $result->id;
      }
    }

    $this->lookup[$cid] = array_values($programme_ids);
    return $programme_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function getStudentProgrammeId(string $student_id): ?string {
    $cid = 'programmes_map';
    if (is_array($this->lookup[$cid] ?? NULL)) {
      $map = $this->lookup[$cid];
    }
    else {
      $map = [];
      if (!$this->moduleEnabled()) {
        $this->lookup[$cid] = [];
        return NULL;
      }

      $results = $this->database->select('user__field_programme', 'up')
        ->fields('up', ['entity_id', 'field_programme_target_id'])
        ->execute();

      foreach ($results as $result) {
        $map[$result->entity_id] = $result->field_programme_target_id;
      }

      $this->lookup[$cid] = $map;
    }

    return $map[$student_id] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getProgrammeIds(bool $include_inactive = FALSE): array {
    if (!$this->moduleEnabled()) {
      return [];
    }

    $query = $this->entityTypeManager->getStorage('ssr_school_programme')->getQuery()
      ->accessCheck(FALSE);
    if (!$include_inactive) {
      $query->condition('status', 1);
    }
    return array_values($query->execute());
  }
}
