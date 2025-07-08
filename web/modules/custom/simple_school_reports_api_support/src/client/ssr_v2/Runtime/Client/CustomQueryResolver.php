<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Runtime\Client;

use Symfony\Component\OptionsResolver\Options;
interface CustomQueryResolver
{
    public function __invoke(Options $options, $value);
}