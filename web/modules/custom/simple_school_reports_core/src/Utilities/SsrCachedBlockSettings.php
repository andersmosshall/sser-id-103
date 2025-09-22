<?php

namespace Drupal\simple_school_reports_core\Utilities;

use Drupal\Core\StringTranslation\TranslatableMarkup;

class SsrCachedBlockSettings {
  public function __construct(
    public string $id,
    public string|TranslatableMarkup|null $label = NULL,
    public string $type = 'block',
  ) {}
}

