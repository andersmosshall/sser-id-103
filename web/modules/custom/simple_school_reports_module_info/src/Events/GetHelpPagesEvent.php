<?php

namespace Drupal\simple_school_reports_module_info\Events;

use Drupal\simple_school_reports_core\Service\ExportUsersServiceInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Get help pages event.
 */
class GetHelpPagesEvent extends Event {

  const EVENT_NAME = 'ssr_get_help_pages';

  protected array $helpPages = [];

  public function __construct() {}

  public function addHelpPageNid(string $module_name, string $help_page_nid) {
    $this->helpPages[$module_name][] = $help_page_nid;
  }

  public function getHelpPageNids(string $module_name): array {
    return $this->helpPages[$module_name] ?? [];
  }

}
