<?php

namespace Drupal\simple_school_reports_entities\Plugin\views\field;

use Drupal\views\Attribute\ViewsField;
use Drupal\views\Plugin\views\field\BulkForm;

/**
 * Defines a calendar event operations bulk form element.
 */
#[ViewsField("ssr_calendar_event_bulk_form")]
class CalendarEventBulkForm extends BulkForm {

  /**
   * {@inheritdoc}
   */
  protected function emptySelectedMessage() {
    return $this->t('No calendar event selected.');
  }

}
