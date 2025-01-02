<?php

namespace Drupal\simple_school_reports_budget\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Render\Element\Html;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\simple_school_reports_core\Service\UserMetaDataServiceInterface;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class TermService
 *
 * @package Drupal\simple_school_reports_core\Service
 */
class BudgetService implements BudgetServiceInterface {

  /**
   * @var \Drupal\simple_school_reports_core\Service\UserMetaDataServiceInterface
   */
  protected $userMetaData;

  /**
   * @param \Drupal\simple_school_reports_core\Service\UserMetaDataServiceInterface $user_meta_data
   */
  public function __construct(
    UserMetaDataServiceInterface $user_meta_data
  ) {
    $this->userMetaData = $user_meta_data;
  }

  use StringTranslationTrait;

  public function getRowTypeDefinitions(bool $labels_only = FALSE): array {
    $row_definitions = [
      'per_student' => $this->t('Per student'),
      'annual_worker' => $this->t('Per student depending of annual worker'),
      'per_unit' => $this->t('Per unit'),
    ];

    if (!$labels_only) {
      foreach ($row_definitions as $row_type => &$row_definition) {
        $label = $row_definition;
        $row_definition = [];
        $row_definition['label'] = $label;
        $row_definition['fields'] = $this->getFields($row_type);
      }
    }

    return $row_definitions;
  }

  protected function getFields(string $row_type): array {
    $fields = [
      'field_evaluatable',
    ];
    switch ($row_type) {
      case 'per_student':
      case 'annual_worker':
        $fields[] = 'field_age_limit_from';
        $fields[] = 'field_age_limit_to';
        if ($row_type === 'annual_worker') {
          $fields[] = 'field_annual_worker';
          $fields[] = 'field_mean_salary';
        }
        else {
          $fields[] = 'field_value';
        }
        break;
      case 'per_unit':
        $fields[] = 'field_value';
        break;
      default:
        $fields = [];
    }
    return $fields;
  }

  public function budgetRowFormAlter(&$form, FormStateInterface $formState, $delta) {
    $row_type_definitions = $this->getRowTypeDefinitions();
    $fields_to_states = [];

    foreach ($row_type_definitions as $row_type => $definitions) {
      if (!empty($definitions['fields'])) {
        foreach ($definitions['fields'] as $field) {
          if (isset($form[$field])) {
            $fields_to_states[$field][] = $row_type;
          }
        }
      }
    }

    foreach ($fields_to_states as $field => $row_types) {
      foreach ($row_types as $row_type) {
        $form[$field]['#states']['visible'][] = [
          ':input[name="field_budget_row[' . $delta . '][subform][field_row_type]"]' => [
            'value' => $row_type,
          ],
        ];
      }
    }
  }

  public function buildBudgetTable(NodeInterface $budget, array &$form, ?FormStateInterface $form_state = NULL) {
    if ($budget->bundle() !== 'budget') {
      return;
    }

    $is_form = $form_state !== NULL;
    $show_evaluate = FALSE;

    $paragraphs = $budget->get('field_budget_row')->referencedEntities();
    /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
    foreach ($paragraphs as $paragraph) {
      if ($paragraph->hasField('field_evaluatable') && $paragraph->get('field_evaluatable')->value && $paragraph->get('field_real_value')->value) {
        $show_evaluate = TRUE;
        break;
      }
    }

    $header = [
      'name' => $this->t('Name'),
      'remark' => $this->t('Remark'),
      'sum' => $this->t('Sum'),
      'real_sum' => $this->t('Evaluated sum'),
      'result' => $this->t('Result'),
    ];

    if (!($is_form || $show_evaluate)) {
      unset($header['real_sum']);
      unset($header['result']);
    }

    $form['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#empty' => $this->t('No budget rows to show'),
      '#tree' => TRUE,
    ];

    foreach ($this->getBudgetTableRows($budget, $form_state) as $row_key => $row) {
      $form['table'][$row_key] = $row;
    }

    $form['#attached']['library'][] = 'simple_school_reports_budget/table';
    $form['#attributes']['class'][] = 'budget-table';
    $form['#type'] = 'container';

  }

