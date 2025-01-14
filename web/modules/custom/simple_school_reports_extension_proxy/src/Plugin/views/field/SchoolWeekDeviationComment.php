<?php

/**
 * @file
 * Definition of Drupal\d8views\Plugin\views\field\DaysLeft
 */

namespace Drupal\simple_school_reports_extension_proxy\Plugin\views\field;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Form\FormStateInterface;
use Drupal\simple_school_reports_entities\Service\SchoolWeekServiceInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to show school week deviation comment.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("school_week_deviation_comment")
 */
class SchoolWeekDeviationComment extends FieldPluginBase {

  /**
   * @var \Drupal\simple_school_reports_entities\Service\SchoolWeekServiceInterface
   */
  protected $schoolWeekService;

  /**
   * @var \Drupal\simple_school_reports_core\Service\UserMetaDataServiceInterface
   */
  protected $userMetaDataService;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->userMetaDataService = $container->get('simple_school_reports_core.user_meta_data');
    $instance->schoolWeekService = $container->get('simple_school_reports_entities.school_week_service');
    return $instance;
  }

  /**
   * @{inheritdoc}
   */
  public function query() {}

  /**
   * @{inheritdoc}
   */
  public function render(ResultRow $values) {
    $dev_id = $values->id ?? -1;

    $comment = NULL;

    $dev_data = $this->schoolWeekService->getDeviationData($dev_id);
    if (!empty($dev_data)) {
      $first_entry = reset($dev_data);
      $comment_id = $first_entry['comment_id'] ?? NULL;

      if ($comment_id) {
        $comment_map = [
          SchoolWeekServiceInterface::DEVIATION_COMMENT_GRADE => $this->t('School grade'),
          SchoolWeekServiceInterface::DEVIATION_COMMENT_ADAPTED_STUDIES => $this->t('Adapted studies'),
          SchoolWeekServiceInterface::DEVIATION_COMMENT_SCHOOL_WEEK => $this->t('School week'),
          SchoolWeekServiceInterface::DEVIATION_COMMENT_SCHOOL_CLASS => $this->t('Class'),
        ];
        $comment = $comment_map[$comment_id] ?? NULL;
      }
    }

    $build = [];

    $build['value'] = [
      '#type' => 'html_tag',
      '#tag' => 'em',
      '#value' => $comment ?? '',
    ];

    $cache = new CacheableMetadata();
    $cache->addCacheTags(['school_week_list', 'school_week_deviation_list', 'ssr_school_week_per_grade',]);
    $cache->applyTo($build);
    return $build;
  }

}
