<?php

namespace Drupal\simple_school_reports_core\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\State\StateInterface;
use Drupal\simple_school_reports_core\Service\TermService;
use Symfony\Component\DependencyInjection\ContainerInterface;


class InvoiceSupportForm extends FormBase {

  const EXTRA_MAIL_FEE = 50;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected StateInterface $state,
    protected TermService $termService,
    protected Connection $connection,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('state'),
      $container->get('simple_school_reports_core.term_service'),
      $container->get('database'),
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
    $use_module_sum_only = $invoice_settings['module_sum_only'] ?? FALSE;
    $module_sum_label = $invoice_settings['module_sum_label'] ?? 'Årsavgift SSR med tillägg';
    $mail_count_extra = $invoice_settings['mail_count_extra'] ?? 'last_school_year';
    $extra_mail_count_batch = (int) ($invoice_settings['extra_mail_count_batch'] ?? 1000);
    $discount = (int) ($invoice_settings['discount'] ?? 0);

    $module_info_ids = $this->entityTypeManager->getStorage('ssr_module_info')
      ->getQuery()
      ->sort('weight')
      ->accessCheck(FALSE)
      ->execute();

    $invoice_items = [];

    $module_sum = 0;

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

        $module_sum += $fee;

        $label = $use_monthly_fee
          ? $this->t('Monthly fee: @label', ['@label' => $module_info->label()])
          : $this->t('Annual fee: @label', ['@label' => $module_info->label()]);

