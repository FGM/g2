<?php

/**
 * @file
 * Install file for G2 Glossary.
 *
 * @copyright 2005-2015 Frédéric G. Marand, for Ouest Systemes Informatiques.
 */

$_g2_install_er = error_reporting(E_ALL | E_STRICT);

/**
 * Implements hook_install().
 *
 * @return void
 */
function g2_install() {
  // Create tables.
  drupal_install_schema('g2');
  $t = get_t();
  drupal_set_message($t('Installed G2 schema'), 'status');
}

/**
 * Implements hook_requirements().
 *
 * @param string $phase
 * @return array
 */
function g2_requirements($phase) {
  $ret = array();
  if ($phase != 'runtime') {
    return;
  }

  // Since it's runtime, t() is available, so no get_t()

  // 1. Main page req. check
  $main = variable_get(G2VARMAIN, G2DEFMAIN);
  $ret['main'] = array(
    'title' => t('G2 main page'),
  );
  if (is_numeric($main)) {
    if ($main) {
      $node = node_load($main);
      if (!is_object($node)) {
        $ret['main'] += array(
          'value' => t('The node chosen for the main page must be a valid one, or 0: "@nid" is not a valid node id.',
            array('@nid' => $main)),
          'severity' => REQUIREMENT_ERROR,
        );
      }
      else {
        $ret['main'] += array(
          'value' => t('Valid node: !link', array(
            '!link' => l($node->title, 'node/'. $main),
          )),
          'severity' => REQUIREMENT_OK,
        );
      }
    }
    else {
      $ret['main'] += array(
        'value' => t('Set to empty'),
        'severity' => REQUIREMENT_INFO,
      );
    }
  }
  elseif (!function_exists($main)) {
    $ret['main'] += array(
      'value' => t('The chosen function must visible to G2: "@function" is not a valid function name.',
        array('@function' => $main)),
      'severity' => REQUIREMENT_ERROR,
    );
  }
  else {
    $ret['main'] += array(
      'value' => t('Set to visible function %main', array('%main' => $main)),
      'severity' => REQUIREMENT_OK,
    );
  }

  // 2. Disambiguation req. check
  $ret['homonyms'] = array(
    'title' => t('G2 disambiguation page'),
  );
  $nid = variable_get(G2VARHOMONYMS, G2DEFHOMONYMS);
  if ($nid) {
    $node = node_load($nid);
    if (!is_object($node)) {
      $ret['homonyms'] += array(
        'value' => t('The node chosen for the homonyms disambiguation page must be a valid one, or 0: "@nid" is not a valid node id.',
          array('@nid' => $nid)),
        'severity' => REQUIREMENT_ERROR,
      );
    }
    else {
      $ret['homonyms'] += array(
        'value' => t('Valid node: !link', array(
          '!link' => l($node->title, 'node/'. $nid),
        )),
      );
    }
  }
  else {
    $ret['homonyms'] += array(
      'value' => t('Set to empty.'),
      'severity' => REQUIREMENT_INFO,
    );
  }


  // 3. Statistics req. check
  $stats = module_exists('statistics');
  $count = variable_get('statistics_count_content_views', NULL);
  if (!$stats && !$count) {
    $severity = REQUIREMENT_INFO; // this one is a (questionable) choice
    $value = t('G2 statistics disabled.');
  }
  elseif ($stats xor $count) {
    $severity = REQUIREMENT_WARNING; // this one is inconsistent
    $value = t('G2 statistics incorrectly configured.');
  }
  else { // both on
    $severity = REQUIREMENT_OK; // optimal
    $value = t('G2 statistics configured correctly.');
  }

  $ar = array();
  $stats_link = array('!link' => url('admin/build/modules/list'));
  $ar[] = $stats
    ? t('<a href="!link">Statistics module</a> installed and activated: OK.', $stats_link)
    : t('<a href="!link">Statistics module</a> not installed or not activated.', $stats_link);
  $link_text = $count ? t('ON') : t('OFF');
  if ($stats) {
    $link = l($link_text, 'admin/reports/settings',
        array('fragment' => 'statistics_count_content_views'));
    $ar[] = t('Count content views" setting is !link', array('!link' => $link));
  }
  else {
    $ar[] = t('G2 relies on statistics.module to provide data for the G2 "Top" block and XML-RPC service. If you do not use either block, you can leave statistics.module disabled.');
  }
  $description = theme('item_list', $ar);
  $ret['statistics'] = array(
    'title'       => t('G2 statistics'),
    'value'       => $value,
    'description' => $description,
    'severity'    => $severity,
  );
  return $ret;
}