  protected function getBudgetTableRows(NodeInterface $budget, ?FormStateInterface $form_state = NULL) {
    $rows = [];

    $is_form = $form_state !== NULL;




    if ($is_form && $form_state->get('budget_rows')) {
      return $form_state->get('budget_rows');
    }

    $total_sum = 0;
    $part_sum = 0;

    $total_real_sum = 0;
    $part_real_sum = 0;

    $students_count_map = $this->userMetaData->getAgeGroupsFromBudgetNode($budget)['ages'] ?? [];
    $total_students = $students_count_map['total'] ?? 0;


    $paragraphs = $budget->get('field_budget_row')->referencedEntities();

    $show_evaluate = FALSE;

    /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
    foreach ($paragraphs as $paragraph) {
      if ($paragraph->hasField('field_evaluatable') && $paragraph->get('field_evaluatable')->value && $paragraph->get('field_real_value')->value) {
        $show_evaluate = TRUE;
        break;
      }
    }


    /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
    foreach ($paragraphs as $paragraph) {
      $label = [];
      $remark = [];

      $row = [];

      if ($paragraph->hasField('field_label') && $paragraph->get('field_label')->value) {
        $label = [
          '#markup' => $paragraph->get('field_label')->value,
        ];
      }

      if ($paragraph->bundle() === 'budget_row_label') {
        $label['#prefix'] = '<h4>';
        $label['#suffix'] = '</h4>';

        $row = [
          'name' => [
            'label' => $label,
            '#attributes' => [
              'colspan' => $show_evaluate || $is_form ? 5 : 3,
            ],
          ],
        ];
      }

      if ($paragraph->bundle() === 'budget_sum') {
        $sum_value = '__TOTALSUM__';
        $is_part = $paragraph->get('field_sum_type')->value === 'part';
        if ($is_part) {
          $sum_value = round($part_sum, 2);
          $sum_value = number_format($sum_value, 2, ',', ' ');

          $real_sum_value = round($part_real_sum != 0 ? ($part_real_sum * -1) : 0, 2);
          $real_sum_value = number_format($real_sum_value, 2, ',', ' ');

          $label = [
            '#markup' => $this->t('Part sum'),
          ];
        }
        else {
          $label = [
            '#markup' => $this->t('Sum'),
          ];
        }

        $label['#prefix'] = '<strong>';
        $label['#suffix'] = '</strong>';

        $row = [
          'name' => [
            'label' => $label,
            '#attributes' => [
              'colspan' => 2,
            ],
          ],
          'sum' => [
            '#prefix' => '<strong>',
            '#suffix' => '</strong>',
            '#markup' => $sum_value,
          ],
          'real_sum' => [
            '#prefix' => '<strong>',
            '#suffix' => '</strong>',
            '#markup' => $is_part ? $real_sum_value : '',
          ],
          'result' => $is_part ? $this->getResultCol($part_sum, $part_real_sum) : [],
        ];
        if ($is_part) {
          $part_sum = 0;
          $part_real_sum = 0;
        }
      }


      if ($paragraph->bundle() === 'budget_row') {
        $row_type = $paragraph->get('field_row_type')->value;
        $factor = $paragraph->get('field_expense')->value ? -1 : 1;
        $is_expense = $paragraph->get('field_expense')->value;

        $value = $paragraph->get('field_value')->value ?? 0;


        if ($row_type === 'per_student') {
          $remark_value = $factor * $value;

          $remark = [
            '#markup' => number_format($remark_value, 2, ',', ' ') . ' ' . $this->t('kr/student'),
          ];
        }
        if ($row_type === 'annual_worker') {
          $annual_worker_value = $paragraph->get('field_annual_worker')->value ?? 0;
          $mean_salary = $paragraph->get('field_mean_salary')->value ?? 0;
          $mean_salary = number_format($mean_salary, 2, ',', ' ');
          $remark_value = $factor * $annual_worker_value;

          $remark = [
            '#markup' => $this->t('Mean salary') . ': ' . $mean_salary . ' x ' . $remark_value . ' ' . $this->t('annual worker/student'),
          ];
        }
        if ($row_type === 'per_unit') {
          $remark_value = $factor * $value;
          $remark = [
            '#markup' => number_format($remark_value, 2, ',', ' ') . ' ' . $this->t('kr/unit'),
          ];
        }


        if ($row_type === 'per_student' || $row_type === 'annual_worker') {
          $age_from = $paragraph->get('field_age_limit_from')->value ?? '';
          $age_to = $paragraph->get('field_age_limit_to')->value ?? '';

          $key = NULL;

          if ($age_from || $age_to) {
            if ($age_from && $age_to) {
              $key = $age_from . '-' . $age_to;
            }
            elseif ($age_from) {
              $key = '>=' . $age_from;
            }
            elseif ($age_to) {
              $key = '<=' . $age_to;
            }
          }

          if ($key) {
            $label['#markup'] = '<strong>' . $key . ' ' . $this->t('year') . '</strong> ' . $label['#markup'];
            if (!empty($students_count_map[$key])) {
              $factor *= $students_count_map[$key];
            }
            else {
              $factor = 0;
            }
          }
          else {
            $factor *= $total_students;
          }
        }

        $sum_value = $factor * $value;
        if ($row_type === 'annual_worker') {
          $annual_worker_value = $paragraph->get('field_annual_worker')->value ?? 0;
          $mean_salary = $paragraph->get('field_mean_salary')->value ?? 0;
          $sum_value = $factor * $annual_worker_value * $mean_salary;
        }

        $total_sum += $sum_value;
        $part_sum += $sum_value;

        $result = [
          '#markup' => '',
        ];
        $real_sum = [
          '#markup' => '',
        ];

        if ($paragraph->get('field_evaluatable')->value) {
          $real_sum_value = $paragraph->get('field_real_value')->value ?? 0;

          if (!$is_expense) {
            $total_real_sum += $real_sum_value;
            $part_real_sum += $real_sum_value;
          }
          else {
            $total_real_sum -= $real_sum_value;
            $part_real_sum -= $real_sum_value;
          }

          $result = $this->getResultCol($sum_value, $real_sum_value, $is_expense);

          if ($is_form) {
            $real_sum = [
              '#type' => 'number',
              '#min' => 0,
              '#max' => 99999999,
              '#default_value' => $real_sum_value ?? NULL,
              '#prefix' => '<div class="input-wrapper"><div class="prefix-sign">' . ($is_expense ? '+' : '-') .' </div>',
              '#suffix' => '</div>',
            ];
          }
          else {
            $real_sum_value = round($real_sum_value, 2);
            $real_sum_value = number_format($real_sum_value, 2, ',', ' ');

            $real_sum = [
              '#markup' => $real_sum_value,
            ];
          }

        }

        $sum_value = round($sum_value, 2);
        $sum_value = number_format($sum_value, 2, ',', ' ');

        $row = [
          'name' => $label,
          'remark' => $remark,
          'sum' => [
            '#markup' => $sum_value,
          ],
          'real_sum' => $real_sum,
          'result' => $result,
        ];

      }

      foreach ($row as $col_name => &$col_data) {
        $col_data['#type'] = $col_data['#type'] ?? 'container';
        $col_data['#attributes']['class'][] = 'col-wrapper';
        $col_data['#attributes']['class'][] = 'col--type--' . str_replace('_', '-', $paragraph->bundle());
        $col_data['#attributes']['class'][] = 'col--' . $col_name;
      }

      $rows[$paragraph->id()] = $row;
    }

    $sum_value = round($total_sum, 2);
    $sum_value = number_format($sum_value, 2, ',', ' ');

    $real_sum_value = round($total_real_sum != 0 ? ($total_real_sum * -1) : 0, 2);
    $real_sum_value = number_format($real_sum_value, 2, ',', ' ');

    // Insert total sum.
    foreach ($paragraphs as $paragraph) {
      if ($paragraph->bundle() === 'budget_sum') {
        if ($paragraph->get('field_sum_type')->value !== 'part') {
          $rows[$paragraph->id()]['sum']['#markup'] = $sum_value;
          $rows[$paragraph->id()]['real_sum']['#markup'] = $real_sum_value;
          $rows[$paragraph->id()]['result'] = $this->getResultCol($total_sum, $total_real_sum);
          $rows[$paragraph->id()]['result']['#attributes']['class'][] = 'col--type--budget-sum';

          $rows[$paragraph->id()]['result']['#prefix'] = '<strong>';
          $rows[$paragraph->id()]['result']['#suffix'] = '</strong>';
        }
      }

      if (!$show_evaluate && !$is_form) {
        unset($rows[$paragraph->id()]['real_sum']);
        unset($rows[$paragraph->id()]['result']);
      }
    }

    if ($is_form) {
      $form_state->set('budget_rows', $rows);
    }

    return $rows;
  }

  protected function getResultCol($sum, $real_sum, $is_expense = FALSE): array {

    if (!$is_expense) {
      $real_sum *= -1;
    }

    $result = $sum + $real_sum;
    $result = number_format($result, 2, ',', ' ');

    $result_class = $result >= 0 ? 'result--green' : 'result--red';

    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => [$result_class, 'col--result'],
      ],
      'value' => [
        '#markup' => $result,
      ],
    ];
  }

}
