<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YS_Shortcode {

	public function register() {
		add_shortcode( 'yacht_selector', [ $this, 'render' ] );
	}

	public function render( $atts = [] ) {
		ob_start();

		$template = YS_PLUGIN_PATH . 'templates/shortcode-output.php';

		if ( file_exists( $template ) ) {
			include $template;
		} else {
			echo '<p>Template not found.</p>';
		}

		return ob_get_clean();
	}
}