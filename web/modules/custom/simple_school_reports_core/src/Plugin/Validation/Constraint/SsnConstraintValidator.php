<?php

namespace Drupal\simple_school_reports_core\Plugin\Validation\Constraint;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\StringItem;
use Drupal\Core\Site\Settings;
use Drupal\simple_school_reports_core\Pnum;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that a string is a valid and unique SSN.
 */
class SsnConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  protected null|array $staticSsnMap = NULL;

  /**
   * Constructs a SsnConstraintValidator object.
   */
  public function __construct(
    protected CacheBackendInterface $cache,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Pnum $pnum,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('cache.default'),
      $container->get('entity_type.manager'),
      $container->get('simple_school_reports_core.pnum')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function hashSsn(string $ssn): string {
    return hash('sha256', $ssn . '1567531166244965' . Settings::getHashSalt());
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    if (!$value instanceof FieldItemListInterface) {
      throw new UnexpectedTypeException($value, FieldItemListInterface::class);
    }

    if (!$constraint instanceof SsnConstraint) {
      return;
    }

    $user = $value->getEntity();
    if ($user instanceof UserInterface) {
      if ($user->isSyncing()) {
        return;
      }
    }

    $local_values_map = [];
    foreach ($value as $delta => $item) {
      if (!$item instanceof StringItem) {
        throw new UnexpectedTypeException($item, StringItem::class);
      }

      $typed_value = $item->getValue();
      $original_value = is_array($typed_value) ?
        $typed_value['value'] ?? NULL
        : NULL;

      // Skip empty values.
      if (empty($original_value)) {
        continue;
      }

      // For now, different syntax of ssn may have been stored in the database.
      // Check all variants of the ssn,
      $long_ssn = $this->pnum->normalizeIfValid($original_value, TRUE);

      if (!$long_ssn) {
        $this->context->addViolation($constraint->invalidMessage, ['@value' => $original_value]);
        continue;
      }

      $masked_ssn = substr($long_ssn, 0, -4) . '****';
      $local_values_map[$delta] = [
        'ssn' => $masked_ssn,
        'hash' => $this->hashSsn($long_ssn),
      ];
    }

    if (empty($local_values_map)) {
      return;
    }

    $uid = 'local';

    if ($user instanceof UserInterface) {
      if ($user->id()) {
        $uid = $user->id();
      }
    }

    // Warm up the static ssn map.
    $this->getHashedSsnMap();

    foreach ($local_values_map as $delta => $data) {
      $hash = $data['hash'];
      $ssn = $data['ssn'];

      if (isset($this->staticSsnMap[$hash])) {
        $existing_uid = $this->staticSsnMap[$hash];
        if ($existing_uid !== $uid) {
          $existing_user_name = '*** ***';
          if (is_numeric($existing_uid)) {
            $existing_user = $this->entityTypeManager->getStorage('user')->load($existing_uid);
            if ($existing_user instanceof UserInterface) {
              if ($existing_user->isNew() || $existing_user->access('update')) {
                $existing_user_name = $existing_user->getDisplayName();
              }
            }
          }

          $this->context->addViolation($constraint->notUniqueMessage, [
            '@value' => $ssn,
            '@user' => $existing_user_name,
          ]);
          continue;
        }
      }

      $this->staticSsnMap[$hash] = $uid;
    }
  }

  protected function getHashedSsnMap() {
    if ($this->staticSsnMap) {
      return $this->staticSsnMap;
    }

    $cid = 'ssr_ssn_map';
    if ($cache = $this->cache->get($cid)) {
      if (is_array($cache->data)) {
        $this->staticSsnMap = $cache->data;
        return $this->staticSsnMap;
      }
    }

    $this->staticSsnMap = [];

    $uids = $this->entityTypeManager->getStorage('user')->getQuery()
      ->accessCheck(FALSE)
      ->condition('field_ssn', NULL, 'IS NOT NULL')
      ->execute();

    foreach ($uids as $uid) {
      $user = $this->entityTypeManager->getStorage('user')->load($uid);
      if (!$user instanceof UserInterface) {
        continue;
      }
      $ssn = $user->get('field_ssn')->value;
      if (!$ssn) {
        continue;
      }
      $long_ssn = $this->pnum->normalizeIfValid($ssn, TRUE);
      if (!$long_ssn) {
        continue;
      }
      $this->staticSsnMap[$this->hashSsn($long_ssn)] = $uid;
    }

    $tags = [
      'user:ssn',
    ];
    $this->cache->set($cid, $this->staticSsnMap, CacheBackendInterface::CACHE_PERMANENT, $tags);
    return $this->staticSsnMap;
  }

}
