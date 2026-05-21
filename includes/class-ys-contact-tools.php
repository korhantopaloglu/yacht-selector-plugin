<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YS_Contact_Tools {

	/**
	 * Taxonomy slug.
	 */
	const TAXONOMY = 'ys_contact_tool';

	/**
	 * Contact tools meta box nonce action.
	 */
	const NONCE_ACTION = 'ys_save_contact_tools';

	/**
	 * Contact tools meta box nonce name.
	 */
	const NONCE_NAME = 'ys_contact_tools_nonce';

	/**
	 * Allowed protocols for fallback/default tool URLs.
	 *
	 * @var array
	 */
	private $allowed_url_protocols = [ 'http', 'https', 'mailto', 'tel', 'facetime' ];

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', [ $this, 'register_taxonomy' ], 20 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'save_post', [ $this, 'save_post_terms' ], 20, 2 );
		add_action( self::TAXONOMY . '_add_form_fields', [ $this, 'render_add_form_fields' ] );
		add_action( self::TAXONOMY . '_edit_form_fields', [ $this, 'render_edit_form_fields' ] );
		add_action( 'created_' . self::TAXONOMY, [ $this, 'save_term_meta' ] );
		add_action( 'edited_' . self::TAXONOMY, [ $this, 'save_term_meta' ] );
	}

	/**
	 * Register the contact tools taxonomy.
	 *
	 * @return void
	 */
	public function register_taxonomy() {
		register_taxonomy(
			self::TAXONOMY,
			[ 'ys_crew' ],
			[
				'labels'            => $this->get_labels(),
				'public'            => false,
				'hierarchical'      => false,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'meta_box_cb'       => false,
				'rewrite'           => false,
			]
		);
	}

	/**
	 * Enqueue admin assets on contact tool taxonomy screens.
	 *
	 * @param string $hook_suffix Current admin hook.
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( ! in_array( $hook_suffix, [ 'edit-tags.php', 'term.php', 'post.php', 'post-new.php' ], true ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen ) {
			return;
		}

		$is_taxonomy_screen = self::TAXONOMY === $screen->taxonomy;
		$is_crew_screen     = 'ys_crew' === $screen->post_type;

		if ( ! $is_taxonomy_screen && ! $is_crew_screen ) {
			return;
		}

		wp_enqueue_style(
			'ys-admin-contact-tools',
			YS_PLUGIN_URL . 'assets/css/admin.css',
			[],
			YS_PLUGIN_VERSION
		);

		wp_enqueue_script(
			'ys-admin-contact-tools',
			YS_PLUGIN_URL . 'assets/js/admin.js',
			[],
			YS_PLUGIN_VERSION,
			true
		);

		if ( function_exists( 'ys_localize_admin_script' ) ) {
			ys_localize_admin_script( 'ys-admin-contact-tools' );
		}

		wp_enqueue_media();
	}

	/**
	 * Render the contact tools checkbox list on crew posts.
	 *
	 * @param WP_Post $post Post object.
	 * @return void
	 */
	public function render_post_meta_box( $post ) {
		$terms       = get_terms(
			[
				'taxonomy'   => self::TAXONOMY,
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			]
		);
		$assigned    = wp_get_object_terms( $post->ID, self::TAXONOMY, [ 'fields' => 'ids' ] );
		$assigned    = is_array( $assigned ) ? array_map( 'absint', $assigned ) : [];

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		?>
		<p class="description"><?php esc_html_e( 'Select one or more contact tools for this crew member.', 'yacht-selector' ); ?></p>
		<?php if ( empty( $terms ) || is_wp_error( $terms ) ) : ?>
			<p class="description"><?php esc_html_e( 'No contact tools found. Create terms in the Contact Tools taxonomy first.', 'yacht-selector' ); ?></p>
		<?php else : ?>
			<div class="ys-contact-tool-post-list">
				<?php foreach ( $terms as $term ) : ?>
					<?php $meta = $this->get_contact_tool_term_admin_meta( $term ); ?>
					<label class="ys-contact-tool-post-item">
						<input type="checkbox" name="tax_input[<?php echo esc_attr( self::TAXONOMY ); ?>][]" value="<?php echo esc_attr( $term->slug ); ?>" data-ys-contact-tool-term-id="<?php echo esc_attr( (string) $term->term_id ); ?>" data-ys-contact-tool-key="<?php echo esc_attr( (string) $term->slug ); ?>" <?php checked( in_array( (int) $term->term_id, $assigned, true ) ); ?> />
						<span class="ys-contact-tool-post-content">
							<span class="ys-contact-tool-post-title"><?php echo esc_html( $term->name ); ?></span>
							<?php if ( '' !== $meta['icon_name'] ) : ?>
								<span class="ys-contact-tool-post-meta"><?php echo esc_html( $meta['icon_name'] ); ?></span>
							<?php endif; ?>
							<?php if ( '' !== $meta['description'] ) : ?>
								<span class="ys-contact-tool-post-meta"><?php echo esc_html( $meta['description'] ); ?></span>
							<?php endif; ?>
							<?php if ( $meta['online_sensitive'] ) : ?>
								<span class="ys-contact-tool-post-badge"><?php esc_html_e( 'Online-sensitive', 'yacht-selector' ); ?></span>
							<?php endif; ?>
						</span>
					</label>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<?php
	}

	/**
	 * Save assigned contact tool terms on crew posts.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post Post object.
	 * @return void
	 */
	public function save_post_terms( $post_id, $post ) {
		if ( ! $post instanceof WP_Post || 'ys_crew' !== $post->post_type ) {
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

		$post_type_object = get_post_type_object( 'ys_crew' );
		if ( ! $post_type_object || ! current_user_can( $post_type_object->cap->edit_post, $post_id ) ) {
			return;
		}

		$values = isset( $_POST['tax_input'][ self::TAXONOMY ] ) ? wp_unslash( $_POST['tax_input'][ self::TAXONOMY ] ) : [];
		$values = is_array( $values ) ? array_map( 'sanitize_title', $values ) : [];
		$values = array_values( array_filter( array_unique( $values ) ) );

		wp_set_object_terms( $post_id, $values, self::TAXONOMY, false );
	}

	/**
	 * Render taxonomy add form fields.
	 *
	 * @return void
	 */
	public function render_add_form_fields() {
		?>
		<div class="form-field ys-contact-tool-fields" data-ys-contact-tool-fields>
			<h3><?php esc_html_e( 'Visual', 'yacht-selector' ); ?></h3>
			<?php $this->render_visual_fields(); ?>
		</div>

		<div class="form-field ys-contact-tool-fields">
			<h3><?php esc_html_e( 'Behavior', 'yacht-selector' ); ?></h3>
			<?php $this->render_behavior_field(); ?>
		</div>
		<?php
	}

	/**
	 * Render taxonomy edit form fields.
	 *
	 * @param WP_Term $term Term object.
	 * @return void
	 */
	public function render_edit_form_fields( $term ) {
		if ( ! $term instanceof WP_Term ) {
			return;
		}
		?>
		<tr class="form-field ys-contact-tool-fields" data-ys-contact-tool-fields>
			<th scope="row"><?php esc_html_e( 'Visual', 'yacht-selector' ); ?></th>
			<td>
				<?php $this->render_visual_fields( $term ); ?>
			</td>
		</tr>
		<tr class="form-field ys-contact-tool-fields">
			<th scope="row"><?php esc_html_e( 'Behavior', 'yacht-selector' ); ?></th>
			<td>
				<?php $this->render_behavior_field( $term ); ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Save contact tool term meta.
	 *
	 * @param int $term_id Term ID.
	 * @return void
	 */
	public function save_term_meta( $term_id ) {
		if ( ! current_user_can( 'manage_categories' ) ) {
			return;
		}

		$term = get_term( $term_id, self::TAXONOMY );

		if ( ! $term instanceof WP_Term ) {
			return;
		}

		$icon_type = $this->sanitize_icon_type( isset( $_POST['ys_contact_tool_icon_type'] ) ? wp_unslash( $_POST['ys_contact_tool_icon_type'] ) : '' );
		$icon      = isset( $_POST['ys_contact_tool_icon'] ) ? sanitize_text_field( wp_unslash( $_POST['ys_contact_tool_icon'] ) ) : '';
		$image_id  = isset( $_POST['ys_contact_tool_image_id'] ) ? absint( wp_unslash( $_POST['ys_contact_tool_image_id'] ) ) : 0;
		$respects  = isset( $_POST['ys_contact_tool_respects_online_status'] ) ? '1' : '0';
		$default_url = isset( $_POST['ys_contact_tool_default_url'] ) ? $this->sanitize_default_url( wp_unslash( $_POST['ys_contact_tool_default_url'] ) ) : '';

		update_term_meta( $term_id, 'ys_contact_tool_icon_type', $icon_type );
		update_term_meta( $term_id, 'ys_contact_tool_respects_online_status', $respects );

		if ( '' !== $default_url ) {
			update_term_meta( $term_id, 'ys_contact_tool_default_url', $default_url );
		} else {
			delete_term_meta( $term_id, 'ys_contact_tool_default_url' );
		}

		if ( 'image' === $icon_type ) {
			delete_term_meta( $term_id, 'ys_contact_tool_icon' );

			if ( $image_id && 'attachment' === get_post_type( $image_id ) ) {
				update_term_meta( $term_id, 'ys_contact_tool_image_id', $image_id );
			} else {
				delete_term_meta( $term_id, 'ys_contact_tool_image_id' );
			}

			return;
		}

		delete_term_meta( $term_id, 'ys_contact_tool_image_id' );

		if ( '' !== $icon ) {
			update_term_meta( $term_id, 'ys_contact_tool_icon', $icon );
		} else {
			delete_term_meta( $term_id, 'ys_contact_tool_icon' );
		}
	}

	/**
	 * Get icon data for a term.
	 *
	 * @param int $term_id Term ID.
	 * @return array
	 */
	public function get_contact_tool_icon_data( $term_id ) {
		$type     = $this->sanitize_icon_type( get_term_meta( $term_id, 'ys_contact_tool_icon_type', true ) );
		$icon     = (string) get_term_meta( $term_id, 'ys_contact_tool_icon', true );
		$image_id = absint( get_term_meta( $term_id, 'ys_contact_tool_image_id', true ) );
		$image    = $image_id ? wp_get_attachment_image_url( $image_id, 'thumbnail' ) : '';

		return [
			'type'     => $type,
			'icon'     => $icon,
			'image_id' => $image_id,
			'image_url' => $image ? (string) $image : '',
		];
	}

	/**
	 * Get compact admin meta values for a contact tool term.
	 *
	 * @param WP_Term $term Term object.
	 * @return array
	 */
	private function get_contact_tool_term_admin_meta( $term ) {
		return [
			'icon_name'        => (string) get_term_meta( $term->term_id, 'ys_contact_tool_icon', true ),
			'description'      => isset( $term->description ) ? wp_strip_all_tags( (string) $term->description ) : '',
			'online_sensitive' => '1' === (string) get_term_meta( $term->term_id, 'ys_contact_tool_respects_online_status', true ),
		];
	}

	/**
	 * Get available suggested icon keys.
	 *
	 * @return array
	 */
	public function get_available_contact_tool_icons() {
		return [
			'phone',
			'calendar',
			'message-circle',
			'mail',
			'video',
			'message-square',
			'link',
			'globe',
		];
	}

	/**
	 * Get the official icon library URL.
	 *
	 * @return string
	 */
	public function get_icon_library_url() {
		return 'https://lucide.dev/icons/';
	}

	/**
	 * Render visual field controls.
	 *
	 * @param WP_Term|null $term Term object.
	 * @return void
	 */
	private function render_visual_fields( $term = null ) {
		$term_id   = $term instanceof WP_Term ? (int) $term->term_id : 0;
		$icon_data = $term_id ? $this->get_contact_tool_icon_data( $term_id ) : [
			'type'      => 'icon',
			'icon'      => '',
			'image_id'  => 0,
			'image_url' => '',
		];
		?>
		<div class="ys-contact-tool-visual">
			<p>
				<strong><?php esc_html_e( 'Icon Type', 'yacht-selector' ); ?></strong>
			</p>
			<fieldset class="ys-contact-tool-type">
				<label>
					<input type="radio" name="ys_contact_tool_icon_type" value="icon" <?php checked( 'icon', $icon_data['type'] ); ?> data-ys-contact-tool-type />
					<?php esc_html_e( 'Icon', 'yacht-selector' ); ?>
				</label>
				<label>
					<input type="radio" name="ys_contact_tool_icon_type" value="image" <?php checked( 'image', $icon_data['type'] ); ?> data-ys-contact-tool-type />
					<?php esc_html_e( 'Image', 'yacht-selector' ); ?>
				</label>
			</fieldset>

			<div class="ys-contact-tool-group<?php echo 'icon' === $icon_data['type'] ? '' : ' hidden'; ?>" data-ys-contact-tool-group="icon">
				<label for="ys_contact_tool_icon"><strong><?php esc_html_e( 'Icon Name', 'yacht-selector' ); ?></strong></label>
				<input type="text" id="ys_contact_tool_icon" name="ys_contact_tool_icon" value="<?php echo esc_attr( $icon_data['icon'] ); ?>" class="regular-text ys-contact-tool-icon-input" placeholder="phone" data-ys-contact-tool-icon-input />
				<p class="description"><?php esc_html_e( 'Enter the icon identifier used by the frontend or icon map, for example: whatsapp, phone, calendar, email.', 'yacht-selector' ); ?></p>
				<div class="ys-contact-tool-icon-help">
					<span class="ys-contact-tool-icon-help-label"><?php esc_html_e( 'Available icons:', 'yacht-selector' ); ?></span>
					<div class="ys-contact-tool-icon-list ys-contact-tool-icon-suggestions">
						<?php foreach ( $this->get_available_contact_tool_icons() as $icon_key ) : ?>
							<button type="button" class="button-link ys-contact-tool-icon-chip ys-contact-tool-icon-option" data-ys-contact-tool-icon-fill="<?php echo esc_attr( $icon_key ); ?>">
								<span class="ys-contact-tool-icon-preview" aria-hidden="true"><?php echo $this->get_icon_svg( $icon_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								<span class="ys-contact-tool-icon-text"><?php echo esc_html( $icon_key ); ?></span>
							</button>
						<?php endforeach; ?>
					</div>
					<p class="description">
						<?php esc_html_e( 'These icon names come from the icon library used by the plugin.', 'yacht-selector' ); ?>
						<a href="<?php echo esc_url( $this->get_icon_library_url() ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Browse more icons', 'yacht-selector' ); ?></a>
					</p>
				</div>
			</div>

			<div class="ys-contact-tool-group<?php echo 'image' === $icon_data['type'] ? '' : ' hidden'; ?>" data-ys-contact-tool-group="image">
				<label><strong><?php esc_html_e( 'Image', 'yacht-selector' ); ?></strong></label>
				<?php $this->render_image_field( $icon_data ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render behavior field.
	 *
	 * @param WP_Term|null $term Term object.
	 * @return void
	 */
	private function render_behavior_field( $term = null ) {
		$term_id   = $term instanceof WP_Term ? (int) $term->term_id : 0;
		$checked   = $term_id ? '1' === (string) get_term_meta( $term_id, 'ys_contact_tool_respects_online_status', true ) : false;
		$default_url = $term_id ? (string) get_term_meta( $term_id, 'ys_contact_tool_default_url', true ) : '';
		?>
		<p>
			<label for="ys_contact_tool_default_url"><strong><?php esc_html_e( 'Default URL', 'yacht-selector' ); ?></strong></label>
			<input type="text" id="ys_contact_tool_default_url" name="ys_contact_tool_default_url" value="<?php echo esc_attr( $default_url ); ?>" class="regular-text" placeholder="https://example.com/..." />
		</p>
		<p class="description"><?php esc_html_e( 'Fallback URL used when no crew-specific URL is available for this tool.', 'yacht-selector' ); ?></p>

		<label>
			<input type="checkbox" name="ys_contact_tool_respects_online_status" value="1" <?php checked( $checked ); ?> />
			<?php esc_html_e( 'Affected by online status', 'yacht-selector' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'Enable this if this tool should behave differently when the crew member is offline/unavailable.', 'yacht-selector' ); ?></p>
		<?php
	}

	/**
	 * Render contact tool image field.
	 *
	 * @param array $icon_data Icon data.
	 * @return void
	 */
	private function render_image_field( $icon_data ) {
		$image_id  = ! empty( $icon_data['image_id'] ) ? absint( $icon_data['image_id'] ) : 0;
		$image_url = ! empty( $icon_data['image_url'] ) ? (string) $icon_data['image_url'] : '';
		?>
		<div class="ys-field ys-term-media-field ys-contact-tool-image-field" data-ys-media-field data-ys-contact-tool-image-field>
			<input type="hidden" id="ys_contact_tool_image_id" name="ys_contact_tool_image_id" value="<?php echo esc_attr( $image_id ); ?>" class="ys-contact-tool-image-id" data-ys-image-id data-ys-contact-tool-image-id />
			<div class="ys-image-preview-wrap ys-image-preview-wrap-small ys-contact-tool-image-preview-wrap<?php echo $image_url ? '' : ' is-empty'; ?>" data-ys-image-preview-wrap>
				<?php if ( $image_url ) : ?>
					<img src="<?php echo esc_url( $image_url ); ?>" alt="" class="ys-image-preview ys-image-preview-small ys-contact-tool-image-preview" data-ys-image-preview />
					<span class="ys-image-placeholder" data-ys-image-placeholder hidden><?php esc_html_e( 'No image selected.', 'yacht-selector' ); ?></span>
				<?php else : ?>
					<img src="" alt="" class="ys-image-preview ys-image-preview-small ys-contact-tool-image-preview" data-ys-image-preview hidden />
					<span class="ys-image-placeholder" data-ys-image-placeholder><?php esc_html_e( 'No image selected.', 'yacht-selector' ); ?></span>
				<?php endif; ?>
			</div>
			<div class="ys-image-actions">
				<button type="button" class="button ys-contact-tool-image-select" data-ys-media-select data-ys-media-title="<?php echo esc_attr__( 'Select Contact Tool Image', 'yacht-selector' ); ?>" data-ys-media-button="<?php echo esc_attr__( 'Use image', 'yacht-selector' ); ?>" data-ys-media-replace="<?php echo esc_attr__( 'Change Image', 'yacht-selector' ); ?>" data-ys-media-default="<?php echo esc_attr__( 'Select Image', 'yacht-selector' ); ?>">
					<?php echo $image_id ? esc_html__( 'Change Image', 'yacht-selector' ) : esc_html__( 'Select Image', 'yacht-selector' ); ?>
				</button>
				<button type="button" class="button-link-delete ys-contact-tool-image-remove<?php echo $image_id ? '' : ' hidden'; ?>" data-ys-media-remove>
					<?php esc_html_e( 'Remove', 'yacht-selector' ); ?>
				</button>
			</div>
		</div>
		<?php
	}

	/**
	 * Get a lightweight inline SVG preview for a supported icon key.
	 *
	 * @param string $icon_key Icon key.
	 * @return string
	 */
	private function get_icon_svg( $icon_key ) {
		$svg_map = [
			'phone' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.86 19.86 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.86 19.86 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72l.34 2.72a2 2 0 0 1-.57 1.73L7.1 9.9a16 16 0 0 0 7 7l1.73-1.78a2 2 0 0 1 1.73-.57l2.72.34A2 2 0 0 1 22 16.92z"/></svg>',
			'calendar' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4"/><path d="M16 2v4"/><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M3 10h18"/></svg>',
			'message-circle' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 1 12 21a8.96 8.96 0 0 1-4.1-1L3 21z"/></svg>',
			'mail' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>',
			'video' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 8-6 4 6 4V8Z"/><rect x="2" y="6" width="14" height="12" rx="2"/></svg>',
			'message-square' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
			'link' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>',
			'globe' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15 15 0 0 1 4 10 15 15 0 0 1-4 10 15 15 0 0 1-4-10 15 15 0 0 1 4-10Z"/></svg>',
		];

		return isset( $svg_map[ $icon_key ] ) ? $svg_map[ $icon_key ] : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>';
	}

	/**
	 * Get taxonomy labels.
	 *
	 * @return array
	 */
	private function get_labels() {
		return [
			'name'                       => __( 'Contact Tools', 'yacht-selector' ),
			'singular_name'              => __( 'Contact Tool', 'yacht-selector' ),
			'search_items'               => __( 'Search Contact Tools', 'yacht-selector' ),
			'popular_items'              => __( 'Popular Contact Tools', 'yacht-selector' ),
			'all_items'                  => __( 'All Contact Tools', 'yacht-selector' ),
			'edit_item'                  => __( 'Edit Contact Tool', 'yacht-selector' ),
			'update_item'                => __( 'Update Contact Tool', 'yacht-selector' ),
			'add_new_item'               => __( 'Add New Contact Tool', 'yacht-selector' ),
			'new_item_name'              => __( 'New Contact Tool Name', 'yacht-selector' ),
			'separate_items_with_commas' => __( 'Separate contact tools with commas', 'yacht-selector' ),
			'add_or_remove_items'        => __( 'Add or remove contact tools', 'yacht-selector' ),
			'choose_from_most_used'      => __( 'Choose from the most used contact tools', 'yacht-selector' ),
			'menu_name'                  => __( 'Contact Tools', 'yacht-selector' ),
		];
	}

	/**
	 * Sanitize icon type.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	private function sanitize_icon_type( $value ) {
		$value = is_scalar( $value ) ? sanitize_key( (string) $value ) : '';

		return in_array( $value, [ 'icon', 'image' ], true ) ? $value : 'icon';
	}

	/**
	 * Sanitize a default tool URL.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	private function sanitize_default_url( $value ) {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$value = trim( (string) $value );

		if ( '' === $value ) {
			return '';
		}

		return esc_url_raw( $value, $this->allowed_url_protocols );
	}
}
