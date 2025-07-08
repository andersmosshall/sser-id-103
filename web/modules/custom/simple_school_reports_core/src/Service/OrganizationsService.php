<?php

namespace Drupal\simple_school_reports_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\State\StateInterface;
use Drupal\simple_school_reports_core\SchoolTypeHelper;
use Drupal\simple_school_reports_entities\SSROrganizationInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class OrganizationsService
 */
class OrganizationsService implements OrganizationsServiceInterface, EventSubscriberInterface {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected StateInterface $state,
    protected LoggerChannelFactoryInterface $loggerChannelFactory,
  ) {}

  public function getOrganization(string $organization_type): ?SSROrganizationInterface {
    $organization_storage = $this->entityTypeManager->getStorage('ssr_organization');
    $organizations = $this->state->get('simple_school_reports_core.organizations', []);
    if (!empty($organizations[$organization_type])) {
      return $organization_storage->load($organizations[$organization_type]);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function assertOrganizations(): bool {
    $organization_storage = $this->entityTypeManager->getStorage('ssr_organization');
    $organizations = $this->state->get('simple_school_reports_core.organizations', []);
    $organizations += [
      'school_organization' => NULL,
      'school_unit' => NULL,
    ];

    $has_changes = FALSE;
    $school_organizer = $organizations['school_organization']
      ? $organization_storage->load($organizations['school_organization'])
      : NULL;
    if (!$school_organizer) {
      $school_organizer = $organization_storage->create([
        'label' => Settings::get('ssr_school_organiser', NULL),
        'langcode' => 'sv',
      ]);
      $has_changes = TRUE;
    }
    /** @var \Drupal\simple_school_reports_entities\SSROrganizationInterface $school_organizer */
    $school_organizer->set('organization_type', 'school_organiser');
    $school_organizer->set('status', TRUE);
    $school_organizer->set('parent', NULL);
    $school_organizer->set('school_types', NULL);
    $school_organizer->set('school_unit_code', NULL);

    $value_map = [
      'label' => Settings::get('ssr_school_organiser', NULL),
      'municipality_code' => Settings::get('ssr_school_municipality_code', NULL),
    ];
    foreach ($value_map as $field => $value) {
      if ($value !== NULL && $school_organizer->get($field)->value !== $value) {
        $school_organizer->set($field, $value);
        $has_changes = TRUE;
      }
    }
    if ($has_changes) {
      $school_organizer->save();
      $organizations['school_organization'] = $school_organizer->id();
    }

    $has_changes = FALSE;
    $school_unit = $organizations['school_unit']
      ? $organization_storage->load($organizations['school_unit'])
      : NULL;
    if (!$school_unit) {
      $school_unit = $organization_storage->create([
        'label' => Settings::get('ssr_school_name', NULL),
        'langcode' => 'sv',
      ]);
      $has_changes = TRUE;
    }
    /** @var \Drupal\simple_school_reports_entities\SSROrganizationInterface $school_unit */
    $school_unit->set('organization_type', 'school_unit');
    $school_unit->set('status', TRUE);
    $school_unit->set('parent', $school_organizer->id());

    $school_types = SchoolTypeHelper::getSchoolTypes();
    $current_school_types = array_column($school_unit->get('school_types')->getValue(), 'value');
    if (array_diff($school_types, $current_school_types) || array_diff($current_school_types, $school_types)) {
      $school_unit->set('school_types', $school_types);
      $has_changes = TRUE;
    }
    $value_map = [
      'label' => Settings::get('ssr_school_name', NULL),
      'school_unit_code' => Settings::get('ssr_school_unit_code', NULL),
      'municipality_code' => Settings::get('ssr_school_municipality_code', NULL),
    ];
    foreach ($value_map as $field => $value) {
      if ($value !== NULL && $school_unit->get($field)->value !== $value) {
        $school_unit->set($field, $value);
        $has_changes = TRUE;
      }
    }
    if ($has_changes) {
      $school_unit->save();
      $organizations['school_unit'] = $school_unit->id();
    }

    $this->state->set('simple_school_reports_core.organizations', $organizations);
    return TRUE;
  }

  public static function getSubscribedEvents() {
    $events['ssr_post_deploy'][] = 'onSsrPostDeploy';
    return $events;
  }

  public function onSsrPostDeploy() {
    $this->assertOrganizations();
    $this->loggerChannelFactory->get('simple_school_reports_core')->info('Organizations have been asserted.');
  }
}
