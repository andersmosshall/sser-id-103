<?php

/**
 * @file
 * Definition of Drupal\d8views\Plugin\views\field\DaysLeft
 */

namespace Drupal\simple_school_reports_extension_proxy\Plugin\views\field;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\Core\Url;
use Drupal\simple_school_reports_core\Service\AbsenceStatisticsServiceInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to show progress in consent.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("student_di_book_unbook")
 */
class StudentDIBookUnbook extends FieldPluginBase {

  use RedirectDestinationTrait;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * @var \Drupal\simple_school_reports_core\Service\AbsenceStatisticsServiceInterface
   */
  protected $absenceStatisticsService;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->moduleHandler = $container->get('module_handler');
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
    if (!$this->moduleHandler->moduleExists('simple_school_reports_student_di')) {
      return '';
    }
    $build = [];
    $cache = new CacheableMetadata();

    /** @var \Drupal\simple_school_reports_entities\SsrMeetingInterface|null $meeting */
    $meeting = $this->getEntity($values);

    $uid = $this->routeMatch->getRawParameter('user');
    $cache->addCacheTags(['user:' . $uid]);
    $cache->addCacheContexts(['route', 'user']);

    if (!$meeting || $meeting->bundle() !== 'student_di') {
      $cache->applyTo($build);
      return $build;
    }

    $operations = [];
    $query = $this->getDestinationArray();


    /** @var \Drupal\user\UserInterface|null $booked_student */
    $booked_student = $meeting->get('field_student')->entity;

    if ($booked_student) {
      $uid = $booked_student->id();
      $cache->addCacheableDependency($booked_student);
      $access = $meeting->access('unbook', NULL, TRUE);
      $cache->addCacheableDependency($access);
      if (!$access->isAllowed()) {
        $cache->applyTo($build);
        return $build;
      }

      $operations[] = [
        'title' => $this->t('Change'),
        'weight' => 0,
        'url' => Url::fromRoute('simple_school_reports_student_di.meeting_unbook', ['meeting' => $meeting->id(), 'user' => $uid]),
        'query' => $query,
      ];
    }
    else {
      $meeting->set('field_student', ['target_id' => $uid]);
      $access = $meeting->access('book', NULL, TRUE);
      $cache->addCacheableDependency($access);
      if (!$access->isAllowed()) {
        $cache->applyTo($build);
        return $build;
      }

      $operations[] = [
        'title' => $this->t('Book', [], ['context' => 'verb']),
        'weight' => 0,
        'url' => Url::fromRoute('simple_school_reports_student_di.meeting_book', ['meeting' => $meeting->id(), 'user' => $uid]),
        'query' => $query,
      ];
    }

    if (!empty($operations)) {
      $build['operations'] = [
        '#type' => 'operations',
        '#links' => $operations,
      ];
    }

    $cache->applyTo($build);
    return $build;
  }

}
