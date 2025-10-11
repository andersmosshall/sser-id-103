<?php

/**
 * @file
 * Definition of Drupal\d8views\Plugin\views\field\DaysLeft
 */

namespace Drupal\simple_school_reports_grade_support\Plugin\views\field;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Session\AccountInterface;
use Drupal\simple_school_reports_core\Service\AbsenceStatisticsServiceInterface;
use Drupal\simple_school_reports_grade_support\GradeRegistrationRoundInterface;
use Drupal\simple_school_reports_grade_support\GradeSigningInterface;
use Drupal\simple_school_reports_grade_support\Service\GradableCourseServiceInterface;
use Drupal\views\Annotation\ViewsField;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to show grade registration round status per user.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("ssr_grade_reg_round_status_per_user")
 */
class GradeRegistrationRoundStatusPerUser extends FieldPluginBase {

  protected GradableCourseServiceInterface $gradableCourseService;
  protected AccountInterface $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->gradableCourseService = $container->get('simple_school_reports_grade_support.gradable_course');
    $instance->currentUser = $container->get('current_user');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $cache = new CacheableMetadata();
    $cache->addCacheContexts(['user']);
    $cache->addCacheTags(['ssr_grade_reg_course_list']);
    $build = [];
    $grade_reg_round = $values->_entity;
    if (!$grade_reg_round || !$grade_reg_round instanceof GradeRegistrationRoundInterface) {
      $cache->applyTo($build);
      return $build;
    }
    $cache->addCacheableDependency($grade_reg_round);

    $percent = $this->gradableCourseService->getGradeRoundStatus($grade_reg_round->id(), $this->currentUser);

    $build['status'] = [
      '#plain_text' => floor($percent) . '%',
    ];
    $cache->applyTo($build);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // This function exists to override parent query function.
    // Do nothing.
  }

}
