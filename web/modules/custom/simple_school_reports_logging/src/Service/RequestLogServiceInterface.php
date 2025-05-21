<?php

namespace Drupal\simple_school_reports_logging\Service;

use Symfony\Component\HttpFoundation\Request;

/**
 * Provides an interface defining RequestLogService.
 */
interface RequestLogServiceInterface {

  public function addRequestLogItem(Request $request);

  public function clearRequestLogItems();

  public function getRequestLogMessage(bool $compressed = TRUE, bool $keep_local_log = FALSE): ?string;

  public function decompressRequestLogMessage(string $compressed_message): string;
}
