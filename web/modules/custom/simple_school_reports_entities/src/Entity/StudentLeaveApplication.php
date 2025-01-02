<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_entities\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\simple_school_reports_entities\StudentLeaveApplicationInterface;
use Drupal\user\EntityOwnerTrait;
use http\Exception\RuntimeException;

/**
 * Defines the student leave application entity class.
 *
 * @ContentEntityType(
 *   id = "ssr_student_leave_application",
 *   label = @Translation("Student leave application"),
 *   label_collection = @Translation("Student leave applications"),
 *   label_singular = @Translation("student leave application"),
 *   label_plural = @Translation("student leave applications"),
 *   label_count = @PluralTranslation(
 *     singular = "@count student leave applications",
 *     plural = "@count student leave applications",
 *   ),
 *   handlers = {
 *     "list_builder" =
 *   "Drupal\simple_school_reports_entities\StudentLeaveApplicationListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" =
 *   "Drupal\simple_school_reports_entities\StudentLeaveApplicationAccessControlHandler",
 *     "form" = {
 *       "add" =
 *   "Drupal\simple_school_reports_entities\Form\StudentLeaveApplicationForm",
 *       "edit" =
 *   "Drupal\simple_school_reports_entities\Form\StudentLeaveApplicationForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" =
 *   "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "ssr_student_leave_application",
 *   data_table = "ssr_student_leave_application_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer ssr_student_leave_application",
 *   entity_keys = {
 *     "id" = "id",
 *     "langcode" = "langcode",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/ssr-student-leave-application",
 *     "add-form" = "/leave-application-student/add",
 *     "canonical" =
 *   "/leave-application-student/{ssr_student_leave_application}",
 *     "edit-form" =
 *   "/leave-application-student/{ssr_student_leave_application}/edit",
 *     "delete-form" =
 *   "/leave-application-student/{ssr_student_leave_application}/delete",
 *     "delete-multiple-form" =
 *   "/admin/content/ssr-student-leave-application/delete-multiple",
 *   },
 *   field_ui_base_route = "entity.ssr_student_leave_application.settings",
 * )
 */
