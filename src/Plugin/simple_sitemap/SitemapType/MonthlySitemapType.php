<?php

namespace Drupal\monthly_simple_sitemap\Plugin\simple_sitemap\SitemapType;

use Drupal\simple_sitemap\Plugin\simple_sitemap\SitemapType\SitemapTypeBase;

/**
 * The dynamic sitemap type.
 *
 * @SitemapType(
 *   id = "monthly_sitemap_type",
 *   label = @Translation("Monthly sitemap type"),
 *   description = @Translation("Dynamic sitemap to show articles by month."),
 *   sitemapGenerator = "monthly_sitemap_generator",
 *   urlGenerators = {
 *     "monthly_url_generator"
 *   },
 * )
 */
class MonthlySitemapType extends SitemapTypeBase {

}
