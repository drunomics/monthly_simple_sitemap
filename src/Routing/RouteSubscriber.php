<?php

namespace Drupal\monthly_simple_sitemap\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('simple_sitemap.sitemap_variant')) {
      $route->setDefault('_controller', '\Drupal\monthly_simple_sitemap\Controller\MonthlySitemapController::getSitemap');
    }
    if ($route = $collection->get('simple_sitemap.sitemap_default')) {
      $route->setDefault('_controller', '\Drupal\monthly_simple_sitemap\Controller\MonthlySitemapController::getSitemap');
    }
    if ($route = $collection->get('simple_sitemap_extensions.sitemap_variant_page')) {
      $route->setDefault('_controller', '\Drupal\monthly_simple_sitemap\Controller\MonthlySitemapController::getSitemap');
    }

  }

}
