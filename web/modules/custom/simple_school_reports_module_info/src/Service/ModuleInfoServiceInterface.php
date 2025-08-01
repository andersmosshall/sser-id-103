<?php

namespace Drupal\simple_school_reports_module_info\Service;

/**
 * Provides an interface defining ModuleInfoService.
 */
interface ModuleInfoServiceInterface {

  const CORE_PRICE = 8700;
  const CORE_ANNUAL_FEE = 3300;
  const CORE_BIG_ANNUAL_FEE = 3900;
  const MODULE_PRICE = 4900;
  const MODULE_ANNUAL_FEE = 500;
  const MINI_MODULE_ANNUAL_FEE = 250;
  const MINI_MODULE_PRICE = 1900;

  public function syncModuleInfo(bool $force = FALSE): bool;

  public function getModules(?string $module_type = NULL): array;

  public function getModuleType(string $module_name): ?string;

  public function isSsrModule(string $module_name, ?string $module_type = NULL): bool;

}
