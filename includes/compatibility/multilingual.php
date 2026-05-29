<?php
/**
 * Optional WPML / Polylang helper functions for Yacht Selector.
 *
 * Frontend UI labels use gettext (.po/.mo). Model and extra-feature option labels
 * from ys_settings are registered here for WPML/Polylang string translation and
 * translated at render time only (canonical DB values unchanged).
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
 * Whether any model/feature string translation backend is available.
 *
 * @return bool
 */
function ys_ml_model_feature_backend_active() {
	return ys_ml_wpml_active() || function_exists( 'pll_register_string' );
}

/**
 * Build a stable WPML/Polylang string name for a model option label.
 *
 * @param string $label Canonical model label.
 * @return string
 */
function ys_model_option_string_name( $label ) {
	return 'model_option_' . md5( 'model|' . (string) $label );
}

/**
 * Build a stable WPML/Polylang string name for an extra feature label.
 *
 * @param string $label Canonical feature label.
 * @return string
 */
function ys_extra_feature_string_name( $label ) {
	return 'extra_feature_' . md5( 'feature|' . (string) $label );
}

/**
 * Read merged plugin settings for model/feature registration.
 *
 * @return array{ys_model_options: string[], ys_extra_feature_options: string[]}
 */
function ys_ml_get_model_feature_settings() {
	if ( class_exists( 'YS_Settings' ) ) {
		$settings = ( new YS_Settings() )->get_settings();
	} else {
		$settings = get_option( 'ys_settings', [] );
		$settings = is_array( $settings ) ? $settings : [];
	}

	$model_options = isset( $settings['ys_model_options'] ) && is_array( $settings['ys_model_options'] )
		? $settings['ys_model_options']
		: [];
	$feature_options = isset( $settings['ys_extra_feature_options'] ) && is_array( $settings['ys_extra_feature_options'] )
		? $settings['ys_extra_feature_options']
		: [];

	return [
		'ys_model_options'         => ys_ml_sanitize_label_list( $model_options ),
		'ys_extra_feature_options' => ys_ml_sanitize_label_list( $feature_options ),
	];
}

/**
 * Sanitize a list of option labels for registration.
 *
 * @param mixed $values Raw values.
 * @return string[]
 */
function ys_ml_sanitize_label_list( $values ) {
	if ( ! is_array( $values ) ) {
		return [];
	}

	$labels = [];

	foreach ( $values as $value ) {
		if ( ! is_scalar( $value ) ) {
			continue;
		}

		$value = trim( sanitize_text_field( (string) $value ) );

		if ( '' === $value ) {
			continue;
		}

		$labels[] = $value;
	}

	return array_values( array_unique( $labels ) );
}

/**
 * Register one model or feature label with WPML and Polylang.
 *
 * @param string $string_name Stable string identifier.
 * @param string $label       Canonical label text.
 * @return void
 */
function ys_ml_register_option_label_string( $string_name, $label ) {
	$string_name = (string) $string_name;
	$label       = (string) $label;

	if ( '' === $string_name || '' === $label ) {
		return;
	}

	if ( ys_ml_wpml_active() ) {
		do_action(
			'wpml_register_single_string',
			YS_SELECTOR_ML_CONTEXT,
			$string_name,
			$label
		);
	}

	if ( function_exists( 'pll_register_string' ) ) {
		pll_register_string( $string_name, $label, YS_SELECTOR_ML_CONTEXT, false );
	}
}

/**
 * Register all model and extra-feature option labels for translation plugins.
 *
 * @return void
 */
function ys_register_model_feature_translation_strings() {
	if ( ! ys_ml_model_feature_backend_active() ) {
		return;
	}

	$settings = ys_ml_get_model_feature_settings();

	foreach ( $settings['ys_model_options'] as $label ) {
		ys_ml_register_option_label_string( ys_model_option_string_name( $label ), $label );
	}

	foreach ( $settings['ys_extra_feature_options'] as $label ) {
		ys_ml_register_option_label_string( ys_extra_feature_string_name( $label ), $label );
	}
}

/**
 * Translate a registered model or feature label for display.
 *
 * @param string $label       Canonical label from settings/meta.
 * @param string $string_name Stable string identifier.
 * @return string
 */
function ys_ml_translate_registered_option_label( $label, $string_name ) {
	$label       = trim( (string) $label );
	$string_name = (string) $string_name;

	if ( '' === $label ) {
		return $label;
	}

	if ( ys_ml_wpml_active() ) {
		return (string) apply_filters(
			'wpml_translate_single_string',
			$label,
			YS_SELECTOR_ML_CONTEXT,
			$string_name
		);
	}

	if ( function_exists( 'pll_translate_string' ) && function_exists( 'pll_current_language' ) ) {
		$language = pll_current_language( 'slug' );

		if ( is_string( $language ) && '' !== $language ) {
			return (string) pll_translate_string( $label, $language );
		}
	}

	if ( function_exists( 'pll__' ) ) {
		// Fallback when pll_translate_string is unavailable or language slug is missing.
		return (string) pll__( $label );
	}

	return $label;
}

/**
 * Translate a model option label for display.
 *
 * @param string|null $label Canonical model label from meta/settings.
 * @return string|null
 */
function ys_translate_model_option_label( $label ) {
	if ( null === $label ) {
		return null;
	}

	$label = trim( (string) $label );

	if ( '' === $label ) {
		return '';
	}

	return ys_ml_translate_registered_option_label( $label, ys_model_option_string_name( $label ) );
}

/**
 * Translate an extra feature label for display.
 *
 * @param string $label Canonical feature label from meta/settings.
 * @return string
 */
function ys_translate_extra_feature_label( $label ) {
	$label = trim( (string) $label );

	if ( '' === $label ) {
		return '';
	}

	return ys_ml_translate_registered_option_label( $label, ys_extra_feature_string_name( $label ) );
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

/**
 * Register model/feature strings during normal bootstrap.
 *
 * @return void
 */
function ys_register_model_feature_translation_strings_on_load() {
	ys_register_model_feature_translation_strings();
}

add_action( 'init', 'ys_register_model_feature_translation_strings_on_load' );
add_action( 'admin_init', 'ys_register_model_feature_translation_strings_on_load' );

/**
 * Re-register model/feature strings after settings are updated (including JSON import sync).
 *
 * @param string $option    Option name.
 * @param mixed  $old_value Previous value.
 * @param mixed  $value     New value.
 * @return void
 */
function ys_register_model_feature_strings_on_settings_update( $option, $old_value, $value ) {
	unset( $old_value, $value );

	if ( 'ys_settings' !== $option ) {
		return;
	}

	ys_register_model_feature_translation_strings();
}

add_action( 'updated_option', 'ys_register_model_feature_strings_on_settings_update', 10, 3 );
