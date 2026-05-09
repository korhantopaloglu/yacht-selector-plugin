<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YS_Meta_Boxes {

	/**
	 * Nonce action.
	 */
	const NONCE_ACTION = 'ys_save_meta_boxes';

	/**
	 * Nonce field name.
	 */
	const NONCE_NAME = 'ys_meta_boxes_nonce';

	/**
	 * Settings service.
	 *
	 * @var YS_Settings
	 */
	private $settings;

	/**
	 * Taxonomy service.
	 *
	 * @var YS_Taxonomy|null
	 */
	private $taxonomy;

	/**
	 * Constructor.
	 *
	 * @param YS_Settings|null $settings Settings instance.
	 * @param YS_Taxonomy|null $taxonomy Taxonomy instance.
	 */
	public function __construct( $settings = null, $taxonomy = null ) {
		$this->settings = $settings instanceof YS_Settings ? $settings : new YS_Settings();
		$this->taxonomy = $taxonomy instanceof YS_Taxonomy ? $taxonomy : null;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
		add_action( 'save_post', [ $this, 'save_meta_boxes' ], 10, 2 );
		add_action( 'save_post', [ $this, 'save_inline_edit_booked_months' ], 20, 2 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'quick_edit_custom_box', [ $this, 'render_quick_edit_booked_field' ], 10, 2 );
		add_action( 'bulk_edit_custom_box', [ $this, 'render_bulk_edit_booked_field' ], 10, 2 );

		$post_type = $this->get_selected_post_type();

		if ( $this->is_valid_post_type( $post_type ) ) {
			add_filter( 'manage_edit-' . $post_type . '_posts_columns', [ $this, 'add_booked_months_column' ] );
			add_action( 'manage_' . $post_type . '_posts_custom_column', [ $this, 'render_booked_months_column' ], 10, 2 );
		}
	}

	/**
	 * Add plugin meta boxes for the selected post type.
	 *
	 * @return void
	 */
	public function add_meta_boxes() {
		$post_type = $this->get_selected_post_type();
		$taxonomy  = $this->get_location_taxonomy_slug();

		if ( ! $this->is_valid_post_type( $post_type ) ) {
			return;
		}

		add_meta_box(
			'ys_admin_panel',
			__( 'Yacht Options', 'yacht-selector' ),
			[ $this, 'render_admin_panel' ],
			$post_type,
			'normal',
			'high'
		);

		if ( '' !== $taxonomy ) {
			remove_meta_box( $taxonomy . 'div', $post_type, 'side' );
			remove_meta_box( $taxonomy . 'div', $post_type, 'normal' );
			remove_meta_box( $taxonomy . 'div', $post_type, 'advanced' );
		}
	}

	/**
	 * Enqueue admin assets on the selected post type edit screen.
	 *
	 * @param string $hook_suffix Admin screen hook suffix.
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( ! in_array( $hook_suffix, [ 'post.php', 'post-new.php', 'edit.php' ], true ) ) {
			return;
		}

		$screen    = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$post_type = $this->get_selected_post_type();

		if ( ! $screen || $screen->post_type !== $post_type || ! $this->is_valid_post_type( $post_type ) ) {
			return;
		}

		wp_enqueue_style(
			'ys-admin-meta-boxes',
			YS_PLUGIN_URL . 'assets/css/admin.css',
			[],
			YS_PLUGIN_VERSION
		);

		wp_enqueue_script(
			'ys-admin-meta-boxes',
			YS_PLUGIN_URL . 'assets/js/admin.js',
			[ 'jquery' ],
			YS_PLUGIN_VERSION,
			true
		);

		wp_enqueue_media();
	}

	/**
	 * Add the booked months admin list column.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function add_booked_months_column( $columns ) {
		$columns['ys_booked_months'] = __( 'Booked Months', 'yacht-selector' );

		return $columns;
	}

	/**
	 * Render booked months in the admin list table.
	 *
	 * @param string $column Column key.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public function render_booked_months_column( $column, $post_id ) {
		if ( 'ys_booked_months' !== $column ) {
			return;
		}

		$values = $this->get_stored_booked_values( $post_id );
		$raw    = implode( ', ', $values );

		echo '<span class="hidden" data-ys-booked-values="' . esc_attr( $raw ) . '"></span>';

		if ( empty( $values ) ) {
			echo '&mdash;';
			return;
		}

		echo esc_html( implode( ', ', $this->format_booked_month_labels( $values ) ) );
	}

	/**
	 * Render booked quick edit controls.
	 *
	 * @param string $column_name Column key.
	 * @param string $post_type Post type key.
	 * @return void
	 */
	public function render_quick_edit_booked_field( $column_name, $post_type ) {
		if ( 'ys_booked_months' !== $column_name || $post_type !== $this->get_selected_post_type() ) {
			return;
		}
		?>
		<fieldset class="inline-edit-col-right ys-quick-edit-fieldset">
			<div class="inline-edit-col">
				<label>
					<span class="title"><?php esc_html_e( 'Booked Months', 'yacht-selector' ); ?></span>
					<textarea name="ys_quick_booked_months" class="ys-quick-booked-months" rows="2" placeholder="2026-06, 2026-07"></textarea>
				</label>
				<p class="description"><?php esc_html_e( 'Comma-separated YYYY-MM values. Example: 2026-06, 2026-07', 'yacht-selector' ); ?></p>
				<input type="hidden" name="ys_quick_booked_present" value="1" />
			</div>
		</fieldset>
		<?php
	}

	/**
	 * Render booked bulk edit controls.
	 *
	 * @param string $column_name Column key.
	 * @param string $post_type Post type key.
	 * @return void
	 */
	public function render_bulk_edit_booked_field( $column_name, $post_type ) {
		if ( 'ys_booked_months' !== $column_name || $post_type !== $this->get_selected_post_type() ) {
			return;
		}
		?>
		<fieldset class="inline-edit-col-right ys-bulk-edit-fieldset">
			<div class="inline-edit-col">
				<label class="alignleft">
					<span class="title"><?php esc_html_e( 'Booked Months', 'yacht-selector' ); ?></span>
					<select name="ys_bulk_booked_action" class="ys-bulk-booked-action" data-ys-bulk-booked-action>
						<option value=""><?php esc_html_e( 'No Change', 'yacht-selector' ); ?></option>
						<option value="replace"><?php esc_html_e( 'Replace Booked Months', 'yacht-selector' ); ?></option>
						<option value="add"><?php esc_html_e( 'Add Booked Months', 'yacht-selector' ); ?></option>
						<option value="remove"><?php esc_html_e( 'Remove Booked Months', 'yacht-selector' ); ?></option>
					</select>
				</label>
				<p class="description"><?php esc_html_e( 'Enter comma-separated YYYY-MM values. Replace overwrites all values, Add merges into existing values, Remove deletes matching values.', 'yacht-selector' ); ?></p>
				<div class="ys-bulk-booked-input" data-ys-bulk-booked-input hidden>
					<textarea name="ys_bulk_booked_months" class="ys-bulk-booked-months" rows="2" placeholder="2026-06, 2026-07"></textarea>
				</div>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * Render the master admin panel meta box.
	 *
	 * @param WP_Post $post Post object.
	 * @return void
	 */
	public function render_admin_panel( $post ) {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		?>
		<div class="ys-admin-panel" data-ys-admin-panel>
			<div class="ys-admin-tabs-nav" role="tablist" aria-label="<?php esc_attr_e( 'Yacht Selector Sections', 'yacht-selector' ); ?>">
				<button type="button" class="ys-tab-button is-active" data-ys-tab="location" aria-selected="true"><?php esc_html_e( 'Location & Booked', 'yacht-selector' ); ?></button>
				<button type="button" class="ys-tab-button" data-ys-tab="specs" aria-selected="false"><?php esc_html_e( 'Specifications', 'yacht-selector' ); ?></button>
				<button type="button" class="ys-tab-button" data-ys-tab="features" aria-selected="false"><?php esc_html_e( 'Features', 'yacht-selector' ); ?></button>
				<button type="button" class="ys-tab-button" data-ys-tab="crew" aria-selected="false"><?php esc_html_e( 'Crew Members', 'yacht-selector' ); ?></button>
				<button type="button" class="ys-tab-button" data-ys-tab="media" aria-selected="false"><?php esc_html_e( 'Media & CTA', 'yacht-selector' ); ?></button>
			</div>

			<div class="ys-admin-tabs-panels">
				<div class="ys-tab-panel is-active" data-ys-panel="location">
					<?php $this->render_location_tab( $post ); ?>
				</div>
				<div class="ys-tab-panel" data-ys-panel="specs" hidden>
					<?php $this->render_specifications_tab( $post ); ?>
				</div>
				<div class="ys-tab-panel" data-ys-panel="features" hidden>
					<?php $this->render_features_tab( $post ); ?>
				</div>
				<div class="ys-tab-panel" data-ys-panel="crew" hidden>
					<?php $this->render_crew_members_tab( $post ); ?>
				</div>
				<div class="ys-tab-panel" data-ys-panel="media" hidden>
					<?php $this->render_media_tab( $post ); ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Save meta box data.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post Post object.
	 * @return void
	 */
	public function save_meta_boxes( $post_id, $post ) {
		$post_type = $this->get_selected_post_type();

		if ( ! $post instanceof WP_Post || ! $this->is_valid_post_type( $post_type ) || $post->post_type !== $post_type ) {
			return;
		}

		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		$post_type_object = get_post_type_object( $post_type );
		if ( ! $post_type_object || ! current_user_can( $post_type_object->cap->edit_post, $post_id ) ) {
			return;
		}

		$this->save_booked_meta( $post_id, $this->sanitize_booked_values( isset( $_POST['ys_booked'] ) ? wp_unslash( $_POST['ys_booked'] ) : [] ) );
		$this->save_scalar_meta( $post_id, 'ys_model', $this->sanitize_model_value( isset( $_POST['ys_model'] ) ? wp_unslash( $_POST['ys_model'] ) : '' ) );
		$this->save_scalar_meta( $post_id, 'ys_length', $this->sanitize_decimal_value( isset( $_POST['ys_length'] ) ? wp_unslash( $_POST['ys_length'] ) : '' ) );
		$this->save_scalar_meta( $post_id, 'ys_beam', $this->sanitize_decimal_value( isset( $_POST['ys_beam'] ) ? wp_unslash( $_POST['ys_beam'] ) : '' ) );
		$this->save_scalar_meta( $post_id, 'ys_engine', $this->sanitize_text_value( isset( $_POST['ys_engine'] ) ? wp_unslash( $_POST['ys_engine'] ) : '' ) );
		$this->save_scalar_meta( $post_id, 'ys_build_year', $this->sanitize_integer_value( isset( $_POST['ys_build_year'] ) ? wp_unslash( $_POST['ys_build_year'] ) : '' ) );
		$this->save_scalar_meta( $post_id, 'ys_refit_year', $this->sanitize_integer_value( isset( $_POST['ys_refit_year'] ) ? wp_unslash( $_POST['ys_refit_year'] ) : '' ) );
		$this->save_scalar_meta( $post_id, 'ys_cabins', $this->sanitize_integer_value( isset( $_POST['ys_cabins'] ) ? wp_unslash( $_POST['ys_cabins'] ) : '' ) );
		$this->save_scalar_meta( $post_id, 'ys_guests', $this->sanitize_integer_value( isset( $_POST['ys_guests'] ) ? wp_unslash( $_POST['ys_guests'] ) : '' ) );
		$this->save_scalar_meta( $post_id, 'ys_crew', $this->sanitize_integer_value( isset( $_POST['ys_crew'] ) ? wp_unslash( $_POST['ys_crew'] ) : '' ) );
		$this->save_scalar_meta( $post_id, 'ys_priority', $this->sanitize_integer_value( isset( $_POST['ys_priority'] ) ? wp_unslash( $_POST['ys_priority'] ) : '' ) );
		$this->save_array_meta( $post_id, 'ys_extra_features', $this->sanitize_extra_features_values( isset( $_POST['ys_extra_features'] ) ? wp_unslash( $_POST['ys_extra_features'] ) : [] ) );
		$this->save_array_meta( $post_id, 'ys_assigned_crew', $this->sanitize_crew_ids( isset( $_POST['ys_assigned_crew'] ) ? wp_unslash( $_POST['ys_assigned_crew'] ) : [] ) );
		$this->save_scalar_meta( $post_id, 'ys_card_image_id', $this->sanitize_attachment_id( isset( $_POST['ys_card_image_id'] ) ? wp_unslash( $_POST['ys_card_image_id'] ) : '' ) );
	}

	/**
	 * Save booked months from inline edit forms.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post Post object.
	 * @return void
	 */
	public function save_inline_edit_booked_months( $post_id, $post ) {
		$post_type = $this->get_selected_post_type();

		if ( ! $post instanceof WP_Post || ! $this->is_valid_post_type( $post_type ) || $post->post_type !== $post_type ) {
			return;
		}

		if ( ! isset( $_REQUEST['_inline_edit'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_inline_edit'] ) ), 'inlineeditnonce' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		$post_type_object = get_post_type_object( $post_type );
		if ( ! $post_type_object || ! current_user_can( $post_type_object->cap->edit_post, $post_id ) ) {
			return;
		}

		$has_quick_input = isset( $_REQUEST['ys_quick_booked_present'] );
		$bulk_action     = isset( $_REQUEST['ys_bulk_booked_action'] ) ? sanitize_key( wp_unslash( $_REQUEST['ys_bulk_booked_action'] ) ) : '';

		if ( ! $has_quick_input && '' === $bulk_action ) {
			return;
		}

		if ( $has_quick_input ) {
			$values = $this->sanitize_booked_text_input( isset( $_REQUEST['ys_quick_booked_months'] ) ? wp_unslash( $_REQUEST['ys_quick_booked_months'] ) : '' );
			$this->save_booked_meta( $post_id, $values );

			return;
		}

		if ( ! in_array( $bulk_action, [ 'replace', 'add', 'remove' ], true ) ) {
			return;
		}

		$input_values = $this->sanitize_booked_text_input( isset( $_REQUEST['ys_bulk_booked_months'] ) ? wp_unslash( $_REQUEST['ys_bulk_booked_months'] ) : '' );
		$stored       = $this->get_stored_booked_values( $post_id );

		if ( 'replace' === $bulk_action ) {
			$this->save_booked_meta( $post_id, $input_values );
			return;
		}

		if ( empty( $input_values ) ) {
			return;
		}

		if ( 'add' === $bulk_action ) {
			$this->save_booked_meta( $post_id, $this->normalize_booked_values( array_merge( $stored, $input_values ) ) );
			return;
		}

		$this->save_booked_meta( $post_id, $this->normalize_booked_values( array_diff( $stored, $input_values ) ) );
	}

	/**
	 * Sanitize booked month values from comma/newline-separated text.
	 *
	 * @param mixed $value Raw value.
	 * @return array
	 */
	private function sanitize_booked_text_input( $value ) {
		if ( ! is_string( $value ) ) {
			return [];
		}

		$chunks = preg_split( '/[\r\n,]+/', $value );

		if ( ! is_array( $chunks ) ) {
			return [];
		}

		return $this->normalize_booked_values( $chunks );
	}

	/**
	 * Get active post type.
	 *
	 * @return string
	 */
	public function get_selected_post_type() {
		return sanitize_key( $this->settings->get_active_post_type() );
	}

	/**
	 * Check whether a post type is valid.
	 *
	 * @param string $post_type Post type key.
	 * @return bool
	 */
	public function is_valid_post_type( $post_type ) {
		if ( '' === $post_type || ! post_type_exists( $post_type ) ) {
			return false;
		}

		return get_post_type_object( $post_type ) instanceof WP_Post_Type;
	}

	/**
	 * Render Location & Booked meta box.
	 *
	 * @param WP_Post $post Post object.
	 * @return void
	 */
	public function render_location_booked_box( $post ) {
		$taxonomy_slug = $this->taxonomy ? $this->taxonomy->get_taxonomy_slug() : sanitize_key( $this->settings->get_setting( 'ys_location_taxonomy_slug', 'ys_location' ) );
		$saved_values  = $this->get_saved_booked_values( $post->ID );

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		?>
		<p>
			<?php
			echo esc_html(
				sprintf(
					/* translators: %s: taxonomy slug */
					__( 'Choose a Port using the native Location taxonomy box. The plugin-managed taxonomy slug is currently "%s", with Country as parent terms and Port as child terms.', 'yacht-selector' ),
					$taxonomy_slug
				)
			);
			?>
		</p>

		<div class="ys-meta-section">
			<h4><?php esc_html_e( 'Booked Months', 'yacht-selector' ); ?></h4>
			<p class="description"><?php esc_html_e( 'Select the months when this yacht is booked and unavailable. The list shows the current month plus the next 11 months.', 'yacht-selector' ); ?></p>
			<div class="ys-booked-grid">
				<?php foreach ( $this->get_month_options() as $month ) : ?>
					<label class="ys-booked-item">
						<input
							type="checkbox"
							name="ys_booked[]"
							value="<?php echo esc_attr( $month['value'] ); ?>"
							<?php checked( in_array( $month['value'], $saved_values, true ) ); ?>
						/>
						<span><?php echo esc_html( $month['label'] ); ?></span>
					</label>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render location and booked tab.
	 *
	 * @param WP_Post $post Post object.
	 * @return void
	 */
	private function render_location_tab( $post ) {
		$taxonomy_slug = $this->get_location_taxonomy_slug();
		$saved_values  = $this->get_saved_booked_values( $post->ID );
		?>
		<div class="ys-admin-grid">
			<section class="ys-admin-card ys-admin-card-location">
				<div class="ys-admin-card-header">
					<h3><?php esc_html_e( 'Location', 'yacht-selector' ); ?></h3>
					<p><?php esc_html_e( 'Assign the yacht to a country and port using the plugin location taxonomy.', 'yacht-selector' ); ?></p>
				</div>
				<div class="ys-taxonomy-container">
					<?php $this->render_location_taxonomy_selector( $post, $taxonomy_slug ); ?>
				</div>
			</section>

			<section class="ys-admin-card ys-admin-card-booked">
				<div class="ys-admin-card-header">
					<h3><?php esc_html_e( 'Booked Months', 'yacht-selector' ); ?></h3>
					<p><?php esc_html_e( 'Select the months when this yacht is booked and unavailable. The list shows the current month plus the next 11 months.', 'yacht-selector' ); ?></p>
				</div>
				<div class="ys-booked-grid">
					<?php foreach ( $this->get_month_options() as $month ) : ?>
						<label class="ys-booked-item">
							<input
								type="checkbox"
								name="ys_booked[]"
								value="<?php echo esc_attr( $month['value'] ); ?>"
								<?php checked( in_array( $month['value'], $saved_values, true ) ); ?>
							/>
							<span><?php echo esc_html( $month['label'] ); ?></span>
						</label>
					<?php endforeach; ?>
					</div>
				</section>

			<section class="ys-admin-card ys-admin-card-priority">
				<div class="ys-admin-card-header">
					<h3><?php esc_html_e( 'Priority', 'yacht-selector' ); ?></h3>
				</div>
				<div class="ys-meta-grid ys-meta-grid-2">
					<?php $this->render_number_field( $post->ID, 'ys_priority', __( 'Priority', 'yacht-selector' ), '1', __( 'Higher values will sort first in later phases.', 'yacht-selector' ) ); ?>
				</div>
			</section>
		</div>
		<?php
	}

	/**
	 * Render specifications tab.
	 *
	 * @param WP_Post $post Post object.
	 * @return void
	 */
	private function render_specifications_tab( $post ) {
		$model_options = $this->get_model_options();
		?>
		<div class="ys-admin-grid">
			<section class="ys-admin-card">
				<div class="ys-admin-card-header">
					<h3><?php esc_html_e( 'Model', 'yacht-selector' ); ?></h3>
				</div>
				<div class="ys-field">
					<label for="ys_model"><strong><?php esc_html_e( 'Model', 'yacht-selector' ); ?></strong></label>
					<?php if ( empty( $model_options ) ) : ?>
						<p class="description"><?php esc_html_e( 'No model options are configured yet. Add them in Yacht Selector settings.', 'yacht-selector' ); ?></p>
					<?php else : ?>
						<select id="ys_model" name="ys_model" class="regular-text">
							<option value=""><?php esc_html_e( 'Select a model', 'yacht-selector' ); ?></option>
							<?php foreach ( $model_options as $option ) : ?>
								<option value="<?php echo esc_attr( $option ); ?>" <?php selected( get_post_meta( $post->ID, 'ys_model', true ), $option ); ?>>
									<?php echo esc_html( $option ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					<?php endif; ?>
				</div>
			</section>

			<section class="ys-admin-card">
				<div class="ys-admin-card-header">
					<h3><?php esc_html_e( 'Dimensions', 'yacht-selector' ); ?></h3>
				</div>
				<div class="ys-meta-grid ys-meta-grid-2">
					<?php $this->render_number_field( $post->ID, 'ys_length', __( 'Length', 'yacht-selector' ), '0.01', __( 'Decimals are supported, for example 28.5.', 'yacht-selector' ) ); ?>
					<?php $this->render_number_field( $post->ID, 'ys_beam', __( 'Beam', 'yacht-selector' ), '0.01', __( 'Decimals are supported, for example 6.2.', 'yacht-selector' ) ); ?>
				</div>
			</section>

			<section class="ys-admin-card">
				<div class="ys-admin-card-header">
					<h3><?php esc_html_e( 'Engine & Years', 'yacht-selector' ); ?></h3>
				</div>
				<div class="ys-meta-grid ys-meta-grid-2">
					<?php $this->render_text_field( $post->ID, 'ys_engine', __( 'Engine', 'yacht-selector' ) ); ?>
					<?php $this->render_number_field( $post->ID, 'ys_build_year', __( 'Build Year', 'yacht-selector' ), '1' ); ?>
					<?php $this->render_number_field( $post->ID, 'ys_refit_year', __( 'Refit Year', 'yacht-selector' ), '1' ); ?>
				</div>
			</section>

			<section class="ys-admin-card">
				<div class="ys-admin-card-header">
					<h3><?php esc_html_e( 'Capacity', 'yacht-selector' ); ?></h3>
				</div>
				<div class="ys-meta-grid ys-meta-grid-2">
					<?php $this->render_number_field( $post->ID, 'ys_cabins', __( 'Cabins', 'yacht-selector' ), '1' ); ?>
					<?php $this->render_number_field( $post->ID, 'ys_guests', __( 'Guests', 'yacht-selector' ), '1' ); ?>
					<?php $this->render_number_field( $post->ID, 'ys_crew', __( 'Crew', 'yacht-selector' ), '1' ); ?>
				</div>
			</section>

		</div>
		<?php
	}

	/**
	 * Render features tab.
	 *
	 * @param WP_Post $post Post object.
	 * @return void
	 */
	private function render_features_tab( $post ) {
		$options = $this->get_extra_feature_options();
		$saved   = get_post_meta( $post->ID, 'ys_extra_features', true );
		$saved   = is_array( $saved ) ? $saved : [];
		?>
		<div class="ys-admin-grid">
			<section class="ys-admin-card ys-admin-card-full">
				<div class="ys-admin-card-header">
					<h3><?php esc_html_e( 'Extra Features', 'yacht-selector' ); ?></h3>
					<p><?php esc_html_e( 'Select the extra features available on this yacht.', 'yacht-selector' ); ?></p>
				</div>
				<?php if ( empty( $options ) ) : ?>
					<p class="description"><?php esc_html_e( 'No extra feature options are configured yet. Add them in Yacht Selector settings.', 'yacht-selector' ); ?></p>
				<?php else : ?>
					<div class="ys-checkbox-list ys-checkbox-grid">
						<?php foreach ( $options as $option ) : ?>
							<label class="ys-checkbox-item">
								<input
									type="checkbox"
									name="ys_extra_features[]"
									value="<?php echo esc_attr( $option ); ?>"
									<?php checked( in_array( $option, $saved, true ) ); ?>
								/>
								<span><?php echo esc_html( $option ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</section>
		</div>
		<?php
	}

	/**
	 * Render crew members tab.
	 *
	 * @param WP_Post $post Post object.
	 * @return void
	 */
	private function render_crew_members_tab( $post ) {
		$crew   = $this->get_crew_posts();
		$chosen = $this->get_assigned_crew_ids( $post->ID );
		?>
		<div class="ys-admin-grid">
			<section class="ys-admin-card ys-admin-card-full">
				<div class="ys-admin-card-header">
					<h3><?php esc_html_e( 'Crew Members', 'yacht-selector' ); ?></h3>
					<p><?php esc_html_e( 'Assign one or more crew members to this yacht.', 'yacht-selector' ); ?></p>
				</div>
				<?php if ( empty( $crew ) ) : ?>
					<p class="description"><?php esc_html_e( 'No crew members found yet. Add crew members first.', 'yacht-selector' ); ?></p>
				<?php else : ?>
					<div class="ys-checkbox-list ys-checkbox-grid">
						<?php foreach ( $crew as $crew_member ) : ?>
							<label class="ys-checkbox-item">
								<input
									type="checkbox"
									name="ys_assigned_crew[]"
									value="<?php echo esc_attr( $crew_member->ID ); ?>"
									<?php checked( in_array( (int) $crew_member->ID, $chosen, true ) ); ?>
								/>
								<span><?php echo esc_html( get_the_title( $crew_member ) ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</section>
		</div>
		<?php
	}

	/**
	 * Render media and CTA tab.
	 *
	 * @param WP_Post $post Post object.
	 * @return void
	 */
	private function render_media_tab( $post ) {
		$image_id  = absint( get_post_meta( $post->ID, 'ys_card_image_id', true ) );
		$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : '';
		?>
		<div class="ys-admin-grid">
			<section class="ys-admin-card ys-admin-card-full">
				<div class="ys-admin-card-header">
					<h3><?php esc_html_e( 'Card Image', 'yacht-selector' ); ?></h3>
					<p><?php esc_html_e( 'Select an image to override the frontend card image. This will later take priority over the featured image.', 'yacht-selector' ); ?></p>
				</div>
				<div class="ys-field">
					<input type="hidden" name="ys_card_image_id" value="<?php echo esc_attr( $image_id ); ?>" data-ys-image-id />
					<div class="ys-image-preview-wrap<?php echo $image_url ? '' : ' is-empty'; ?>" data-ys-image-preview-wrap>
						<?php if ( $image_url ) : ?>
							<img src="<?php echo esc_url( $image_url ); ?>" alt="" class="ys-image-preview" data-ys-image-preview />
						<?php else : ?>
							<img src="" alt="" class="ys-image-preview" data-ys-image-preview hidden />
							<span class="ys-image-placeholder" data-ys-image-placeholder><?php esc_html_e( 'No image selected.', 'yacht-selector' ); ?></span>
						<?php endif; ?>
					</div>
					<div class="ys-image-actions">
						<button type="button" class="button" data-ys-media-select>
							<?php echo $image_id ? esc_html__( 'Replace Image', 'yacht-selector' ) : esc_html__( 'Select Image', 'yacht-selector' ); ?>
						</button>
						<button type="button" class="button-link-delete<?php echo $image_id ? '' : ' hidden'; ?>" data-ys-media-remove>
							<?php esc_html_e( 'Remove', 'yacht-selector' ); ?>
						</button>
					</div>
				</div>
			</section>

		</div>
		<?php
	}

	/**
	 * Render the native taxonomy selector inside the master panel.
	 *
	 * @param WP_Post $post Post object.
	 * @param string  $taxonomy_slug Taxonomy slug.
	 * @return void
	 */
	private function render_location_taxonomy_selector( $post, $taxonomy_slug ) {
		if ( '' === $taxonomy_slug || ! taxonomy_exists( $taxonomy_slug ) || ! function_exists( 'post_categories_meta_box' ) ) {
			echo '<p class="description">' . esc_html__( 'Location taxonomy is not available.', 'yacht-selector' ) . '</p>';
			return;
		}

		$taxonomy = get_taxonomy( $taxonomy_slug );

		if ( ! $taxonomy instanceof WP_Taxonomy ) {
			echo '<p class="description">' . esc_html__( 'Location taxonomy is not available.', 'yacht-selector' ) . '</p>';
			return;
		}

		post_categories_meta_box(
			$post,
			[
				'id'       => $taxonomy_slug . 'div',
				'title'    => $taxonomy->labels->name,
				'callback' => 'post_categories_meta_box',
				'args'     => [
					'taxonomy' => $taxonomy_slug,
				],
			]
		);
	}

	/**
	 * Render Yacht Details meta box.
	 *
	 * @param WP_Post $post Post object.
	 * @return void
	 */
	public function render_yacht_details_box( $post ) {
		$model_options = $this->get_model_options();
		?>
		<div class="ys-meta-grid ys-meta-grid-2">
			<div class="ys-field">
				<label for="ys_model"><strong><?php esc_html_e( 'Model', 'yacht-selector' ); ?></strong></label>
				<?php if ( empty( $model_options ) ) : ?>
					<p class="description"><?php esc_html_e( 'No model options are configured yet. Add them in Yacht Selector settings.', 'yacht-selector' ); ?></p>
				<?php else : ?>
					<select id="ys_model" name="ys_model" class="regular-text">
						<option value=""><?php esc_html_e( 'Select a model', 'yacht-selector' ); ?></option>
						<?php foreach ( $model_options as $option ) : ?>
							<option value="<?php echo esc_attr( $option ); ?>" <?php selected( get_post_meta( $post->ID, 'ys_model', true ), $option ); ?>>
								<?php echo esc_html( $option ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				<?php endif; ?>
			</div>

				<?php $this->render_number_field( $post->ID, 'ys_length', __( 'Length', 'yacht-selector' ), '0.01', __( 'Decimals are supported, for example 28.5.', 'yacht-selector' ) ); ?>
				<?php $this->render_number_field( $post->ID, 'ys_beam', __( 'Beam', 'yacht-selector' ), '0.01', __( 'Decimals are supported, for example 6.2.', 'yacht-selector' ) ); ?>
			<?php $this->render_text_field( $post->ID, 'ys_engine', __( 'Engine', 'yacht-selector' ) ); ?>
			<?php $this->render_number_field( $post->ID, 'ys_build_year', __( 'Build Year', 'yacht-selector' ), '1' ); ?>
			<?php $this->render_number_field( $post->ID, 'ys_refit_year', __( 'Refit Year', 'yacht-selector' ), '1' ); ?>
			<?php $this->render_number_field( $post->ID, 'ys_cabins', __( 'Cabins', 'yacht-selector' ), '1' ); ?>
			<?php $this->render_number_field( $post->ID, 'ys_guests', __( 'Guests', 'yacht-selector' ), '1' ); ?>
			<?php $this->render_number_field( $post->ID, 'ys_crew', __( 'Crew', 'yacht-selector' ), '1' ); ?>
			<?php $this->render_number_field( $post->ID, 'ys_priority', __( 'Priority', 'yacht-selector' ), '1', __( 'Higher values will sort first in later phases.', 'yacht-selector' ) ); ?>
		</div>
		<?php
	}

	/**
	 * Render Extra Features meta box.
	 *
	 * @param WP_Post $post Post object.
	 * @return void
	 */
	public function render_extra_features_box( $post ) {
		$options = $this->get_extra_feature_options();
		$saved   = get_post_meta( $post->ID, 'ys_extra_features', true );
		$saved   = is_array( $saved ) ? $saved : [];

		if ( empty( $options ) ) {
			echo '<p class="description">' . esc_html__( 'No extra feature options are configured yet. Add them in Yacht Selector settings.', 'yacht-selector' ) . '</p>';
			return;
		}
		?>
		<div class="ys-checkbox-list">
			<?php foreach ( $options as $option ) : ?>
				<label class="ys-checkbox-item">
					<input
						type="checkbox"
						name="ys_extra_features[]"
						value="<?php echo esc_attr( $option ); ?>"
						<?php checked( in_array( $option, $saved, true ) ); ?>
					/>
					<span><?php echo esc_html( $option ); ?></span>
				</label>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Render Frontend & CTA meta box.
	 *
	 * @param WP_Post $post Post object.
	 * @return void
	 */
	public function render_frontend_cta_box( $post ) {
		$image_id  = absint( get_post_meta( $post->ID, 'ys_card_image_id', true ) );
		$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : '';
		?>
		<div class="ys-field">
			<label><strong><?php esc_html_e( 'Card Image Override', 'yacht-selector' ); ?></strong></label>
			<p class="description"><?php esc_html_e( 'Select an image to override the frontend card image. This will later take priority over the featured image.', 'yacht-selector' ); ?></p>
			<input type="hidden" name="ys_card_image_id" value="<?php echo esc_attr( $image_id ); ?>" data-ys-image-id />
			<div class="ys-image-preview-wrap<?php echo $image_url ? '' : ' is-empty'; ?>" data-ys-image-preview-wrap>
				<?php if ( $image_url ) : ?>
					<img src="<?php echo esc_url( $image_url ); ?>" alt="" class="ys-image-preview" data-ys-image-preview />
				<?php else : ?>
					<img src="" alt="" class="ys-image-preview" data-ys-image-preview hidden />
					<span class="ys-image-placeholder" data-ys-image-placeholder><?php esc_html_e( 'No image selected.', 'yacht-selector' ); ?></span>
				<?php endif; ?>
			</div>
			<div class="ys-image-actions">
				<button type="button" class="button" data-ys-media-select>
					<?php echo $image_id ? esc_html__( 'Replace Image', 'yacht-selector' ) : esc_html__( 'Select Image', 'yacht-selector' ); ?>
				</button>
				<button type="button" class="button-link-delete<?php echo $image_id ? '' : ' hidden'; ?>" data-ys-media-remove>
					<?php esc_html_e( 'Remove', 'yacht-selector' ); ?>
				</button>
			</div>
		</div>
		<?php
	}

	/**
	 * Get month options for the current month + next 11 months.
	 *
	 * @return array
	 */
	public function get_month_options() {
		$options   = [];
		$timestamp = current_time( 'timestamp' );
		$timezone  = wp_timezone();
		$current   = new DateTimeImmutable( gmdate( 'Y-m-01 H:i:s', $timestamp ), new DateTimeZone( 'UTC' ) );
		$current   = $current->setTimezone( $timezone );

		for ( $i = 0; $i < 12; $i++ ) {
			$month = $current->modify( '+' . $i . ' months' );

			$options[] = [
				'value' => $month->format( 'Y-m' ),
				'label' => wp_date( 'F Y', $month->getTimestamp(), $timezone ),
			];
		}

		return $options;
	}

	/**
	 * Sanitize text array values.
	 *
	 * @param mixed $values Raw values.
	 * @return array
	 */
	public function sanitize_array_text_values( $values ) {
		if ( ! is_array( $values ) ) {
			return [];
		}

		$sanitized = [];

		foreach ( $values as $value ) {
			if ( ! is_scalar( $value ) ) {
				continue;
			}

			$value = trim( sanitize_text_field( (string) $value ) );

			if ( '' === $value ) {
				continue;
			}

			$sanitized[] = $value;
		}

		return array_values( array_unique( $sanitized ) );
	}

	/**
	 * Render reusable number field.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key Meta key.
	 * @param string $label Field label.
	 * @param string $step Step value.
	 * @param string $description Optional description.
	 * @return void
	 */
	private function render_number_field( $post_id, $key, $label, $step = '1', $description = '' ) {
		$value = get_post_meta( $post_id, $key, true );
		?>
		<div class="ys-field">
			<label for="<?php echo esc_attr( $key ); ?>"><strong><?php echo esc_html( $label ); ?></strong></label>
				<input type="number" id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>" class="regular-text" step="<?php echo esc_attr( $step ); ?>" inputmode="decimal" />
			<?php if ( $description ) : ?>
				<p class="description"><?php echo esc_html( $description ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render reusable text field.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key Meta key.
	 * @param string $label Label.
	 * @return void
	 */
	private function render_text_field( $post_id, $key, $label ) {
		$value = get_post_meta( $post_id, $key, true );
		?>
		<div class="ys-field">
			<label for="<?php echo esc_attr( $key ); ?>"><strong><?php echo esc_html( $label ); ?></strong></label>
			<input type="text" id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
		</div>
		<?php
	}

	/**
	 * Get configured model options.
	 *
	 * @return array
	 */
	private function get_model_options() {
		return $this->sanitize_array_text_values( $this->settings->get_setting( 'ys_model_options', [] ) );
	}

	/**
	 * Get configured extra feature options.
	 *
	 * @return array
	 */
	private function get_extra_feature_options() {
		return $this->sanitize_array_text_values( $this->settings->get_setting( 'ys_extra_feature_options', [] ) );
	}

	/**
	 * Get published crew posts.
	 *
	 * @return array
	 */
	private function get_crew_posts() {
		$crew = get_posts(
			[
				'post_type'      => 'ys_crew',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			]
		);

		return is_array( $crew ) ? $crew : [];
	}

	/**
	 * Get assigned crew IDs for a yacht post.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	private function get_assigned_crew_ids( $post_id ) {
		$values = get_post_meta( $post_id, 'ys_assigned_crew', true );

		return is_array( $values ) ? array_map( 'absint', $values ) : [];
	}

	/**
	 * Get the configured location taxonomy slug.
	 *
	 * @return string
	 */
	private function get_location_taxonomy_slug() {
		if ( $this->taxonomy instanceof YS_Taxonomy ) {
			return sanitize_key( $this->taxonomy->get_taxonomy_slug() );
		}

		$slug = sanitize_key( $this->settings->get_setting( 'ys_location_taxonomy_slug', 'ys_location' ) );

		return '' !== $slug ? $slug : 'ys_location';
	}

	/**
	 * Get saved booked values with legacy fallback.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	private function get_saved_booked_values( $post_id ) {
		return array_values( array_intersect( $this->get_stored_booked_values( $post_id ), wp_list_pluck( $this->get_month_options(), 'value' ) ) );
	}

	/**
	 * Get stored booked values with legacy fallback.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	private function get_stored_booked_values( $post_id ) {
		$values = get_post_meta( $post_id, 'ys_booked', true );

		if ( ! is_array( $values ) ) {
			$values = get_post_meta( $post_id, 'ys_availability', true );
		}

		return $this->normalize_booked_values( $values );
	}

	/**
	 * Sanitize booked month values.
	 *
	 * @param mixed $values Raw values.
	 * @return array
	 */
	private function sanitize_booked_values( $values ) {
		$values       = $this->normalize_booked_values( $values );
		$allowed      = wp_list_pluck( $this->get_month_options(), 'value' );
		$booked       = array_values( array_intersect( $values, $allowed ) );

		return array_values( array_unique( $booked ) );
	}

	/**
	 * Normalize booked values without restricting them to the visible 12-month editor window.
	 *
	 * @param mixed $values Raw values.
	 * @return array
	 */
	private function normalize_booked_values( $values ) {
		$values = $this->sanitize_array_text_values( $values );
		$values = array_filter(
			$values,
			static function( $value ) {
				return is_string( $value ) && 1 === preg_match( '/^\d{4}-\d{2}$/', $value );
			}
		);

		$values = array_values( array_unique( $values ) );
		sort( $values, SORT_STRING );

		return $values;
	}

	/**
	 * Format booked months for compact admin display.
	 *
	 * @param array $values Booked month values.
	 * @return array
	 */
	private function format_booked_month_labels( $values ) {
		$labels = [];

		foreach ( $values as $value ) {
			if ( ! is_string( $value ) || 1 !== preg_match( '/^\d{4}-\d{2}$/', $value ) ) {
				continue;
			}

			$timestamp = strtotime( $value . '-01' );

			if ( false === $timestamp ) {
				continue;
			}

			$labels[] = wp_date( 'M Y', $timestamp, wp_timezone() );
		}

		return $labels;
	}

	/**
	 * Save booked meta using the new key and clear the legacy one.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $values Sanitized booked values.
	 * @return void
	 */
	private function save_booked_meta( $post_id, $values ) {
		$this->save_array_meta( $post_id, 'ys_booked', $values );
		delete_post_meta( $post_id, 'ys_availability' );
	}

	/**
	 * Sanitize model value against allowed options.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	private function sanitize_model_value( $value ) {
		$value   = $this->sanitize_text_value( $value );
		$allowed = $this->get_model_options();

		if ( '' === $value || empty( $allowed ) || ! in_array( $value, $allowed, true ) ) {
			return '';
		}

		return $value;
	}

	/**
	 * Sanitize extra features against allowed options.
	 *
	 * @param mixed $values Raw values.
	 * @return array
	 */
	private function sanitize_extra_features_values( $values ) {
		$values  = $this->sanitize_array_text_values( $values );
		$allowed = $this->get_extra_feature_options();

		if ( empty( $allowed ) ) {
			return [];
		}

		return array_values( array_intersect( $values, $allowed ) );
	}

	/**
	 * Sanitize assigned crew IDs.
	 *
	 * @param mixed $values Raw values.
	 * @return array
	 */
	private function sanitize_crew_ids( $values ) {
		if ( ! is_array( $values ) ) {
			return [];
		}

		$sanitized = [];

		foreach ( $values as $value ) {
			$crew_id = absint( $value );

			if ( ! $crew_id || 'ys_crew' !== get_post_type( $crew_id ) ) {
				continue;
			}

			$sanitized[] = $crew_id;
		}

		return array_values( array_unique( $sanitized ) );
	}

	/**
	 * Sanitize plain text value.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	private function sanitize_text_value( $value ) {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		return trim( sanitize_text_field( (string) $value ) );
	}

	/**
	 * Sanitize decimal number string.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	private function sanitize_decimal_value( $value ) {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$value = str_replace( ',', '.', trim( (string) $value ) );

		if ( '' === $value || ! is_numeric( $value ) ) {
			return '';
		}

		return (string) ( 0 + $value );
	}

	/**
	 * Sanitize integer value.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	private function sanitize_integer_value( $value ) {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$value = trim( (string) $value );

		if ( '' === $value || ! preg_match( '/^-?\d+$/', $value ) ) {
			return '';
		}

		return (string) intval( $value, 10 );
	}

	/**
	 * Sanitize attachment ID.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	private function sanitize_attachment_id( $value ) {
		$attachment_id = absint( $value );

		if ( ! $attachment_id || 'attachment' !== get_post_type( $attachment_id ) ) {
			return '';
		}

		return (string) $attachment_id;
	}

	/**
	 * Sanitize URL value.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	private function sanitize_url_value( $value ) {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		return esc_url_raw( trim( (string) $value ) );
	}

	/**
	 * Save scalar meta or delete empty values.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key Meta key.
	 * @param string $value Sanitized value.
	 * @return void
	 */
	private function save_scalar_meta( $post_id, $key, $value ) {
		if ( '' === $value ) {
			delete_post_meta( $post_id, $key );
			return;
		}

		update_post_meta( $post_id, $key, $value );
	}

	/**
	 * Save array meta or delete empty arrays.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key Meta key.
	 * @param array  $values Sanitized values.
	 * @return void
	 */
	private function save_array_meta( $post_id, $key, $values ) {
		if ( empty( $values ) ) {
			delete_post_meta( $post_id, $key );
			return;
		}

		update_post_meta( $post_id, $key, array_values( $values ) );
	}
}
