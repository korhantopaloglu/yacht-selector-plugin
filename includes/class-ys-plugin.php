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
		require_once YS_PLUGIN_PATH . 'includes/class-ys-shortcode.php';
		require_once YS_PLUGIN_PATH . 'includes/class-ys-data-provider.php';
		require_once YS_PLUGIN_PATH . 'includes/class-ys-settings.php';
		require_once YS_PLUGIN_PATH . 'includes/class-ys-post-types.php';
		require_once YS_PLUGIN_PATH . 'includes/class-ys-taxonomy.php';
		require_once YS_PLUGIN_PATH . 'includes/class-ys-contact-tools.php';
		require_once YS_PLUGIN_PATH . 'includes/class-ys-meta-boxes.php';
		require_once YS_PLUGIN_PATH . 'includes/class-ys-crew-meta-boxes.php';
	}

	private function init_modules() {
		$settings = new YS_Settings();
		$settings->register();

		$post_types = new YS_Post_Types( $settings );
		$post_types->register();

		$taxonomy = new YS_Taxonomy( $settings );
		$taxonomy->register();

		$contact_tools = new YS_Contact_Tools();
		$contact_tools->register();

		$data_provider = new YS_Data_Provider( $settings, $taxonomy );

		$shortcode = new YS_Shortcode( $settings, $taxonomy, $data_provider );
		$shortcode->register();

		$meta_boxes = new YS_Meta_Boxes( $settings, $taxonomy );
		$meta_boxes->register();

		$crew_meta_boxes = new YS_Crew_Meta_Boxes();
		$crew_meta_boxes->register();
	}
}
