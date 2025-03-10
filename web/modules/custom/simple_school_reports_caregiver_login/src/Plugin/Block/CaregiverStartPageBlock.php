<?php

namespace Drupal\simple_school_reports_caregiver_login\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\simple_school_reports_core\Service\StartPageContentServiceInterface;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Provides a 'Feedback form' block.
 *
 * @Block(
 *  id = "caregiver_start_page_block",
 *  admin_label = @Translation("Caregiver start page block"),
 * )
 */
class CaregiverStartPageBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\simple_school_reports_core\Service\StartPageContentServiceInterface
   */
  protected $startPageContentService;


  public function __construct(
    array $configuration,
          $plugin_id,
          $plugin_definition,
    StartPageContentServiceInterface $start_page_content_service
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->startPageContentService = $start_page_content_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('simple_school_reports_core.start_page_content_service'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $cache = new CacheableMetadata();
    $cache->addCacheTags(['start_page_settings']);
    $cache->addCacheContexts(['user']);

    $build = [];

    $content = $this->startPageContentService->getFormattedStartPageContent('caregiver');

    if ($content) {
      $build['startpage_info'] = [
        '#markup' => $content,
      ];
    }
    else {
      $build['startpage_info'] = [
        '#markup' => '<h2>Välkommen!</h2>',
      ];
    }

    $build['students'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => 'students-in-school-wrapper',
      ]
    ];

    $build['students']['label'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t('Students in school'),
      '#weight' => -100,
    ];

    // Get view and render it.
    $list_template_view = Views::getView('cargiver_students');
    $list_template_view->setDisplay('students');
    $list_template_view->preExecute();
    $list_template_view->execute();
    $list_template_view->element['#cache']['contexts'][] = 'user';
    $list_template_view_build = $list_template_view->buildRenderable('students');
    $build['students']['view'] = $list_template_view_build;

    $cache->applyTo($build);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIf($account->hasPermission('super user permissions') || in_array('caregiver', $account->getRoles()));
  }

}
