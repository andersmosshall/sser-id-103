<?php

namespace Drupal\simple_school_reports_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class StartPageContentServiceInterface
 *
 * @package Drupal\simple_school_reports_core\Service
 */
interface StartPageContentServiceInterface {

  /**
   * @param string $type
   *
   * @return string|array|null
   */
  public function getStartPageContent(string $type = 'ALL');

  /**
   * @param string $type
   */
  public function getFormattedStartPageContent(string $type): string;

  /**
   * @param array $contents
   */
  public function setStartPageContent(array $contents);

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function buildConfigForm(array &$form, FormStateInterface $form_state);
}
