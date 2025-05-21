<?php

namespace Drupal\simple_school_reports_core\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
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

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;


  public function __construct(
    FileTemplateService $file_template_service,
    DatabaseFileUsageBackend $file_usage,
    EntityTypeManagerInterface $entity_type_manager,
    ModuleHandlerInterface $module_handler
  ) {
    $this->fileTemplateService = $file_template_service;
    $this->fileUsage = $file_usage;
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('simple_school_reports_core.file_template_service'),
      $container->get('file.usage'),
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
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

    $categories_map = [
      'student_grade_term' => 'generated_documents',
      'student_grade_final' => 'generated_documents',
      'student_group_grade' => 'generated_documents',
      'teacher_grade_sign' => 'generated_documents',
      'written_reviews' => 'generated_documents',
      'iup' => 'generated_documents',
      'dnp_empty' => 'generated_documents',
      'doc_logo_left' => 'logos',
      'doc_logo_center' => 'logos',
      'doc_logo_right' => 'logos',
      'logo_header' => 'logos',
    ];

    $form['file_templates']['logos'] = [
      '#type' => 'details',
      '#title' => $this->t('Logos'),
      '#open' => TRUE,
    ];

    $form['file_templates']['other'] = [
      '#type' => 'details',
      '#title' => $this->t('Other files'),
      '#open' => FALSE,
    ];

    $form['file_templates']['generated_documents'] = [
      '#type' => 'details',
      '#title' => $this->t('Generated documents'),
      '#description' => $this->t('Templates are in code, only upload files if it should overwrite the default template in code. NOT RECOMMENDED!'),
      '#open' => FALSE,
    ];

    foreach ($templates as $type => $file) {
      $category = $categories_map[$type] ?? 'other';
      $file_types = ['jpeg'];
      if ($category === 'generated_documents') {
        $file_types = ['docx', 'xlsx'];
      }
      if ($category === 'other') {
        $file_types = ['pdf'];
      }
      if ($type === 'logo_header') {
        $file_types = ['jpeg', 'jpg', 'png', 'svg'];
      }
      $form['file_templates'][$category][$type] = [
        '#title' => $type,
        '#type' => 'managed_file',
        '#upload_location' => 'public://file_templates',
        '#default_value' => $file ? [$file->id()] : NULL,
        '#upload_validators' => [
          'file_validate_extensions' => [implode(' ', $file_types)],
        ],
        '#description' => str_contains($type, 'logo') ? '' : $this->t('Only upload files if it should overwrite the default template in code.'),
      ];
    }

    // Add info about logos.
    $form['file_templates']['logos']['info_label'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => $this->t('Logo info'),
    ];

    $logo_example_path = DIRECTORY_SEPARATOR . $this->moduleHandler->getModule('simple_school_reports_core')->getPath() . DIRECTORY_SEPARATOR . 'ssr-file-templates' . DIRECTORY_SEPARATOR . 'logo_example_l.jpeg';
    $example_doc_logo_link = Link::fromTextAndUrl($this->t('Example doc logo'), Url::fromUserInput($logo_example_path, ['attributes' => ['target' => '_blank']]));
    $form['file_templates']['logos']['info_first'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('It is important that the doc logos using this template: @link. The template is for the left aligned version, the center and right aligned version should have the same size. IMPORTANT: Make sure left and right aligned logo doc versions are aligned as far to the corresponding side as possible.', [
        '@link' => $example_doc_logo_link->toString(),
      ])
    ];

    $form['file_templates']['logos']['info_second'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('For the header logo the recommended size is max 320x90px.'),
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

    foreach ($template_values as $category => $file_data) {
      foreach ($file_data as $key => $value) {
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
    }

    if (!empty($old_fids)) {
      $files = $file_storage->loadMultiple(array_keys($old_fids));
      $file_storage->delete($files);
    }

    Cache::invalidateTags(['config:system.site']);
    $this->fileTemplateService->setFileTemplate($templates);
    $this->messenger()->addStatus($this->t('Configuration has been saved'));
  }
}
