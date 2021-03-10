<?php

namespace Drupal\monthly_simple_sitemap\Plugin\simple_sitemap\SitemapGenerator;

use Drupal\Component\Datetime\Time;
use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Drupal\simple_sitemap\Plugin\simple_sitemap\SitemapGenerator\DefaultSitemapGenerator;
use Drupal\simple_sitemap\Plugin\simple_sitemap\SitemapGenerator\SitemapWriter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generator for sitemap index of variants.
 *
 * @package Drupal\monthly_simple_sitemap\Plugin\simple_sitemap\SitemapGenerator
 *
 * @SitemapGenerator(
 *   id = "monthly_sitemap_generator",
 *   label = @Translation("Monthly sitemap generator"),
 *   description = @Translation("Generates sitemap links to articles per monhtl sitemaps."),
 * )
 */
class MonthlySitemapGenerator extends DefaultSitemapGenerator {

  const MONTHLY_GENERATOR_ID = 'monthly_sitemap_generator';

  /**
   * Delta month mapping.
   *
   * @var array|null
   */
  protected $deltaMonthMapping = NULL;

  /**
   * MonthlySitemapGenerator constructor.
   *
   * @param array $configuration
   *   Configuration of Simple XML Sitemap.
   * @param string $plugin_id
   *   Plugin id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Database\Connection $database
   *   Drupal database connection service.
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   Module handler service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   Language manager service.
   * @param \Drupal\Component\Datetime\Time $time
   *   Time service.
   * @param \Drupal\simple_sitemap\Plugin\simple_sitemap\SitemapGenerator\SitemapWriter $sitemap_writer
   *   Sitemap writer service.
   * @param \Drupal\Core\State\StateInterface $state
   *   Drupal state service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    Connection $database,
    ModuleHandler $module_handler,
    LanguageManagerInterface $language_manager,
    Time $time,
    SitemapWriter $sitemap_writer,
    StateInterface $state
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $database, $module_handler, $language_manager, $time, $sitemap_writer);
    $this->state = $state;
  }

  /**
   * Poor man's dependency injection.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Service container.
   * @param array $configuration
   *   Configuration.
   * @param string $plugin_id
   *   Plugin id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   *
   * @return MonthlySitemapGenerator|\Drupal\simple_sitemap\Plugin\simple_sitemap\SitemapGenerator\SitemapGeneratorBase|static
   *   Constructor parameters.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('module_handler'),
      $container->get('language_manager'),
      $container->get('datetime.time'),
      $container->get('simple_sitemap.sitemap_writer'),
      $container->get('state')
    );
  }

  /**
   * Get sitemap url.
   *
   * @param mixed $delta
   *   Which month to fetch.
   *
   * @return string
   *   Url of a sitemap.
   */
  public function getSitemapUrl($delta = NULL) {
    if (is_null($delta)) {
      $parameters = [];
    }
    else {
      $month = $this->getCurrentMonth($delta);
      $parameter = $month ? 'month' : 'page';
      $parameters = [$parameter => $month ?? $delta];
    }
    $url = Url::fromRoute(
      'simple_sitemap.sitemap_variant',
      $parameters + ['variant' => $this->sitemapVariant],
      $this->getSitemapUrlSettings()
    );

    return $url->toString();
  }

  /**
   * Get current query parameter from the mapping.
   *
   * @param int $delta
   *   Current chunk.
   *
   * @return false|string
   *   Url query parameter or False.
   */
  protected function getCurrentMonth(int $delta) {
    if (empty($this->deltaMonthMapping)) {
      $this->deltaMonthMapping = $this->state->get(self::MONTHLY_GENERATOR_ID . '_' . $this->sitemapVariant, FALSE);
    }
    return $this->deltaMonthMapping[$delta - self::FIRST_CHUNK_DELTA] ?? FALSE;
  }

}
