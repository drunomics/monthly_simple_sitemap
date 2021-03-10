<?php

namespace Drupal\monthly_simple_sitemap;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the language manager service.
 */
class MonthlySimpleSitemapServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if ($container->hasDefinition('simple_sitemap.queue_worker')) {
      $definition = $container->getDefinition('simple_sitemap.queue_worker');
      $definition->setClass('Drupal\monthly_simple_sitemap\MonthlySitemapQueueWorker');
    }
  }

}
