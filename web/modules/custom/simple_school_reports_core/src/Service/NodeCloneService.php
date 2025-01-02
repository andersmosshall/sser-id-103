<?php

namespace Drupal\simple_school_reports_core\Service;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\simple_school_reports_core\AbsenceDayHandler;

/**
 * Class NodeCloneService
 *
 * @package Drupal\simple_school_reports_core\Service
 */
class NodeCloneService implements NodeCloneServiceInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;


  /**
   * TermService constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    AccountInterface $current_user
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
  }

  /**
   * @inheritDoc
   */
  public function clone(ContentEntityInterface $original, string $label, array $fields = [], array $reference_fields = []): ContentEntityInterface {
    $bundle = $original->bundle();
    $entity_type = $original->getEntityTypeId();

    $uid = $this->currentUser->id();

    $create_array = [
      'langcode' => 'sv',
    ];
    if ($label) {
      $create_array['title'] = $label;
    }
    if ($bundle) {
      $create_array['type'] = $bundle;
    }
    /** @var ContentEntityInterface $entity */
    $entity = $this->entityTypeManager->getStorage($entity_type)->create($create_array);

    if ($entity->hasField('uid')) {
      $entity->set('uid', $uid);
    }

    foreach ($fields as $field) {
      if ($original->hasField($field)) {
        $value = $original->get($field)->getValue();
        $entity->set($field, $value);
      }
    }

    foreach ($reference_fields as $field => $data) {
      if ($original->hasField($field) && !empty($data['fields'])) {
        $cloned_referenced = [];
        /** @var ContentEntityInterface $original_referenced */
        foreach ($original->get($field)->referencedEntities() as $original_referenced) {
          $referenced_label = $original_referenced->label() ?? '';
          $referenced_reference_fields = $data['reference_fields'] ?? [];

          $referenced_entity =  $this->clone($original_referenced, $referenced_label, $data['fields'], $referenced_reference_fields);
          $cloned_referenced[] = $referenced_entity;
        }
        $entity->set($field, $cloned_referenced);
      }
    }

    $entity->save();
    return $entity;
  }


}
