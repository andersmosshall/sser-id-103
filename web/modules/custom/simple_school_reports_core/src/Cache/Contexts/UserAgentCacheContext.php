<?php

namespace Drupal\simple_school_reports_core\Cache\Contexts;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CalculatedCacheContextInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class UserAgentCacheContext
 */
class UserAgentCacheContext implements CalculatedCacheContextInterface {

  /**
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   */
  public function __construct(
    protected RequestStack $requestStack
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('User Agent');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext($parameter = NULL) {
    $request = $this->requestStack->getCurrentRequest();
    if (!$request) {
      return 'unknown';
    }

    $user_agent = $request->headers->get('User-Agent', '');

    $is_ios = (bool) preg_match('/(iPhone|iPod|iPad)/i', $user_agent);
    if ($parameter === 'ios') {
      return $is_ios ? 'ios' : 'not_ios';
    }
    $all = [
      $is_ios ? 'ios' : 'not_ios',
    ];

    return implode(':', $all);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($parameter = NULL) {
    return new CacheableMetadata();
  }

}
