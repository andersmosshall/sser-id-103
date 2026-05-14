<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_child_care_support\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\simple_school_reports_child_care_support\ChildCareTypeListBuilder;
use Drupal\simple_school_reports_child_care_support\Form\ChildCareTypeForm;

/**
 * Defines the Child care type configuration entity.
 */
#[ConfigEntityType(
  id: 'ssr_child_care_type',
  label: new TranslatableMarkup('Child care type'),
  label_collection: new TranslatableMarkup('Child care types'),
  label_singular: new TranslatableMarkup('child care type'),
  label_plural: new TranslatableMarkup('child cares types'),
  config_prefix: 'ssr_child_care_type',
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
    'uuid' => 'uuid',
  ],
  handlers: [
    'list_builder' => ChildCareTypeListBuilder::class,
    'route_provider' => [
      'html' => AdminHtmlRouteProvider::class,
    ],
    'form' => [
      'add' => ChildCareTypeForm::class,
      'edit' => ChildCareTypeForm::class,
      'delete' => EntityDeleteForm::class,
    ],
  ],
  links: [
    'add-form' => '/admin/structure/ssr_child_care_types/add',
    'edit-form' => '/admin/structure/ssr_child_care_types/manage/{ssr_child_care_type}',
    'delete-form' => '/admin/structure/ssr_child_care_types/manage/{ssr_child_care_type}/delete',
    'collection' => '/admin/structure/ssr_child_care_types',
  ],
  admin_permission: 'administer ssr_child_care types',
  bundle_of: 'ssr_child_care',
  label_count: [
    'singular' => '@count child care type',
    'plural' => '@count child cares types',
  ],
  config_export: [
    'id',
    'label',
    'uuid',
  ],
)]
final class ChildCareType extends ConfigEntityBundleBase {

  /**
   * The machine name of this child care type.
   */
  protected string $id;

  /**
   * The human-readable name of the child care type.
   */
  protected string $label;

}
