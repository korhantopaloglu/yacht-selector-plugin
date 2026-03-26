<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YS_Plugin {

	public function init() {
		$this->load_dependencies();
		$this->init_modules();
	}

	private function load_dependencies() {
		require_once YS_PLUGIN_PATH . 'includes/class-yacht-selector-shortcode.php';
		require_once YS_PLUGIN_PATH . 'includes/class-yacht-selector-data.php';
	}

	private function init_modules() {
		// Shortcode
		$shortcode = new YS_Shortcode();
		$shortcode->register();
	}
}