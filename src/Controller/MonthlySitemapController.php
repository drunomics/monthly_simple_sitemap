<?php

namespace Drupal\monthly_simple_sitemap\Controller;

use Drupal\Core\State\StateInterface;
use Drupal\monthly_simple_sitemap\Plugin\simple_sitemap\SitemapGenerator\MonthlySitemapGenerator;
use Drupal\simple_sitemap\Controller\SimplesitemapController;
use Drupal\simple_sitemap\Simplesitemap;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Extension of a SimplesitemapController.
 */
class MonthlySitemapController extends SimplesitemapController {

  /**
   * State service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * MonthlySitemapController constructor.
   *
   * @param \Drupal\simple_sitemap\Simplesitemap $generator
   *   Simple sitemap generator service.
   * @param \Drupal\Core\State\StateInterface $state
   *   State service.
   */
  public function __construct(Simplesitemap $generator, StateInterface $state) {
    parent::__construct($generator);
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('simple_sitemap.generator'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSitemap(Request $request, $variant = NULL) {
    // Convert month parameter into delta.
    if (($month = $request->query->get('month')) && ($mapping = $this->state->get(MonthlySitemapGenerator::MONTHLY_GENERATOR_ID . '_' . $variant, FALSE))) {
      $page = array_search($month, $mapping) + MonthlySitemapGenerator::FIRST_CHUNK_DELTA;
      if ($page) {
        $request->query->set('page', $page);
      }
    }
    return parent::getSitemap($request, $variant);
  }

}
