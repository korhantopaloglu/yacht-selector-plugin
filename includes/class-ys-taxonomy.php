<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YS_Taxonomy {

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
	 * Register taxonomy hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', [ $this, 'register_taxonomy' ], 20 );
		add_action( 'admin_notices', [ $this, 'maybe_add_admin_notice' ] );
	}

	/**
	 * Register the location taxonomy.
	 *
	 * @return void
	 */
	public function register_taxonomy() {
		register_taxonomy(
			$this->get_taxonomy_slug(),
			$this->get_object_types(),
			[
				'labels'            => $this->get_labels(),
				'public'            => true,
				'hierarchical'      => true,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'query_var'         => true,
				'rewrite'           => [
					'slug'         => $this->get_taxonomy_slug(),
					'with_front'   => false,
					'hierarchical' => true,
				],
			]
		);
	}

	/**
	 * Get the active taxonomy slug.
	 *
	 * @return string
	 */
	public function get_taxonomy_slug() {
		$slug = $this->settings->get_setting( 'ys_location_taxonomy_slug', 'ys_location' );
		$slug = sanitize_key( $slug );

		return '' !== $slug ? $slug : 'ys_location';
	}

	/**
	 * Get the selected post type setting.
	 *
	 * @return string
	 */
	public function get_selected_post_type() {
		return sanitize_key( $this->settings->get_setting( 'ys_selected_post_type', '' ) );
	}

	/**
	 * Check if a post type is valid.
	 *
	 * @param string $post_type Post type key.
	 * @return bool
	 */
	public function is_valid_post_type( $post_type ) {
		if ( '' === $post_type || ! post_type_exists( $post_type ) ) {
			return false;
		}

		$post_type_object = get_post_type_object( $post_type );

		return $post_type_object instanceof WP_Post_Type;
	}

	/**
	 * Get the object types to attach the taxonomy to.
	 *
	 * @return array
	 */
	public function get_object_types() {
		$post_type = $this->get_selected_post_type();

		if ( ! $this->is_valid_post_type( $post_type ) ) {
			return [];
		}

		return [ $post_type ];
	}

	/**
	 * Get taxonomy labels.
	 *
	 * @return array
	 */
	public function get_labels() {
		return [
			'name'                       => __( 'Locations', 'yacht-selector' ),
			'singular_name'              => __( 'Location', 'yacht-selector' ),
			'search_items'               => __( 'Search Locations', 'yacht-selector' ),
			'all_items'                  => __( 'All Locations', 'yacht-selector' ),
			'parent_item'                => __( 'Parent Location', 'yacht-selector' ),
			'parent_item_colon'          => __( 'Parent Location:', 'yacht-selector' ),
			'edit_item'                  => __( 'Edit Location', 'yacht-selector' ),
			'view_item'                  => __( 'View Location', 'yacht-selector' ),
			'update_item'                => __( 'Update Location', 'yacht-selector' ),
			'add_new_item'               => __( 'Add New Location', 'yacht-selector' ),
			'new_item_name'              => __( 'New Location Name', 'yacht-selector' ),
			'menu_name'                  => __( 'Locations', 'yacht-selector' ),
			'not_found'                  => __( 'No locations found.', 'yacht-selector' ),
			'back_to_items'              => __( 'Back to Locations', 'yacht-selector' ),
		];
	}

	/**
	 * Display an admin notice when taxonomy configuration is incomplete.
	 *
	 * @return void
	 */
	public function maybe_add_admin_notice() {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && 'settings_page_ys-settings' === $screen->id ) {
			return;
		}

		$post_type = $this->get_selected_post_type();
		if ( $this->is_valid_post_type( $post_type ) ) {
			return;
		}

		$settings_url = admin_url( 'options-general.php?page=ys-settings' );
		$message      = '' === $post_type
			? __( 'Yacht Selector location taxonomy is registered, but it is not attached to a post type yet. Choose a post type in Yacht Selector settings.', 'yacht-selector' )
			: sprintf(
				/* translators: %s: invalid post type slug */
				__( 'Yacht Selector location taxonomy is not attached because the saved post type "%s" is no longer available. Choose a valid post type in Yacht Selector settings.', 'yacht-selector' ),
				$post_type
			);
		?>
		<div class="notice notice-warning">
			<p>
				<?php echo esc_html( $message ); ?>
				<a href="<?php echo esc_url( $settings_url ); ?>">
					<?php esc_html_e( 'Open settings', 'yacht-selector' ); ?>
				</a>
			</p>
		</div>
		<?php
	}
}
