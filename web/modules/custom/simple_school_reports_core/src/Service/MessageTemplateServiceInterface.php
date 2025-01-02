<?php

namespace Drupal\simple_school_reports_core\Service;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an interface defining MessageTemplateService.
 */
interface MessageTemplateServiceInterface {

  /**
   * @param string $category
   * @param string $type
   *
   * @return array|null
   */
  public function getMessageTemplates(string $category = 'ALL', string $type = 'ALL'): ?array;

  /**
   * @param array $templates
   */
  public function setMessageTemplates(array $templates);

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function buildConfigForm(array &$form, FormStateInterface $form_state);

}
