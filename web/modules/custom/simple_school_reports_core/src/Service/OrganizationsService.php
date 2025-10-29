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

  protected array $lookup = [];

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  protected function warmUpMap(): void {
    $cid_organization = 'organization_map';
    $cid_school_unit_code = 'school_unit_code_map';
    if (isset($this->lookup[$cid_organization])) {
      return;
    }
    $map = [];

    $organization_storage = $this->entityTypeManager->getStorage('ssr_organization');
    $organization_ids = $organization_storage->getQuery()->accessCheck(FALSE)->execute();

    if (!empty($organization_ids)) {
      $organizations = $organization_storage->loadMultiple($organization_ids);
      foreach ($organizations as $organization) {
        if ($organization instanceof SSROrganizationInterface) {
          $type = $organization->get('organization_type')->value;
          if ($type === 'school_unit') {
            $school_unit_code = $organization->get('school_unit_code')->value;
            $school_types = array_column($organization->get('school_types')->getValue(), 'value');
            foreach ($school_types as $school_type) {
              $school_type = mb_strtolower($school_type);
              $this->lookup[$cid_school_unit_code][$school_type] = $school_unit_code;

              $map[$type][$school_type] = $organization->id();
              $school = $organization->get('parent')->entity;
              if ($school instanceof SSROrganizationInterface) {
                $map['school'][$school_type] = $school->id();
                $parent_organization = $school->get('parent')->entity;
                if ($parent_organization instanceof SSROrganizationInterface) {
                  $map['school_organiser'][$school_type] = $parent_organization->id();
                }
              }
            }
          }
          else {
            $map[$type]['default'] = $organization->id();
          }
        }
      }
    }

    $this->lookup[$cid_organization] = $map;
  }

  public function getOrganization(string $organization_type, string $school_type): ?SSROrganizationInterface {
    $this->warmUpMap();
    $cid_organization = 'organization_map';

    $school_type = mb_strtolower($school_type);

    if (!empty($this->lookup[$cid_organization][$organization_type][$school_type])) {
      $organization_id = $this->lookup[$cid_organization][$organization_type][$school_type];
      $organization_storage = $this->entityTypeManager->getStorage('ssr_organization');
      return $organization_storage->load($organization_id) ?? NULL;
    }
    return NULL;
  }

  public static function getSubscribedEvents() {
    return [];
  }

  public function getSchoolUnitCode(string $school_type): ?string {
    $school_type = mb_strtolower($school_type);
    $this->warmUpMap();
    $cid_school_unit_code = 'school_unit_code_map';
    return $this->lookup[$cid_school_unit_code][$school_type] ?? NULL;
  }

  public function clearLookup(): void {
    $this->lookup = [];
  }

  public static function getStaticSchoolUnitCode(string $school_type): ?string {
    $school_type = mb_strtolower($school_type);
    /** @var \Drupal\simple_school_reports_core\Service\OrganizationsServiceInterface $service */
    $service = \Drupal::service('simple_school_reports_core.organizations_service');
    return $service->getSchoolUnitCode($school_type);
  }

  public static function getStaticSchoolName(string $school_type): ?string {
    $school_type = mb_strtolower($school_type);
    /** @var \Drupal\simple_school_reports_core\Service\OrganizationsServiceInterface $service */
    $service = \Drupal::service('simple_school_reports_core.organizations_service');
    $school = $service->getOrganization('school', $school_type);
    return $school?->label() ?? NULL;
  }

}
