<?php

namespace Drupal\simple_school_reports_core\Form;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\file\FileInterface;
use Drupal\simple_school_reports_core\Events\ExportUsersMethodsEvent;
use Drupal\simple_school_reports_core\Events\SsrCoreEvents;
use Drupal\simple_school_reports_core\Service\ExportUsersServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides a confirmation form for adding date range to url.
 */
class ExportMultipleUsersForm extends ConfirmFormBase {

  /**
   * The temp store factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The event dispatcher user.
   */
  protected EventDispatcherInterface $dispatcher;

  /**
   * Constructs a new MailMultipleCaregiversForm.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The temp store factory.
   */
  public function __construct(
    PrivateTempStoreFactory $temp_store_factory,
    EventDispatcherInterface $dispatcher,
  ) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->dispatcher = $dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('event_dispatcher'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'export_multiple_users_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Export users');
  }

  public function getCancelRoute() {
    return '<front>';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url($this->getCancelRoute());
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Export');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Retrieve the accounts to be canceled from the temp store.
    /** @var \Drupal\user\Entity\User[] $accounts */
    $accounts = $this->tempStoreFactory
      ->get('export_multiple_users')
      ->get($this->currentUser()->id());
    if (empty($accounts)) {
      return $this->redirect($this->getCancelRoute());
    }

    $form['accounts'] = ['#tree' => TRUE];
    foreach ($accounts as $account) {
      $uid = $account->id();
      $form['accounts'][$uid] = [
        '#type' => 'checkbox',
        '#title' => $account->getDisplayName(),
        '#default_value' => TRUE,
      ];
    }

    $event = new ExportUsersMethodsEvent();
    $this->dispatcher->dispatch($event, SsrCoreEvents::EXPORT_USERS_METHODS);

    $services = $event->getExportMethodServices();

    // Sort services by priority.
    uasort($services, function (ExportUsersServiceInterface $a, ExportUsersServiceInterface $b) {
      return $a->getPriority() <=> $b->getPriority();
    });

    $options = [];
    foreach ($services as $service) {
      $options[$service->getServiceId()] = $service->getShortDescription();
    }

    $form['method'] = [
      '#type' => 'select',
      '#title' => $this->t('Export method'),
      '#description' => $this->t('Select export method to use depending on destination system'),
      '#options' => $options,
      '#empty_option' => $this->t('Select export method'),
      '#required' => TRUE,
    ];

    $form['export_method'] = ['#tree' => TRUE];

    foreach ($services as $service_id => $service) {
      $form['export_method'][$service_id] = [
        '#type' => 'container',
        '#states' => [
          'visible' => [
            ':input[name="method"]' => ['value' => $service_id],
          ],
        ],
      ];

      $form['export_method'][$service_id]['info'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $service->getDescription(),
      ];

      $form['export_method'][$service_id]['options'] = $service->getOptionsForm();
    }


    return parent::buildForm($form, $form_state);
  }

  protected function getUids(FormStateInterface $form_state): array {
    $uids = [];
    $uids_values = $form_state->getValue('accounts');

    foreach ($uids_values as $uid => $value) {
      if ($value) {
        $uids[] = $uid;
      }
    }

    return $uids;
  }

  protected function getExportService(FormStateInterface $form_state): ExportUsersServiceInterface {
    $service_id = $form_state->getValue('method');
    return \Drupal::service($service_id);
  }

  protected function getExportOptions(FormStateInterface $form_state): array {
    $export_method = $form_state->getValue('method');
    $export_options = $form_state->getValue(['export_method',  $export_method, 'options'], []);

    $service = $this->getExportService($form_state);
    return $service::getOptionsWithDefaults($export_options);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Do the validation...
    $uids = $this->getUids($form_state);


    if (empty($uids)) {
      $form_state->setError($form['accounts'], $this->t('Please select at least one user.'));
      return;
    }

    $service = $this->getExportService($form_state);
    $options = $this->getExportOptions($form_state);

    $errors = $service->getErrors($uids, $options);

    if (!empty($errors)) {
      $form_state->setError($form, $this->t('There are errors preventing the export to be completed.'));

      foreach ($errors as $uid => $error) {
        if (isset($form['accounts'][$uid])) {
          $form_state->setError($form['accounts'][$uid], $error);
        }
        else {
          $form_state->setErrorByName($uid, $error);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {


    $uids = $this->getUids($form_state);
    $service = $this->getExportService($form_state);
    $options = $this->getExportOptions($form_state);

    // Initialize batch (to set title).
    $batch = [
      'title' => $this->t('Processing data'),
      'init_message' => $this->t('Processing data'),
      'progress_message' => $this->t('Processed @current out of @total.'),
      'finished' => [self::class, 'finished'],
      'operations' => [],
    ];

    foreach ($uids as $uid) {
      $batch['operations'][] = [[self::class, 'exportUser'], [$uid, $service->getServiceId(), $options]];
    }

    if (!empty($batch['operations'])) {
      batch_set($batch);
    }
    else {
      $this->messenger()->addError($this->t('Something went wrong. Try again.'));
    }
  }

  public static function exportUser(string $uid, string $service_id, array $options, &$context) {
    /** @var \Drupal\simple_school_reports_core\Service\ExportUsersServiceInterface $service */
    $service = \Drupal::service($service_id);
    /** @var \Drupal\user\UserInterface $user */
    $user = \Drupal::entityTypeManager()->getStorage('user')->load($uid);

    $user_row = $service->getUserRow($user, $options);
    if (empty($user_row)) {
      return;
    }

    $context['results']['service'] = $service_id;
    $context['results']['options'] = $options;
    $context['results']['userRows'][$user->uuid()] = $user_row;
  }

  public static function finished($success, $results) {
    if (!$success || empty($results['service']) || empty($results['options']) || empty($results['userRows'])) {
      \Drupal::messenger()->addError(t('Something went wrong. Try again.'));
      return;
    }

    /** @var \Drupal\simple_school_reports_core\Service\ExportUsersServiceInterface $service */
    $service = \Drupal::service($results['service']);

    $now = new \DateTime();

    $file_content = $service->makeFileContent($results['userRows'], $results['options']);
    if (!$file_content) {
      \Drupal::messenger()->addError(t('Something went wrong. Try again.'));
      return;
    }

    $file_name = 'export-' . $now->format('Y-m-d-His') . '.' . $service->getFileExtension();

    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');

    /** @var UuidInterface $uuid_service */
    $uuid_service = \Drupal::service('uuid');
    $destination_dir = 'public://ssr_generated' . DIRECTORY_SEPARATOR . $uuid_service->generate() . DIRECTORY_SEPARATOR;
    $destination = $destination_dir . $file_name;
    $file_system->prepareDirectory($destination_dir, FileSystemInterface::CREATE_DIRECTORY);



    $file_system->saveData($file_content, $destination);

    /** @var FileInterface $file */
    $file = \Drupal::entityTypeManager()->getStorage('file')->create([
      'filename' => $file_name,
      'uri' => $destination,
    ]);
    $file->save();
    $path = $file->createFileUrl();
    $link = Markup::create('<a href="' . $path . '" target="_blank" download>' . t('here') . '</a>');
    \Drupal::messenger()->addMessage(t('Export is completed. Save the file in a secure location. This file will shortly be removed from server. Download it now form @link', ['@link' => $link]));
  }

}
