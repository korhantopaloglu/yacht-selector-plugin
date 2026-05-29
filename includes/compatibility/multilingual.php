<?php
/**
 * Optional WPML / Polylang helper functions for Yacht Selector.
 *
 * Frontend UI labels are translated via gettext (.po/.mo). These helpers remain available
 * for optional custom string registration elsewhere; they do not register option-backed
 * frontend text settings.
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
 * @param string $name  Stable identifier.
 * @param string $value Source text; placeholders ({crew_member}, etc.) unchanged.
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
 * Translate a string registered via ys_register_multilingual_string().
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
