<?php
/**
 * Plugin Name: Yacht Selector
 * Plugin URI:  https://github.com/korhantopaloglu/yacht-selector-plugin
 * Description: Yacht selection plugin with shortcode-based frontend rendering.
 * Version:     0.1.0
 * Author:      Korhan Topaloglu
 * Author URI:  https://www.linkedin.com/in/korhantopaloglu/
 * Text Domain: yacht-selector
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Constants
define( 'YS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'YS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'YS_PLUGIN_VERSION', '0.1.0' );

// Includes
require_once YS_PLUGIN_PATH . 'includes/class-ys-plugin.php';

// Init
function ys_init_plugin() {
	$plugin = new YS_Plugin();
	$plugin->init();
}
add_action( 'plugins_loaded', 'ys_init_plugin' );