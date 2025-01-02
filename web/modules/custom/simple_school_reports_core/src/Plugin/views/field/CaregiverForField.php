<?php

/**
 * @file
 * Definition of Drupal\d8views\Plugin\views\field\DaysLeft
 */

namespace Drupal\simple_school_reports_core\Plugin\views\field;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to list what students current user is caregiver for
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("caregiver_for_student")
 */
class CaregiverForField extends FieldPluginBase {

  /**
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * @var \Drupal\simple_school_reports_core\Service\UserMetaDataServiceInterface
   */
  protected $userMetaDataService;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->currentRequest = $container->get('request_stack')->getCurrentRequest();
    $instance->userMetaDataService = $container->get('simple_school_reports_core.user_meta_data');
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

    foreach ($this->userMetaDataService->getCaregiverStudentsData($uid) as $student_uid => $student_data) {
      if (!empty($student_data['link']) && $student_data['link'] instanceof Link) {
        $build[$student_uid] = [
          '#markup' => $student_data['link']->toString(),
          '#prefix' => '<div>',
          '#suffix' => '</div>',
        ];
      }
    }

    $cache = new CacheableMetadata();
    $cache->addCacheTags(['user_list:caregiver']);
    $cache->addCacheContexts(['route']);
    $cache->applyTo($build);
    return $build;
  }

}
