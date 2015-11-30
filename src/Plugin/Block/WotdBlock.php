<?php

/**
 * @file
 * Contains the WOTD block plugin.
 */

namespace Drupal\g2\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\g2\Wotd;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class WotdBlock is the Word of the day block plugin.
 *
 * @Block(
 *   id = "g2_wotd",
 *   admin_label = @Translation("G2 WOTD"),
 *   category = @Translation("G2")
 * )
 */
class WotdBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The g2.settings.block.wotd configuration.
   *
   * @var array
   */
  protected $blockConfig;

  /**
   * The g2.wotd service.
   *
   * @var \Drupal\g2\Wotd
   */
  protected $wotd;

  /**
   * Constructor.
   *
   * @param array $configuration
   *   The block configuration.
   * @param string $plugin_id
   *   The block ID.
   * @param mixed $plugin_definition
   *   The block definition.
   * @param \Drupal\g2\Wotd $wotd
   *   The g2.wotd service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity.manager service.
   * @param array $block_config
   *   The block configuration.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition,
    Wotd $wotd, EntityTypeManagerInterface $entity_type_manager, array $block_config) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->wotd = $wotd;
    $this->blockConfig = $block_config;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var \Drupal\node\NodeInterface|null $node */
    $node = $this->wotd->getEntry();

    if ($node) {
      $view_mode = $this->blockConfig['view_mode'];
      $view_builder = $this->entityTypeManager->getViewBuilder('node');
      $result = [
        'node' => $view_builder->view($node, $view_mode),
      ];
      if ($this->blockConfig['feed']) {
        $result['feed'] = [
          '#theme' => 'feed_icon',
          '#url' => Url::fromRoute('g2.feed.wotd'),
          '#title' => t('A word a day'),
        ];
      }
    }
    else {
      $result = NULL;
    }

    return $result;
  }

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    /* @var \Drupal\g2\Wotd $wotd */
    $wotd = $container->get('g2.wotd');

    /* @var \Drupal\Core\Config\ConfigFactory $config_factory */
    $config_factory = $container->get('config.factory');

    /* @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $container->get('entity.manager');

    /* @var \Drupal\Core\Config\ImmutableConfig $config */
    $config = $config_factory->get('g2.settings');

    $block_config = $config->get('block.wotd');

    return new static($configuration, $plugin_id, $plugin_definition,
      $wotd, $entity_type_manager, $block_config);
  }

}
