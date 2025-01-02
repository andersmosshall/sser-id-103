<?php

namespace Drupal\simple_school_reports_dnp_provisioning\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\simple_school_reports_core\SchoolSubjectHelper;
use Drupal\simple_school_reports_dnp_support\DnpProvSettingsInterface;

/**
 * Support methods for DNP provisioning related stuff.
 */
class DnpProvisioningService implements DnpProvisioningServiceInterface {
  private array $lookup = [];

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getDnpProvisioningSettings(): ?DnpProvSettingsInterface {
    $cid = 'dnp_provisioning_settings_id';
    $id = NULL;
    if (array_key_exists($cid, $this->lookup)) {
      $id = $this->lookup[$cid];
    }

    if (!$id) {
      $id = current($this->entityTypeManager
        ->getStorage('dnp_prov_settings')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 1)
        ->sort('id', 'DESC')
        ->range(0, 1)
        ->execute()
      );
      $this->lookup[$cid] = $id;
    }

    return $id
      ? $this->entityTypeManager->getStorage('dnp_prov_settings')->load($id)
      : NULL;
  }



}
