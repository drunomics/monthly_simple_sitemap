<?php

namespace Drupal\monthly_simple_sitemap;

use Drupal\monthly_simple_sitemap\Plugin\simple_sitemap\SitemapGenerator\MonthlySitemapGenerator;
use Drupal\simple_sitemap\Queue\QueueWorker;

/**
 * Sitemap queue worker with monthly sitemap variant extension.
 *
 * @package Drupal\monthly_simple_sitemap
 */
class MonthlySitemapQueueWorker extends QueueWorker {

  /**
   * Generate chunks of the articles by month sitemap.
   *
   * @param bool $complete
   *   All the links has been processed.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function generateVariantChunksFromResults($complete = FALSE) {
    if (!empty($this->results)) {
      $processed_results = $this->results;
      $this->moduleHandler->alter('simple_sitemap_links', $processed_results, $this->variantProcessedNow);
      $this->processedResults = array_merge($this->processedResults, $processed_results);
      $this->results = [];
    }

    if (empty($this->processedResults)) {
      return;
    }

    $generator = $this->manager->getSitemapGenerator($this->generatorProcessedNow)
      ->setSitemapVariant($this->variantProcessedNow)
      ->setSettings($this->generatorSettings);

    if ($this->generatorProcessedNow == MonthlySitemapGenerator::MONTHLY_GENERATOR_ID) {
      $monthly_chunks_max_links = $this->getMonthlyChunks();
      foreach ($monthly_chunks_max_links as $chunk_links) {
        if ($complete) {
          $generator->generate($chunk_links);
          $this->processedResults = array_diff_key($this->processedResults, $chunk_links);
        }
      }
    }
    elseif (!empty($this->maxLinks)) {
      foreach (array_chunk($this->processedResults, $this->maxLinks, TRUE) as $chunk_links) {
        if ($complete || count($chunk_links) === $this->maxLinks) {
          $generator->generate($chunk_links);
          $this->processedResults = array_diff_key($this->processedResults, $chunk_links);
        }
      }
    }
    else {
      $generator->generate($this->processedResults);
      $this->processedResults = [];
    }
  }

  /**
   * Create chunks by month and by max links configuration option.
   *
   * @return array
   *   Array of monthly chunks.
   */
  protected function getMonthlyChunks() {
    // Create chunks per month.
    $monthly_chunks = [];
    foreach ($this->processedResults as $link) {
      $monthly_chunks[$link['meta']['month']][] = $link;
    }
    $monthly_chunks_max_links = [];
    if (!empty($this->maxLinks)) {
      foreach ($monthly_chunks as $month => $monthly_chunk) {
        $max_links_chunks = array_chunk($monthly_chunk, $this->maxLinks, TRUE);
        $counter = 1;
        foreach ($max_links_chunks as $max_links_chunk) {
          $monthly_chunks_max_links[$month . '-' . (string) ($counter)] = $max_links_chunk;
          $counter++;
        }
      }
    }
    else {
      foreach ($monthly_chunks as $month => $monthly_chunk) {
        $monthly_chunks_max_links[$month . '-1'] = $monthly_chunk;
      }
    }
    $this->state->set(MonthlySitemapGenerator::MONTHLY_GENERATOR_ID . '_' . $this->variantProcessedNow, array_keys($monthly_chunks_max_links));
    return $monthly_chunks_max_links;
  }

}
