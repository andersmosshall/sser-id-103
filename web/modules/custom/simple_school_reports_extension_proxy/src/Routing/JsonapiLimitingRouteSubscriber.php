<?php

namespace Drupal\simple_school_reports_extension_proxy\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class JsonapiLimitingRouteSubscriber.
 *
 * Remove all none GET routes from jsonapi resources to protect content.
 *
 * Remove ALL routes from jsonapi resources except for those
 * a specified in enabledResources().
 */
class JsonapiLimitingRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $enabled_resources = $this->enabledResources();
    foreach ($collection as $name => $route) {
      $defaults = $route->getDefaults();
      if (!empty($defaults['_is_jsonapi']) && !empty($defaults['resource_type'])) {
        $route->setRequirement('_permission', 'jsonapi access');
        $methods = $route->getMethods();
        if (!in_array('GET', $methods)) {
          $collection->remove($name);
        }
        else {
          $resource_type = $defaults['resource_type'];
          if (empty($enabled_resources[$resource_type])) {
            $collection->remove($name);
          }
        }
      }
    }
  }

  /**
   * Get enabled resource types.
   *
   * @return array
   *   List of enabled jsonapi resource types as keys.
   */
  public function enabledResources(): array {
    // No resources allowed for now.
    return [
       //'user--user' => TRUE,
    ];
  }

}
