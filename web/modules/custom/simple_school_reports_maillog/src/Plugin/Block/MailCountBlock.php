<?php

namespace Drupal\simple_school_reports_maillog\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a MailCountBlock for the current student tab.
 *
 * @Block(
 *  id = "mail_cont_block",
 *  admin_label = @Translation("Mail count"),
 * )
 */
class MailCountBlock extends BlockBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  public function __construct(
    array $configuration,
          $plugin_id,
          $plugin_definition,
    protected Connection $connection,
    protected RequestStack $requestStack,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('request_stack'),
    );
  }

  protected function formatNumber(int|string $input): string {
    return number_format((float) $input, 0, ',', ' ');
  }

  protected function currentRequest() {
    return $this->requestStack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $cache = new CacheableMetadata();
    $cache->addCacheContexts(['route', 'current_day', 'url.query_args:from', 'url.query_args:to']);
    $cache->addCacheTags(['ssr_mail_count_list']);
    $build = [
      '#markup' => '<em>' . $this->t('No data available.') . '</em>',
    ];

    $this_to = new \DateTime();
    $this_to->setTime(23, 59, 59);

    $from = $this->currentRequest()->get('from');
    $to = min($this->currentRequest()->get('to'), $this_to->getTimestamp());

    if (!$from || !$to || $from > $to) {
      $cache->applyTo($build);
      return $build;
    }

    // Allow max 2 years of data.
    if ($to - $from > 86400 * 365 * 2) {
      $cache->applyTo($build);
      return $build;
    }

    $per_day_data = [];

    $results = $this->connection->select('ssr_mail_count', 'mc')
      ->condition('from', $from, '>=')
      ->condition('to', $to, '<=')
      ->fields('mc', ['from', 'sent', 'failed', 'simulated'])
      ->execute();

    foreach ($results as $result) {
      $day = new \DateTime();
      $day->setTimestamp($result->from);
      $per_day_data[$day->format('Y-m-d')] = [
        'sent' => $result->sent ?? 0,
        'failed' => $result->failed ?? 0,
        'simulated' => $result->simulated ?? 0,
      ];
    }

    $headers = [
      'week' => $this->t('Week'),
      1 => $this->t('Monday'),
      2 => $this->t('Tuesday'),
      3 => $this->t('Wednesday'),
      4 => $this->t('Thursday'),
      5 => $this->t('Friday'),
      6 => $this->t('Saturday'),
      7 => $this->t('Sunday'),
    ];

    $use_weekend = FALSE;

    $from_time_object = new \DateTime();
    $from_time_object->setTimestamp($from);

    $to_time_object = new \DateTime();
    $to_time_object->setTimestamp($to);

    $rows = [];
    $current_day = $from_time_object;

    $safe_break = 0;

    $total_sent = 0;
    $total_failed = 0;
    $total_simulated = 0;

    while ($current_day <= $to_time_object || $safe_break > 1080) {
      $day = (int) $current_day->format('N');
      $year = $current_day->format('Y');
      $week_number = $current_day->format('W');
      $row_key = $year . '-' . $week_number;

      if (empty($rows[$row_key])) {
        $rows[$row_key] = [
          'week' => $week_number,
          1 => [],
          2 => [],
          3 => [],
          4 => [],
          5 => [],
          6 => [],
          7 => [],
        ];
      }

      $day_stat_classes = 'mail-count';
      $day_stat_value = '';

      $data = $per_day_data[$current_day->format('Y-m-d')] ?? [];

      $data['sent'] = $data['sent'] ?? 0;
      $data['failed'] = $data['failed'] ?? 0;
      $data['simulated'] = $data['simulated'] ?? 0;

      $total_sent += $data['sent'];
      $total_failed += $data['failed'];
      $total_simulated += $data['simulated'];

      $data['total'] = $data['sent'] + $data['failed'] + $data['simulated'];
      if ($data['total'] <= 0) {
        $day_stat_classes .= ' no-data';
      }
      else {
        if ($day === 6 || $day === 7) {
          $use_weekend = TRUE;
        }

        $day_stat_class = 'mail-count--ok';
        if ($data['sent'] + $data['failed'] > 300) {
          $day_stat_class = 'mail-count--warning';
        }
        if ($data['sent'] + $data['failed'] > 480) {
          $day_stat_class = 'mail-count--danger';
        }
        $day_stat_classes .= ' ' . $day_stat_class;

        $day_stat_value = $this->formatNumber($data['sent']) . '/' . $this->formatNumber($data['failed']) . '/' . $this->formatNumber($data['simulated']);
        $day_stat_value = str_replace(' %', ' %<br>', $day_stat_value);
      }

      $rows[$row_key][$day]['data']['day_label'] = [
        '#markup' => '<div><strong>' . $current_day->format('j/n') . '</strong></div>',
      ];

      $rows[$row_key][$day]['data']['day_stats'] = [
        '#markup' => '<div class="' . $day_stat_classes . '">' . $day_stat_value . '</div>',
      ];

      $current_day->modify('+1 day');
      $safe_break++;
    }

    ksort($rows);

    if (!$use_weekend) {
      unset($headers[6]);
      unset($headers[7]);
      foreach ($rows as &$row) {
        unset($row[6]);
        unset($row[7]);
      }
    }

    if (empty($rows)) {
      $cache->applyTo($build);
      return $build;
    }

    $build = [];

    $build['totals'] = [
      '#type' => 'html_tag',
      '#tag' => 'strong',
      '#value' => $this->t('Total: @sent sent, @failed failed, @simulated simulated', [
        '@sent' => $this->formatNumber($total_sent),
        '@failed' => $this->formatNumber($total_failed),
        '@simulated' => $this->formatNumber($total_simulated),
      ]),
    ];

    $build['stat_wrapper'] = [
      '#type' => 'details',
      '#title' => $this->t('Mail counts per day'),
      '#open' => TRUE,
    ];

    $build['stat_wrapper']['table'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#attributes' => [
        'class' => ['stats-day-table'],
      ],
    ];

    $build['stat_wrapper']['not_current_grade_info'] = [
      '#type' => 'html_tag',
      '#tag' => 'em',
      '#value' => $this->t('Data is presented as Sent/Failed/Simulated'),
    ];

    $build['#attached']['library'][] = 'simple_school_reports_maillog/mail_count';
    $build['#attributes']['class'][] = 'mail-count-block';

    $cache->applyTo($build);
    return $build;
  }

  public function getCacheTags() {
    return Cache::mergeTags(['ssr_mail_count_list'], parent::getCacheTags());
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeTags(['route', 'current_day', 'url.query_args:from', 'url.query_args:to'], parent::getCacheContexts());
  }

  /**
   * {@inheritdoc}
   */
  public function blockAccess(AccountInterface $account) {
    return ssr_views_permission_maillog_active($account);
  }

}
