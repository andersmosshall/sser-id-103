<?php

namespace Drupal\simple_school_reports_module_info\Plugin\views\area;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\views\Plugin\views\area\AreaPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Module info links area handler.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsArea("module_info_links_area")
 */
class ModueInfoLinksArea extends AreaPluginBase {

  /**
   * @inheritDoc
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * @inheritDoc
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
    );
  }

  /**
   * @inheritDoc
   */
  public function render($empty = FALSE) {
    if ($empty) {
      return [];
    }

    $results = $this->view?->result ?? [];
    if (empty($results) || !is_array($results)) {
      return [];
    }

    $links = [];

    /** @var \Drupal\views\ResultRow $result */
    foreach ($results as $result) {
      /** @var \Drupal\simple_school_reports_module_info\ModuleInfoInterface | null $module_info */
      $module_info = $result->_entity;
      if ($module_info?->get('module')->value) {
        $links[] = '<li><a href="#' . $module_info->get('module')->value . '">' . $module_info->label() . '</a></li>';
      }
    }

    if (empty($links)) {
      return [];
    }

    $markup = '<b class="module-info-links">' . $this->t('Direct navigate to module info') . '</b><ul>';
    $markup .= implode('', $links);
    $markup .= '</ul>';

    return [
      '#markup' => $markup,
    ];
  }

}
