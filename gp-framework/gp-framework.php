<?php if ( ! defined( 'ABSPATH' ) ) { die; } // Cannot access directly.
/**
 *
 * @package   Guava Pattern - WordPress Theme Development Framework
 * @link      https://guavapattern.com
 * @license   GPL-2.0-or-later
 *
 * Guava Pattern Framework 2.0.0
 *
 * The options engine is built on the Codestar Framework 2.3.1
 * (Copyright 2015-2022 Codestar, https://codestarframework.com, GPL-2.0-or-later),
 * rebuilt for Guava Pattern: GP / gp_ prefixes, a native metabox, bundled assets,
 * and the dynamic gp_place() / gp_page() option registry.
 *
 * Text Domain: gp-framework
 * Domain Path: /languages
 *
 */

// ------------------------------------------------------------------------------------------------
// Constants
// ------------------------------------------------------------------------------------------------
if ( ! defined( 'GP_VERSION' ) ) {
  $gp_framework_info = ( function_exists( 'gp_info' ) ) ? gp_info() : array();
  define( 'GP_VERSION', ( ! empty( $gp_framework_info['version'] ) ) ? $gp_framework_info['version'] : '2.0.0' );
  unset( $gp_framework_info );
}

defined( 'GP_OPTION' ) or define( 'GP_OPTION', '_gp_options' ); // options row (admin panel)
defined( 'GP_META' )   or define( 'GP_META',   'gp_meta' );     // page metabox row

// ------------------------------------------------------------------------------------------------
// Core
// ------------------------------------------------------------------------------------------------
require_once dirname( __FILE__ ) .'/classes/setup.class.php';

// Placeholder tokens: {{gp:key|type|group}} in block theme HTML files (see functions/tokens.php)
GP_Setup::include_plugin_file( 'functions/tokens.php' );

// ------------------------------------------------------------------------------------------------
// Configs: build the admin panel and the page metabox before GP::setup() runs
// ------------------------------------------------------------------------------------------------
if ( ! function_exists( 'gp_framework_load_configs' ) ) {
  function gp_framework_load_configs() {

    static $loaded = false;

    if ( $loaded ) {
      return;
    }

    $loaded = true;

    // Every module can be switched off before this point:
    // define( 'GP_ACTIVE_FRAMEWORK', false ); define( 'GP_ACTIVE_METABOX', false );
    if ( ! defined( 'GP_ACTIVE_FRAMEWORK' ) || GP_ACTIVE_FRAMEWORK ) {
      GP_Setup::include_plugin_file( 'config/framework.config.php' );
    }

    if ( ! defined( 'GP_ACTIVE_METABOX' ) || GP_ACTIVE_METABOX ) {
      GP_Setup::include_plugin_file( 'config/metabox.config.php' );
    }

  }
  add_action( 'after_setup_theme', 'gp_framework_load_configs', 5 );
  add_action( 'init', 'gp_framework_load_configs', 5 );
  add_action( 'plugins_loaded', 'gp_framework_load_configs', 5 );
}
