<?php

namespace Drupal\simple_school_reports_core\Service;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining NodeCloneService.
 */
interface NodeCloneServiceInterface {

  /**
   * @param \Drupal\Core\Entity\ContentEntityInterface $original
   * @param string $label
   * @param array $fields
   * @param array $reference_fields
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   */
  public function clone(ContentEntityInterface $original, string $label, array $fields = [], array $reference_fields = []): ContentEntityInterface;

}
