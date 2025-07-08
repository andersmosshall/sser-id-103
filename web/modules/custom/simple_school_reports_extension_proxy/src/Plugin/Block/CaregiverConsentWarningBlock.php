<?php

namespace Drupal\simple_school_reports_extension_proxy\Plugin\Block;

use Couchbase\RegexpSearchQuery;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\NodeInterface;
use Drupal\simple_school_reports_attendance_analyse\Service\AttendanceAnalyseServiceInterface;
use Drupal\simple_school_reports_core\SchoolGradeHelper;
use Drupal\simple_school_reports_core\Service\TermServiceInterface;
use Drupal\simple_school_reports_core\Service\UserMetaDataServiceInterface;
use Drupal\simple_school_reports_entities\SchoolWeekInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a CaregiverConsentWarningBlock that shows a warning if caregivers is set for adult students that has not given consent for the school to share their data with caregivers.
 *
 * @Block(
 *  id = "caregiver_consent_warning_block",
 *  admin_label = @Translation("Caregiver consent warning"),
 * )
 */
class CaregiverConsentWarningBlock extends BlockBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
          $plugin_id,
          $plugin_definition,
    protected RouteMatchInterface $routeMatch,
    protected UserMetaDataServiceInterface $userMetaDataService,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Connection $connection,
    protected AccountInterface $currentUser,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('simple_school_reports_core.user_meta_data'),
      $container->get('entity_type.manager'),
      $container->get('database'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $cache = new CacheableMetadata();
    $cache->addCacheContexts(['route']);
    $cache->addCacheTags(['node_list:consent', 'user_list:student']);
    $build = [];

    $warnings_per_consent_nodes = [];

    $adult_students = $this->userMetaDataService->getAdultUids();
    if (empty($adult_students)) {
      $cache->applyTo($build);
      return $build;
    }

    $query = $this->connection->select('paragraph__field_students', 's');
    $query->innerJoin('node__field_consent_target_groups', 'n', 'n.field_consent_target_groups_target_id = s.entity_id');
    $query->condition('s.bundle', 'consent_target_caregivers');
    $query->condition('s.field_students_target_id', $adult_students, 'IN');
    $query->fields('n', ['entity_id']);
    $query->fields('s', ['field_students_target_id']);
    $results = $query->execute();

    foreach ($results as $result) {
      $nid = $result->entity_id;
      $uid = $result->field_students_target_id;

      $student = $this->entityTypeManager->getStorage('user')->load($uid);
      if (!$student instanceof UserInterface || !$student->isActive() || !$student->hasRole('student')) {
        continue;
      }

      if (!isset($warnings_per_consent_nodes[$nid])) {
        $warnings_per_consent_nodes[$nid] = [];
      }
      $warnings_per_consent_nodes[$nid][$uid] = $this->t('Consent for caregivers to @student may not be relevant or valid since @student is an adult.', ['@student' => $student->getDisplayName()]);
    }

    $warnings = [];

    if ($this->routeMatch->getRouteName() === 'entity.node.canonical') {
      $nid = $this->routeMatch->getRawParameter('node');
      $warnings = $warnings_per_consent_nodes[$nid] ?? [];
    }
    else {
      foreach ($warnings_per_consent_nodes as $warnings_list) {
        foreach ($warnings_list as $id => $warning) {
          $warnings[$id] = $warning;
        }
      }
    }

    if (empty($warnings)) {
      $cache->applyTo($build);
      return $build;
    }

    $build['warnings'] = [
      '#theme' => 'status_messages',
      '#message_list' => [
        'warning' => array_values($warnings),
      ],
      '#status_headings' => [
        'status' => $this->t('Status message'),
        'error' => $this->t('Error message'),
        'warning' => $this->t('Warning message'),
      ],
    ];

    $cache->applyTo($build);
    return $build;
  }

  public function getCacheTags() {
    return Cache::mergeTags(['node_list:consent', 'user_list:student'], parent::getCacheTags());
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeTags(['route'], parent::getCacheContexts());
  }

  /**
   * {@inheritdoc}
   */
  public function blockAccess(AccountInterface $account) {
    if (!$this->currentUser->hasPermission('school staff permissions')) {
      return AccessResult::forbidden()->addCacheContexts(['route'])->cachePerPermissions();
    }

    $route_name = $this->routeMatch->getRouteName();
    if ($route_name === 'entity.node.canonical') {
      $node = $this->routeMatch->getParameter('node');
      if ($node instanceof NodeInterface && $node->bundle() === 'consent') {
        return AccessResult::allowed()->addCacheContexts(['route'])->cachePerPermissions();
      }
    }

    if ($route_name === 'view.consents_per_user.reminder' || $route_name === 'view.consents_per_user.list') {
      $node_storage = \Drupal::entityTypeManager()->getStorage('node');
      $consent_nodes = $node_storage->loadByProperties(['type' => 'consent']);
      if (!empty($consent_nodes)) {
        return AccessResult::allowed()->addCacheContexts(['route'])->cachePerPermissions();
      }
    }

    return AccessResult::forbidden()->addCacheContexts(['route'])->cachePerPermissions();
  }

}
