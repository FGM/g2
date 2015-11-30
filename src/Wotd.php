<?php

/**
 * @file
 * Contains G2 WOTD service.
 */

namespace Drupal\g2;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\node\Entity\Node;

/**
 * Class WOTD implements the g2.wotd service.
 */
class Wotd {
  /**
   * The configuration hash for this service.
   *
   * Keys:
   * - max: the maximum number of entries returned. 0 for unlimited.
   *
   * @var array
   */
  protected $config;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config factory service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(ConfigFactoryInterface $config, StateInterface $state) {
    $this->state = $state;

    $g2_config = $config->get('g2.settings');
    $this->config = $g2_config->get('service.wotd');
  }

  /**
   * Return the word of the day.
   *
   * @return \Drupal\node\NodeInterface|null
   *   The node for the word of the day, or null if not found. This can happen
   *   if the WOTD has not been set, or set to an unavailable node.
   */
  public function getEntry() {
    $nid = intval($this->state->get('g2.wotd'));
    $result = $nid > 0 ? Node::load($nid) : NULL;
    return $result;
  }

}
