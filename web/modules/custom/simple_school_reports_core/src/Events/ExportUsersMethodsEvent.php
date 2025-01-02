<?php

namespace Drupal\simple_school_reports_core\Events;

use Drupal\simple_school_reports_core\Service\ExportUsersServiceInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * A code based personalisation option alter event.
 */
class ExportUsersMethodsEvent extends Event {

  public function __construct(
    protected array $exportMethodServiceIds = [],
  ) {}

  /**
   * @return ExportUsersServiceInterface[]
   */
  public function getExportMethodServices(): array {
    $services = [];

    foreach ($this->exportMethodServiceIds as $service_id) {
      $services[$service_id] = \Drupal::service($service_id);
    }

    return $services;
  }

  public function getServiceById(string $service_id): ExportUsersServiceInterface {
    return \Drupal::service($service_id);
  }

  public function addExportMethodService(string $service_id): self {
    $this->exportMethodServiceIds[$service_id] = $service_id;
    return $this;
  }

}
