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
				'block' => '',
			],
			is_array( $atts ) ? $atts : [],
			'ys_yacht_selector'
		);

		if ( 'selector' === sanitize_key( $atts['block'] ) ) {
			$this->enqueue_selector_assets();
			return $this->render_selector_shortcode();
		}

		$this->enqueue_default_assets();

		$payload          = $this->get_payload();
		$items            = isset( $payload['items'] ) && is_array( $payload['items'] ) ? $payload['items'] : [];
		$months           = isset( $payload['months'] ) && is_array( $payload['months'] ) ? $payload['months'] : [];
		$countries        = isset( $payload['countries'] ) && is_array( $payload['countries'] ) ? $payload['countries'] : [];
		$selected_country = isset( $payload['selectedCountry'] ) ? (string) $payload['selectedCountry'] : '';
		$selected_month   = isset( $payload['selectedMonth'] ) ? (string) $payload['selectedMonth'] : '';
		$container_id     = $this->get_container_id();
		$initial_items    = array_values(
			array_filter(
				$items,
				static function( $item ) use ( $selected_country ) {
					return isset( $item['country']['slug'] ) && $item['country']['slug'] === $selected_country;
				}
			)
		);
		$active_item      = ! empty( $initial_items ) ? $initial_items[0] : null;

		return $this->render_template(
			[
				'payload'          => $payload,
				'items'            => $items,
				'months'           => $months,
				'countries'        => $countries,
				'selected_country' => $selected_country,
				'selected_month'   => $selected_month,
				'container_id'     => $container_id,
				'initial_items'    => $initial_items,
				'active_item'      => $active_item,
			]
		);
	}

	/**
	 * Enqueue assets for the default shortcode view.
	 *
	 * @return void
	 */
	private function enqueue_default_assets() {
		wp_enqueue_style(
			'ys-shortcode-default-prototype',
			YS_PLUGIN_URL . 'templates/yacht-selector/assets/css/shortcode.css',
			[],
			YS_PLUGIN_VERSION
		);

		wp_enqueue_style(
			'ys-frontend-default',
			YS_PLUGIN_URL . 'assets/css/frontend-default.css',
			[ 'ys-shortcode-default-prototype' ],
			YS_PLUGIN_VERSION
		);

		wp_enqueue_script(
			'ys-frontend-default',
			YS_PLUGIN_URL . 'assets/js/frontend-default.js',
			[],
			YS_PLUGIN_VERSION,
			true
		);
	}

	/**
	 * Enqueue assets for the selector block view.
	 *
	 * @return void
	 */
	private function enqueue_selector_assets() {
		wp_enqueue_style(
			'ys-frontend-selector',
			YS_PLUGIN_URL . 'assets/css/frontend-selector.css',
			[],
			YS_PLUGIN_VERSION
		);

		wp_enqueue_script(
			'ys-frontend-selector',
			YS_PLUGIN_URL . 'assets/js/frontend-selector.js',
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
	private function render_selector_shortcode() {
		$settings       = $this->get_selector_settings();
		$items          = $this->get_selector_items( $settings );
		$countries      = $this->get_selector_country_terms();
		$months         = $this->get_selector_months( $items );
		$container_id   = $this->get_container_id();
		$watch_text     = isset( $settings['ys_watch_me_text'] ) && '' !== $settings['ys_watch_me_text'] ? $settings['ys_watch_me_text'] : 'Watch Me';
		$book_text      = isset( $settings['ys_book_now_text'] ) && '' !== $settings['ys_book_now_text'] ? $settings['ys_book_now_text'] : 'Book Now';
		$all_label_text = isset( $settings['ys_all_label_text'] ) && '' !== $settings['ys_all_label_text'] ? $settings['ys_all_label_text'] : 'All';
		$empty_text     = isset( $settings['ys_empty_state_text'] ) && '' !== $settings['ys_empty_state_text'] ? $settings['ys_empty_state_text'] : 'No results found';

		return $this->render_template(
			[
				'items'          => $items,
				'countries'      => $countries,
				'months'         => $months,
				'container_id'   => $container_id,
				'watch_text'     => $watch_text,
				'book_text'      => $book_text,
				'all_label_text' => $all_label_text,
				'empty_text'     => $empty_text,
			],
			YS_PLUGIN_PATH . 'templates/shortcode-ys-selector-block.php'
		);
	}

	/**
	 * Build the frontend payload.
	 *
	 * @return array
	 */
	public function get_payload() {
		$items            = $this->data_provider->get_items();
		$months           = $this->get_month_options();
		$countries        = $this->get_country_options( $items );
		$selected_country = $this->get_default_country( $countries );
		$selected_month   = $this->get_default_month( $months );

		return [
			'items'           => $items,
			'months'          => $months,
			'countries'       => $countries,
			'selectedCountry' => $selected_country,
			'selectedMonth'   => $selected_month,
		];
	}

	/**
	 * Generate current month + next 11 months.
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
	 * Get unique country options from items.
	 *
	 * @param array $items Normalized items.
	 * @return array
	 */
	public function get_country_options( $items ) {
		$countries = [];

		foreach ( $items as $item ) {
			if ( empty( $item['country']['slug'] ) || empty( $item['country']['name'] ) || empty( $item['country']['id'] ) ) {
				continue;
			}

			$slug = (string) $item['country']['slug'];

			if ( isset( $countries[ $slug ] ) ) {
				continue;
			}

			$countries[ $slug ] = [
				'id'   => (int) $item['country']['id'],
				'name' => (string) $item['country']['name'],
				'slug' => $slug,
			];
		}

		$countries = array_values( $countries );

		usort(
			$countries,
			static function( $left, $right ) {
				return strcasecmp( $left['name'], $right['name'] );
			}
		);

		return $countries;
	}

	/**
	 * Get default country slug.
	 *
	 * @param array $countries Country options.
	 * @return string
	 */
	public function get_default_country( $countries ) {
		return ! empty( $countries[0]['slug'] ) ? (string) $countries[0]['slug'] : '';
	}

	/**
	 * Get default month value.
	 *
	 * @param array $months Month options.
	 * @return string
	 */
	public function get_default_month( $months ) {
		return ! empty( $months[0]['value'] ) ? (string) $months[0]['value'] : '';
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
	 * Get selector items using a WP_Query for the selected post type.
	 *
	 * @param array $settings Saved settings.
	 * @return array
	 */
	private function get_selector_items( $settings ) {
		$post_type = isset( $settings['ys_selected_post_type'] ) ? sanitize_key( $settings['ys_selected_post_type'] ) : '';

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
				'density' => $density,
				'active'  => $month === $current_month,
			];
		}

		return $months;
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
	 * Get template path.
	 *
	 * @return string
	 */
	public function get_template_path() {
		return YS_PLUGIN_PATH . 'templates/shortcode-ys-selector.php';
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
