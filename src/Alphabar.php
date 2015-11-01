<?php
/**
 * @file
 * Contains Alphabar.
 */

namespace Drupal\g2;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGenerator;

/**
 * Class Alphabar provides a list of links to entries-by-initial pages.
 */
class Alphabar {
  /**
   * The configuration hash for this service.
   *
   * Keys:
   * - contents: a string of the initials to use in the alphabar.
   *
   * @var array
   */
  protected $config;

  /**
   * The link generator service.
   *
   * @var \Drupal\Core\Utility\LinkGenerator
   */
  protected $linkGenerator;

  /**
   * The entity query service.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $query;

  /**
   * The name of the route to use when building alphabar links.
   *
   * @var string
   */
  protected $routeName;

  /**
   * The URL generator service.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config factory service.
   * @param \Drupal\Core\Utility\LinkGenerator $link_generator
   *   The link generator service.
   * @param \Drupal\Core\Entity\Query\QueryFactory $query
   *   The entity query service.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The URL generator service.
   */
  public function __construct(ConfigFactoryInterface $config, LinkGenerator $link_generator, QueryFactory $query, UrlGeneratorInterface $url_generator) {
    $this->linkGenerator = $link_generator;
    $this->query = $query;
    $this->urlGenerator = $url_generator;

    $g2_config = $config->get('g2.settings');
    $this->config = $g2_config->get('service.alphabar');
    $this->routeName = $g2_config->get('controller.initial.route');
  }

  /**
   * Return the alphabar.contents configuration.
   *
   * @return string
   *   The configured alphabar.contents
   */
  public function getContents() {
    $result = $this->config['contents'];
    return $result;
  }

  /**
   * Return an array of links to entries-by-initial pages.
   *
   * @return array<string,\Drupal\Core\GeneratedLink>
   *   A hash of initials to entry pages.
   */
  public function getLinks() {
    $result = [];
    $options = [
      // So alphabar can be used outside site pages.
      'absolute' => TRUE,
      // To preserve the pre-encoded path.
      'html'     => TRUE,
    ];

    $initials = $this->config['contents'];
    $route_name = $this->routeName;

    for ($i = 0; $i < Unicode::strlen($initials); $i++) {
      $initial = Unicode::substr($initials, $i, 1);
      $path = G2::encodeTerminal($initial);
      $parameters = ['g2_match' => $path];
      $url = Url::fromRoute($route_name, $parameters, $options);
      $result[] = $this->linkGenerator->generate($initial, $url);
    }

    return $result;
  }

}
