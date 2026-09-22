<?php

namespace Drupal\simple_school_reports_child_care_support\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Annotation\Action;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\simple_school_reports_child_care_support\Service\ChildCareCheckInServiceInterface;
use Drupal\simple_school_reports_child_care_support\Service\ChildCareServiceInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Check out students.
 *
 * @Action(
 *   id = "ssr_bulk_check_out_students",
 *   label = @Translation("Bulk check out students (fth)"),
 *   type = "user",
 *   confirm_form_route_name = "simple_school_reports_child_care_fth_check_in.check_out_students"
 * )
 */
class BulkCheckOutStudentsFth extends ActionBase implements ContainerFactoryPluginInterface {

  public function __construct(array
  $configuration,
    $plugin_id,
    $plugin_definition,
    protected PrivateTempStoreFactory $tempStoreFactory,
    protected AccountInterface $currentUser,
    protected ChildCareServiceInterface $childCareService,
    protected ChildCareCheckInServiceInterface $childCareCheckInService,
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
      $container->get('tempstore.private'),
      $container->get('current_user'),
      $container->get('simple_school_reports_child_care_support.child_care'),
      $container->get('simple_school_reports_child_care_support.child_care_check_in'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities) {
    $ids = array_map(fn($entity) => $entity?->id(), $entities);
    $ids = array_filter($ids);
    $this->tempStoreFactory->get('child_care_check_out_students')->set($this->currentUser->id(), $ids);

    $date = $this->childCareService->getDateFromRequest();
    $this->tempStoreFactory->get('child_care_check_out_date')->set($this->currentUser->id(), $date?->format('Y-m-d'));

  }

  /**
   * {@inheritdoc}
   */
  public function execute($object = NULL) {
    $this->executeMultiple([$object]);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {

    $user = $object;
    if (!($user instanceof UserInterface)) {
      return $return_as_object ? AccessResult::forbidden() : FALSE;
    }

    $date = $this->childCareService->getDateFromRequest();

    $cache = new CacheableMetadata();
    if ($return_as_object) {
      $cache = $this->childCareCheckInService->getCacheableMetadata($date);
    }

    $student_access = AccessResult::allowedIf($date && $this->childCareCheckInService->allowStudentCheckOut($user->id(), $date))
      ->addCacheableDependency($user)
      ->addCacheableDependency($cache);

    return $return_as_object
      ? $student_access
      : $student_access->isAllowed();
  }

}
