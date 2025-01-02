<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_dnp_support;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of dnp provisioning test settings type entities.
 *
 * @see \Drupal\simple_school_reports_dnp_support\Entity\DnpProvTestSettingsType
 */
final class DnpProvTestSettingsTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['label'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $row['label'] = $entity->label();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    $build = parent::render();

    $build['table']['#empty'] = $this->t(
      'No dnp provisioning test settings types available. <a href=":link">Add dnp provisioning test settings type</a>.',
      [':link' => Url::fromRoute('entity.dnp_prov_test_settings_type.add_form')->toString()],
    );

    return $build;
  }

}
