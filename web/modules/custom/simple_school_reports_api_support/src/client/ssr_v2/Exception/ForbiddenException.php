<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Exception;

class ForbiddenException extends \RuntimeException implements ClientException
{
    public function __construct(string $message)
    {
        parent::__construct($message, 403);
    }
}