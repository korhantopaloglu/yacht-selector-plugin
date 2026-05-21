<?php
/**
 * Optional WPML / Polylang compatibility for Yacht Selector settings-string output.
 *
 * Registers option-backed frontend labels so they appear in multilingual string UIs without
 * mutating saved options, taxonomies, or import data.
 *
 * @package Yacht_Selector
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'YS_SELECTOR_ML_CONTEXT' ) ) {
	define( 'YS_SELECTOR_ML_CONTEXT', 'Yacht Selector' );
}


/**
 * Bootstrap multilingual hooks once.
 *
 * @return void
 */
function ys_ml_bootstrap_compat() {
	add_action( 'init', 'ys_ml_register_option_strings_all', 20 );
	add_action( 'admin_init', 'ys_ml_register_option_strings_all', 20 );
	add_action(
		'updated_option',
		static function ( $option ) {
			if ( class_exists( 'YS_Settings' ) && \YS_Settings::OPTION_KEY === $option ) {
				ys_ml_register_option_strings_all();
			}
		},
		10,
		1
	);
}

/**
 * Frontend text keys stored inside {@see YS_Settings::OPTION_KEY} (merged with defaults).
 *
 * Uses the real option identifiers (there is “Watch Me”; no separate ys_watch_video_text field).
 *
 * @return array<int, string>
 */
function ys_ml_frontend_text_setting_keys() {
	return [
		'ys_watch_me_text',
		'ys_book_now_text',
		'ys_call_crew_text',
		'ys_booked_text',
		'ys_all_label_text',
		'ys_empty_state_text',
		'ys_connect_with_top_title',
		'ys_crew_group_title',
		'ys_crew_group_subtitle',
		'ys_currently_on_board_text',
		'ys_currently_offline_text',
	];
}

/**
 * Whether WPML SitePress appears active.
 *
 * @return bool
 */
function ys_ml_wpml_active() {
	return defined( 'ICL_SITEPRESS_VERSION' );
}

/**
 * Register one string record with multilingual plugins that are available.
 *
 * @param string $name  Stable identifier (recommended: the ys_* option key).
 * @param string $value Current source text; placeholders ({crew_member}, etc.) unchanged.
 * @return void
 */
function ys_register_multilingual_string( $name, $value ) {
	$name = (string) $name;

	if ( '' === trim( $name ) ) {
		return;
	}

	$value = (string) $value;

	if ( '' === $value ) {
		return;
	}

	if ( ys_ml_wpml_active() ) {
		do_action(
			'wpml_register_single_string',
			YS_SELECTOR_ML_CONTEXT,
			sanitize_key( $name ),
			$value
		);
	}

	if ( function_exists( 'pll_register_string' ) ) {
		pll_register_string( sanitize_key( $name ), $value, YS_SELECTOR_ML_CONTEXT, false );
	}
}

/**
 * Translate a multilingual string resolved from settings (or gettext fallback output).
 *
 * @param string $name  Stable identifier used when registering strings.
 * @param string $value Source text for the active language/context.
 * @return string
 */
function ys_translate_multilingual_string( $name, $value ) {
	$name  = (string) $name;
	$value = (string) $value;

	if ( '' === $value ) {
		return $value;
	}

	if ( ys_ml_wpml_active() ) {
		return (string) apply_filters(
			'wpml_translate_single_string',
			$value,
			YS_SELECTOR_ML_CONTEXT,
			sanitize_key( $name )
		);
	}

	if ( function_exists( 'pll__' ) ) {
		return (string) pll__( $value );
	}

	return $value;
}

/**
 * Register merged option strings whenever WordPress initializes (and after settings saves).
 *
 * @return void
 */
function ys_ml_register_option_strings_all() {
	if ( ! class_exists( 'YS_Settings' ) ) {
		return;
	}

	$snapshot = new YS_Settings();
	$merged   = $snapshot->get_settings();

	if ( ! is_array( $merged ) ) {
		return;
	}

	foreach ( ys_ml_frontend_text_setting_keys() as $key ) {
		if ( ! isset( $merged[ $key ] ) ) {
			continue;
		}

		$raw = (string) $merged[ $key ];
		if ( '' === $raw ) {
			continue;
		}

		ys_register_multilingual_string( $key, $raw );
	}
}

/**
 * Produce display text for frontend output: prefers stored ys_* strings, falls back to gettext literals.
 *
 * Mirrors existing shortcode empty checks and runs multilingual translation on the resolved value only.
 *
 * @param mixed  $settings YS_Settings instance or scalar/null (falls back safely).
 * @param string $option_key Option array key inside ys_settings (e.g. ys_watch_me_text).
 * @param string $gettext_fallback Fallback when the merged option resolves empty.
 * @return string
 */
function ys_frontend_option_display_text( $settings, string $option_key, string $gettext_fallback ): string {
	$gettext_fallback = (string) $gettext_fallback;

	$candidate = $gettext_fallback;

	if ( $settings instanceof YS_Settings ) {
		$merged = $settings->get_settings();
		if ( is_array( $merged ) && isset( $merged[ $option_key ] ) && '' !== (string) $merged[ $option_key ] ) {
			$candidate = (string) $merged[ $option_key ];
		}
	}

	return ys_translate_multilingual_string( $option_key, $candidate );
}

ys_ml_bootstrap_compat();
