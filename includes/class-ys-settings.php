<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YS_Settings {

	const OPTION_KEY = 'ys_settings';

	/**
	 * Settings page hook suffix.
	 *
	 * @var string
	 */
	private $page_hook = '';

	/**
	 * Register admin hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	/**
	 * Register settings menu.
	 *
	 * @return void
	 */
	public function register_menu() {
		$this->page_hook = add_options_page(
			__( 'Yacht Selector', 'yacht-selector' ),
			__( 'Yacht Selector', 'yacht-selector' ),
			'manage_options',
			'ys-settings',
			[ $this, 'render_page' ]
		);
	}

	/**
	 * Register plugin settings.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'ys_settings_group',
			self::OPTION_KEY,
			[
				'type'              => 'array',
				'sanitize_callback' => [ $this, 'sanitize_settings' ],
				'default'           => $this->get_default_settings(),
			]
		);

		register_setting(
			'ys_settings_group',
			'ys_json_data',
			[
				'type'              => 'string',
				'sanitize_callback' => [ $this, 'sanitize_json_data' ],
				'default'           => '',
			]
		);

		add_settings_section(
			'ys_general_section',
			__( 'General', 'yacht-selector' ),
			[ $this, 'render_general_section' ],
			'ys-settings'
		);

		add_settings_field(
			'ys_selected_post_type',
			__( 'Selected Post Type', 'yacht-selector' ),
			[ $this, 'render_selected_post_type_field' ],
			'ys-settings',
			'ys_general_section'
		);

		add_settings_field(
			'ys_location_taxonomy_slug',
			__( 'Location Taxonomy Slug', 'yacht-selector' ),
			[ $this, 'render_location_taxonomy_slug_field' ],
			'ys-settings',
			'ys_general_section'
		);

		add_settings_field(
			'ys_global_watch_video_url',
			__( 'Watch Video URL', 'yacht-selector' ),
			[ $this, 'render_watch_video_url_field' ],
			'ys-settings',
			'ys_general_section'
		);

		add_settings_field(
			'ys_global_video_call_url',
			__( 'Video Call URL', 'yacht-selector' ),
			[ $this, 'render_video_call_url_field' ],
			'ys-settings',
			'ys_general_section'
		);

		add_settings_field(
			'ys_global_schedule_url',
			__( 'Schedule URL', 'yacht-selector' ),
			[ $this, 'render_schedule_url_field' ],
			'ys-settings',
			'ys_general_section'
		);

		add_settings_section(
			'ys_model_options_section',
			__( 'Model Options', 'yacht-selector' ),
			[ $this, 'render_model_options_section' ],
			'ys-settings'
		);

		add_settings_field(
			'ys_model_options',
			__( 'Models', 'yacht-selector' ),
			[ $this, 'render_model_options_field' ],
			'ys-settings',
			'ys_model_options_section'
		);

		add_settings_section(
			'ys_extra_feature_options_section',
			__( 'Extra Features', 'yacht-selector' ),
			[ $this, 'render_extra_features_section' ],
			'ys-settings'
		);

		add_settings_field(
			'ys_extra_feature_options',
			__( 'Extra Features', 'yacht-selector' ),
			[ $this, 'render_extra_feature_options_field' ],
			'ys-settings',
			'ys_extra_feature_options_section'
		);

		add_settings_section(
			'ys_frontend_text_settings_section',
			__( 'Frontend Text Settings', 'yacht-selector' ),
			[ $this, 'render_frontend_text_settings_section' ],
			'ys-settings'
		);

		add_settings_field(
			'ys_watch_me_text',
			__( 'Watch Me Button Text', 'yacht-selector' ),
			[ $this, 'render_watch_me_text_field' ],
			'ys-settings',
			'ys_frontend_text_settings_section'
		);

		add_settings_field(
			'ys_book_now_text',
			__( 'Book Now Button Text', 'yacht-selector' ),
			[ $this, 'render_book_now_text_field' ],
			'ys-settings',
			'ys_frontend_text_settings_section'
		);

		add_settings_field(
			'ys_all_label_text',
			__( 'All Label Text', 'yacht-selector' ),
			[ $this, 'render_all_label_text_field' ],
			'ys-settings',
			'ys_frontend_text_settings_section'
		);

		add_settings_field(
			'ys_empty_state_text',
			__( 'Empty State Text', 'yacht-selector' ),
			[ $this, 'render_empty_state_text_field' ],
			'ys-settings',
			'ys_frontend_text_settings_section'
		);

		add_settings_section(
			'ys_json_data_import_section',
			__( 'JSON Data Import', 'yacht-selector' ),
			[ $this, 'render_json_data_import_section' ],
			'ys-settings'
		);

		add_settings_field(
			'ys_json_data',
			__( 'JSON Data', 'yacht-selector' ),
			[ $this, 'render_json_data_field' ],
			'ys-settings',
			'ys_json_data_import_section'
		);

		add_settings_field(
			'ys_json_file',
			__( 'Upload JSON File', 'yacht-selector' ),
			[ $this, 'render_json_file_field' ],
			'ys-settings',
			'ys_json_data_import_section'
		);
	}

	/**
	 * Enqueue admin-only assets for the settings page.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( $hook_suffix !== $this->page_hook ) {
			return;
		}

		wp_enqueue_style(
			'ys-admin-settings',
			YS_PLUGIN_URL . 'assets/css/admin.css',
			[],
			YS_PLUGIN_VERSION
		);

		wp_enqueue_script(
			'ys-admin-settings',
			YS_PLUGIN_URL . 'assets/js/admin.js',
			[],
			YS_PLUGIN_VERSION,
			true
		);
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap ys-settings-page">
			<h1><?php esc_html_e( 'Yacht Selector', 'yacht-selector' ); ?></h1>
			<p class="ys-settings-intro">
				<?php esc_html_e( 'Configure the post type, plugin-managed location taxonomy slug, global CTA URLs, and reusable option lists for yacht data management.', 'yacht-selector' ); ?>
			</p>

			<?php settings_errors( self::OPTION_KEY ); ?>

			<form action="options.php" method="post" enctype="multipart/form-data">
				<?php
				settings_fields( 'ys_settings_group' );
				do_settings_sections( 'ys-settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Sanitize settings values before save.
	 *
	 * @param array $input Raw settings.
	 * @return array
	 */
	public function sanitize_settings( $input ) {
		$defaults = $this->get_default_settings();
		$current  = $this->get_settings();
		$input    = is_array( $input ) ? $input : [];
		$output   = $defaults;

		$selected_post_type = isset( $input['ys_selected_post_type'] ) ? sanitize_key( $input['ys_selected_post_type'] ) : '';
		$public_post_types  = $this->get_available_post_types();

		if ( '' !== $selected_post_type && isset( $public_post_types[ $selected_post_type ] ) ) {
			$output['ys_selected_post_type'] = $selected_post_type;
		} else {
			$output['ys_selected_post_type'] = '';
		}

		$taxonomy_slug = isset( $input['ys_location_taxonomy_slug'] ) ? sanitize_key( $input['ys_location_taxonomy_slug'] ) : '';
		if ( '' === $taxonomy_slug ) {
			$taxonomy_slug = $defaults['ys_location_taxonomy_slug'];
		}
		$output['ys_location_taxonomy_slug'] = $taxonomy_slug;

		$output['ys_global_watch_video_url'] = isset( $input['ys_global_watch_video_url'] ) ? esc_url_raw( $input['ys_global_watch_video_url'] ) : '';
		$output['ys_global_video_call_url']  = isset( $input['ys_global_video_call_url'] ) ? esc_url_raw( $input['ys_global_video_call_url'] ) : '';
		$output['ys_global_schedule_url']    = isset( $input['ys_global_schedule_url'] ) ? esc_url_raw( $input['ys_global_schedule_url'] ) : '';
		$output['ys_watch_me_text']          = isset( $input['ys_watch_me_text'] ) ? sanitize_text_field( wp_unslash( $input['ys_watch_me_text'] ) ) : $defaults['ys_watch_me_text'];
		$output['ys_book_now_text']          = isset( $input['ys_book_now_text'] ) ? sanitize_text_field( wp_unslash( $input['ys_book_now_text'] ) ) : $defaults['ys_book_now_text'];
		$output['ys_all_label_text']         = isset( $input['ys_all_label_text'] ) ? sanitize_text_field( wp_unslash( $input['ys_all_label_text'] ) ) : $defaults['ys_all_label_text'];
		$output['ys_empty_state_text']       = isset( $input['ys_empty_state_text'] ) ? sanitize_text_field( wp_unslash( $input['ys_empty_state_text'] ) ) : $defaults['ys_empty_state_text'];
		$output['ys_model_options']          = $this->sanitize_repeatable_list( isset( $input['ys_model_options'] ) ? $input['ys_model_options'] : [] );
		$output['ys_extra_feature_options']  = $this->sanitize_repeatable_list( isset( $input['ys_extra_feature_options'] ) ? $input['ys_extra_feature_options'] : [] );

		return wp_parse_args( $output, $current );
	}

	/**
	 * Sanitize and validate imported JSON data.
	 *
	 * @param mixed $input Raw textarea value.
	 * @return string
	 */
	public function sanitize_json_data( $input ) {
		static $has_run = false;
		static $cached_result = null;

		if ( $has_run ) {
			return is_string( $cached_result ) ? $cached_result : '';
		}

		$current     = get_option( 'ys_json_data', '' );
		$is_import   = isset( $_POST['ys_json_import'] );
		$json_string = is_string( $input ) ? wp_unslash( $input ) : '';
		$json_string = trim( $json_string );
		$has_run     = true;
		$cached_result = is_string( $current ) ? $current : '';

		if ( $is_import && ! empty( $_FILES['ys_json_file']['tmp_name'] ) ) {
			$file_error = isset( $_FILES['ys_json_file']['error'] ) ? (int) $_FILES['ys_json_file']['error'] : UPLOAD_ERR_NO_FILE;

			if ( UPLOAD_ERR_OK !== $file_error ) {
				add_settings_error(
					self::OPTION_KEY,
					'ys_json_upload_error',
					__( 'The JSON file could not be uploaded. Please try again.', 'yacht-selector' ),
					'error'
				);

				return $cached_result;
			}

			$file_name = isset( $_FILES['ys_json_file']['name'] ) ? sanitize_file_name( wp_unslash( $_FILES['ys_json_file']['name'] ) ) : '';
			$file_ext  = strtolower( (string) pathinfo( $file_name, PATHINFO_EXTENSION ) );

			if ( 'json' !== $file_ext ) {
				add_settings_error(
					self::OPTION_KEY,
					'ys_json_upload_invalid_type',
					__( 'Please upload a valid .json file.', 'yacht-selector' ),
					'error'
				);

				return $cached_result;
			}

			$file_contents = file_get_contents( $_FILES['ys_json_file']['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

			if ( false === $file_contents ) {
				add_settings_error(
					self::OPTION_KEY,
					'ys_json_upload_read_error',
					__( 'The uploaded JSON file could not be read.', 'yacht-selector' ),
					'error'
				);

				return $cached_result;
			}

			$json_string = trim( $file_contents );
		}

		if ( '' === $json_string ) {
			$cached_result = '';
			return $cached_result;
		}

		$decoded = json_decode( $json_string, true );

		if ( JSON_ERROR_NONE !== json_last_error() ) {
			add_settings_error(
				self::OPTION_KEY,
				'ys_json_invalid_syntax',
				sprintf(
					/* translators: %s: JSON error message. */
					__( 'Invalid JSON data: %s', 'yacht-selector' ),
					json_last_error_msg()
				),
				'error'
			);

			return $cached_result;
		}

		if ( ! is_array( $decoded ) || array_values( $decoded ) !== $decoded ) {
			add_settings_error(
				self::OPTION_KEY,
				'ys_json_invalid_root',
				__( 'JSON data must contain a root array of yacht items.', 'yacht-selector' ),
				'error'
			);

			return $cached_result;
		}

		foreach ( $decoded as $index => $item ) {
			if ( ! is_array( $item ) ) {
				add_settings_error(
					self::OPTION_KEY,
					'ys_json_invalid_item',
					sprintf(
						/* translators: %d: item position. */
						__( 'Each JSON item must be an object. Item %d is invalid.', 'yacht-selector' ),
						$index + 1
					),
					'error'
				);

				return $cached_result;
			}
		}

		if ( $is_import ) {
			$this->import_json_location_terms( $decoded );
			$this->sync_import_option_values( $decoded );
			$this->import_json_posts( $decoded );
		}

		$cached_result = $json_string;

		return $cached_result;
	}

	/**
	 * Get all settings merged with defaults.
	 *
	 * @return array
	 */
	public function get_settings() {
		$defaults = $this->get_default_settings();
		$stored   = get_option( self::OPTION_KEY, [] );

		if ( ! is_array( $stored ) ) {
			$stored = [];
		}

		$settings                           = wp_parse_args( $stored, $defaults );
		$settings['ys_model_options']       = $this->sanitize_repeatable_list( $settings['ys_model_options'] );
		$settings['ys_extra_feature_options'] = $this->sanitize_repeatable_list( $settings['ys_extra_feature_options'] );
		$settings['ys_location_taxonomy_slug'] = sanitize_key( $settings['ys_location_taxonomy_slug'] );
		$settings['ys_watch_me_text']          = sanitize_text_field( (string) $settings['ys_watch_me_text'] );
		$settings['ys_book_now_text']          = sanitize_text_field( (string) $settings['ys_book_now_text'] );
		$settings['ys_all_label_text']         = sanitize_text_field( (string) $settings['ys_all_label_text'] );
		$settings['ys_empty_state_text']       = sanitize_text_field( (string) $settings['ys_empty_state_text'] );

		return $settings;
	}

	/**
	 * Get a single setting.
	 *
	 * @param string $key Setting key.
	 * @param mixed  $default Default fallback.
	 * @return mixed
	 */
	public function get_setting( $key, $default = null ) {
		$settings = $this->get_settings();

		if ( array_key_exists( $key, $settings ) ) {
			return $settings[ $key ];
		}

		return $default;
	}

	/**
	 * Render general section description.
	 *
	 * @return void
	 */
	public function render_general_section() {
		echo '<p>' . esc_html__( 'Choose the content type this plugin will manage and set the global CTA destinations used across yacht records.', 'yacht-selector' ) . '</p>';
	}

	/**
	 * Render model options section description.
	 *
	 * @return void
	 */
	public function render_model_options_section() {
		echo '<p>' . esc_html__( 'Define the available yacht model labels used by the plugin. Add one item per row.', 'yacht-selector' ) . '</p>';
	}

	/**
	 * Render extra features section description.
	 *
	 * @return void
	 */
	public function render_extra_features_section() {
		echo '<p>' . esc_html__( 'Define the reusable extra feature labels available for yachts. Add one item per row.', 'yacht-selector' ) . '</p>';
	}

	/**
	 * Render frontend text settings section description.
	 *
	 * @return void
	 */
	public function render_frontend_text_settings_section() {
		echo '<p>' . esc_html__( 'Customize reusable frontend labels and empty-state text for later shortcode output.', 'yacht-selector' ) . '</p>';
	}

	/**
	 * Render JSON data import section description.
	 *
	 * @return void
	 */
	public function render_json_data_import_section() {
		echo '<p>' . esc_html__( 'Paste or upload structured yacht data in JSON format. The JSON is validated before it is saved.', 'yacht-selector' ) . '</p>';
	}

	/**
	 * Render selected post type field.
	 *
	 * @return void
	 */
	public function render_selected_post_type_field() {
		$settings           = $this->get_settings();
		$saved_post_type    = $settings['ys_selected_post_type'];
		$available_post_types = $this->get_available_post_types();
		?>
		<select name="<?php echo esc_attr( self::OPTION_KEY ); ?>[ys_selected_post_type]" class="regular-text">
			<option value=""><?php esc_html_e( 'Select a post type', 'yacht-selector' ); ?></option>
			<?php foreach ( $available_post_types as $post_type_slug => $post_type_obj ) : ?>
				<option value="<?php echo esc_attr( $post_type_slug ); ?>" <?php selected( $saved_post_type, $post_type_slug ); ?>>
					<?php echo esc_html( sprintf( '%1$s (%2$s)', $post_type_obj->labels->singular_name, $post_type_slug ) ); ?>
				</option>
			<?php endforeach; ?>
			<?php if ( $saved_post_type && ! isset( $available_post_types[ $saved_post_type ] ) ) : ?>
				<option value="<?php echo esc_attr( $saved_post_type ); ?>" selected>
					<?php echo esc_html( sprintf( __( '%1$s (no longer available)', 'yacht-selector' ), $saved_post_type ) ); ?>
				</option>
			<?php endif; ?>
		</select>
		<p class="description"><?php esc_html_e( 'This post type will later receive Yacht Selector taxonomy and meta box integrations.', 'yacht-selector' ); ?></p>
		<?php
	}

	/**
	 * Render taxonomy slug field.
	 *
	 * @return void
	 */
	public function render_location_taxonomy_slug_field() {
		$value = $this->get_setting( 'ys_location_taxonomy_slug', 'ys_location' );
		?>
		<input
			type="text"
			name="<?php echo esc_attr( self::OPTION_KEY ); ?>[ys_location_taxonomy_slug]"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
			spellcheck="false"
		/>
		<p class="description"><?php esc_html_e( 'Plugin-managed hierarchical taxonomy for Country > Port. Slug will be sanitized with sanitize_key().', 'yacht-selector' ); ?></p>
		<?php
	}

	/**
	 * Render watch video URL field.
	 *
	 * @return void
	 */
	public function render_watch_video_url_field() {
		$this->render_url_field(
			'ys_global_watch_video_url',
			__( 'Optional global URL used for the Watch Video CTA.', 'yacht-selector' )
		);
	}

	/**
	 * Render video call URL field.
	 *
	 * @return void
	 */
	public function render_video_call_url_field() {
		$this->render_url_field(
			'ys_global_video_call_url',
			__( 'Optional global URL used for the Video Call CTA.', 'yacht-selector' )
		);
	}

	/**
	 * Render schedule URL field.
	 *
	 * @return void
	 */
	public function render_schedule_url_field() {
		$this->render_url_field(
			'ys_global_schedule_url',
			__( 'Optional global URL used for the Schedule CTA.', 'yacht-selector' )
		);
	}

	/**
	 * Render model options repeatable field.
	 *
	 * @return void
	 */
	public function render_model_options_field() {
		$this->render_repeatable_field(
			'ys_model_options',
			__( 'Add the yacht models available for selection, such as Gulet or Trawler.', 'yacht-selector' )
		);
	}

	/**
	 * Render extra feature options repeatable field.
	 *
	 * @return void
	 */
	public function render_extra_feature_options_field() {
		$this->render_repeatable_field(
			'ys_extra_feature_options',
			__( 'Add reusable feature labels such as Internet, Jetski, or Joker Boat.', 'yacht-selector' )
		);
	}

	/**
	 * Render watch me text field.
	 *
	 * @return void
	 */
	public function render_watch_me_text_field() {
		$this->render_text_field(
			'ys_watch_me_text',
			__( 'Text used for the Watch Me button.', 'yacht-selector' )
		);
	}

	/**
	 * Render book now text field.
	 *
	 * @return void
	 */
	public function render_book_now_text_field() {
		$this->render_text_field(
			'ys_book_now_text',
			__( 'Text used for the Book Now button.', 'yacht-selector' )
		);
	}

	/**
	 * Render all label text field.
	 *
	 * @return void
	 */
	public function render_all_label_text_field() {
		$this->render_text_field(
			'ys_all_label_text',
			__( 'Text used for the default country filter label.', 'yacht-selector' )
		);
	}

	/**
	 * Render empty state text field.
	 *
	 * @return void
	 */
	public function render_empty_state_text_field() {
		$this->render_text_field(
			'ys_empty_state_text',
			__( 'Text shown when no cards match the selected filters.', 'yacht-selector' )
		);
	}

	/**
	 * Render JSON data textarea field.
	 *
	 * @return void
	 */
	public function render_json_data_field() {
		$value = get_option( 'ys_json_data', '' );
		?>
		<textarea
			name="ys_json_data"
			rows="16"
			class="large-text code"
			spellcheck="false"
		><?php echo esc_textarea( is_string( $value ) ? $value : '' ); ?></textarea>
		<p class="description"><?php esc_html_e( 'Paste structured yacht data as a JSON array. Valid JSON will be stored as raw text.', 'yacht-selector' ); ?></p>
		<?php
	}

	/**
	 * Render JSON upload field, import button, and help panel.
	 *
	 * @return void
	 */
	public function render_json_file_field() {
		$schema = $this->get_json_schema_example();
		?>
		<input type="file" name="ys_json_file" accept=".json,application/json" />
		<?php submit_button( __( 'Upload JSON Data', 'yacht-selector' ), 'secondary', 'ys_json_import', false ); ?>
		<details class="ys-json-help-panel" style="margin-top:12px;">
			<summary><?php esc_html_e( 'Schema Help', 'yacht-selector' ); ?></summary>
			<p><?php esc_html_e( 'This area accepts structured yacht data in JSON format.', 'yacht-selector' ); ?></p>
			<p><?php esc_html_e( 'The JSON should follow the plugin’s current field structure.', 'yacht-selector' ); ?></p>
			<p><?php esc_html_e( 'The example below reflects the plugin’s current meta boxes and frontend data contract.', 'yacht-selector' ); ?></p>
			<p><?php esc_html_e( 'You can copy the example and adapt it.', 'yacht-selector' ); ?></p>
			<pre class="ys-json-help-schema" style="max-width:900px; overflow:auto; padding:12px; background:#f6f7f7; border:1px solid #dcdcde;"><code id="ys-json-schema-template"><?php echo esc_html( $schema ); ?></code></pre>
			<p>
				<button type="button" class="button" id="ys-copy-json-template"><?php esc_html_e( 'Copy JSON Template', 'yacht-selector' ); ?></button>
			</p>
		</details>
		<script>
			(function() {
				var copyButton = document.getElementById('ys-copy-json-template');
				var template = document.getElementById('ys-json-schema-template');

				if (!copyButton || !template) {
					return;
				}

				copyButton.addEventListener('click', function() {
					var text = template.textContent || '';

					if (navigator.clipboard && navigator.clipboard.writeText) {
						navigator.clipboard.writeText(text);
						return;
					}

					var textArea = document.createElement('textarea');
					textArea.value = text;
					document.body.appendChild(textArea);
					textArea.select();
					document.execCommand('copy');
					document.body.removeChild(textArea);
				});
			}());
		</script>
		<p class="description"><?php esc_html_e( 'Upload a .json file to replace the saved JSON data, or use the textarea above and click the import button to validate and save it.', 'yacht-selector' ); ?></p>
		<?php
	}

	/**
	 * Render a URL field.
	 *
	 * @param string $key Field key.
	 * @param string $description Field description.
	 * @return void
	 */
	private function render_url_field( $key, $description ) {
		$value = $this->get_setting( $key, '' );
		?>
		<input
			type="url"
			name="<?php echo esc_attr( self::OPTION_KEY ); ?>[<?php echo esc_attr( $key ); ?>]"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text code"
			placeholder="https://"
		/>
		<p class="description"><?php echo esc_html( $description ); ?></p>
		<?php
	}

	/**
	 * Render a text field.
	 *
	 * @param string $key Field key.
	 * @param string $description Field description.
	 * @return void
	 */
	private function render_text_field( $key, $description ) {
		$value = $this->get_setting( $key, '' );
		?>
		<input
			type="text"
			name="<?php echo esc_attr( self::OPTION_KEY ); ?>[<?php echo esc_attr( $key ); ?>]"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
		/>
		<p class="description"><?php echo esc_html( $description ); ?></p>
		<?php
	}

	/**
	 * Create or resolve location taxonomy terms from imported JSON items.
	 *
	 * @param array $items Decoded JSON items.
	 * @return void
	 */
	private function import_json_location_terms( $items ) {
		$taxonomy = $this->get_location_taxonomy_slug();

		if ( '' === $taxonomy || ! taxonomy_exists( $taxonomy ) || ! is_array( $items ) ) {
			return;
		}

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$country_term = $this->resolve_import_term(
				$taxonomy,
				isset( $item['country'] ) && is_array( $item['country'] ) ? $item['country'] : [],
				[
					'name' => 'Unknown',
					'slug' => 'unknown',
				]
			);

			if ( ! $country_term instanceof WP_Term ) {
				continue;
			}

			if ( ! empty( $item['port'] ) && is_array( $item['port'] ) ) {
				$this->resolve_import_term(
					$taxonomy,
					$item['port'],
					[],
					(int) $country_term->term_id
				);
			}
		}
	}

	/**
	 * Resolve or create an imported taxonomy term.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @param array  $data Raw term data.
	 * @param array  $fallback Fallback term data.
	 * @param int    $parent Parent term ID.
	 * @return WP_Term|null
	 */
	private function resolve_import_term( $taxonomy, $data, $fallback = [], $parent = 0 ) {
		$name = isset( $data['name'] ) && is_scalar( $data['name'] ) ? sanitize_text_field( (string) $data['name'] ) : '';
		$slug = isset( $data['slug'] ) && is_scalar( $data['slug'] ) ? sanitize_title( (string) $data['slug'] ) : '';

		if ( '' === $name || '' === $slug ) {
			$name = isset( $fallback['name'] ) ? sanitize_text_field( (string) $fallback['name'] ) : '';
			$slug = isset( $fallback['slug'] ) ? sanitize_title( (string) $fallback['slug'] ) : '';
		}

		if ( '' === $name || '' === $slug ) {
			return null;
		}

		$existing_term = get_term_by( 'slug', $slug, $taxonomy );

		if ( $existing_term instanceof WP_Term ) {
			return $existing_term;
		}

		$term = wp_insert_term(
			$name,
			$taxonomy,
			[
				'slug'   => $slug,
				'parent' => max( 0, (int) $parent ),
			]
		);

		if ( is_wp_error( $term ) || empty( $term['term_id'] ) ) {
			return null;
		}

		$resolved_term = get_term( (int) $term['term_id'], $taxonomy );

		return $resolved_term instanceof WP_Term ? $resolved_term : null;
	}

	/**
	 * Get the configured location taxonomy slug.
	 *
	 * @return string
	 */
	private function get_location_taxonomy_slug() {
		$settings = $this->get_settings();
		$slug     = isset( $settings['ys_location_taxonomy_slug'] ) ? sanitize_key( $settings['ys_location_taxonomy_slug'] ) : 'ys_location';

		return '' !== $slug ? $slug : 'ys_location';
	}

	/**
	 * Sync imported model and feature values into plugin settings.
	 *
	 * @param array $items Decoded JSON items.
	 * @return void
	 */
	private function sync_import_option_values( $items ) {
		if ( ! is_array( $items ) ) {
			return;
		}

		$settings = $this->get_settings();
		$updated  = false;

		$model_options         = isset( $settings['ys_model_options'] ) ? $this->sanitize_repeatable_list( $settings['ys_model_options'] ) : [];
		$extra_feature_options = isset( $settings['ys_extra_feature_options'] ) ? $this->sanitize_repeatable_list( $settings['ys_extra_feature_options'] ) : [];

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$model_value = $this->normalize_import_setting_label( isset( $item['model'] ) ? $item['model'] : '' );

			if ( '' !== $model_value ) {
				$matched_model = $this->match_existing_setting_label( $model_value, $model_options );

				if ( '' === $matched_model ) {
					$model_options[] = $model_value;
					$updated         = true;
				}
			}

			$features = isset( $item['features'] ) && is_array( $item['features'] ) ? $item['features'] : [];

			foreach ( $features as $feature ) {
				$feature_value = $this->normalize_import_setting_label( $feature );

				if ( '' === $feature_value ) {
					continue;
				}

				$matched_feature = $this->match_existing_setting_label( $feature_value, $extra_feature_options );

				if ( '' === $matched_feature ) {
					$extra_feature_options[] = $feature_value;
					$updated                = true;
				}
			}
		}

		if ( ! $updated ) {
			return;
		}

		$settings['ys_model_options']          = $this->sanitize_repeatable_list( $model_options );
		$settings['ys_extra_feature_options']  = $this->sanitize_repeatable_list( $extra_feature_options );

		update_option( self::OPTION_KEY, $settings );
	}

	/**
	 * Create or update posts from imported JSON items.
	 *
	 * @param array $items Decoded JSON items.
	 * @return void
	 */
	private function import_json_posts( $items ) {
		$post_type = sanitize_key( $this->get_setting( 'ys_selected_post_type', '' ) );

		if ( '' === $post_type || ! post_type_exists( $post_type ) || ! is_array( $items ) ) {
			add_settings_error(
				self::OPTION_KEY,
				'ys_json_import_post_type_missing',
				__( 'Select a valid post type before importing JSON data.', 'yacht-selector' ),
				'error'
			);

			return;
		}

		$settings              = $this->get_settings();
		$model_options         = isset( $settings['ys_model_options'] ) ? $this->sanitize_repeatable_list( $settings['ys_model_options'] ) : [];
		$extra_feature_options = isset( $settings['ys_extra_feature_options'] ) ? $this->sanitize_repeatable_list( $settings['ys_extra_feature_options'] ) : [];
		$taxonomy              = $this->get_location_taxonomy_slug();

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$post_id = $this->upsert_import_post( $item, $post_type );

			if ( ! $post_id ) {
				continue;
			}

			$this->assign_import_location_terms( $post_id, $taxonomy, $item );
			$this->save_import_meta( $post_id, $item, $model_options, $extra_feature_options );
		}
	}

	/**
	 * Create or update a single imported post.
	 *
	 * @param array  $item Imported item.
	 * @param string $post_type Target post type.
	 * @return int
	 */
	private function upsert_import_post( $item, $post_type ) {
		$title       = isset( $item['title'] ) ? sanitize_text_field( (string) $item['title'] ) : '';
		$description = isset( $item['description'] ) && is_scalar( $item['description'] ) ? wp_kses_post( (string) $item['description'] ) : '';
		$source_url  = isset( $item['url'] ) && is_scalar( $item['url'] ) ? esc_url_raw( (string) $item['url'] ) : '';
		$post_id     = $this->find_import_post_id( $post_type, $title, $source_url );

		if ( '' === $title ) {
			return 0;
		}

		$postarr = [
			'post_type'    => $post_type,
			'post_title'   => $title,
			'post_content' => $description,
			'post_status'  => 'publish',
		];

		if ( $post_id > 0 ) {
			$postarr['ID'] = $post_id;
			$result        = wp_update_post( $postarr, true );
		} else {
			$result = wp_insert_post( $postarr, true );
		}

		if ( is_wp_error( $result ) || ! $result ) {
			return 0;
		}

		return (int) $result;
	}

	/**
	 * Find an existing post for an imported item.
	 *
	 * @param string $post_type Target post type.
	 * @param string $title Imported title.
	 * @param string $source_url Imported external URL.
	 * @return int
	 */
	private function find_import_post_id( $post_type, $title, $source_url ) {
		if ( '' !== $source_url ) {
			$matched_by_url = get_posts(
				[
					'post_type'      => $post_type,
					'post_status'    => 'any',
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'meta_key'       => 'ys_import_source_url',
					'meta_value'     => $source_url,
				]
			);

			if ( ! empty( $matched_by_url[0] ) ) {
				return (int) $matched_by_url[0];
			}
		}

		if ( '' === $title ) {
			return 0;
		}

		$matched_by_title = get_posts(
			[
				'post_type'      => $post_type,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				's'              => $title,
			]
		);

		foreach ( $matched_by_title as $post ) {
			if ( $post instanceof WP_Post && $title === $post->post_title ) {
				return (int) $post->ID;
			}
		}

		return 0;
	}

	/**
	 * Attach imported location terms to a post.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $taxonomy Taxonomy slug.
	 * @param array  $item Imported item.
	 * @return void
	 */
	private function assign_import_location_terms( $post_id, $taxonomy, $item ) {
		if ( '' === $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
			return;
		}

		$country_term = $this->resolve_import_term(
			$taxonomy,
			isset( $item['country'] ) && is_array( $item['country'] ) ? $item['country'] : [],
			[
				'name' => 'Unknown',
				'slug' => 'unknown',
			]
		);

		if ( ! $country_term instanceof WP_Term ) {
			return;
		}

		$term_ids = [ (int) $country_term->term_id ];

		if ( ! empty( $item['port'] ) && is_array( $item['port'] ) ) {
			$port_term = $this->resolve_import_term( $taxonomy, $item['port'], [], (int) $country_term->term_id );

			if ( $port_term instanceof WP_Term ) {
				$term_ids[] = (int) $port_term->term_id;
			}
		}

		wp_set_object_terms( $post_id, array_values( array_unique( $term_ids ) ), $taxonomy, false );
	}

	/**
	 * Save imported meta values for a post.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $item Imported item.
	 * @param array $model_options Available model options.
	 * @param array $extra_feature_options Available feature options.
	 * @return void
	 */
	private function save_import_meta( $post_id, $item, $model_options, $extra_feature_options ) {
		$meta_map = $this->get_json_import_meta_map();

		if ( ! array_key_exists( 'booked', $item ) && array_key_exists( 'availability', $item ) ) {
			$item['booked'] = $item['availability'];
		}

		foreach ( $meta_map as $json_key => $meta_key ) {
			if ( ! array_key_exists( $json_key, $item ) ) {
				continue;
			}

			if ( is_array( $meta_key ) ) {
				$this->save_import_nested_meta( $post_id, $item[ $json_key ], $meta_key );
				continue;
			}

			$value = $this->normalize_import_meta_value( $json_key, $item[ $json_key ], $model_options, $extra_feature_options );

			if ( null === $value ) {
				delete_post_meta( $post_id, $meta_key );
				continue;
			}

			if ( is_array( $value ) ) {
				if ( empty( $value ) ) {
					delete_post_meta( $post_id, $meta_key );
				} else {
					update_post_meta( $post_id, $meta_key, array_values( $value ) );
				}

				if ( 'ys_booked' === $meta_key ) {
					delete_post_meta( $post_id, 'ys_availability' );
				}

				continue;
			}

			update_post_meta( $post_id, $meta_key, $value );

			if ( 'ys_booked' === $meta_key ) {
				delete_post_meta( $post_id, 'ys_availability' );
			}
		}
	}

	/**
	 * Save nested imported meta values such as CTA fields.
	 *
	 * @param int   $post_id Post ID.
	 * @param mixed $value Raw nested value.
	 * @param array $meta_map Nested meta map.
	 * @return void
	 */
	private function save_import_nested_meta( $post_id, $value, $meta_map ) {
		$value = is_array( $value ) ? $value : [];

		foreach ( $meta_map as $nested_key => $meta_key ) {
			$nested_value = isset( $value[ $nested_key ] ) && is_scalar( $value[ $nested_key ] ) ? esc_url_raw( (string) $value[ $nested_key ] ) : '';

			if ( '' === $nested_value ) {
				delete_post_meta( $post_id, $meta_key );
			} else {
				update_post_meta( $post_id, $meta_key, $nested_value );
			}
		}
	}

	/**
	 * Get the JSON key to post meta key mapping.
	 *
	 * @return array
	 */
	private function get_json_import_meta_map() {
		return [
			'url'        => 'ys_import_source_url',
			'image'      => 'ys_import_image_url',
			'length'     => 'ys_length',
			'beam'       => 'ys_beam',
			'engine'     => 'ys_engine',
			'build_year' => 'ys_build_year',
			'refit_year' => 'ys_refit_year',
			'cabins'     => 'ys_cabins',
			'guests'     => 'ys_guests',
			'crew'       => 'ys_crew',
			'priority'   => 'ys_priority',
			'booked'     => 'ys_booked',
			'model'      => 'ys_model',
			'features'   => 'ys_extra_features',
			'cta'        => [
				'watch_video' => 'ys_watch_video_url',
				'video_call'  => 'ys_video_call_url',
				'schedule'    => 'ys_schedule_url',
			],
		];
	}

	/**
	 * Normalize an imported value to the target meta format.
	 *
	 * @param string $json_key Source JSON key.
	 * @param mixed  $value Raw value.
	 * @param array  $model_options Available model options.
	 * @param array  $extra_feature_options Available feature options.
	 * @return mixed
	 */
	private function normalize_import_meta_value( $json_key, $value, $model_options, $extra_feature_options ) {
		switch ( $json_key ) {
			case 'url':
			case 'image':
				if ( null === $value || ! is_scalar( $value ) ) {
					return null;
				}

				$value = esc_url_raw( (string) $value );

				return '' !== $value ? $value : null;

			case 'length':
			case 'beam':
				return $this->sanitize_import_decimal_value( $value );

			case 'build_year':
			case 'refit_year':
			case 'cabins':
			case 'guests':
			case 'crew':
			case 'priority':
				return $this->sanitize_import_integer_value( $value );

			case 'engine':
				$value = $this->normalize_import_setting_label( $value );

				return '' !== $value ? $value : null;

			case 'booked':
			case 'availability':
				return $this->sanitize_import_booked_values( $value );

			case 'model':
				$value = $this->normalize_import_setting_label( $value );

				if ( '' === $value ) {
					return null;
				}

				$matched_model = $this->match_existing_setting_label( $value, $model_options );

				return '' !== $matched_model ? $matched_model : $value;

			case 'features':
				return $this->match_import_feature_values( $value, $extra_feature_options );
		}

		return null;
	}

	/**
	 * Sanitize an imported decimal value.
	 *
	 * @param mixed $value Raw value.
	 * @return string|null
	 */
	private function sanitize_import_decimal_value( $value ) {
		if ( null === $value || ! is_scalar( $value ) ) {
			return null;
		}

		$value = str_replace( ',', '.', trim( (string) $value ) );

		if ( '' === $value || ! is_numeric( $value ) ) {
			return null;
		}

		return (string) ( 0 + $value );
	}

	/**
	 * Sanitize an imported integer value.
	 *
	 * @param mixed $value Raw value.
	 * @return string|null
	 */
	private function sanitize_import_integer_value( $value ) {
		if ( null === $value || ! is_scalar( $value ) ) {
			return null;
		}

		$value = trim( (string) $value );

		if ( '' === $value || ! preg_match( '/^-?\d+$/', $value ) ) {
			return null;
		}

		return (string) intval( $value, 10 );
	}

	/**
	 * Sanitize imported booked month values.
	 *
	 * @param mixed $value Raw booked values.
	 * @return array
	 */
	private function sanitize_import_booked_values( $value ) {
		if ( ! is_array( $value ) ) {
			return [];
		}

		$values = [];

		foreach ( $value as $month ) {
			if ( ! is_scalar( $month ) ) {
				continue;
			}

			$month = trim( sanitize_text_field( (string) $month ) );

			if ( 1 !== preg_match( '/^\d{4}-\d{2}$/', $month ) ) {
				continue;
			}

			$values[] = $month;
		}

		$values = array_values( array_unique( $values ) );
		sort( $values, SORT_STRING );

		return $values;
	}

	/**
	 * Match imported feature values against configured feature options.
	 *
	 * @param mixed $value Raw feature values.
	 * @param array $extra_feature_options Available feature options.
	 * @return array
	 */
	private function match_import_feature_values( $value, $extra_feature_options ) {
		if ( ! is_array( $value ) ) {
			return [];
		}

		$matched_values = [];

		foreach ( $value as $feature ) {
			$feature = $this->normalize_import_setting_label( $feature );

			if ( '' === $feature ) {
				continue;
			}

			$matched_feature = $this->match_existing_setting_label( $feature, $extra_feature_options );
			$matched_values[] = '' !== $matched_feature ? $matched_feature : $feature;
		}

		$matched_values = $this->sanitize_repeatable_list( $matched_values );

		return array_values( $matched_values );
	}

	/**
	 * Normalize an imported settings label.
	 *
	 * @param mixed $value Raw imported value.
	 * @return string
	 */
	private function normalize_import_setting_label( $value ) {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		return trim( sanitize_text_field( (string) $value ) );
	}

	/**
	 * Match an imported label against existing setting labels.
	 *
	 * @param string $value Imported value.
	 * @param array  $options Existing labels.
	 * @return string
	 */
	private function match_existing_setting_label( $value, $options ) {
		$normalized_value = $this->get_import_setting_match_key( $value );

		foreach ( $options as $option ) {
			if ( $normalized_value === $this->get_import_setting_match_key( $option ) ) {
				return is_string( $option ) ? $option : '';
			}
		}

		return '';
	}

	/**
	 * Build a duplicate-safe comparison key for imported labels.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	private function get_import_setting_match_key( $value ) {
		$label = $this->normalize_import_setting_label( $value );

		return '' !== $label ? strtolower( $label ) : '';
	}

	/**
	 * Get the JSON schema example shown in admin help.
	 *
	 * @return string
	 */
	private function get_json_schema_example() {
		return <<<'JSON'
[
  {
    "title": "Lucky You",
    "description": "Short yacht description",
    "url": "https://example.com/yachts/lucky-you",
    "image": "https://example.com/image.jpg",

    "country": {
      "name": "Turkey",
      "slug": "turkey"
    },

    "port": {
      "name": "Bodrum",
      "slug": "bodrum"
    },

    "booked": ["2026-03", "2026-04", "2026-06"],

    "model": "Motor Yacht",
    "length": 28.5,
    "beam": 6.2,
    "engine": "2x280hp",
    "build_year": 2018,
    "refit_year": 2021,
    "cabins": 4,
    "guests": 10,
    "crew": 4,
    "priority": 90,

    "features": ["Internet", "TV", "Jetski"],

    "cta": {
      "watch_video": "https://example.com/watch",
      "video_call": "https://example.com/call",
      "schedule": "https://example.com/schedule"
    }
  }
]
JSON;
	}

	/**
	 * Render repeatable text field list.
	 *
	 * @param string $key Field key.
	 * @param string $description Field description.
	 * @return void
	 */
	private function render_repeatable_field( $key, $description ) {
		$values = $this->get_setting( $key, [] );
		$values = is_array( $values ) ? $values : [];

		if ( empty( $values ) ) {
			$values = [ '' ];
		}
		?>
		<div class="ys-repeatable" data-ys-repeatable>
			<div class="ys-repeatable-rows" data-ys-repeatable-rows>
				<?php foreach ( $values as $value ) : ?>
					<?php $this->render_repeatable_row( $key, $value ); ?>
				<?php endforeach; ?>
			</div>

			<script type="text/template" data-ys-repeatable-template>
				<?php
				ob_start();
				$this->render_repeatable_row( $key, '' );
				echo trim( ob_get_clean() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			</script>

			<button type="button" class="button button-secondary" data-ys-repeatable-add>
				<?php esc_html_e( 'Add item', 'yacht-selector' ); ?>
			</button>
			<p class="description"><?php echo esc_html( $description ); ?></p>
		</div>
		<?php
	}

	/**
	 * Render a single repeatable row.
	 *
	 * @param string $key Field key.
	 * @param string $value Field value.
	 * @return void
	 */
	private function render_repeatable_row( $key, $value ) {
		?>
		<div class="ys-repeatable-row">
			<input
				type="text"
				name="<?php echo esc_attr( self::OPTION_KEY ); ?>[<?php echo esc_attr( $key ); ?>][]"
				value="<?php echo esc_attr( $value ); ?>"
				class="regular-text"
			/>
			<button type="button" class="button-link-delete" data-ys-repeatable-remove>
				<?php esc_html_e( 'Remove', 'yacht-selector' ); ?>
			</button>
		</div>
		<?php
	}

	/**
	 * Get default settings.
	 *
	 * @return array
	 */
	private function get_default_settings() {
		return [
			'ys_selected_post_type'      => '',
			'ys_location_taxonomy_slug'  => 'ys_location',
			'ys_global_watch_video_url'  => '',
			'ys_global_video_call_url'   => '',
			'ys_global_schedule_url'     => '',
			'ys_watch_me_text'           => 'Watch Me',
			'ys_book_now_text'           => 'Book Now',
			'ys_all_label_text'          => 'All',
			'ys_empty_state_text'        => 'No results found',
			'ys_model_options'           => [
				'Gulet',
				'Motor Yat',
				'Trawler',
			],
			'ys_extra_feature_options'   => [
				'Internet',
				'TV',
				'Joker Boat',
				'Jetski',
				'Kano',
				'Minder',
				'Duş',
			],
		];
	}

	/**
	 * Get available public post types for selection.
	 *
	 * @return array
	 */
	private function get_available_post_types() {
		$post_types = get_post_types(
			[
				'public' => true,
			],
			'objects'
		);

		unset( $post_types['attachment'] );

		return $post_types;
	}

	/**
	 * Sanitize repeatable text lists.
	 *
	 * @param mixed $values Raw values.
	 * @return array
	 */
	private function sanitize_repeatable_list( $values ) {
		if ( ! is_array( $values ) ) {
			return [];
		}

		$sanitized = [];

		foreach ( $values as $value ) {
			$value = is_scalar( $value ) ? sanitize_text_field( wp_unslash( (string) $value ) ) : '';
			$value = trim( $value );

			if ( '' === $value ) {
				continue;
			}

			$sanitized[] = $value;
		}

		return array_values( array_unique( $sanitized ) );
	}
}
