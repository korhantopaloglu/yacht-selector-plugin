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
 * Frontend display strings for the yacht selector shortcode (gettext only).
 *
 * @return array<string, string>
 */
function ys_frontend_display_strings() {
	return [
		'watch_text'     => __( 'Watch Video', 'yacht-selector' ),
		'call_text'      => __( 'Call the Crew', 'yacht-selector' ),
		'book_text'      => __( 'Book Now', 'yacht-selector' ),
		'booked_text'    => __( 'Booked', 'yacht-selector' ),
		'all_label_text' => __( 'All', 'yacht-selector' ),
		'empty_text'     => __( 'No results found', 'yacht-selector' ),
		'crew_top_title' => __( 'Connect with', 'yacht-selector' ),
		'crew_title'     => __( 'The Crew', 'yacht-selector' ),
		'crew_subtitle'  => __( 'See the yacht live - choose how you\'d like to connect. The crew is currently on board.', 'yacht-selector' ),
		'on_board_text'  => __( 'Currently on board: {crew_member}', 'yacht-selector' ),
		'offline_text'   => __( 'Currently offline: {crew_member}', 'yacht-selector' ),
	];
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
