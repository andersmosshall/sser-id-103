<?php

namespace Drupal\simple_school_reports_core\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\FileInterface;
use Drupal\file\FileUsage\DatabaseFileUsageBackend;
use Drupal\simple_school_reports_core\Service\FileTemplateService;
use Symfony\Component\DependencyInjection\ContainerInterface;


class FileTemplatesConfigForm extends FormBase {

  /**
   * @var \Drupal\simple_school_reports_core\Service\FileTemplateServiceInterface
   */
  protected $fileTemplateService;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\file\FileUsage\DatabaseFileUsageBackend
   */
  protected $fileUsage;


  public function __construct(
    FileTemplateService $file_template_service,
    DatabaseFileUsageBackend $file_usage,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->fileTemplateService = $file_template_service;
    $this->fileUsage = $file_usage;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('simple_school_reports_core.file_template_service'),
      $container->get('file.usage'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'file_templates_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['file_templates'] = [
      '#type' => 'container',
      '#tree' => TRUE,
    ];

    $templates = $this->fileTemplateService->getFileTemplate();

    foreach ($templates as $type => $file) {
      $form['file_templates'][$type] = [
        '#title' => $type,
        '#type' => 'managed_file',
        '#upload_location' => 'public://file_templates',
        '#default_value' => $file ? [$file->id()] : NULL,
        '#upload_validators' => [
          'file_validate_extensions' => str_contains($type, 'logo') ? ['jpeg'] : ['docx xlsx'],
        ],
        '#description' => str_contains($type, 'logo') ? '' : $this->t('Only upload files if it should overwrite the default template in code.'),
      ];
    }

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
    $template_values = $form_state->getValue('file_templates');

    $old_fids = [];
    foreach ($this->fileTemplateService->getFileTemplate() as $old_file) {
      if ($old_file instanceof FileInterface) {
        $old_fids[$old_file->id()] = TRUE;
      }
    }

    $templates = [];
    /** @var \Drupal\file\FileStorageInterface $file_storage */
    $file_storage = $this->entityTypeManager->getStorage('file');

    foreach ($template_values as $key => $value) {
      /** @var \Drupal\file\FileInterface $file */
      if (!empty($value) && $file = $file_storage->load(current($value))) {
        if (!$file->isPermanent()) {
          $this->fileUsage->add($file, 'simple_school_reports_core', 'file_templates', $key, 1);
          $file->setPermanent();
        }
        $file->save();
        $templates[$key] = $file->id();
        unset($old_fids[$file->id()]);
      }
    }

    if (!empty($old_fids)) {
      $files = $file_storage->loadMultiple(array_keys($old_fids));
      $file_storage->delete($files);
    }

    $this->fileTemplateService->setFileTemplate($templates);
    $this->messenger()->addStatus($this->t('Configuration has been saved'));
  }
}