/**
 * Implements hook_schema().
 *
 * Define the structure of the non-core tables used by G2.
 *
 * Schema API does not define it, but thes tables should have UTF-8
 * as their default charset
 *
 * @return array
 */
function g2_schema() {
  $schema = array();

  /**
   * Additional fields in G2 entries
   *
   * G2 does not currently revision the additional information it stores
   * its entries, so it does not need to keep the vid.
   */
  $schema['g2_node'] = array(
    'fields' => array(
      'nid' => array(
        'type' => 'int',
        'unsigned' => TRUE,
        'not null' => TRUE,
        'default' => 0,
        'description' => 'The node id for the current G2 entry',
      ),
      'period' => array(
        'type' => 'varchar',
        'length' => 50,
        'not null' => FALSE,
        'description' => 'A time period during which the entity of concept described by the term was in use',
      ),
      'complement' => array(
        'type' => 'text',
        'size' => 'medium',
        'not null' => FALSE,
        'description' => 'Editor-only general information about the item content',
      ),
      'origin' => array(
        'type' => 'text',
        'size' => 'medium',
        'not null' => FALSE,
        'description' => 'Editor-only intellectual property-related information about the item content',
      ),
    ),
    'primary key' => array('nid'),
    'unique keys' => array(),
    'indexes' => array(),
    'description' => 'The G2-specific, non-versioned, informations contained in G2 entry nodes in addition to default node content.',
  );

  /**
   * G2 per-node referer stats
   */
  $schema['g2_referer'] = array(
    'fields' => array(
      'nid' => array(
        'type' => 'int',
        'unsigned' => TRUE,
        'not null' => TRUE,
        'default' => 0,
        'description' => 'The node id for the current G2 entry',
      ),
      'referer' => array(
        'type' => 'varchar',
        'length' => 128,
        'not null' => TRUE,
        'default' => '',
        'description' => 'The URL on which a link was found to the current item',
      ),
      'incoming' => array(
        'type' => 'int',
        'unsigned' => TRUE,
        'not null' => TRUE,
        'default' => 0,
        'description' => 'The number of hits coming from this referer',
      ),
    ),
    'indexes' => array(),
    'primary key' => array('nid', 'referer'),
    'unique keys' => array(),
    'indexes' => array(
      'referer' => array('referer'),
    ),
    'description' => 'The referer tracking table for G2 entries',
  );

  return $schema;
}

/**
 * Implements hook_uninstall().
 */
function g2_uninstall() {
  // Remove tables. Automatic removal starts with D7.
  drupal_uninstall_schema('g2');
  drupal_set_message(st('Uninstalled G2 schema'), 'status');

  // Remove variables
  $variables = array();

  $sq = 'SELECT v.name '
      . 'FROM {variable} v '
      . "WHERE v.name LIKE 'g2_%%' OR v.name LIKE 'g2/%%' ";
  $q = db_query($sq);
  while ($row = db_fetch_object($q)) {
    $variables[] = $row->name;
  }
  array_walk($variables, 'variable_del');
  drupal_set_message(st('Removed G2 Glossary variables'), 'status');

  // @todo TODO check: node_type_delete(G2NODETYPE) needed or not ?
}

/**
 * Implements hook_update_N().
 *
 * - 6000: Update the schema for Drupal 6 first version.
 *   - remove g2_*[info|title] variables, which were used in block setup. Title is
 *     now managed by core, Info was really needed.
 *   - have a valid schema version recorded for future updates.
 *
 * @return array
 */
