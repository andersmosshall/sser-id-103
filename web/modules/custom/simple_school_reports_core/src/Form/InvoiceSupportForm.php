<?php

namespace Drupal\simple_school_reports_core\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


class InvoiceSupportForm extends FormBase {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected StateInterface $state,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('state'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ssr_invoice_support';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $invoice_settings = $this->state->get('ssr_invoice_settings', []);

    $use_monthly_fee = $invoice_settings['monthly_fee'] ?? FALSE;
    $use_sum_only = $invoice_settings['sum_only'] ?? FALSE;
    $sum_label = $invoice_settings['sum_label'] ?? 'Årsavgift SSR med tillägg';
    $discount = (int) ($invoice_settings['discount'] ?? 0);

    $module_info_ids = $this->entityTypeManager->getStorage('ssr_module_info')
      ->getQuery()
      ->sort('weight')
      ->accessCheck(FALSE)
      ->execute();

    $invoice_items = [];

    $sum = 0;

    if (!empty($module_info_ids)) {
      /** @var \Drupal\simple_school_reports_module_info\ModuleInfoInterface $module_info */
      foreach ($this->entityTypeManager->getStorage('ssr_module_info')->loadMultiple($module_info_ids) as $module_info) {
        if (!$module_info->get('enabled')->value) {
          continue;
        }
        $fee = $module_info->get('annual_fee')->value ?? '0';

        // Parse the fee.
        $fee = str_replace(' ', '', $fee);
        $fee = str_replace(',', '.', $fee);
        // Remove all after "kr" or "sek" if it exists ignore case.
        $fee = preg_replace('/kr.*/i', '', $fee);
        $fee = preg_replace('/sek.*/i', '', $fee);
        $fee = (int) $fee;

        if ($fee <= 0) {
          continue;
        }
        if ($use_monthly_fee) {
          $fee = round($fee / 12);
        }

        $sum += $fee;

        $label = $use_monthly_fee
          ? $this->t('Monthly fee: @label', ['@label' => $module_info->label()])
          : $this->t('Annual fee: @label', ['@label' => $module_info->label()]);

        $invoice_items[$module_info->id()] = [
          'label' => $label,
          'fee' => number_format($fee, 2, ',', ' '),
        ];
      }
    }

    if ($discount > 0) {
      $discount = min($discount, $sum);
      $invoice_items[] = [
        'label' => $this->t('Discount'),
        'fee' => number_format($discount * -1, 2, ',', ' '),
      ];
      $sum = $sum - $discount;
    }

    if (!empty($invoice_items)) {
      if ($use_sum_only) {
        $invoice_items = [];
      }

      $label = $use_sum_only ? $sum_label : $this->t('Sum', [], ['context' => 'invoice']);

      $invoice_items[] = [
        'label' => $label,
        'fee' => number_format($sum, 2, ',', ' '),
      ];
    }

    $form['invoice_items'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Item'),
        $this->t('Fee'),
      ],
    ];

    foreach ($invoice_items as $id => $item) {
      $form['invoice_items'][$id] = [
        'label' => [
          '#markup' => $item['label'],
        ],
        'fee' => [
          '#markup' => $item['fee'],
        ],
      ];
    }

    $form['invoice_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Invoice settings'),
      '#open' => FALSE,
    ];

    $form['invoice_settings']['monthly_fee'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Monthly fee'),
      '#default_value' => $use_monthly_fee,
    ];

    $form['invoice_settings']['sum_only'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use only sum'),
      '#default_value' => $use_sum_only,
    ];

    $form['invoice_settings']['sum_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sum label'),
      '#default_value' => $sum_label,
      '#states' => [
        'visible' => [
          ':input[name="sum_only"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['invoice_settings']['discount'] = [
      '#type' => 'number',
      '#title' => $this->t('Discount'),
      '#default_value' => $discount,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Refresh'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $invoice_settings = [
      'monthly_fee' => !!$form_state->getValue('monthly_fee'),
      'sum_only' => !!$form_state->getValue('sum_only'),
      'sum_label' => $form_state->getValue('sum_label'),
      'discount' => $form_state->getValue('discount'),
    ];
    $this->state->set('ssr_invoice_settings', $invoice_settings);
  }

}
