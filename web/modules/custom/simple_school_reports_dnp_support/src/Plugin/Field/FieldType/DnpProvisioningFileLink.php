<?php

namespace Drupal\simple_school_reports_dnp_support\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\Core\Url;

/**
 * Computed field for the DNP Provisioning File Link.
 */
class DnpProvisioningFileLink extends FieldItemList {
  use ComputedItemListTrait;

  /**
  * {@inheritdoc}
  */
  public function computeValue() {
    $entity = $this->getEntity();
    if (!$entity->id()) {
      return;
    }

    try {
      $url = Url::fromRoute('simple_school_reports_dnp_provisioning.download_xlsx', ['dnp_provisioning' => $entity->id()], ['absolute' => TRUE]);
      $this->list[0] = $this->createItem(0, [
        'uri' => $url->toString(TRUE)->getGeneratedUrl(),
        'title' => 'Ladda ner fil',
      ]);
    }
    catch (\Exception $e) {
      // Do nothing.
      return;
    }

  }

}
