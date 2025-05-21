<?php

namespace Drupal\simple_school_reports_core\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Class SSRVersionService
 */
class SSRVersionService implements SSRVersionServiceInterface {

  protected array $lookup = [];

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected CacheBackendInterface $cache,
    protected ModuleHandlerInterface $moduleHandler,
    protected ModuleExtensionList $moduleExtensionList,
  ) {}

  public function getSsrVersion(): string {
    $cid = 'ssr_version';
    if (!empty($this->lookup[$cid])) {
      return $this->lookup[$cid];
    }

    $cache = $this->cache->get($cid);
    if (!empty($cache)) {
      $this->lookup[$cid] = $cache->data;
      return $cache->data;
    }

    $module_list = $this->moduleHandler->getModuleList();
    $core_version = \Drupal::VERSION ?? '1.0.0';

    $version_list = [];
    foreach ($module_list as $module => $extension) {
      $version = $this->moduleExtensionList->getExtensionInfo($module)['version'] ?? $core_version;
      $version_list[$module] = $version;
    }
    ksort($version_list);

    $version_list_json = json_encode($version_list);
    $version_list_hash = sha1($version_list_json);
    $ssr_version = $core_version . '-' . substr($version_list_hash, 0, 5);

    // Make sure there is a lookup entry for ssr version.
    try {
      $ssr_lookup = $this->entityTypeManager->getStorage('ssr_lookup')->loadByProperties(['identifier' => $version_list_hash, 'type' => 'ssr_version']);
      if (!$ssr_lookup) {
        $ssr_lookup = $this->entityTypeManager->getStorage('ssr_lookup')->create([
          'identifier' => $version_list_hash,
          'type' => 'ssr_version',
          'label' => $ssr_version,
          'meta' => $version_list_json,
        ]);
        $ssr_lookup->save();
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('simple_school_reports_core')->error('Failed to store SSR version @version to ssr lookup: @message', [
        '@version' => $ssr_version,
        '@message' => $e->getMessage(),
      ]);
    }

    $this->cache->set($cid, $ssr_version);
    $this->lookup[$cid] = $ssr_version;

    return $ssr_version;
  }

}
