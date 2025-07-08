<?php

namespace Drupal\simple_school_reports_api_support;

use Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\JaneObjectNormalizer;

/**
 * Wrap the Jane ObjectNormalizer to handle adjustments for SSR API objects.
 *
 * NOTE: Jane ObjectNormalizer seems to have a bug not handling the 'meta'
 * property correctly. This class takes care of that.
 */
class SsrApiObjectNormalizer extends JaneObjectNormalizer {

  public function normalize(mixed $data, ?string $format = NULL, array $context = []): array|string|int|float|bool|\ArrayObject|null {
    $normalized = parent::normalize($data, $format, $context);

    if (is_object($data) && method_exists($data, 'getMeta')) {
      if ($data->isInitialized('meta') && null !== $data->getMeta()) {
        $normalized['meta'] = $this->normalizer->normalize($data->getMeta(), 'json', $context);
      }
    }

    return $normalized;
  }

}