        $invoice_items[$module_info->id()] = [
          'label' => $label,
          'items' => 1,
          'value' => $fee,
        ];
      }
    }


    if (!empty($invoice_items) && $use_module_sum_only) {
      $invoice_items = [];

      $label = $module_sum_label ?? $this->t('Module sum');

      $invoice_items[] = [
        'label' => $label,
        'items' => 1,
        'value' => $module_sum,
      ];
    }


    // Calculate the mail count fee.
    $mail_count_from = 0;
    $mail_count_to = 0;
    $mail_count_label_suffix = '';

    if ($mail_count_extra === 'this_month') {
      $mail_count_from = strtotime('first day of this month at 00:00:00');
      $mail_count_to = strtotime('last day of this month at 23:59:59');
      $mail_count_label_suffix = ' ' . date('M', $mail_count_from);
    }
    elseif ($mail_count_extra === 'last_month') {
      $mail_count_from = strtotime('first day of last month at 00:00:00');
      $mail_count_to = strtotime('last day of last month at 23:59:59');
      $mail_count_label_suffix = ' ' . date('M', $mail_count_from);
    }
    elseif ($mail_count_extra === 'this_school_year' || $mail_count_extra === 'last_school_year') {
      $this_school_year_start = $this->termService->getDefaultSchoolYearStart();
      $this_school_year_end = $this->termService->getDefaultSchoolYearEnd();

      if ($mail_count_extra === 'last_school_year') {
        $this_school_year_start->sub(new \DateInterval('P1Y'));
        $this_school_year_end->sub(new \DateInterval('P1Y'));
      }

      $mail_count_from = $this_school_year_start->getTimestamp();
      $mail_count_to = $this_school_year_end->getTimestamp();
      $mail_count_label_suffix = ' ' . date('y', $mail_count_from) . '/' . date('y', $mail_count_to);
    }

    if ($mail_count_from > 0 && $mail_count_to > 0) {
      $results = $this->connection->select('ssr_mail_count', 'mc')
        ->condition('from', $mail_count_from, '>=')
        ->condition('to', $mail_count_to, '<=')
        ->fields('mc', ['from', 'sent'])
        ->execute();

      $sent_count_per_month = [];
      foreach ($results as $result) {
        $month = new \DateTime();
        $month->setTimestamp($result->from);
        $month_string = $month->format('Y-m');

        if (!isset($sent_count_per_month[$month_string])) {
          $sent_count_per_month[$month_string] = [
            'month' => $this->t($month->format('M')),
            'sent' => 0,
          ];
        }

        $sent_count_per_month[$month_string]['sent'] += $result->sent ?? 0;
      }

      // Add current day if part of the intervall.
      $now = new \DateTime();

      if ($now->getTimestamp() >= $mail_count_from && $now->getTimestamp() <= $mail_count_to) {
        $current_day_string = $now->format('Y-m-d');
        $current_day_month_string = $now->format('Y-m');

        $current_day_data = $this->state->get('simple_school_reports_maillog.mailcount', [])[$current_day_string] ?? [];

        if (!isset($sent_count_per_month[$current_day_month_string])) {
          $sent_count_per_month[$current_day_month_string] = [
            'month' => $this->t($now->format('M')),
            'sent' => 0,
          ];
        }

        $sent_count_per_month[$current_day_month_string]['sent'] += $current_day_data['sent'] ?? 0;
      }

      $mails_included = Settings::get('mails_included', 1040);
      $extra_batches = 0;
      $extra_batches_months = [];
      foreach ($sent_count_per_month as $month => $count_data) {
        $sent = $count_data['sent'] ?? 0;
        // Remove the included mail count.
        $sent -= $mails_included;

        if ($sent <= 0) {
          continue;
        }

        $extra_mail_count_batch = abs($extra_mail_count_batch);
        if ($extra_mail_count_batch <= 0) {
          $extra_mail_count_batch = 1;
        }

        $this_extra_batches = ceil($sent / $extra_mail_count_batch);
        $extra_batches += $this_extra_batches;
        $month_name = $count_data['month'] ?? NULL;
        if ($month_name) {
          $extra_batches_months[] = $month_name . ' (' . $this_extra_batches . ')';
        }
      }

      if ($extra_batches > 0) {

        $suffix = $mail_count_label_suffix;
        if (!empty($extra_batches_months)) {
          if ($suffix) {
            $suffix .= '<br>';
          }
          $suffix .= '<small>' . implode(', ', $extra_batches_months) . '</small>';
        }
        $invoice_items[] = [
          'label' => $this->t('Extra messages') . $suffix,
          'value' => self::EXTRA_MAIL_FEE,
          'items' => $extra_batches,
        ];

      }
    }

    if ($discount > 0) {
      $discount = min(abs($discount), $module_sum);
      $discount = max(0, $discount);
      $invoice_items[] = [
        'label' => $this->t('Discount'),
        'value' => $discount * -1,
        'items' => 1,
      ];
    }

    $form['invoice_items'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Item'),
        $this->t('Count'),
        $this->t('á Fee'),
        $this->t('Fee'),
      ],
    ];

    $total_sum = 0;
    foreach ($invoice_items as $id => $item) {
      $value = $item['value'] ?? 0;
      if ($value === 0) {
        continue;
      }
      $items = $item['items'] ?? 1;
      $total_sum += $value * $items;

      $fee_per_item = number_format($value, 2, ',', ' ');
      $fee = number_format($value * $items, 2, ',', ' ');
      $form['invoice_items'][$id] = [
        'label' => [
          '#markup' => $item['label'] ?? '',
        ],
        'items' => [
          '#markup' => $items,
        ],
        'fee_per_item' => [
          '#markup' => $fee_per_item,
        ],
        'fee' => [
          '#markup' => $fee,
        ],
      ];
    }

    // Add total sum.
    $form['invoice_items'][] = [
      'label' => [
        '#markup' => $this->t('Sum', [], ['context' => 'invoice']),
      ],
      'items' => [
        '#markup' => '',
      ],
      'fee_per_item' => [
        '#markup' => '',
      ],
      'fee' => [
        '#markup' => number_format($total_sum, 2, ',', ' '),
      ],
    ];

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

    $form['invoice_settings']['module_sum_only'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use only sum for modules'),
      '#default_value' => $use_module_sum_only,
    ];

    $form['invoice_settings']['module_sum_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Module sum label'),
      '#default_value' => $module_sum_label,
      '#states' => [
        'visible' => [
          ':input[name="module_sum_only"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $mail_count_extra_options = [
      'no_fee' => $this->t('No mail count fee'),
      'last_school_year' => $this->t('Last school year'),
      'last_month' => $this->t('Last month'),
      'this_school_year' => $this->t('This school year'),
      'this_month' => $this->t('This month'),
    ];
    $form['invoice_settings']['mail_count_extra'] = [
      '#type' => 'select',
      '#title' => $this->t('Mail count fee'),
      '#options' => $mail_count_extra_options,
      '#default_value' => $mail_count_extra,
    ];
    $form['invoice_settings']['extra_mail_count_batch'] = [
      '#type' => 'number',
      '#title' => $this->t('Extra mail count batch size'),
      '#default_value' => $extra_mail_count_batch,
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
      'module_sum_only' => !!$form_state->getValue('module_sum_only'),
      'module_sum_label' => $form_state->getValue('module_sum_label'),
      'discount' => $form_state->getValue('discount'),
      'mail_count_extra' => $form_state->getValue('mail_count_extra'),
      'extra_mail_count_batch' => $form_state->getValue('extra_mail_count_batch'),
    ];
    $this->state->set('ssr_invoice_settings', $invoice_settings);
  }

}
