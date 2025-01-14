<?php

namespace Drupal\views_custom_permissions\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration Form for Views Custom Permissions settings.
 */
class AddPermissionsForm extends ConfigFormBase {

  /** 
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'views_custom_permissions.settings';

  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'views_custom_permissions';
  }

  /** 
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /** 
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('views_custom_permissions.settings');
     
    $header = [
      $this->t('Permission(Title)'),
      $this->t('Callback(Access Callback Fuction Name)')
    ];
    array_push($header, $this->t('Operation'), $this->t('Weight'));
    $form['vcp_table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#empty' => $this->t('Please add items', []),
      '#prefix' => '<div id="custom-permissions-wrapper">',
      '#suffix' => '</div>',
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'vcp-order-weight',
        ],
      ],
    ];    
    
    $vcp_table = $form_state->get('vcp_table');
    if (empty($vcp_table)) {      
      if (NULL !== $config->get('vcp_table')) {
        $vcp_table = $config->get('vcp_table');
        $form_state->set('vcp_table', $vcp_table);
      }
      else {
        $vcp_table = [''];
        $form_state->set('vcp_table', $vcp_table);
      }
    }   

    foreach ($vcp_table as $i => $value) {
      $form['vcp_table'][$i]['#attributes']['class'][] = 'draggable';
      $form['vcp_table'][$i]['title'] = [
        '#type' => 'textfield',        
        '#default_value' => isset($value['title']) ? $value['title'] : [],
        '#maxlength' => 50
      ];
      $form['vcp_table'][$i]['callback'] = [
        '#type' => 'textfield',        
        '#default_value' => isset($value['callback']) ? $value['callback'] : [],
        '#maxlength' => 50
      ];
      $form['vcp_table'][$i]['remove'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove'),
        '#name' => "remove-" . $i,
        '#submit' => ['::removeCallback'],
        '#limit_validation_errors' => [],
        '#ajax' => [
          'callback' => '::addmoreCallback',
          'wrapper' => 'custom-permissions-wrapper',
        ],
        '#index_position' => $i,
      ];
      
      $form['vcp_table'][$i]['weight'] = [
        '#type' => 'weight',
        '#title_display' => 'invisible',
        '#default_value' => isset($value['weight']) ? $value['weight'] : [],
        '#attributes' => ['class' => ['vcp-order-weight']],
      ];
    }
    
    $form['addmore'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add one more'),
      '#submit' => ['::addMore'],
      '#ajax' => [
        'callback' => '::addmoreCallback',
        'wrapper' => 'custom-permissions-wrapper',
      ],
    ];
    
   

    return parent::buildForm($form, $form_state);
  }

  public function addmoreCallback(array &$form, FormStateInterface $form_state) {
    return $form['vcp_table'];
  }
  
  public function addMore(array &$form, FormStateInterface $form_state) {
    $vcp_table = $form_state->get('vcp_table');
    array_push($vcp_table, "");
    $form_state->set('vcp_table', $vcp_table);
    $form_state->setRebuild();
  }

  
  public function removeCallback(array &$form, FormStateInterface $form_state) {   
    $vcp_table = $form_state->get('vcp_table');
    $remove = key($form_state->getValue('vcp_table'));    
    unset($vcp_table[$remove]);
    if (empty($vcp_table)) {
      array_push($vcp_table, "");
    }
    $form_state->set('vcp_table', $vcp_table);
    $form_state->setRebuild();
  }  

   /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {    
    $this->config('views_custom_permissions.settings')
      ->set('vcp_table', $form_state->getValue('vcp_table'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
