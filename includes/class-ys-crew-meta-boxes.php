<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YS_Crew_Meta_Boxes {

	/**
	 * Nonce action.
	 */
	const NONCE_ACTION = 'ys_save_crew_meta_boxes';

	/**
	 * Nonce field name.
	 */
	const NONCE_NAME = 'ys_crew_meta_boxes_nonce';

	/**
	 * Crew post type slug.
	 */
	const POST_TYPE = 'ys_crew';

	/**
	 * Allowed online day values.
	 *
	 * @var array
	 */
	private $allowed_online_days = [ 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun' ];

	/**
	 * Allowed UTC offset values.
	 *
	 * @var array
	 */
	private $allowed_utc_offsets = [
		'UTC-12',
		'UTC-11',
		'UTC-10',
		'UTC-9',
		'UTC-8',
		'UTC-7',
		'UTC-6',
		'UTC-5',
		'UTC-4',
		'UTC-3',
		'UTC-2',
		'UTC-1',
		'UTC+0',
		'UTC+1',
		'UTC+2',
		'UTC+3',
		'UTC+4',
		'UTC+5',
		'UTC+6',
		'UTC+7',
		'UTC+8',
		'UTC+9',
		'UTC+10',
		'UTC+11',
		'UTC+12',
	];

	/**
	 * Allowed URL schemes for crew contact tools.
	 *
	 * @var array
	 */
	private $allowed_contact_url_protocols = [ 'http', 'https', 'mailto', 'tel', 'facetime' ];

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
		add_action( 'save_post', [ $this, 'save_meta_boxes' ], 10, 2 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	/**
	 * Add crew meta boxes.
	 *
	 * @return void
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'ys_crew_contact_availability',
			__( 'Contact Availability', 'yacht-selector' ),
			[ $this, 'render_contact_availability_box' ],
			self::POST_TYPE,
			'normal',
			'default'
		);
	}

	/**
	 * Enqueue shared admin assets on crew screens.
	 *
	 * @param string $hook_suffix Current hook.
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( ! in_array( $hook_suffix, [ 'post.php', 'post-new.php' ], true ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || self::POST_TYPE !== $screen->post_type ) {
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
	}

	/**
	 * Render the crew availability meta box.
	 *
	 * @param WP_Post $post Post object.
	 * @return void
	 */
	public function render_contact_availability_box( $post ) {
		$is_contact    = '1' === (string) get_post_meta( $post->ID, 'ys_crew_is_contact', true );
		$hours_range   = get_post_meta( $post->ID, 'ys_crew_online_hours_range', true );
		$parsed_hours  = $this->parse_online_hours_range( $hours_range );
		$start_hour    = $parsed_hours['start'];
		$end_hour      = $parsed_hours['end'];
		$days          = get_post_meta( $post->ID, 'ys_crew_online_days', true );
		$utc_offset    = get_post_meta( $post->ID, 'ys_crew_utc_offset', true );
		$days          = is_array( $days ) ? $days : [];
		$selected_tools  = $this->get_selected_contact_tools( $post->ID );
		$saved_tool_urls = $this->get_saved_contact_tool_urls( $post->ID );
		$all_tools       = $this->get_all_contact_tools();

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		?>
		<div class="ys-crew-meta-box">
			<p>
				<label>
					<input type="checkbox" name="ys_crew_is_contact" value="1" <?php checked( $is_contact ); ?> data-ys-crew-is-contact />
					<strong><?php esc_html_e( 'Contact Crew', 'yacht-selector' ); ?></strong>
				</label>
			</p>
			<p class="description"><?php esc_html_e( 'Enable this if this crew member can be used later as a contact person.', 'yacht-selector' ); ?></p>

			<div class="ys-crew-contact-fields" data-ys-crew-contact-fields>
				<div class="ys-crew-contact-availability-fields<?php echo $is_contact ? '' : ' is-inactive'; ?>" data-ys-crew-contact-availability-fields>
				<div class="ys-crew-top-row">
					<div class="ys-field ys-crew-hours-field">
						<label><strong><?php esc_html_e( 'Online Hours', 'yacht-selector' ); ?></strong></label>
						<div class="ys-crew-hours-controls">
							<select id="ys_crew_online_hours_start" name="ys_crew_online_hours_start" class="regular-text" <?php disabled( ! $is_contact ); ?>>
								<option value=""><?php esc_html_e( 'Start', 'yacht-selector' ); ?></option>
								<?php foreach ( $this->get_hour_options() as $value ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $start_hour, $value ); ?>>
										<?php echo esc_html( $value ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<span class="ys-crew-hours-separator" aria-hidden="true">-</span>
							<select id="ys_crew_online_hours_end" name="ys_crew_online_hours_end" class="regular-text" <?php disabled( ! $is_contact ); ?>>
								<option value=""><?php esc_html_e( 'End', 'yacht-selector' ); ?></option>
								<?php foreach ( $this->get_hour_options() as $value ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $end_hour, $value ); ?>>
										<?php echo esc_html( $value ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
						<p class="description"><?php esc_html_e( 'Choose whole hours only. The value is saved as a normalized range like 09-18.', 'yacht-selector' ); ?></p>
					</div>

					<div class="ys-field ys-crew-utc-field">
						<label for="ys_crew_utc_offset"><strong><?php esc_html_e( 'UTC Offset', 'yacht-selector' ); ?></strong></label>
						<select id="ys_crew_utc_offset" name="ys_crew_utc_offset" class="widefat" <?php disabled( ! $is_contact ); ?>>
							<option value=""><?php esc_html_e( 'Select a UTC offset', 'yacht-selector' ); ?></option>
							<?php foreach ( $this->get_utc_offset_options() as $value ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $utc_offset, $value ); ?>>
									<?php echo esc_html( $value ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>

				<div class="ys-crew-days">
					<p><strong><?php esc_html_e( 'Online Days', 'yacht-selector' ); ?></strong></p>
					<div class="ys-crew-day-actions">
						<button type="button" class="button button-small" data-ys-crew-days-select="weekdays"><?php esc_html_e( 'Weekdays', 'yacht-selector' ); ?></button>
						<button type="button" class="button button-small" data-ys-crew-days-select="weekend"><?php esc_html_e( 'Weekend', 'yacht-selector' ); ?></button>
						<button type="button" class="button button-small" data-ys-crew-days-select="all"><?php esc_html_e( 'All Days', 'yacht-selector' ); ?></button>
						<button type="button" class="button button-small" data-ys-crew-days-select="clear"><?php esc_html_e( 'Clear', 'yacht-selector' ); ?></button>
					</div>
					<div class="ys-checkbox-list ys-crew-days-list">
						<?php foreach ( $this->get_online_day_labels() as $value => $label ) : ?>
							<label class="ys-checkbox-item">
								<input type="checkbox" name="ys_crew_online_days[]" value="<?php echo esc_attr( $value ); ?>" <?php checked( in_array( $value, $days, true ) ); ?> <?php disabled( ! $is_contact ); ?> />
								<span><?php echo esc_html( $label ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
				</div>
				</div>

				<div class="ys-crew-contact-tools-section">
					<p><strong><?php esc_html_e( 'Contact Tools', 'yacht-selector' ); ?></strong></p>
					<p class="description"><?php esc_html_e( 'Select one or more contact tools for this crew member. Each selected tool reveals its own direct URL field immediately.', 'yacht-selector' ); ?></p>
					<?php if ( class_exists( 'YS_Contact_Tools' ) ) : ?>
						<?php wp_nonce_field( YS_Contact_Tools::NONCE_ACTION, YS_Contact_Tools::NONCE_NAME ); ?>
					<?php endif; ?>

					<div class="ys-crew-contact-urls-header">
						<p><strong><?php esc_html_e( 'Tool Contact URLs', 'yacht-selector' ); ?></strong></p>
						<button type="button" class="button-link ys-crew-contact-url-help-toggle" data-ys-crew-contact-url-help-toggle aria-expanded="false"><?php esc_html_e( 'Contact URL Help', 'yacht-selector' ); ?></button>
					</div>

					<div class="ys-crew-contact-url-help-panel hidden" data-ys-crew-contact-url-help-panel hidden>
						<p><?php esc_html_e( 'Enter the direct communication URL for this crew member and selected tool. This URL will open when the related contact tool card is clicked on the frontend. The fields below are generated dynamically from the currently selected contact tools.', 'yacht-selector' ); ?></p>
						<ul class="ys-crew-contact-url-help-list">
							<li><strong><?php esc_html_e( 'Phone', 'yacht-selector' ); ?></strong>: <code>tel:+905551112233</code></li>
							<li><strong><?php esc_html_e( 'Email', 'yacht-selector' ); ?></strong>: <code>mailto:hello@example.com</code>, <code>mailto:hello@example.com?subject=Yacht%20Inquiry</code></li>
							<li><strong><?php esc_html_e( 'WhatsApp', 'yacht-selector' ); ?></strong>: <code>https://wa.me/905551112233</code>, <code>https://wa.me/905551112233?text=Hello%20I%20want%20to%20connect</code></li>
							<li><strong><?php esc_html_e( 'Google Meet', 'yacht-selector' ); ?></strong>: <code>https://meet.google.com/abc-defg-hij</code></li>
							<li><strong><?php esc_html_e( 'FaceTime', 'yacht-selector' ); ?></strong>: <code>facetime:email@example.com</code>, <code>facetime:+905551112233</code></li>
							<li><strong><?php esc_html_e( 'Zoom', 'yacht-selector' ); ?></strong>: <code>https://zoom.us/j/1234567890</code></li>
							<li><strong><?php esc_html_e( 'Telegram', 'yacht-selector' ); ?></strong>: <code>https://t.me/username</code></li>
							<li><strong><?php esc_html_e( 'Signal', 'yacht-selector' ); ?></strong>: <?php esc_html_e( 'Use a direct profile or invite URL when available.', 'yacht-selector' ); ?></li>
							<li><strong><?php esc_html_e( 'Generic', 'yacht-selector' ); ?></strong>: <?php esc_html_e( 'For web links use https://. For email use mailto:. For phone use tel:. For supported apps use their official direct link format.', 'yacht-selector' ); ?></li>
						</ul>
					</div>

					<?php if ( empty( $all_tools ) ) : ?>
						<p class="description"><?php esc_html_e( 'No contact tools found. Create terms in the Contact Tools taxonomy first.', 'yacht-selector' ); ?></p>
					<?php else : ?>
						<div class="ys-contact-tool-post-list ys-crew-contact-tool-list" data-ys-crew-contact-tool-list>
						<?php foreach ( $all_tools as $tool ) : ?>
							<?php
							$tool_key = $tool instanceof WP_Term ? (string) $tool->slug : '';
							$is_selected = isset( $selected_tools[ $tool_key ] );
							$tool_value = isset( $saved_tool_urls[ $tool_key ] ) && is_string( $saved_tool_urls[ $tool_key ] ) ? $saved_tool_urls[ $tool_key ] : '';
							$tool_meta = $this->get_contact_tool_admin_meta( $tool );
							?>
							<div class="ys-contact-tool-post-item ys-crew-contact-tool-item" data-ys-crew-contact-tool-item="<?php echo esc_attr( $tool_key ); ?>">
								<div class="ys-crew-contact-tool-checkbox">
									<input type="checkbox" id="ys_contact_tool_<?php echo esc_attr( $tool_key ); ?>" name="tax_input[ys_contact_tool][]" value="<?php echo esc_attr( $tool_key ); ?>" data-ys-contact-tool-term-id="<?php echo esc_attr( (string) $tool->term_id ); ?>" data-ys-contact-tool-key="<?php echo esc_attr( $tool_key ); ?>" <?php checked( $is_selected ); ?> />
								</div>
								<div class="ys-contact-tool-post-content ys-crew-contact-tool-content">
									<label class="ys-contact-tool-post-title ys-crew-contact-tool-label" for="ys_contact_tool_<?php echo esc_attr( $tool_key ); ?>"><?php echo esc_html( $tool->name ); ?></label>
									<?php if ( '' !== $tool_meta['icon_name'] ) : ?>
										<span class="ys-contact-tool-post-meta"><?php echo esc_html( $tool_meta['icon_name'] ); ?></span>
									<?php endif; ?>
									<?php if ( '' !== $tool_meta['description'] ) : ?>
										<span class="ys-contact-tool-post-meta"><?php echo esc_html( $tool_meta['description'] ); ?></span>
									<?php endif; ?>
									<?php if ( $tool_meta['online_sensitive'] ) : ?>
										<span class="ys-contact-tool-post-badge"><?php esc_html_e( 'Online-sensitive', 'yacht-selector' ); ?></span>
									<?php endif; ?>
									<div class="ys-crew-contact-tool-url-wrap<?php echo $is_selected ? '' : ' hidden'; ?>" data-ys-contact-tool-url-item="<?php echo esc_attr( $tool_key ); ?>" <?php echo $is_selected ? '' : 'hidden'; ?>>
										<input
											type="text"
											id="ys_contact_tool_urls_<?php echo esc_attr( $tool_key ); ?>"
											name="ys_contact_tool_urls[<?php echo esc_attr( $tool_key ); ?>]"
											class="widefat ys-crew-contact-tool-url-input"
											value="<?php echo esc_attr( $tool_value ); ?>"
											placeholder="<?php echo esc_attr( $this->get_contact_tool_url_placeholder( $tool ) ); ?>"
											<?php disabled( ! $is_selected ); ?>
										/>
										<p class="description ys-crew-contact-url-help"><?php echo esc_html( $this->get_contact_tool_url_help_text( $tool ) ); ?></p>
									</div>
								</div>
							</div>
						<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Save crew meta fields.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post Post object.
	 * @return void
	 */
	public function save_meta_boxes( $post_id, $post ) {
		if ( ! $post instanceof WP_Post || self::POST_TYPE !== $post->post_type ) {
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

		$post_type_object = get_post_type_object( self::POST_TYPE );
		if ( ! $post_type_object || ! current_user_can( $post_type_object->cap->edit_post, $post_id ) ) {
			return;
		}

		$is_contact = isset( $_POST['ys_crew_is_contact'] ) ? '1' : '0';
		update_post_meta( $post_id, 'ys_crew_is_contact', $is_contact );

		if ( '1' !== $is_contact ) {
			delete_post_meta( $post_id, 'ys_crew_online_hours_range' );
			delete_post_meta( $post_id, 'ys_crew_online_days' );
			delete_post_meta( $post_id, 'ys_crew_utc_offset' );
			delete_post_meta( $post_id, 'ys_crew_online_hours_start' );
			delete_post_meta( $post_id, 'ys_crew_online_hours_end' );
			delete_post_meta( $post_id, 'ys_crew_timezone' );
		} else {
			$range      = $this->sanitize_online_hours_range(
				isset( $_POST['ys_crew_online_hours_start'] ) ? wp_unslash( $_POST['ys_crew_online_hours_start'] ) : '',
				isset( $_POST['ys_crew_online_hours_end'] ) ? wp_unslash( $_POST['ys_crew_online_hours_end'] ) : ''
			);
			$days       = $this->sanitize_online_days( isset( $_POST['ys_crew_online_days'] ) ? wp_unslash( $_POST['ys_crew_online_days'] ) : [] );
			$utc_offset = $this->sanitize_utc_offset_value( isset( $_POST['ys_crew_utc_offset'] ) ? wp_unslash( $_POST['ys_crew_utc_offset'] ) : '' );

			$this->save_scalar_meta( $post_id, 'ys_crew_online_hours_range', $range );
			$this->save_array_meta( $post_id, 'ys_crew_online_days', $days );
			$this->save_scalar_meta( $post_id, 'ys_crew_utc_offset', $utc_offset );
			delete_post_meta( $post_id, 'ys_crew_online_hours_start' );
			delete_post_meta( $post_id, 'ys_crew_online_hours_end' );
			delete_post_meta( $post_id, 'ys_crew_timezone' );
		}

		$this->save_contact_tool_urls( $post_id );
	}

	/**
	 * Get all contact tool terms.
	 *
	 * @return WP_Term[]
	 */
	private function get_all_contact_tools() {
		$terms = get_terms(
			[
				'taxonomy'   => 'ys_contact_tool',
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			]
		);

		return is_array( $terms ) ? $terms : [];
	}

	/**
	 * Get selected contact tool terms keyed by slug.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	private function get_selected_contact_tools( $post_id ) {
		$terms = get_the_terms( $post_id, 'ys_contact_tool' );

		if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
			return [];
		}

		$selected = [];

		foreach ( $terms as $term ) {
			if ( $term instanceof WP_Term ) {
				$selected[ (string) $term->slug ] = $term;
			}
		}

		return $selected;
	}

	/**
	 * Get saved contact tool URL map, with legacy fallback.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	private function get_saved_contact_tool_urls( $post_id ) {
		$saved_tool_urls = get_post_meta( $post_id, 'ys_contact_tool_urls', true );
		$saved_tool_urls = is_array( $saved_tool_urls ) ? $saved_tool_urls : [];

		if ( ! empty( $saved_tool_urls ) ) {
			return $saved_tool_urls;
		}

		$legacy_values = get_post_meta( $post_id, 'ys_crew_contact_tool_urls', true );
		$legacy_values = is_array( $legacy_values ) ? $legacy_values : [];

		if ( empty( $legacy_values ) ) {
			return [];
		}

		$normalized = [];
		$selected_tools = $this->get_selected_contact_tools( $post_id );

		foreach ( $selected_tools as $tool_key => $term ) {
			if ( isset( $legacy_values[ $tool_key ] ) && is_string( $legacy_values[ $tool_key ] ) ) {
				$normalized[ $tool_key ] = $legacy_values[ $tool_key ];
				continue;
			}

			$legacy_id = $term instanceof WP_Term ? (string) (int) $term->term_id : '';

			if ( '' !== $legacy_id && isset( $legacy_values[ $legacy_id ] ) && is_string( $legacy_values[ $legacy_id ] ) ) {
				$normalized[ $tool_key ] = $legacy_values[ $legacy_id ];
			}
		}

		return $normalized;
	}

	/**
	 * Get compact admin meta values for a contact tool term.
	 *
	 * @param WP_Term $term Tool term.
	 * @return array
	 */
	private function get_contact_tool_admin_meta( $term ) {
		if ( ! $term instanceof WP_Term ) {
			return [
				'icon_name'        => '',
				'description'      => '',
				'online_sensitive' => false,
			];
		}

		return [
			'icon_name'        => (string) get_term_meta( $term->term_id, 'ys_contact_tool_icon', true ),
			'description'      => isset( $term->description ) ? wp_strip_all_tags( (string) $term->description ) : '',
			'online_sensitive' => '1' === (string) get_term_meta( $term->term_id, 'ys_contact_tool_respects_online_status', true ),
		];
	}

	/**
	 * Get URL placeholder text for a tool.
	 *
	 * @param WP_Term $tool Tool term.
	 * @return string
	 */
	private function get_contact_tool_url_placeholder( $tool ) {
		$slug = $tool instanceof WP_Term ? sanitize_title( $tool->slug ) : '';

		switch ( $slug ) {
			case 'phone':
				return 'tel:+905551112233';
			case 'email':
			case 'mail':
				return 'mailto:hello@example.com';
			case 'whatsapp':
				return 'https://wa.me/905551112233';
			case 'google-meet':
			case 'meet':
				return 'https://meet.google.com/abc-defg-hij';
			case 'facetime':
				return 'facetime:email@example.com';
			case 'zoom':
				return 'https://zoom.us/j/1234567890';
			case 'telegram':
				return 'https://t.me/username';
			default:
				return 'https://example.com/...';
		}
	}

	/**
	 * Get helper text for a tool URL.
	 *
	 * @param WP_Term $tool Tool term.
	 * @return string
	 */
	private function get_contact_tool_url_help_text( $tool ) {
		$slug = $tool instanceof WP_Term ? sanitize_title( $tool->slug ) : '';

		switch ( $slug ) {
			case 'phone':
				return __( 'Use tel: links for supported devices.', 'yacht-selector' );
			case 'email':
			case 'mail':
				return __( 'Use mailto: links. You can include a subject parameter if needed.', 'yacht-selector' );
			case 'whatsapp':
				return __( 'Use the direct wa.me format, with an optional prefilled message.', 'yacht-selector' );
			case 'facetime':
				return __( 'Use the facetime: scheme with an email address or phone number.', 'yacht-selector' );
			default:
				return __( 'Use a full valid URL or supported scheme for this contact method.', 'yacht-selector' );
		}
	}

	/**
	 * Save tool-specific contact URLs for the crew member.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	private function save_contact_tool_urls( $post_id ) {
		$selected_slugs = isset( $_POST['tax_input']['ys_contact_tool'] ) ? wp_unslash( $_POST['tax_input']['ys_contact_tool'] ) : [];
		$selected_slugs = is_array( $selected_slugs ) ? array_values( array_filter( array_map( 'sanitize_title', $selected_slugs ) ) ) : [];

		if ( empty( $selected_slugs ) ) {
			delete_post_meta( $post_id, 'ys_contact_tool_urls' );
			delete_post_meta( $post_id, 'ys_crew_contact_tool_urls' );
			return;
		}

		$selected_terms = get_terms(
			[
				'taxonomy'   => 'ys_contact_tool',
				'hide_empty' => false,
				'slug'       => $selected_slugs,
			]
		);

		if ( is_wp_error( $selected_terms ) || ! is_array( $selected_terms ) ) {
			delete_post_meta( $post_id, 'ys_contact_tool_urls' );
			delete_post_meta( $post_id, 'ys_crew_contact_tool_urls' );
			return;
		}

		$raw_values = isset( $_POST['ys_contact_tool_urls'] ) ? wp_unslash( $_POST['ys_contact_tool_urls'] ) : [];
		$raw_values = is_array( $raw_values ) ? $raw_values : [];
		$saved = [];

		foreach ( $selected_terms as $term ) {
			if ( ! $term instanceof WP_Term ) {
				continue;
			}

			$tool_key = sanitize_title( $term->slug );
			$value = isset( $raw_values[ $tool_key ] ) ? $this->sanitize_contact_tool_url( $raw_values[ $tool_key ] ) : '';

			if ( '' !== $value ) {
				$saved[ $tool_key ] = $value;
			}
		}

		if ( empty( $saved ) ) {
			delete_post_meta( $post_id, 'ys_contact_tool_urls' );
			delete_post_meta( $post_id, 'ys_crew_contact_tool_urls' );
			return;
		}

		update_post_meta( $post_id, 'ys_contact_tool_urls', $saved );
		delete_post_meta( $post_id, 'ys_crew_contact_tool_urls' );
	}

	/**
	 * Sanitize a contact tool URL while preserving supported schemes.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	private function sanitize_contact_tool_url( $value ) {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$value = trim( (string) $value );

		if ( '' === $value ) {
			return '';
		}

		return esc_url_raw( $value, $this->allowed_contact_url_protocols );
	}

	/**
	 * Get hour options.
	 *
	 * @return array
	 */
	private function get_hour_options() {
		$options = [];

		for ( $hour = 0; $hour <= 23; $hour++ ) {
			$options[] = sprintf( '%02d', $hour );
		}

		return $options;
	}

	/**
	 * Get UTC offset options.
	 *
	 * @return array
	 */
	private function get_utc_offset_options() {
		return $this->allowed_utc_offsets;
	}

	/**
	 * Get labels for online day values.
	 *
	 * @return array
	 */
	private function get_online_day_labels() {
		return [
			'mon' => __( 'Monday', 'yacht-selector' ),
			'tue' => __( 'Tuesday', 'yacht-selector' ),
			'wed' => __( 'Wednesday', 'yacht-selector' ),
			'thu' => __( 'Thursday', 'yacht-selector' ),
			'fri' => __( 'Friday', 'yacht-selector' ),
			'sat' => __( 'Saturday', 'yacht-selector' ),
			'sun' => __( 'Sunday', 'yacht-selector' ),
		];
	}

	/**
	 * Parse online hours range.
	 *
	 * @param mixed $value Raw value.
	 * @return array
	 */
	private function parse_online_hours_range( $value ) {
		if ( ! is_scalar( $value ) ) {
			return [
				'start' => '',
				'end'   => '',
			];
		}

		$value = trim( sanitize_text_field( (string) $value ) );

		if ( ! preg_match( '/^(2[0-3]|[01]\d)-(2[0-3]|[01]\d)$/', $value, $matches ) ) {
			return [
				'start' => '',
				'end'   => '',
			];
		}

		return [
			'start' => $matches[1],
			'end'   => $matches[2],
		];
	}

	/**
	 * Sanitize online hours range.
	 *
	 * @param mixed $start Raw start hour.
	 * @param mixed $end Raw end hour.
	 * @return string
	 */
	private function sanitize_online_hours_range( $start, $end ) {
		$start = $this->sanitize_hour_value( $start );
		$end   = $this->sanitize_hour_value( $end );

		if ( '' === $start || '' === $end ) {
			return '';
		}

		return $start . '-' . $end;
	}

	/**
	 * Sanitize hour input.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	private function sanitize_hour_value( $value ) {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$value = trim( sanitize_text_field( (string) $value ) );

		if ( ! preg_match( '/^(2[0-3]|[01]?\d)$/', $value ) ) {
			return '';
		}

		return sprintf( '%02d', (int) $value );
	}

	/**
	 * Sanitize online day values.
	 *
	 * @param mixed $values Raw values.
	 * @return array
	 */
	private function sanitize_online_days( $values ) {
		if ( ! is_array( $values ) ) {
			return [];
		}

		$sanitized = [];

		foreach ( $values as $value ) {
			$value = sanitize_key( $value );

			if ( in_array( $value, $this->allowed_online_days, true ) ) {
				$sanitized[] = $value;
			}
		}

		return array_values( array_unique( $sanitized ) );
	}

	/**
	 * Sanitize UTC offset value.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	private function sanitize_utc_offset_value( $value ) {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$value = trim( sanitize_text_field( (string) $value ) );

		return in_array( $value, $this->allowed_utc_offsets, true ) ? $value : '';
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
