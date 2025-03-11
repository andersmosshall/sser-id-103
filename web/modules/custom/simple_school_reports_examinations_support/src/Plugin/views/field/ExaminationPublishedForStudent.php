<?php

/**
 * @file
 * Definition of Drupal\d8views\Plugin\views\field\DaysLeft
 */

namespace Drupal\simple_school_reports_examinations_support\Plugin\views\field;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Site\Settings;
use Drupal\simple_school_reports_examinations_support\Service\ExaminationService;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to show examination results.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("examination_result_published_for_student")
 */
class ExaminationPublishedForStudent extends FieldPluginBase {

  /**
   * The module handler.
   */
  protected $moduleHandler;

  /**
   * The examination service.
   */
  protected ExaminationService $examinationService;

  /**
   * The route match.
   */
  protected RouteMatchInterface $routeMatch;

  protected EntityTypeManagerInterface $entityTypeManager;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->moduleHandler = $container->get('module_handler');
    $instance->examinationService = $container->get('simple_school_reports_examinations_support.examination_service');
    $instance->routeMatch = $container->get('current_route_match');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * @{inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * @{inheritdoc}
   */
  public function render(ResultRow $values) {
    $student_uid = $values->uid ?? 0;
    $examination_id = $this->routeMatch->getRawParameter('ssr_examination');

    $examination = $this->entityTypeManager->getStorage('ssr_examination')->load($examination_id);
    $parent_published = !!$examination->get('status')->value;
    $result_published = FALSE;

    $status = $this->examinationService->getExaminationResultValuesForUser($student_uid, TRUE)[$examination_id] ?? 'no_value';
    if ($status === 'no_value') {
      $result_published = FALSE;
    } elseif ($status === ($this->examinationService->getExaminationResultValuesForUser($student_uid)[$examination_id] ?? 'no_value')) {
      $result_published = TRUE;
    }

    if ($status === Settings::get('ssr_abstract_hash_1')) {
      $result_published = FALSE;
    }

    $build = [
      '#markup' => $result_published && $parent_published ? $this->t('Yes') : $this->t('No'),
    ];

    $cache = new CacheableMetadata();
    $cache->addCacheTags([
      'ssr_examination:' . $examination_id,
      'ssr_examination_result_list:e:' . $examination_id,
      'ssr_examination_result_list:u:' . $student_uid,
    ]);
    $cache->applyTo($build);
    return $build;
  }

}
