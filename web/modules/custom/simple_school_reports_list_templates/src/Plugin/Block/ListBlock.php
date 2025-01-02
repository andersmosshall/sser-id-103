<?php

namespace Drupal\simple_school_reports_list_templates\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;
use Drupal\simple_school_reports_core\Service\AbsenceStatisticsServiceInterface;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'StudentGradeStatisticsBlock' block.
 *
 * @Block(
 *  id = "ssr_list_templates_list_block",
 *  admin_label = @Translation("List template list block"),
 * )
 */
class ListBlock extends BlockBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * @var AccountInterface
   */
  protected $currentUser;

  /**
   * @var \Drupal\simple_school_reports_core\Service\AbsenceStatisticsServiceInterface
   */
  protected $absenceStatisticsService;

  /**
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\simple_school_reports_core\Service\TermServiceInterface $term_service
   */
  public function __construct(
    array $configuration,
          $plugin_id,
          $plugin_definition,
    AccountInterface $current_user,
    AbsenceStatisticsServiceInterface $absence_statistics
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
    $this->absenceStatisticsService = $absence_statistics;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('simple_school_reports_core.absence_statistics'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(?NodeInterface $list_template = NULL) {
    if (!$list_template || $list_template->bundle() !== 'list_template') {
      return [];
    }

    $build = [
      '#type' => 'container',
    ];
    $cache = new CacheableMetadata();
    $cache->addCacheableDependency($list_template);
    $cache->addCacheContexts(['user']);
    $cache->addCacheTags(['user_list']);


    // Get view and render it.
    $list_template_view = Views::getView('list_template_support');
    $list_template_view->setDisplay('list_template_support');
    $list_template_view->preExecute();
    $list_template_view->execute();
    $view_cache = CacheableMetadata::createFromRenderArray($list_template_view->element);
    $view_cache->addCacheableDependency($cache);
    $view_cache->applyTo($list_template_view->element);
    $list_template_view_build = $list_template_view->buildRenderable('list_template_support');
    $build['view'] = $list_template_view_build;

    $drupal_settings = [
      'customFields' => [],
      'crossOverUids' => [],
    ];

    /** @var \Drupal\paragraphs\ParagraphInterface $field_paragraph */
    foreach ($list_template->get('field_list_template_field')->referencedEntities() as $field_paragraph) {
      if ($field_paragraph->get('field_field_type')->value === 'custom') {
        $drupal_settings['customFields'][] = [
          'size' => $field_paragraph->get('field_size')->value ?? 's',
        ];
      }
    }

    if ($list_template->get('field_mark_absence')->value) {
      $cache->addCacheContexts(['current_day']);

      $today = new DrupalDateTime();
      $today->setTime(0,0,0);
      $limit_from = $today->getTimestamp();
      $today->setTime(23,59, 59);
      $limit_to = $today->getTimestamp();

      $data = $this->absenceStatisticsService->getAllAbsenceDayData($limit_from, $limit_to);
      $uids = [];
      foreach ($data as $value => $uid_list) {
        foreach ($uid_list as $uid) {
          $uids[] = (string) $uid;
        }
      }
      $drupal_settings['crossOverUids'] = array_unique($uids);
    }

    $build['#attached']['library'][] = 'simple_school_reports_list_templates/list_template_block';
    $build['#attributes']['class'][] = 'list-template-block';
    $build['#attached']['drupalSettings']['listTemplateBlock'] = $drupal_settings;

    $cache->applyTo($build);
    return $build;
  }
}
