<?php

namespace Drupal\simple_school_reports_grade_support\Plugin\views\field;

use Drupal\views\Attribute\ViewsField;
use Drupal\views\Plugin\views\field\BulkForm;

/**
 * Defines a grade signing event operations bulk form element.
 */
#[ViewsField("ssr_grade_signing_bulk_form")]
class GradeSigningBulkForm extends BulkForm {

  /**
   * {@inheritdoc}
   */
  protected function emptySelectedMessage() {
    return $this->t('No signing document selected.');
  }

}