final class StudentLeaveApplication extends ContentEntityBase implements StudentLeaveApplicationInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);

    if ($this->isNew() && !$this->isSyncing()) {
      if (!empty($this->validateApplication())) {
        throw new RuntimeException('Validation failed');
      }
    }

    $student = $this->get('student')->entity;

    $label = 'Okänd elev';
    if ($student) {
      $label =  $student->getDisplayName();
    }

    $from = $this->get('from')->value;
    $to = $this->get('to')->value;

    if ($from && $to) {
      if ($to < $from) {
        throw new \RuntimeException('To date must be after from date');
      }

      $from = (new \DateTime())->setTimestamp((int) $from);
      $to = (new \DateTime())->setTimestamp((int) $to);

      $label .= ' ' . $from->format('Y-m-d') . ' - ' . $to->format('Y-m-d');
    }

    $this->set('label', $label);
    $this->set('leave_days', $this->calculateLeaveDays());

    if (!$this->getOwnerId()) {
      // If no owner has been set explicitly, make the anonymous user the owner.
      $this->setOwnerId(0);
    }
  }

  public function label() {
    $label = '';

    if (TRUE) {
      $label = $this->t('Leave application') . ' - ';
    }

    $label .= parent::label();
    return $label;
  }

  protected function calculateLeaveDays(): int {
    $from = $this->get('from')->value;
    $to = $this->get('to')->value;

    if (!$from || !$to) {
      return 0;
    }

    $diff = abs($to - $from);
    $days = floor($diff / (60 * 60 * 24)) + 1;
    return (int) $days;
  }

  /**
   * {@inheritdoc}
   */
  public function validateApplication(): array {
    try {
      $errors = [];

      /** @var \Drupal\Core\Extension\ModuleHandlerInterface $module_handler */
      $module_handler = \Drupal::service('module_handler');
      if (!$module_handler->moduleExists('simple_school_reports_leave_application')) {
        $errors['general'] = $this->t('Something went wrong. Try again.');
        return $errors;
      }

      $student = $this->get('student')->entity;
      if (!$student) {
        $errors['student'] = $this->t('Student is required');
      }

      $reason = $this->get('field_reason')->processed;
      if (!$reason) {
        $errors['field_reason'] = $this->t('Reason for leave is required');
      }

      $from = $this->get('from')->value;
      $to = $this->get('to')->value;

      if (!$from) {
        $errors['from'] = $this->t('From date is required');
      }
      if (!$to) {
        $errors['to'] = $this->t('To date is required');
      }

      if ($from && $to) {
        if ($to < $from) {
          $errors['to'] = $this->t('To date must be after from date');
        }
      }

      if (!empty($errors)) {
        return $errors;
      }

      $validate_period = FALSE;

      if ($this->isNew()) {
        $validate_period = TRUE;
      }
      else {
        $original = $this->entityTypeManager()->getStorage('ssr_student_leave_application')->loadUnchanged($this->id());
        if ($original instanceof StudentLeaveApplicationInterface) {
          $original_from = $original->get('from')->value;
          $original_to = $original->get('to')->value;

          if ($from !== $original_from || $to !== $original_to) {
            $validate_period = TRUE;

            // If any of the diffs are less than 12 hours, we need skip the validation.
            $diff_from = abs($from - $original_from);
            $diff_to = abs($to - $original_to);
            if ($diff_from < 60 * 60 * 12 || $diff_to < 60 * 60 * 12) {
              $validate_period = FALSE;
            }
          }
        }

      }

      if (!$validate_period) {
        return $errors;
      }

      $leave_days = $this->calculateLeaveDays();
      if ($leave_days < 1) {
        $errors['general_short_period'] = $this->t('Invalid period. Try again.');
        return $errors;
      }

      /** @var \Drupal\simple_school_reports_leave_application\Service\LeaveApplicationServiceInterface $leave_application_service */
      $leave_application_service = \Drupal::service('simple_school_reports_leave_application.leave_application_service');

      $max_application_days = $leave_application_service->getSetting('max_application_days', 30);
      if ($leave_days > $max_application_days) {
        $errors['general_long_period'] = $this->t('The leave application is too long. Maximum @days days allowed.', ['@days' => $max_application_days]);
      }

      $max_application_days_ago = $leave_application_service->getSetting('max_application_days_ago', 7);
      $max_application_days_future = $leave_application_service->getSetting('max_application_days_future', 365);

      // Verify that from date is not too far in the past.
      $from_limit = new \DateTime();
      $from_limit->setTime(0, 0, 0);
      $from_limit->modify("-$max_application_days_ago days");
      $now_timestamp = $from_limit->getTimestamp();
      if ($from < $now_timestamp) {
        $errors['from'] = $this->t('From date is too far in the past. Maximum @days days ago allowed.', ['@days' => $max_application_days_ago]);
      }

      // Verify that to date is not too far in the future.
      $to_limit = new \DateTime();
      $to_limit->setTime(0, 0, 0);
      $to_limit->modify("+$max_application_days_future days");
      $to_limit->setTime(23, 59, 59);
      $to_limit_timestamp = $to_limit->getTimestamp();
      if ($to > $to_limit_timestamp) {
        $errors['to'] = $this->t('To date is too far in the future. Maximum @days days ahead allowed.', ['@days' => $max_application_days_future]);
      }

      if (!empty($errors)) {
        return $errors;
      }

      // Verify it there is any applications already covering the same period for the same student. Application that is pending or approved that is.
      $query = $this->entityTypeManager()->getStorage('ssr_student_leave_application')->getQuery()->accessCheck(FALSE);
      $query->condition('student', $student->id());
      $query->condition('state', ['pending', 'approved'], 'IN');
      $query->condition('from', $from, '<=');
      $query->condition('to', $to, '>=');
      $query->condition('id', $this->id() ?? '-1', '<>');
      $query->range(0, 1);
      $result = $query->execute();

      if (!empty($result)) {
        $errors['general_duplications'] = $this->t('There is already a leave application covering the same period for this student.');
      }

    } catch (\Exception $e) {
      $errors['general'] = $this->t('Something went wrong. Try again.');
    }

    return $errors;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setTranslatable(TRUE)
      ->setLabel(t('Label'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', 'Enabled')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Created by'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(self::class . '::getDefaultEntityOwner')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the student leave application was created.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the student leave application was last edited.'));

    $fields['student'] = BaseFieldDefinition::create('entity_reference')
      ->setRequired(TRUE)
      ->setLabel(t('Student'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler_settings', [
        'filter' => [
          'type' => 'role',
          'role' => [
            'student' => 'student',
          ],
        ],
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['handled_by'] = BaseFieldDefinition::create('entity_reference')
      ->setRequired(TRUE)
      ->setLabel(t('Handled by'))
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['from'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('From'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['to'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('To'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['leave_days'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Leave days'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['state'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('State'))
      ->setDefaultValue('pending')
      ->setSetting('allowed_values_function', 'simple_school_reports_student_leave_application_states')
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    return $fields;
  }

}
