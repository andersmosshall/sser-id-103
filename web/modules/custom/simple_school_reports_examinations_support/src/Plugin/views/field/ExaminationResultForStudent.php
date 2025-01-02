<?php

/**
 * @file
 * Definition of Drupal\d8views\Plugin\views\field\DaysLeft
 */

namespace Drupal\simple_school_reports_examinations_support\Plugin\views\field;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Extension\ModuleHandlerInterface;
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
 * @ViewsField("examination_result_for_student")
 */
class ExaminationResultForStudent extends FieldPluginBase {

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

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->moduleHandler = $container->get('module_handler');
    $instance->examinationService = $container->get('simple_school_reports_examinations_support.examination_service');
    $instance->routeMatch = $container->get('current_route_match');
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
    $assessment_group_id = $this->routeMatch->getRawParameter('ssr_assessment_group');

    return $this->makeResultBuild($student_uid, $examination_id, $assessment_group_id, TRUE);
  }

  protected function makeResultBuild(string $student_uid, string $examination_id, ?string $assessment_group_id = NULL, bool $suffix_ungrouped = FALSE): array {
    $state_not_completed = Settings::get('ssr_examination_result_not_completed', 'no_value');
    $value = $state_not_completed;
    $in_group = TRUE;
    if ($this->moduleHandler->moduleExists('simple_school_reports_examinations')) {
      $data = $this->examinationService->getExaminationResultValueDataForUser($student_uid, $examination_id) ?? NULL;
      if ($data) {
        $value = $data['value'];
        $in_group = $data['in_group'];
      }
    }

    $label = assessment_group_user_examination_result_state_options()[$value] ?? '';
    if (!$in_group && $suffix_ungrouped) {
      $label .= ' (' . t('Not in assessment group') . ')';
    }
    $build = [];
    $build['value'] = [
      '#markup' => $label,
    ];

    $cache = new CacheableMetadata();
    $cache->addCacheContexts(['route']);
    if ($assessment_group_id) {
      $cache->addCacheTags(['ssr_assessment_group:' . $assessment_group_id]);
    }

    $cache->addCacheTags([
      'ssr_examination:' . $examination_id,
      'ssr_examination_result_list:e:' . $examination_id,
      'ssr_examination_result_list:u:' . $student_uid,
    ]);
    $cache->applyTo($build);
    return $build;
  }

}
