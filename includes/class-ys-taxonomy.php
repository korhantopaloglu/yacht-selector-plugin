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
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( $this->get_taxonomy_slug() . '_add_form_fields', [ $this, 'render_add_form_fields' ] );
		add_action( $this->get_taxonomy_slug() . '_edit_form_fields', [ $this, 'render_edit_form_fields' ] );
		add_action( 'created_' . $this->get_taxonomy_slug(), [ $this, 'save_term_meta' ] );
		add_action( 'edited_' . $this->get_taxonomy_slug(), [ $this, 'save_term_meta' ] );
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
	 * Enqueue admin assets on location taxonomy screens.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( ! in_array( $hook_suffix, [ 'edit-tags.php', 'term.php' ], true ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || $screen->taxonomy !== $this->get_taxonomy_slug() ) {
			return;
		}

		wp_enqueue_style(
			'ys-admin-taxonomy',
			YS_PLUGIN_URL . 'assets/css/admin.css',
			[],
			YS_PLUGIN_VERSION
		);

		wp_enqueue_script(
			'ys-admin-taxonomy',
			YS_PLUGIN_URL . 'assets/js/admin.js',
			[],
			YS_PLUGIN_VERSION,
			true
		);

		wp_enqueue_media();
	}

	/**
	 * Render add form fields.
	 *
	 * @return void
	 */
	public function render_add_form_fields() {
		?>
		<div class="form-field ys-term-flag-field">
			<label for="ys_flag_image_id"><?php esc_html_e( 'Country Flag', 'yacht-selector' ); ?></label>
			<?php $this->render_flag_media_field(); ?>
			<p><?php esc_html_e( 'Only used for top-level locations (countries). If this term is saved as a child location, the flag will be ignored.', 'yacht-selector' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Render edit form fields for parent terms only.
	 *
	 * @param WP_Term $term Term object.
	 * @return void
	 */
	public function render_edit_form_fields( $term ) {
		if ( ! $term instanceof WP_Term || (int) $term->parent > 0 ) {
			return;
		}
		?>
		<tr class="form-field ys-term-flag-field">
			<th scope="row">
				<label for="ys_flag_image_id"><?php esc_html_e( 'Country Flag', 'yacht-selector' ); ?></label>
			</th>
			<td>
				<?php $this->render_flag_media_field( $term ); ?>
				<p class="description"><?php esc_html_e( 'Upload a flag image for this country. Child locations do not support flags.', 'yacht-selector' ); ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Save term meta for country flags.
	 *
	 * @param int $term_id Term ID.
	 * @return void
	 */
	public function save_term_meta( $term_id ) {
		if ( ! current_user_can( 'manage_categories' ) ) {
			return;
		}

		$term = get_term( $term_id, $this->get_taxonomy_slug() );

		if ( ! $term instanceof WP_Term ) {
			return;
		}

		$image_id = isset( $_POST['ys_flag_image_id'] ) ? absint( wp_unslash( $_POST['ys_flag_image_id'] ) ) : 0;

		if ( (int) $term->parent > 0 || ! $image_id || 'attachment' !== get_post_type( $image_id ) ) {
			delete_term_meta( $term_id, 'ys_flag_image_id' );
			return;
		}

		update_term_meta( $term_id, 'ys_flag_image_id', $image_id );
	}

	/**
	 * Get the stored country flag attachment ID.
	 *
	 * @param int $term_id Term ID.
	 * @return int
	 */
	public function get_term_flag_image_id( $term_id ) {
		return absint( get_term_meta( $term_id, 'ys_flag_image_id', true ) );
	}

	/**
	 * Get the flag URL for a term.
	 *
	 * @param int    $term_id Term ID.
	 * @param string $size Image size.
	 * @return string
	 */
	public function get_term_flag_url( $term_id, $size = 'thumbnail' ) {
		$image_id = $this->get_term_flag_image_id( $term_id );

		if ( ! $image_id ) {
			return '';
		}

		$url = wp_get_attachment_image_url( $image_id, $size );

		return $url ? (string) $url : '';
	}

	/**
	 * Render the reusable flag media field.
	 *
	 * @param WP_Term|null $term Term object.
	 * @return void
	 */
	private function render_flag_media_field( $term = null ) {
		$term_id   = $term instanceof WP_Term ? (int) $term->term_id : 0;
		$image_id  = $term_id ? $this->get_term_flag_image_id( $term_id ) : 0;
		$image_url = $image_id ? $this->get_term_flag_url( $term_id, 'thumbnail' ) : '';
		?>
		<div class="ys-field ys-term-media-field" data-ys-media-field>
			<input type="hidden" id="ys_flag_image_id" name="ys_flag_image_id" value="<?php echo esc_attr( $image_id ); ?>" data-ys-image-id />
			<div class="ys-image-preview-wrap ys-image-preview-wrap-small<?php echo $image_url ? '' : ' is-empty'; ?>" data-ys-image-preview-wrap>
				<?php if ( $image_url ) : ?>
					<img src="<?php echo esc_url( $image_url ); ?>" alt="" class="ys-image-preview ys-image-preview-small" data-ys-image-preview />
					<span class="ys-image-placeholder" data-ys-image-placeholder hidden><?php esc_html_e( 'No image selected.', 'yacht-selector' ); ?></span>
				<?php else : ?>
					<img src="" alt="" class="ys-image-preview ys-image-preview-small" data-ys-image-preview hidden />
					<span class="ys-image-placeholder" data-ys-image-placeholder><?php esc_html_e( 'No image selected.', 'yacht-selector' ); ?></span>
				<?php endif; ?>
			</div>
			<div class="ys-image-actions">
				<button type="button" class="button" data-ys-media-select data-ys-media-title="<?php echo esc_attr__( 'Select Country Flag', 'yacht-selector' ); ?>" data-ys-media-button="<?php echo esc_attr__( 'Use flag', 'yacht-selector' ); ?>" data-ys-media-replace="<?php echo esc_attr__( 'Change Flag', 'yacht-selector' ); ?>" data-ys-media-default="<?php echo esc_attr__( 'Select Flag', 'yacht-selector' ); ?>">
					<?php echo $image_id ? esc_html__( 'Change Flag', 'yacht-selector' ) : esc_html__( 'Select Flag', 'yacht-selector' ); ?>
				</button>
				<button type="button" class="button-link-delete<?php echo $image_id ? '' : ' hidden'; ?>" data-ys-media-remove>
					<?php esc_html_e( 'Remove', 'yacht-selector' ); ?>
				</button>
			</div>
		</div>
		<?php
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
	 * Get the active post type setting.
	 *
	 * @return string
	 */
	public function get_selected_post_type() {
		return sanitize_key( $this->settings->get_active_post_type() );
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
