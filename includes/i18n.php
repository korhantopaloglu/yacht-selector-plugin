<?php
/**
 * Internationalization helpers.
 *
 * @package Yacht_Selector
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load plugin translations.
 *
 * @return void
 */
function ys_plugin_load_textdomain() {
	load_plugin_textdomain(
		'yacht-selector',
		false,
		dirname( plugin_basename( YS_PLUGIN_FILE ) ) . '/languages/'
	);
}

/**
 * Strings passed to frontend JavaScript via wp_localize_script (ys-frontend-selector).
 *
 * @return array<string, string>
 */
function ys_frontend_script_i18n() {
	return [
		'statusOnlineTpl'  => __( 'Currently on board: {crew_member}', 'yacht-selector' ),
		'statusOfflineTpl' => __( 'Currently offline: {crew_member}', 'yacht-selector' ),
	];
}

/**
 * Strings passed to admin JavaScript via wp_localize_script.
 *
 * @return array<string, string>
 */
function ys_admin_script_i18n() {
	return [
		'mediaTitleFallback'  => __( 'Select Card Image', 'yacht-selector' ),
		'mediaButtonFallback' => __( 'Use image', 'yacht-selector' ),
		'replaceImageFallback'=> __( 'Replace Image', 'yacht-selector' ),
		'selectImageFallback' => __( 'Select Image', 'yacht-selector' ),
	];
}

/**
 * Attach translated strings so admin JS can read window.ysAdminI18n.
 *
 * Admin screens enqueue `admin.js` under different handles; localize each handle when enqueued.
 *
 * @param string $handle Script handle passed to wp_enqueue_script.
 * @return void
 */
function ys_localize_admin_script( $handle ) {
	wp_localize_script(
		$handle,
		'ysAdminI18n',
		ys_admin_script_i18n()
	);
}
