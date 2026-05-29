<?php
/**
 * Plugin Name: Yacht Selector
 * Plugin URI:  https://github.com/korhantopaloglu/yacht-selector-plugin
 * Description: Yacht selection plugin with shortcode-based frontend rendering.
 * Version:     1.1.7
 * Author:      Korhan Topaloglu
 * Author URI:  https://www.linkedin.com/in/korhantopaloglu/
 * Text Domain: yacht-selector
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Constants
define( 'YS_PLUGIN_FILE', __FILE__ );
define( 'YS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'YS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'YS_PLUGIN_VERSION', '1.1.7' );

require_once YS_PLUGIN_PATH . 'includes/i18n.php';
require_once YS_PLUGIN_PATH . 'includes/compatibility/multilingual.php';

add_action( 'plugins_loaded', 'ys_plugin_load_textdomain', 5 );

/**
 * Add Settings link on the Plugins screen (beside Deactivate).
 *
 * @param array $links Existing plugin action links.
 * @return array
 */
function ys_plugin_action_links( $links ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return $links;
	}

	$settings_url = admin_url( 'options-general.php?page=ys-settings' );

	$settings_link = sprintf(
		'<a href="%s">%s</a>',
		esc_url( $settings_url ),
		esc_html__( 'Settings', 'yacht-selector' )
	);

	array_unshift( $links, $settings_link );

	return $links;
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'ys_plugin_action_links' );

// Includes
require_once YS_PLUGIN_PATH . 'includes/class-ys-plugin.php';

// Init
function ys_init_plugin() {
	$plugin = new YS_Plugin();
	$plugin->init();
}
add_action( 'plugins_loaded', 'ys_init_plugin' );