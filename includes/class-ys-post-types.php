<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YS_Post_Types {

	/**
	 * Settings service.
	 *
	 * @var YS_Settings
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param YS_Settings|null $settings Settings instance.
	 */
	public function __construct( $settings = null ) {
		$this->settings = $settings instanceof YS_Settings ? $settings : new YS_Settings();
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', [ $this, 'register_post_types' ], 10 );
	}

	/**
	 * Register plugin-managed post types.
	 *
	 * @return void
	 */
	public function register_post_types() {
		register_post_type(
			$this->settings->get_plugin_post_type(),
			[
				'labels' => [
					'name'          => __( 'Yachts', 'yacht-selector' ),
					'singular_name' => __( 'Yacht', 'yacht-selector' ),
					'add_new_item'  => __( 'Add New Yacht', 'yacht-selector' ),
					'edit_item'     => __( 'Edit Yacht', 'yacht-selector' ),
					'view_item'     => __( 'View Yacht', 'yacht-selector' ),
					'menu_name'     => __( 'Yachts', 'yacht-selector' ),
				],
				'public'       => true,
				'show_ui'      => true,
				'show_in_menu' => true,
				'show_in_rest' => true,
				'has_archive'  => true,
				'rewrite'      => [
					'slug'       => 'yachts',
					'with_front' => false,
				],
				'menu_icon'    => 'dashicons-palmtree',
				'supports'     => [ 'title', 'editor', 'thumbnail' ],
			]
		);

		register_post_type(
			'ys_crew',
			[
				'labels' => [
					'name'          => __( 'Crew', 'yacht-selector' ),
					'singular_name' => __( 'Crew', 'yacht-selector' ),
					'add_new_item'  => __( 'Add New Crew', 'yacht-selector' ),
					'edit_item'     => __( 'Edit Crew', 'yacht-selector' ),
					'view_item'     => __( 'View Crew', 'yacht-selector' ),
					'menu_name'     => __( 'Crews', 'yacht-selector' ),
				],
				'public'       => true,
				'show_ui'      => true,
				'show_in_menu' => true,
				'show_in_rest' => true,
				'rewrite'      => [
					'slug'       => 'crew',
					'with_front' => false,
				],
				'menu_icon'    => 'dashicons-groups',
				'supports'     => [ 'title', 'editor', 'thumbnail' ],
			]
		);
	}
}
