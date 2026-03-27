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

			<div class="ys-crew-contact-fields<?php echo $is_contact ? '' : ' is-inactive'; ?>" data-ys-crew-contact-fields>
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
			return;
		}

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
