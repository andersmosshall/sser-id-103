<?php

namespace Drupal\simple_school_reports_core\Plugin\views\sort;

use Drupal\views\Plugin\views\sort\SortPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handler for invalid absence student sort.
 *
 * @ViewsSort("invalid_absence_student")
 */
class InvalidAbsenceStudentSort extends SortPluginBase {

  /**
   * Called to add the field to a query.
   */
  public function query() {
    $this->ensureMyTable();
    $this->query->addOrderBy(NULL, NULL, $this->options['order'], 'cia');
  }

  public function adminLabel($short = FALSE) {
    return $this->t('Invalid absence student sort');
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $cache_contexts = [];
    $cache_contexts[] = 'url.query_args:sort_by';
    return $cache_contexts;
  }

}
