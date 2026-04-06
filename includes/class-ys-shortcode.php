<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YS_Shortcode {

	/**
	 * Settings service.
	 *
	 * @var YS_Settings
	 */
	private $settings;

	/**
	 * Taxonomy service.
	 *
	 * @var YS_Taxonomy
	 */
	private $taxonomy;

	/**
	 * Data provider.
	 *
	 * @var YS_Data_Provider
	 */
	private $data_provider;

	/**
	 * Constructor.
	 *
	 * @param YS_Settings|null      $settings Settings instance.
	 * @param YS_Taxonomy|null      $taxonomy Taxonomy instance.
	 * @param YS_Data_Provider|null $data_provider Data provider instance.
	 */
	public function __construct( $settings = null, $taxonomy = null, $data_provider = null ) {
		$this->settings      = $settings instanceof YS_Settings ? $settings : new YS_Settings();
		$this->taxonomy      = $taxonomy instanceof YS_Taxonomy ? $taxonomy : new YS_Taxonomy( $this->settings );
		$this->data_provider = $data_provider instanceof YS_Data_Provider ? $data_provider : new YS_Data_Provider( $this->settings, $this->taxonomy );
	}

	/**
	 * Register shortcodes.
	 *
	 * @return void
	 */
	public function register() {
		add_shortcode( 'ys_yacht_selector', [ $this, 'render_shortcode' ] );
		add_shortcode( 'yacht_selector', [ $this, 'render_shortcode' ] );
	}

	/**
	 * Render the shortcode output.
	 *
	 * @param array       $atts Shortcode attributes.
	 * @param string|null $content Enclosed content.
	 * @return string
	 */
	public function render_shortcode( $atts = [], $content = null ) {
		unset( $content );

		$atts = shortcode_atts(
			[
				'ui'    => '',
			],
			is_array( $atts ) ? $atts : [],
			'ys_yacht_selector'
		);
		$ui_class = $this->build_ui_class( isset( $atts['ui'] ) ? (string) $atts['ui'] : '' );

		$this->enqueue_selector_assets();

		return $this->render_selector_shortcode( $ui_class );
	}

	/**
	 * Enqueue assets for the selector block view.
	 *
	 * @return void
	 */
	private function enqueue_selector_assets() {
		wp_enqueue_style(
			'ys-frontend-selector',
			YS_PLUGIN_URL . 'assets/css/frontend.css',
			[],
			YS_PLUGIN_VERSION
		);

		wp_enqueue_script(
			'ys-frontend-selector',
			YS_PLUGIN_URL . 'assets/js/frontend.js',
			[],
			YS_PLUGIN_VERSION,
			true
		);
	}

	/**
	 * Render the selector block shortcode output.
	 *
	 * @return string
	 */
	private function render_selector_shortcode( $ui_class = '' ) {
		$settings       = $this->get_selector_settings();
		$items          = $this->get_selector_items( $settings );
		$countries      = $this->get_selector_country_terms();
		$months         = $this->get_selector_months( $items );
		$contact_tools  = $this->data_provider->get_registered_contact_tools();
		$container_id   = $this->get_container_id();
		$watch_text     = isset( $settings['ys_watch_me_text'] ) && '' !== $settings['ys_watch_me_text'] ? $settings['ys_watch_me_text'] : 'Watch Me';
		$call_text      = isset( $settings['ys_call_crew_text'] ) && '' !== $settings['ys_call_crew_text'] ? $settings['ys_call_crew_text'] : 'Call the Crew';
		$book_text      = isset( $settings['ys_book_now_text'] ) && '' !== $settings['ys_book_now_text'] ? $settings['ys_book_now_text'] : 'Book Now';
		$booked_text    = isset( $settings['ys_booked_text'] ) && '' !== $settings['ys_booked_text'] ? $settings['ys_booked_text'] : 'Booked';
		$all_label_text = isset( $settings['ys_all_label_text'] ) && '' !== $settings['ys_all_label_text'] ? $settings['ys_all_label_text'] : 'All';
		$empty_text     = isset( $settings['ys_empty_state_text'] ) && '' !== $settings['ys_empty_state_text'] ? $settings['ys_empty_state_text'] : 'No results found';
		$crew_top_title = isset( $settings['ys_connect_with_top_title'] ) && '' !== $settings['ys_connect_with_top_title'] ? $settings['ys_connect_with_top_title'] : 'Connect with';
		$crew_title     = isset( $settings['ys_crew_group_title'] ) && '' !== $settings['ys_crew_group_title'] ? $settings['ys_crew_group_title'] : 'The Crew';
		$crew_subtitle  = isset( $settings['ys_crew_group_subtitle'] ) && '' !== $settings['ys_crew_group_subtitle'] ? $settings['ys_crew_group_subtitle'] : 'See the yacht live - choose how you’d like to connect. The crew is currently on board.';
		$on_board_text  = isset( $settings['ys_currently_on_board_text'] ) && '' !== $settings['ys_currently_on_board_text'] ? $settings['ys_currently_on_board_text'] : 'Currently on board: {crew_member}';
		$offline_text   = isset( $settings['ys_currently_offline_text'] ) && '' !== $settings['ys_currently_offline_text'] ? $settings['ys_currently_offline_text'] : 'Currently offline: {crew_member}';
		$online_icon_id = isset( $settings['ys_online_status_icon'] ) ? absint( $settings['ys_online_status_icon'] ) : 0;
		$online_icon    = $online_icon_id ? wp_get_attachment_image_url( $online_icon_id, 'thumbnail' ) : '';

		return $this->render_template(
			[
				'items'          => $items,
				'countries'      => $countries,
				'months'         => $months,
				'contact_tools'  => $contact_tools,
				'container_id'   => $container_id,
				'watch_text'     => $watch_text,
				'call_text'      => $call_text,
				'book_text'      => $book_text,
				'booked_text'    => $booked_text,
				'all_label_text' => $all_label_text,
				'empty_text'     => $empty_text,
				'crew_top_title' => $crew_top_title,
				'crew_title'     => $crew_title,
				'crew_subtitle'  => $crew_subtitle,
				'on_board_text'  => $on_board_text,
				'offline_text'   => $offline_text,
				'online_icon'    => $online_icon,
				'ui_class'       => is_string( $ui_class ) ? $ui_class : '',
			],
			YS_PLUGIN_PATH . 'templates/shortcode-ys-selector-block.php'
		);
	}

	/**
	 * Build an optional ui-* modifier class from shortcode ui attribute.
	 *
	 * @param string $ui_value Raw shortcode ui attribute.
	 * @return string
	 */
	private function build_ui_class( $ui_value ) {
		$normalized = sanitize_title( $ui_value );

		if ( '' === $normalized ) {
			return '';
		}

		return sanitize_html_class( 'ui-' . $normalized );
	}

	/**
	 * Get selector settings from the stored option.
	 *
	 * @return array
	 */
	private function get_selector_settings() {
		$settings = get_option( YS_Settings::OPTION_KEY, [] );

		return is_array( $settings ) ? $settings : [];
	}

	/**
	 * Get selector items using a WP_Query for the active post type.
	 *
	 * @param array $settings Saved settings.
	 * @return array
	 */
	private function get_selector_items( $settings ) {
		unset( $settings );
		$post_type = sanitize_key( $this->settings->get_active_post_type() );

		if ( ! $this->data_provider->is_valid_post_type( $post_type ) ) {
			return [];
		}

		$query = new WP_Query(
			[
				'post_type'              => $post_type,
				'post_status'            => 'publish',
				'posts_per_page'         => -1,
				'orderby'                => 'title',
				'order'                  => 'ASC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'update_post_term_cache' => true,
			]
		);

		$items = [];

		if ( $query->have_posts() ) {
			foreach ( $query->posts as $post ) {
				$item = $this->data_provider->normalize_post( $post );

				if ( empty( $item ) ) {
					continue;
				}

				$items[] = $item;
			}
		}

		wp_reset_postdata();

		return $this->data_provider->sort_items( $items );
	}

	/**
	 * Get first-level country terms from the active taxonomy.
	 *
	 * @return array
	 */
	private function get_selector_country_terms() {
		$taxonomy_slug = $this->taxonomy->get_taxonomy_slug();

		if ( '' === $taxonomy_slug || ! taxonomy_exists( $taxonomy_slug ) ) {
			return [];
		}

		$terms = get_terms(
			[
				'taxonomy'   => $taxonomy_slug,
				'hide_empty' => false,
				'parent'     => 0,
				'orderby'    => 'name',
				'order'      => 'ASC',
			]
		);

		if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
			return [];
		}

		$countries = [];

		foreach ( $terms as $term ) {
			if ( ! $term instanceof WP_Term ) {
				continue;
			}

			$countries[] = [
				'id'   => (int) $term->term_id,
				'name' => (string) $term->name,
				'slug' => (string) $term->slug,
				'flag' => $this->taxonomy->get_term_flag_url( $term->term_id, 'thumbnail' ),
			];
		}

		return $countries;
	}

	/**
	 * Get selector month items with simple density placeholders.
	 *
	 * @param array $items Normalized items.
	 * @return array
	 */
	private function get_selector_months( $items ) {
		$months        = [];
		$current_month = (int) wp_date( 'n', current_time( 'timestamp' ), wp_timezone() );
		$total_items   = count( $items );

		for ( $month = 1; $month <= 12; $month++ ) {
			$matching_items = 0;

			foreach ( $items as $item ) {
				if ( in_array( $month, $this->get_item_month_numbers( $item ), true ) ) {
					$matching_items++;
				}
			}

			$density = $total_items > 0 ? (int) round( ( $matching_items / $total_items ) * 100 ) : 0;

			$months[] = [
				'month'   => $month,
				'label'   => wp_date( 'M', mktime( 0, 0, 0, $month, 1, (int) wp_date( 'Y' ) ) ),
				'full'    => wp_date( 'F', mktime( 0, 0, 0, $month, 1, (int) wp_date( 'Y' ) ) ),
				'density' => $density,
				'active'  => $month === $current_month,
			];
		}

		$current_index = -1;

		foreach ( $months as $index => $month_item ) {
			if ( (int) ( $month_item['month'] ?? 0 ) === $current_month ) {
				$current_index = (int) $index;
				break;
			}
		}

		if ( $current_index > 0 ) {
			$months = array_merge(
				array_slice( $months, $current_index ),
				array_slice( $months, 0, $current_index )
			);
		}

		return array_values( $months );
	}

	/**
	 * Extract unique numeric months from an item's booked list.
	 *
	 * @param array $item Normalized item.
	 * @return array
	 */
	private function get_item_month_numbers( $item ) {
		$booked = isset( $item['booked'] ) && is_array( $item['booked'] ) ? $item['booked'] : [];
		$months = [];

		foreach ( $booked as $value ) {
			if ( ! is_string( $value ) || 1 !== preg_match( '/^\d{4}-(\d{2})$/', $value, $matches ) ) {
				continue;
			}

			$month = (int) $matches[1];

			if ( $month < 1 || $month > 12 ) {
				continue;
			}

			$months[] = $month;
		}

		$months = array_values( array_unique( $months ) );
		sort( $months, SORT_NUMERIC );

		return $months;
	}

	/**
	 * Get default shortcode template path.
	 *
	 * @return string
	 */
	public function get_template_path() {
		return YS_PLUGIN_PATH . 'templates/shortcode-ys-selector-block.php';
	}

	/**
	 * Render the shortcode template.
	 *
	 * @param array $vars Template variables.
	 * @return string
	 */
	public function render_template( $vars = [], $template = '' ) {
		$template = is_string( $template ) && '' !== $template ? $template : $this->get_template_path();

		if ( ! file_exists( $template ) ) {
			return '<p>' . esc_html__( 'Template not found.', 'yacht-selector' ) . '</p>';
		}

		if ( ! is_array( $vars ) ) {
			$vars = [];
		}

		ob_start();
		extract( $vars, EXTR_SKIP );
		include $template;

		return ob_get_clean();
	}

	/**
	 * Get a unique container id.
	 *
	 * @return string
	 */
	public function get_container_id() {
		return wp_unique_id( 'ys-yacht-selector-' );
	}
}
