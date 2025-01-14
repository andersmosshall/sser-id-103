<?php

namespace Drupal\simple_school_reports_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;

/**
 * Class ReplaceTokenService
 *
 * @package Drupal\simple_school_reports_core\Service
 */
class ReplaceTokenService implements ReplaceTokenServiceInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->entityTypeManager = $entity_type_manager;
  }

  protected function structureReplaceContext(array $replace_context) : array {
    foreach ($replace_context as $category => $value) {
      switch ($category) {
        case self::STUDENT_REPLACE_TOKENS:
        case self::RECIPIENT_REPLACE_TOKENS:
          if (is_array($value) && isset($value['target_id'])) {
            $value = $this->entityTypeManager->getStorage('user')->load($value['target_id']);
          }
          if ($value instanceof UserInterface) {
            $replace_context[$category] = $value;
          }
          else {
            unset($replace_context[$category] );
          }
          break;
        case self::CURRENT_USER_REPLACE_TOKENS:
          if (is_array($value) && isset($value['target_id'])) {
            $value = $this->entityTypeManager->getStorage('user')->load($value['target_id']);
          }
          if ($value instanceof UserInterface) {
            $replace_context[$category]  = $value;
          }
          else {
            // @ToDo load current user instead.
            unset($replace_context[$category] );
          }
          break;
        case self::ATTENDANCE_REPORT_TOKENS:
          if (is_array($value) && isset($value['target_id'])) {
            $value = $this->entityTypeManager->getStorage('node')->load($value['target_id']);
          }
          if ($value instanceof NodeInterface) {
            $replace_context[$category] = $value;
          }
          else {
            unset($replace_context[$category]);
          }
          break;
        case self::INVALID_ABSENCE_TOKENS:
          if (!is_numeric($value)) {
            unset($replace_context[$category]);
          }
          break;
        default:
          unset($replace_context[$category] );
      }
    }

    return $replace_context;
  }

  public function getReplaceTokenDescriptions(array $categories = ['ALL'], bool $flat = FALSE) : array {
    $descriptions = [
      self::STUDENT_REPLACE_TOKENS => [
        '[EF]' => $this->t('Student first name'),
        '[EE]' => $this->t('Student last name'),
        '[E]' => $this->t('Student full name'),
        '[OF]' => $this->t('Total invalid absence'),
      ],
      self::RECIPIENT_REPLACE_TOKENS => [
        '[MF]' => $this->t('Recipient first name'),
        '[ME]' => $this->t('Recipient last name'),
        '[M]' => $this->t('Recipient full name'),
        '[MM]' => $this->t('Recipient email address'),
      ],
      self::CURRENT_USER_REPLACE_TOKENS => [
        '[AF]' => $this->t('Current user first name'),
        '[AE]' => $this->t('Current user last name'),
        '[A]' => $this->t('Current user full name'),
        '[AM]' => $this->t('Current user email address'),
      ],
      self::ATTENDANCE_REPORT_TOKENS => [
        '[L]' => $this->t('Class name'),
      ],
      self::INVALID_ABSENCE_TOKENS => [
        '[T]' => $this->t('Invalid absence time in minutes'),
      ],
    ];

    $descriptions_return = [];
    $all = in_array('ALL', $categories);
    foreach ($descriptions as $category => $token_descriptions) {
      if ($all || in_array($category, $categories)) {
        if ($flat) {
          foreach ($token_descriptions as $token => $token_description) {
            $descriptions_return[$token] = $token_description;
          }
        }
        else {
          $descriptions_return[$category] = $token_descriptions;
        }
      }
    }

    return $descriptions_return;
  }

  protected function getSearchAndReplaceMap(array $replace_context) {
    $search_replace_map = [];
    $tokens = $this->getReplaceTokenDescriptions(array_keys($replace_context));
    foreach ($replace_context as $category => $item) {
      if (empty($tokens[$category])) {
        continue;
      }
      foreach (array_keys($tokens[$category]) as $token) {
        switch ($token) {
          case '[EF]':
          case '[MF]':
          case '[AF]':
            $search_replace_map[$token] = $item->get('field_first_name')->value;
            break;
          case '[EE]':
          case '[ME]':
          case '[AE]':
            $search_replace_map[$token] = $item->get('field_last_name')->value;
            break;
          case '[E]':
          case '[M]':
          case '[A]':
            $search_replace_map[$token] = $item->get('field_first_name')->value . ' ' . $item->get('field_last_name')->value;
            break;
          case '[MM]':
          case '[AM]':
            $search_replace_map[$token] = $item->getEmail();
            break;
          case '[OF]':
            $search_replace_map[$token] = $item->get('field_invalid_absence')->value;
            break;
          case '[L]':
            $search_replace_map[$token] = $item->label();
            break;
          case '[T]':
            $search_replace_map[$token] = $item;
            break;
          default:
            if (!is_numeric($token)) {
              $search_replace_map[$token] = '';
            }
        }
      }
    }

    return $search_replace_map;
  }

  public function handleText(string $text, array $replace_context) : string {
    $search_replace_map = $this->getSearchAndReplaceMap($this->structureReplaceContext($replace_context));
    return str_replace(array_keys($search_replace_map), array_values($search_replace_map), $text);
  }


}
