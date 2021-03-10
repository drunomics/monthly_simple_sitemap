<?php

namespace Drupal\monthly_simple_sitemap\Plugin\simple_sitemap\UrlGenerator;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\simple_sitemap\EntityHelper;
use Drupal\simple_sitemap\Logger;
use Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator\EntityUrlGenerator;
use Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator\UrlGeneratorManager;
use Drupal\simple_sitemap\Simplesitemap;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityUrlGenerator.
 *
 * @package Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator
 *
 * @UrlGenerator(
 *   id = "monthly_url_generator",
 *   label = @Translation("Monthly URL generator"),
 *   description = @Translation("Generates URLs for entity bundles and bundle overrides on a monthly basis."),
 * )
 */
class MonthlySitemapUrlGenerator extends EntityUrlGenerator {

  /**
   * Drupal datetime formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * MonthlySitemapUrlGenerator constructor.
   *
   * @param array $configuration
   *   Configuration.
   * @param string $plugin_id
   *   Plugin id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\simple_sitemap\Simplesitemap $generator
   *   Simple Sitemap generator service.
   * @param \Drupal\simple_sitemap\Logger $logger
   *   Logger service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   Language manager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\simple_sitemap\EntityHelper $entityHelper
   *   Entity helper service.
   * @param \Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator\UrlGeneratorManager $url_generator_manager
   *   Simple sitemap url generator manager service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   Drupal datetime formatter service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    Simplesitemap $generator,
    Logger $logger,
    LanguageManagerInterface $language_manager,
    EntityTypeManagerInterface $entity_type_manager,
    EntityHelper $entityHelper,
    UrlGeneratorManager $url_generator_manager,
    DateFormatterInterface $date_formatter
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $generator,
      $logger,
      $language_manager,
      $entity_type_manager,
      $entityHelper,
      $url_generator_manager
    );
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('simple_sitemap.generator'),
      $container->get('simple_sitemap.logger'),
      $container->get('language_manager'),
      $container->get('entity_type.manager'),
      $container->get('simple_sitemap.entity_helper'),
      $container->get('plugin.manager.simple_sitemap.url_generator'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDataSets() {
    $data_sets = [];
    $sitemap_entity_types = $this->entityHelper->getSupportedEntityTypes();
    $bundle_settings = $this->generator->setVariants($this->sitemapVariant)->getBundleSettings();
    // Iterate over months until we come to the oldest entity date.
    $date_oldest = new DrupalDateTime($this->dateFormatter->format($this->getOldestEntityChangedDate($bundle_settings), 'custom', 'Y-m'));
    foreach ($bundle_settings as $entity_type_name => $bundles) {
      $date_now = new DrupalDateTime($this->dateFormatter->format(time(), 'custom', 'Y-m'));
      while ($date_now >= $date_oldest) {
        if (isset($sitemap_entity_types[$entity_type_name])) {

          // Skip this entity type if another plugin is written to override its
          // generation.
          foreach ($this->urlGeneratorManager->getDefinitions() as $plugin) {
            if (isset($plugin['settings']['overrides_entity_type'])
              && $plugin['settings']['overrides_entity_type'] === $entity_type_name) {
              continue 3;
            }
          }

          $entityTypeStorage = $this->entityTypeManager->getStorage($entity_type_name);
          $keys = $sitemap_entity_types[$entity_type_name]->getKeys();

          foreach ($bundles as $bundle_name => $bundle_settings) {
            if (!empty($bundle_settings['index'])) {
              $query = $entityTypeStorage->getQuery();

              if (empty($keys['id'])) {
                $query->sort($keys['id'], 'ASC');
              }
              if (!empty($keys['bundle'])) {
                $query->condition($keys['bundle'], $bundle_name);
              }
              if (!empty($keys['status'])) {
                $query->condition($keys['status'], 1);
              }
              $query
                ->condition('changed', strtotime($date_now), '>')
                ->condition('changed', strtotime($date_now->modify('+1 month')), '<');
              // Shift access check to EntityUrlGeneratorBase for language
              // specific access. See
              // https://www.drupal.org/project/simple_sitemap/issues/3102450.
              $query->accessCheck(FALSE);
              // Set month back to correct value.
              $date_now->modify('-1 month');
              foreach ($query->execute() as $entity_id) {
                $data_sets[] = [
                  'month' => (string) $date_now->format('Y-m'),
                  'entity_type' => $entity_type_name,
                  'id' => $entity_id,
                ];
              }
            }
          }
        }
        $date_now->modify('-1 month');
      }
    }
    return $data_sets;
  }

  /**
   * Get date of the oldest article.
   *
   * @return mixed
   *   Usually a timestamp.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getOldestEntityChangedDate($bundle_settings) {
    $oldest = NULL;
    $sitemap_entity_types = $this->entityHelper->getSupportedEntityTypes();
    foreach ($bundle_settings as $entity_type_name => $bundles) {
      if (isset($sitemap_entity_types[$entity_type_name])) {

        // Skip this entity type if another plugin is written to override its
        // generation.
        foreach ($this->urlGeneratorManager->getDefinitions() as $plugin) {
          if (isset($plugin['settings']['overrides_entity_type'])
            && $plugin['settings']['overrides_entity_type'] === $entity_type_name) {
            continue 2;
          }
        }

        $entityTypeStorage = $this->entityTypeManager->getStorage($entity_type_name);
        $keys = $sitemap_entity_types[$entity_type_name]->getKeys();

        foreach ($bundles as $bundle_name => $bundle_settings) {
          if (!empty($bundle_settings['index'])) {
            $query = $entityTypeStorage->getQuery();
            if (!empty($keys['bundle'])) {
              $query->condition($keys['bundle'], $bundle_name);
            }
            if (!empty($keys['status'])) {
              $query->condition($keys['status'], 1);
            }
            $result = $query
              ->sort('changed', 'ASC')
              ->range(0, 1)
              ->execute();
            $oldest_entity = $entityTypeStorage->load(reset($result));
            if (empty($oldest)) {
              $oldest = $oldest_entity->changed->value;
            }
            else {
              $oldest = $oldest_entity->changed->value < $oldest ? $oldest_entity->changed->value : $oldest;
            }
          }
        }
      }
    }
    return $oldest ?? time();
  }

  /**
   * {@inheritdoc}
   */
  protected function processDataSet($data_set) {
    $processed_data_set = parent::processDataSet($data_set);
    $processed_data_set['meta']['month'] = $data_set['month'];
    return $processed_data_set;
  }

}
