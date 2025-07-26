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

}
