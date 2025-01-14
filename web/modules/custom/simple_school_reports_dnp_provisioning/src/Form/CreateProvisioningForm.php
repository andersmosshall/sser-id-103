<?php

namespace Drupal\simple_school_reports_dnp_provisioning\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\ConfirmFormHelper;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Drupal\simple_school_reports_core\UserFormAlter;
use Drupal\simple_school_reports_dnp_provisioning\Service\DnpProvisioningServiceInterface;
use Drupal\simple_school_reports_dnp_support\DnpProvisioningConstantsInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


class CreateProvisioningForm extends FormBase {

  public function __construct(
    protected DnpProvisioningServiceInterface $dnpProvisioningService,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('simple_school_reports_dnp_provisioning.dnp_provisioning_service'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ssr_create_dnp_provisioning';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $step = $form_state->get('step') ?? 'settings_preview';

    $query = $this->getRequest()->query;
    $url = NULL;
    // If a destination is specified, that serves as the cancel link.
    if ($query->has('destination')) {
      $options = UrlHelper::parse($query->get('destination'));
      try {
        $url = Url::fromUserInput('/' . ltrim($options['path'], '/'), $options);
      }
      catch (\InvalidArgumentException $e) {
      }
    }

    if (!$url) {
      $url = Url::fromRoute('view.dnp_provisioning.list');
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#attributes' => ['class' => ['button', 'dialog-cancel']],
      '#url' => $url,
      '#cache' => [
        'contexts' => [
          'url.query_args:destination',
        ],
      ],
    ];

    switch ($step) {
      case 'settings_preview':
        $form = $this->buildSettingsPreviewStep($form, $form_state);
        break;
      case 'dnp_provisioning_preview':
        $form = $this->buildDnpProvisioningPreviewStep($form, $form_state);
        break;
    }

    return $form;
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildSettingsPreviewStep(array $form, FormStateInterface $form_state): array {
    $dnp_provisioning_settings = $this->dnpProvisioningService->getDnpProvisioningSettings();
    $form['label'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t('DNP provisioning settings preview'),
    ];

    $form['preview_wrapper'] = [
      '#type' => 'container',
    ];

    if (!$dnp_provisioning_settings) {
      $form['preview_wrapper']['preview'] = [
        '#type' => 'html_tag',
        '#tag' => 'em',
        '#value' => $this->t('No DNP provisioning settings found.'),
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      ];
      return $form;
    }

    $view_builder = $this->entityTypeManager->getViewBuilder('dnp_prov_settings');
    $form['preview_wrapper']['preview'] = $view_builder->view($dnp_provisioning_settings);

    $previous_dnp_provisioning_ids = $this->entityTypeManager->getStorage('dnp_provisioning')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('synced', 1)
      ->sort('created', 'DESC')
      ->execute();

    if (!empty($previous_dnp_provisioning_ids)) {
      $previous_dnp_provisionings = $this->entityTypeManager->getStorage('dnp_provisioning')->loadMultiple($previous_dnp_provisioning_ids);
      $options = [];
      foreach ($previous_dnp_provisionings as $previous_dnp_provisioning) {
        $options[$previous_dnp_provisioning->id()] = $previous_dnp_provisioning->label();
        if (!empty($options)) {
          $form['previous_dnp_provisioning'] = [
            '#type' => 'select',
            '#title' => $this->t('Select last uploaded DNP provisioning'),
            '#description' => $this->t('The last uploaded DNP provisioning is used to determine the changes in the new provisioning to mark "Ta bort" property in the generated file properly.'),
            '#options' => $options,
            '#default_value' => array_keys($options)[0] ?? NULL,
            '#empty_option' => $this->t('Ignore'),
          ];
        }
      }
    }

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#submit' => ['::submitSettingsPreviewStep'],
      '#value' => $this->t('Next step'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitSettingsPreviewStep(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);

    // Make the provisioning object.
    $dnp_provisioning_settings = $this->dnpProvisioningService->getDnpProvisioningSettings();
    if (!$dnp_provisioning_settings) {
      $this->messenger()->addError($this->t('No DNP provisioning settings found.'));
      return;
    }

    $previous_dnp_provisioning = $form_state->getValue('previous_dnp_provisioning');
    if ($previous_dnp_provisioning) {
      /** @var \Drupal\simple_school_reports_dnp_support\DnpProvisioningInterface|null $previous_dnp_provisioning */
      $previous_dnp_provisioning = $this->entityTypeManager->getStorage('dnp_provisioning')->load($previous_dnp_provisioning);
      if ($previous_dnp_provisioning) {
        $dnp_provisioning_settings->setLastProvisioningData($previous_dnp_provisioning->parseSrc());
      }
    }

    /** @var \Drupal\simple_school_reports_dnp_support\DnpProvisioningInterface $dnp_provisioning */
    $dnp_provisioning = $this->entityTypeManager->getStorage('dnp_provisioning')->create([
      'langcode' => 'sv',
    ]);
    $dnp_provisioning->createSrcData($dnp_provisioning_settings);
    $form_state->set('dnp_provisioning', $dnp_provisioning);
    $form_state->set('step', 'dnp_provisioning_preview');
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildDnpProvisioningPreviewStep(array $form, FormStateInterface $form_state): array {
    /** @var \Drupal\simple_school_reports_dnp_support\DnpProvisioningInterface|null $dnp_provisioning */
    $dnp_provisioning = $form_state->get('dnp_provisioning');
    if (!$dnp_provisioning) {
      $this->messenger()->addError($this->t('Something went wrong. Try again.'));
      return $form;
    }

    $form['label'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t('DNP provisioning settings preview'),
    ];
    $form['preview_wrapper'] = [
      '#type' => 'container',
    ];

    $view_builder = $this->entityTypeManager->getViewBuilder('dnp_provisioning');
    $form['preview_wrapper']['preview'] = $view_builder->view($dnp_provisioning);

    $sheets = [
      DnpProvisioningConstantsInterface::DNP_CLASSES_SHEET => $this->t('Classes'),
      DnpProvisioningConstantsInterface::DNP_SUBJECT_GROUPS_SHEET => $this->t('Subject groups'),
      DnpProvisioningConstantsInterface::DNP_STUDENTS_SHEET => $this->t('Students'),
      DnpProvisioningConstantsInterface::DNP_STAFF_SHEET => $this->t('Staff'),
    ];

    foreach ($sheets as $sheet => $label) {
      $form['table_data'][$sheet] = [
        '#type' => 'details',
        '#title' => $label,
        '#open' => FALSE,
      ];
      $form['table_data'][$sheet]['table'] = $dnp_provisioning->getTableRenderArray($sheet);
    }

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $dnp_provisioning = $form_state->get('dnp_provisioning');
    if (!$dnp_provisioning) {
      $this->messenger()->addError($this->t('Something went wrong. Try again.'));
      return;
    }
    $dnp_provisioning->save();
    $this->messenger()->addStatus($this->t('DNP provisioning created.'));
    $form_state->setRedirect('view.dnp_provisioning.list');
  }

}
