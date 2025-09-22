<?php

namespace Drupal\simple_school_reports_list_templates\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\simple_school_reports_core\Service\NodeCloneServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a confirmation form for duplicate list template.
 */
class DuplicateListTemplateForm extends ConfirmFormBase {


  /**
   * @var \Drupal\simple_school_reports_core\Service\NodeCloneServiceInterface
   */
  protected $cloneService;

  /**
   * @var NodeInterface
   */
  protected $listTemplate;

  public function __construct(NodeCloneServiceInterface $clone_service) {
    $this->cloneService = $clone_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('simple_school_reports_core.node_clone'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'clone_list_template_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $name = $this->listTemplate ? $this->listTemplate->label() : '';

    return $this->t('Duplicate list template @name', ['@name' => $name]);
  }

  public function getCancelRoute() {
    return 'view.list_template.list';
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
    return $this->t('Duplicate', [], ['context' => 'copy']);
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
  public function buildForm(array $form, FormStateInterface $form_state, ?NodeInterface $node = NULL) {
    if (!$node || $node->bundle() !== 'list_template') {
      throw new AccessDeniedHttpException();
    }

    $this->listTemplate = $node;

    $form['list_template'] = [
      '#type' => 'value',
      '#value' => $node->id(),
    ];

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name of new list template'),
      '#default_value' => '',
      '#required' => TRUE,
    ];

    $form = parent::buildForm($form, $form_state);

    $form['#title'] = $this->t('Duplicate list template @name', ['@name' => $node->label()]);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    try {
      $original = $this->listTemplate;

      if (!$original) {
        throw new \RuntimeException();
      }

      $label = $form_state->getValue('label', 'Untitled');

      $fields = [
        'field_mentoring_students',
        'field_mark_absence',
        'field_sorting',
        'field_show_checkbox',
        'field_grades',
      ];
      $reference_fields = [
        'field_list_template_field' => [
          'fields' => [
            'field_label',
            'field_field_type',
            'field_size',
          ],
        ],
      ];


      $new_node = $this->cloneService->clone($original, $label, $fields, $reference_fields);

      $this->messenger()->addStatus($this->t('@type %title has been created.', ['@type' => $this->t('List template'), '%title' => $label]));
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Something went wrong'));
    }
  }
}