function g2_update_6000() {
  // Clean-up obsolete variables
  $sq = 'SELECT v.name '
      . 'FROM {variable} v '
      . "WHERE v.name LIKE 'g2_%%info' OR v.name LIKE 'g2_%%title' "
      . "  OR v.name LIKE 'g2/%%' ";
  $q = db_query($sq);

  $count = 0;
  while (is_object($row = db_fetch_object($q))) {
    variable_del($row->name);
    $count++;
  }
  $t = get_t();
  if ($count) {
    $message = $t('Removed @count G2 obsolete 4.7.x/5.x variables', array('@count' => $count));
    cache_clear_all('variables', 'cache');
  }
  else {
    $message = $t('No obsolete variable to clean.');
  }
  drupal_set_message($message, status);

  /** 
   * Convert Drupal 4.7.x/5.x block deltas
   *
   * This is really only needed for sites upgrading from D5.
   */
  module_load_include('inc', 'g2', 'g2_data');
  $delta_changes = array(
    0 => G2DELTAALPHABAR,
    1 => G2DELTARANDOM,
    2 => G2DELTATOP,
    3 => G2DELTAWOTD,
    4 => G2DELTALATEST,
  );
  $sq = "UPDATE {blocks} b SET delta = '%s' WHERE module = '%s' AND delta = %d ";
  $count = 0;
  foreach ($delta_changes as $old => $new) {
    db_query($sq, $new, 'g2', $old);
    $count += db_affected_rows();
  }
  
  if ($count) {
    $message = $t('Converted G2 block deltas to new format.');
    cache_clear_all('variables', 'cache');
  }
  else {
    $message = $t('No obsolete delta to convert.');
  }

  drupal_set_message($message, 'status');
  return array();
}

/**
 * Implement hook_update_N().
 *
 * - 6001: Convert "%" tokens from 4.7.x/5.1.[01] in the WOTD feed
 *   configuration to "!".
 *
 * This is really only needed for sites upgrading from D4.7 or D5.
 */
function g2_update_6001() {
  $count = 0;
  $wotd_author = variable_get(G2VARWOTDFEEDAUTHOR, G2DEFWOTDFEEDAUTHOR);
  if (strpos($wotd_author, '%author') !== FALSE) {
    variable_set(G2VARWOTDFEEDAUTHOR, str_replace('%author', '!author', $wotd_author));
    $count++;
  }
  $wotd_descr = variable_get(G2VARWOTDFEEDDESCR, G2DEFWOTDFEEDDESCR);
  if (strpos($wotd_descr, '%site') !== FALSE) {
    variable_set(G2VARWOTDFEEDDESCR, str_replace('%site', '!site', $wotd_descr));
    $count++;
  }

  $t = get_t();
  if ($count) {
    $message = $t('Replaced @count occurrences of old "percent" tokens by new "bang" ones on the <a href="!link">WOTD block feed settings</a>.', array( // coder false positive: !link is filtered
      '@count' => $count,
      '!link'  => url('admin/build/block/configure/g2/'. G2DELTAWOTD), // Constant: no need to check_url()
    ));
  }
  else {
    $message = $t('No old token to convert for the WOTD feed settings.');
  }
  drupal_set_message($message, 'status');
  return array();
}

/**
 * Implement hook_update_N().
 *
 * - 6002: Temporarily restore the g2_referer table: unlike the D5 branch, the current
 *   code in the 6.x and 7.x-1.x branches still uses it. The 7.x-2.x branch will
 *   likely remove it as in D5.
 *
 * This is really only needed for sites upgrading from D5.
 */
function g2_update_6002() {
  $ret = array();
  $t = get_t();
  if (!db_table_exists('g2_referer')) {
    $message = $t("Temporarily reinstating g2_referer table for current version. In future versions, use an external tracking module instead.");
    $schema = g2_schema();
    db_create_table($ret, 'g2_referer', $schema['g2_referer']);
  }
  else {
    $message = $t('g2_referer table was there. No need to recreate it.');
  }
  drupal_set_message($message, 'status');
  return $ret;
}

error_reporting($_g2_install_er);
unset($_g2_install_er);