<?php

namespace Drupal\simple_school_reports_core\Cache\Contexts;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CalculatedCacheContextInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\unionen_klubb\Service\Luss;

/**
 * Class MembersCurrentLocalUnionSiteCacheContext
 *
 * @package Drupal\simple_school_reports_core\Cache\Contexts
 */
class CurrentDayCacheContext implements CalculatedCacheContextInterface {

  /**
   * @inheritDoc
   */
  public static function getLabel() {
    return t('Current day');
  }

  /**
   * @inheritDoc
   */
  public function getContext($parameter = NULL) {
    if ($parameter === NULL) {
      $date = new \DateTime();
      return $date->format('Y-m-d');
    }
    else {
      return $parameter;
    }
  }

  /**
   * @inheritDoc
   */
  public function getCacheableMetadata($parameter = NULL) {
    return new CacheableMetadata();
  }

}
