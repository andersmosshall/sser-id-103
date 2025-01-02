<?php

namespace Drupal\simple_school_reports_examinations_support\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\simple_school_reports_examinations_support\AssessmentGroupInterface;
use Drupal\simple_school_reports_examinations_support\Entity\Examination;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Set examination results action.
 *
 * @Action(
 *   id = "simple_school_reports_examinations_support_set_examination_results",
 *   label = @Translation("Set examination results"),
 *   type = "user",
 *   confirm_form_route_name = "simple_school_reports_examinations_support.set_examination_results"
 * )
 */
class SetExaminationResultsAction extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * The tempstore factory.
   */
  protected PrivateTempStoreFactory $tempStoreFactory;

  /**
   * The current user.
   */
  protected AccountInterface $currentUser;

  /**
   * The route match.
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;


  /**
   * Constructs a CancelUser object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   */
  public function __construct(array
      $configuration,
      $plugin_id,
      $plugin_definition,
      PrivateTempStoreFactory $temp_store_factory,
      AccountInterface $current_user,
      RouteMatchInterface $route_match,
      EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->currentUser = $current_user;
    $this->tempStoreFactory = $temp_store_factory;
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
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
      $container->get('current_route_match'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities) {
    $this->tempStoreFactory->get('set_multiple_examinations_results')->set($this->currentUser->id(), $entities);
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
    /** @var \Drupal\user\UserInterface $object */
    $assessment_group_id = $this->routeMatch->getRawParameter('ssr_assessment_group');
    $examination_id = $this->routeMatch->getRawParameter('ssr_examination');

    $assessment_group = $assessment_group_id
      ? $this->entityTypeManager->getStorage('ssr_assessment_group')->load($assessment_group_id)
      : NULL;
    $examination = $examination_id
      ? $this->entityTypeManager->getStorage('ssr_examination')->load($examination_id)
      : NULL;

    $is_student_in_assessment_group = FALSE;
    if ($object->hasRole('student') && $assessment_group instanceof AssessmentGroupInterface) {
      $student_uids = array_column($assessment_group->get('students')->getValue(), 'target_id');
      $is_student_in_assessment_group = in_array($object->id(), $student_uids);
    }

    $examination_assessment_group_id = $examination->get('assessment_group')->target_id;
    $allow_to_handle_examination = $examination_assessment_group_id == $assessment_group_id && !!$assessment_group?->access('handle_all_results', $account);

    $access = AccessResult::allowedIf($is_student_in_assessment_group && $allow_to_handle_examination);
    $access->addCacheableDependency($object);
    if ($examination instanceof Examination) {
      $access->addCacheableDependency($examination);
    }
    if ($assessment_group instanceof AssessmentGroupInterface) {
      $access->addCacheableDependency($assessment_group);
    }

    return $return_as_object ? $access : $access->isAllowed();
  }

}
