<?php

namespace Drupal\simple_school_reports_extension_proxy\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\NodeInterface;
use Drupal\simple_school_reports_core\Service\UserMetaDataServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Provides a GradeRoundProgress block.
 *
 * @Block(
 *  id = "grade_age_statistics",
 *  admin_label = @Translation("Student grade age statistics"),
 * )
 */
class StudentGradeAgeStatistics extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\simple_school_reports_core\Service\UserMetaDataServiceInterface
   */
  protected $userMetaData;


  /**
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ModuleHandlerInterface $module_handler,
    RouteMatchInterface $route_match,
    EntityTypeManagerInterface $entity_type_manager,
    UserMetaDataServiceInterface $user_meta_data
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
    $this->userMetaData = $user_meta_data;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('current_route_match'),
      $container->get('entity_type.manager'),
      $container->get('simple_school_reports_core.user_meta_data'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $cache = new CacheableMetadata();
    $cache->addCacheTags(['user_list:student']);
    $cache->addCacheContexts(['route', 'user']);

    $budget_mode = FALSE;

    if ($node = $this->routeMatch->getParameter('node')) {
      if ($node instanceof NodeInterface && $node->bundle() === 'budget') {
        $budget_mode = TRUE;
      }
      else {
        $cache->applyTo($build);
        return $build;
      }
    }

    if ($budget_mode && $node) {
      $data = $this->userMetaData->getAgeGroupsFromBudgetNode($node);
      $cache->setCacheMaxAge($this->userMetaData->getStudentCacheAgeMax(TRUE));
    }
    else {
      $data = $this->userMetaData->getStudentGradesAgeData();
      $cache->setCacheMaxAge($this->userMetaData->getStudentCacheAgeMax());
    }

    $grades = $data['grades'] ?? [];
    $ages = $data['ages'] ?? [];

    if (!empty($grades)) {
      $build['grade_wrapper'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['grade-wrapper'],
        ],
      ];

      $build['grade_wrapper']['label'] = [
        '#type' => 'html_tag',
        '#tag' => 'h6',
        '#value' => $this->t('Students in grade'),
      ];

      $grade_map = simple_school_reports_core_allowed_user_grade();

      foreach ($grades as $grade => $value) {
        if (!$value) {
          continue;
        }
        if (isset($grade_map[$grade])) {
          $label = $grade_map[$grade];
          if ($grade >= 0 && $grade < 30) {
            $label =  $this->t('Gr @grade', ['@grade' => $label]);
          }
        }
        else if ($grade === 'total') {
          $label = $this->t('Sum');
        }

        $build['grade_wrapper'][$grade] = [
          '#type' => 'container',
          'value' => [
            '#markup' => '<strong>' . $label . ':</strong> ' . $value,
          ],
        ];
      }
    }

    if (!empty($ages)) {
      $build['age_wrapper'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['age-wrapper'],
        ],
      ];

      $build['age_wrapper']['label'] = [
        '#type' => 'html_tag',
        '#tag' => 'h6',
        '#value' => $this->t('Student age'),
      ];

      foreach ($ages as $age => $value) {
        if (!$value && ($age == -99 || $age == 'total')) {
          continue;
        }

        if ($age === 'total') {
          $label = $this->t('Sum');
        }
        elseif ($age == -99) {
          $label = $this->t('Unknown age');
        }
        else {
          $label = $age . ' ' . $this->t('year');
        }

        $build['age_wrapper'][$age] = [
          '#type' => 'container',
          'value' => [
            '#markup' => '<strong>' . $label . ':</strong> ' . $value,
          ],
        ];
      }
    }

    if (!$budget_mode) {
      $build['#type'] = 'details';
      $build['#open'] = FALSE;
      $build['#title'] = $this->t('Statistics');
    }

    $cache->applyTo($build);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function blockAccess(AccountInterface $account) {
    if (!$this->moduleHandler->moduleExists('simple_school_reports_budget')) {
      return AccessResult::forbidden();
    }

    $route_name = $this->routeMatch->getRouteName();
    $allowed_routes = ['view.students.students'];

    if ($node = $this->routeMatch->getParameter('node')) {
      if (!($node instanceof NodeInterface && $node->bundle() === 'budget')) {
        return AccessResult::forbidden()->addCacheContexts(['route']);
      }
    }
    elseif (!in_array($route_name, $allowed_routes)) {
      return AccessResult::forbidden()->addCacheContexts(['route']);
    }

    return AccessResult::allowedIfHasPermissions($account, ['school staff permissions', 'budget review', 'administer budget'], 'OR')->addCacheContexts(['route']);
  }

}
