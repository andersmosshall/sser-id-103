<?php

namespace Drupal\simple_school_reports_schema_support\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\simple_school_reports_core\Service\EmailService;
use Drupal\simple_school_reports_core\Service\EmailServiceInterface;
use Drupal\simple_school_reports_core\Service\MessageTemplateServiceInterface;
use Drupal\simple_school_reports_core\Service\ReplaceTokenServiceInterface;
use Drupal\simple_school_reports_core\Service\StartPageContentServiceInterface;
use Drupal\simple_school_reports_schema_support\Service\CalendarEventsSyncServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;


class CalendarEventSyncConfigForm extends FormBase {

  public function __construct(
    protected CalendarEventsSyncServiceInterface $calendarEventsSyncService,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('simple_school_reports_schema_support.calendar_events_sync'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'calendar_event_sync_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $sync_settings = $this->calendarEventsSyncService->getEnabledSettings();

    $form['all_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Sync is enabled'),
      '#description' => $this->t('Enable or disable syncing of lessons to report'),
      '#default_value' => $sync_settings['all']
    ];

    $form['remove_unreported'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remove unreported events'),
      '#description' => $this->t('Remove all unreported lessons to report'),
      '#default_value' => FALSE,
    ];

    $form['remove_unreported']['#states'] = [
      'visible' => [
        ':input[name="all_enabled"]' => ['checked' => FALSE],
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $all_enabled = $form_state->getValue('all_enabled', TRUE);
    $remove_unreported = !$all_enabled ? $form_state->getValue('remove_unreported', FALSE) : FALSE;

    $sync_settings = $this->calendarEventsSyncService->getEnabledSettings();
    $sync_settings['all'] = $all_enabled;
    $this->calendarEventsSyncService->setEnabledSettings($sync_settings);

    if ($remove_unreported) {
      // Initialize batch (to set title).
      $batch = [
        'title' => $this->t('Remove unreported lessons'),
        'init_message' => $this->t('Remove unreported lessons'),
        'progress_message' => $this->t('Processed @current out of @total.'),
        'operations' => [],
        'finished' => [self::class, 'finished'],
      ];

      $calendar_event_ids = $this->entityTypeManager->getStorage('ssr_calendar_event')->getQuery()
        ->accessCheck(FALSE)
        ->condition('completed', 0)
        ->execute();

      foreach ($calendar_event_ids as $calendar_event_id) {
        $batch['operations'][] = [[self::class, 'batchRemoveEvent'], [$calendar_event_id]];
      }

      if (!empty($batch['operations'])) {
        if (count($batch['operations']) < 10) {
          $batch['progressive'] = FALSE;
        }
        batch_set($batch);
      }
    }

    Cache::invalidateTags(['ssr_calendar_event_list']);
    Cache::invalidateTags(['ssr_calendar_event_sync_config']);
    $this->messenger()->addStatus($this->t('Configuration has been saved'));
  }

  public static function batchRemoveEvent(string|int $calendar_event_id, &$context): void {
    $calendar_event = \Drupal::entityTypeManager()->getStorage('ssr_calendar_event')->load($calendar_event_id);
    if (!$calendar_event || $calendar_event->get('completed')->value) {
      return;
    }

    try {
      $calendar_event->delete();
      $context['results']['removed_events'][$calendar_event_id] = TRUE;
    }
    catch (\Exception $e) {
      \Drupal::logger('simple_school_reports_schema_support')->error($e->getMessage());
    }
  }

  public static function finished($success, $results): void {
    if (!$success || empty($results['removed_events'])) {
      return;
    }

    \Drupal::messenger()->addStatus(t('@count unreported lessons has been removed.', ['@count'  => count($results['removed_events'])]));
  }

  public static function access(AccountInterface $account) {
    if (!ssr_use_schema()) {
      return AccessResult::forbidden();
    }

    return AccessResult::allowedIfHasPermission($account, 'administer simple school reports settings');
  }
}
