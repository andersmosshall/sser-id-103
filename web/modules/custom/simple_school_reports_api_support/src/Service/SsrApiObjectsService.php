<?php

namespace Drupal\simple_school_reports_api_support\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Meta;
use Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Organisation;
use Drupal\simple_school_reports_api_support\client\ssr_v2\Model\OrganisationAddress;
use Drupal\simple_school_reports_api_support\client\ssr_v2\Model\OrganisationReference;

/**
 * Support methods for class stuff.
 */
class SsrApiObjectsService implements SsrApiObjectsServiceInterface {
  use StringTranslationTrait;

  private array $lookup = [];

  protected LoggerChannelInterface $logger;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Connection $database,
    protected CacheBackendInterface $cache,
    LoggerChannelFactoryInterface $loggerChannelFactory,
  ) {
    $this->logger = $loggerChannelFactory->get('simple_school_reports_api_support');
  }

  protected function getMeta(ContentEntityInterface $entity): Meta {
    $meta = new Meta();
    if ($entity->hasField('created') && !$entity->get('created')->isEmpty()) {
      $timestamp = $entity->get('created')->value;
      $date = \DateTime::createFromFormat('U', $timestamp);
      $meta->setCreated($date);
    }

    if ($entity->hasField('changed') && !$entity->get('changed')->isEmpty()) {
      $timestamp = $entity->get('changed')->value;
      $date = \DateTime::createFromFormat('U', $timestamp);
      $meta->setModified($date);
    }

    $required_fields = [
      'created',
      'modified',
    ];
    foreach ($required_fields as $field) {
      if (!$meta->isInitialized($field)) {
        throw new \LogicException('Entity ' . $entity->getEntityTypeId() . ' with id ' . $entity->id() . ' has no ' . $field . ' field.');
      }
    }

    return $meta;
  }

  protected function getAddress(ParagraphInterface $entity, OrganisationAddress $address) {

    if ($entity->hasField('field_street_address') && !$entity->get('field_street_address')->isEmpty()) {
      $address->setStreetAddress($entity->get('field_street_address')->value);
    }

    if ($entity->hasField('field_city') && !$entity->get('field_city')->isEmpty()) {
      $address->setLocality($entity->get('field_city')->value);
    }

    if ($entity->hasField('field_zip_code') && !$entity->get('field_zip_code')->isEmpty()) {
      $address->setPostalCode($entity->get('field_zip_code')->value);
    }

    $required_fields = [
      'streetAddress',
      'locality',
      'postalCode',
    ];
    foreach ($required_fields as $field) {
      if (!$address->isInitialized($field)) {
        throw new \LogicException('Entity ' . $entity->getEntityTypeId() . ' with id ' . $entity->id() . ' has no ' . $field . ' field.');
      }
    }

    return $address;
  }

  protected function getUuid(ContentEntityInterface $entity): string {
    $uuid = $entity->uuid();

    if (empty($uuid)) {
      throw new \LogicException('Entity ' . $entity->getEntityTypeId() . ' with id ' . $entity->id() . ' has no UUID.');
    }

    return $uuid;
  }

  public function getOrganizationTypeMap(): array {
    return [
      'school_organiser' => 'Huvudman',
      'school' => 'Skola',
      'school_unit' => 'Skolenhet',
      'other' => 'Övrigt',
    ];
  }

  public function makeOrganization(string $id): ?Organisation {
    return $this->makeOrganizations([$id])[0] ?? NULL;
  }

  public function makeOrganizations(array $ids): array {
    $organizations = [];
    $ids_to_load = [];

    foreach ($ids as $id) {
      if (isset($this->lookup[$id])) {
        if ($this->lookup[$id] === SsrApiObjectsServiceInterface::INVALID_OBJECT) {
          continue;
        }
        $organizations[] = $this->lookup[$id];
        continue;
      }
      $ids_to_load[] = $id;
    }

    if (empty($ids_to_load)) {
      return $organizations;
    }

    $organization_storage = $this->entityTypeManager->getStorage('ssr_organization');
    $loaded_organizations = $organization_storage->loadMultiple($ids_to_load);
    $organization_type_map = $this->getOrganizationTypeMap();

    /** @var \Drupal\simple_school_reports_entities\SSROrganizationInterface $organization_src */
    foreach ($loaded_organizations as $organization_src) {
      try {
        $organization = new Organisation();
        $organization->setId($this->getUuid($organization_src));
        $organization->setMeta($this->getMeta($organization_src));
        $organization->setDisplayName($organization_src->label() ?? '');

        /** @var \Drupal\simple_school_reports_entities\SSROrganizationInterface $parent */
        $parent_src = $organization_src->get('parent')->entity;
        if ($parent_src) {
          $parent = new OrganisationReference();
          $parent->setId($this->getUuid($organization_src));
          $parent->setDisplayName($organization_src->label() ?? '');
          $organization->setParentOrganisation($parent);
        }

        $organization_type = $organization_src->get('organization_type')->value;
        if (!empty($organization_type) && isset($organization_type_map[$organization_type])) {
          $organization->setOrganisationType($organization_type_map[$organization_type]);
        }

        $organization_number = $organization_src->get('organization_number')->value;
        if (!empty($organization_number)) {
          $organization->setOrganisationNumber($organization_number);
        }

        $school_unit_code = $organization_src->get('school_unit_code')->value;
        if (!empty($school_unit_code)) {
          $organization->setSchoolUnitCode($school_unit_code);
        }

        $school_types = array_column($organization_src->get('school_types')->getValue(), 'value');
        if (!empty($school_types) && $organization_type === 'school_unit') {
          $organization->setSchoolTypes($school_types);
        }

        $municipality_code = $organization_src->get('municipality_code')->value;
        if (!empty($municipality_code)) {
          $organization->setMunicipalityCode($municipality_code);
        }

        $email = $organization_src->get('email')->value;
        if (!empty($email)) {
          $organization->setEmail($email);
        }

        $phone_number = $organization_src->get('phone_number')->value;
        if (!empty($phone_number)) {
          $organization->setPhoneNumber($phone_number);
        }

        $address = $organization_src->get('field_address')->entity;
        if ($address) {
          $organization->setAddress($this->getAddress($address, new OrganisationAddress()));
        }

        $required_fields = [
          'id',
          'meta',
          'displayName',
          'organisationType',
        ];
        foreach ($required_fields as $field) {
          if (!$organization->isInitialized($field)) {
            throw new \LogicException('Organization ' . $organization_src->id() . ' has no ' . $field . ' field.');
          }
        }

        $this->lookup[$organization_src->id()] = $organization;
        $organizations[] = $organization;
      }
      catch (\Exception $e) {
        $this->lookup[$organization_src->id()] = SsrApiObjectsServiceInterface::INVALID_OBJECT;
        $this->logger->error('Organization invalid, @id, @error', ['@id' => $organization_src->id(), '@error' => $e->getMessage()]);
      }
    }

    return $organizations;
  }
}
