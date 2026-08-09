<?php

/**
 * @file
 * Definition of Drupal\d8views\Plugin\views\field\DaysLeft
 */

namespace Drupal\simple_school_reports_child_care_support\Plugin\views\field;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\simple_school_reports_child_care_support\Service\ChildCareCheckInServiceInterface;
use Drupal\simple_school_reports_child_care_support\Service\ChildCareSchemaServiceInterface;
use Drupal\simple_school_reports_child_care_support\Service\ChildCareServiceInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Field handler to show child care check in overview.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("child_care_check_in_overview")
 */
class ChildCareCheckInOverview extends FieldPluginBase {
  use StringTranslationTrait;

  protected ChildCareCheckInServiceInterface $childCareCheckInService;

  protected ChildCareServiceInterface $childCareService;

  protected Request $request;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->childCareCheckInService = $container->get('simple_school_reports_child_care_support.child_care_check_in');
    $instance->childCareService = $container->get('simple_school_reports_child_care_support.child_care');
    $instance->request = $container->get('request_stack')->getCurrentRequest();
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
    $uid = $values->uid ?? 0;
    $build = [];

    $date = $this->childCareService->getDateFromRequest();
    $groups = $this->childCareService->getChildCareIdsFromRequest();
    $cache = $this->childCareCheckInService->getCacheableMetadata($date);

    if (!$date || empty($groups)) {
      $cache->applyTo($build);
      return $build;
    }


    $build['value'] = $this->childCareCheckInService->buildStudentCheckIns($uid, $date, $groups);

    if (empty($build['value']['segments'])) {
      $build['value'] = [
        '#markup' => '-'
      ];
      $cache->applyTo($build);
      return $build;
    }

    $message = NULL;
    $poi = $this->childCareCheckInService->getPointOfInterest($uid, $date, $groups);
    $date = NULL;

    if ($poi && $poi['type'] === 'check_in') {
      $date = new \DateTime();
      $date->setTimestamp($poi['time']);
      $message = $this->t('Check in is overdue! (Expected @time)', ['@time' => $date->format('H:i')]);
    }

    if ($poi && $poi['type'] === 'check_out') {
      $date = new \DateTime();
      $date->setTimestamp($poi['time'] + 1);
      $message = $this->t('Check out is overdue! (Expected @time)', ['@time' => $date->format('H:i')]);
    }

    if ($message && $date) {
      $build['message'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['child-care-check-in-message'],
          'style' => 'display: none;',
          'data-display-threshold' => $date->getTimestamp() + 600,
        ],
      ];

      $build['message']['message'] = [
        '#theme' => 'status_messages',
        '#message_list' => [
          'error' => [$message],
        ],
      ];
    }

    $cache->applyTo($build);
    return $build;
  }

}
