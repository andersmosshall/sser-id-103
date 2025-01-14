<?php

namespace Drupal\views_custom_permissions\Plugin\views\access;

use Drupal\views\Plugin\views\access\AccessPluginBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Access plugin that provides custom access control at all.
 *
 * @ingroup views_access_plugins
 *
 * @ViewsAccess(
 *   id = "views_custom_permissions_access",
 *   title = @Translation("Custom Permissions"),
 *   help = @Translation("Access will be granted to users with the custom access control.")
 * )
 */

class ViewsCustomPermissionsAccess extends AccessPluginBase {

  /**
   * {@inheritdoc}
   */
  protected $usesOptions = TRUE;

  /**
    * The config factory service.
    *
    * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
   protected $configFactory;

  /**
   * Constructs a Permission object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              ConfigFactoryInterface $config) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['cpermissions'] = ['default' => ''];
    return $options;
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $config = $this->configFactory->get('views_custom_permissions.settings')->get('vcp_table');
    $options = [];
    foreach($config as $key => $value) {
      $options[$key] = $value['title'];
    }
    $form['cpermissions'] = [
      '#type' => 'select',
      '#title' => $this->t('Custom Permissions'),
      '#default_value' => $this->options['cpermissions'],
      '#options' => $options,
      '#description' => $this->t('Only users with the selected permission flag will be able to access this display.'),
    ];
  }
  public function summaryTitle() {
    $config = $this->configFactory->get('views_custom_permissions.settings')->get('vcp_table');
    if (isset($this->options['cpermissions'])) {
      return $this->t('@cpermissions', ['@cpermissions' => $config[$this->options['cpermissions']]['title']]);
    }
    return $this->t('Custom Permissions');
  }

  public function access(AccountInterface $account) {
    $config = $this->configFactory->get('views_custom_permissions.settings')->get('vcp_table');
    $function = $config[$this->options['cpermissions']]['callback'];
    return $function()->isAllowed();
  }

  public function alterRouteDefinition(Route $route) {
    $config = $this->configFactory->get('views_custom_permissions.settings')->get('vcp_table');
    $function = $config[$this->options['cpermissions']]['callback'];
    $route->setRequirement('_custom_access', "$function");
  }

}

