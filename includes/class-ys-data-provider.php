<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YS_Data_Provider {

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
	 * Constructor.
	 *
	 * @param YS_Settings|null $settings Settings instance.
	 * @param YS_Taxonomy|null $taxonomy Taxonomy instance.
	 */
	public function __construct( $settings = null, $taxonomy = null ) {
		$this->settings = $settings instanceof YS_Settings ? $settings : new YS_Settings();
		$this->taxonomy = $taxonomy instanceof YS_Taxonomy ? $taxonomy : new YS_Taxonomy( $this->settings );
	}

	/**
	 * Get normalized yacht items.
	 *
	 * @return array
	 */
	public function get_items() {
		$post_type    = $this->get_selected_post_type();
		$taxonomy_slug = $this->get_taxonomy_slug();

		if ( ! $this->is_valid_post_type( $post_type ) || '' === $taxonomy_slug || ! taxonomy_exists( $taxonomy_slug ) ) {
			return [];
		}

		$items = [];

		foreach ( $this->get_posts() as $post ) {
			$item = $this->normalize_post( $post );

			if ( empty( $item ) ) {
				continue;
			}

			$items[] = $item;
		}

		return $this->sort_items( $items );
	}

	/**
	 * Get frontend-ready payload wrapper.
	 *
	 * @return array
	 */
	public function get_frontend_payload() {
		return [
			'items'        => $this->get_items(),
			'generated_at' => gmdate( 'c' ),
			'taxonomy'     => $this->get_taxonomy_slug(),
			'post_type'    => $this->get_selected_post_type(),
		];
	}

	/**
	 * Get base query args.
	 *
	 * @return array
	 */
	public function get_query_args() {
		return [
			'post_type'              => $this->get_selected_post_type(),
			'post_status'            => 'publish',
			'posts_per_page'         => -1,
			'orderby'                => 'title',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => true,
		];
	}

	/**
	 * Get active post type from settings.
	 *
	 * @return string
	 */
	public function get_selected_post_type() {
		return sanitize_key( $this->settings->get_active_post_type() );
	}

	/**
	 * Get active taxonomy slug from settings.
	 *
	 * @return string
	 */
	public function get_taxonomy_slug() {
		$slug = sanitize_key( $this->taxonomy->get_taxonomy_slug() );

		return '' !== $slug ? $slug : 'ys_location';
	}

	/**
	 * Validate post type.
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
	 * Query eligible posts.
	 *
	 * @return WP_Post[]
	 */
	public function get_posts() {
		$post_type = $this->get_selected_post_type();

		if ( ! $this->is_valid_post_type( $post_type ) ) {
			return [];
		}

		$posts = get_posts( $this->get_query_args() );

		return is_array( $posts ) ? $posts : [];
	}

	/**
	 * Normalize a post to the frontend contract.
	 *
	 * @param WP_Post $post Post object.
	 * @return array|null
	 */
	public function normalize_post( $post ) {
		if ( ! $post instanceof WP_Post || 'publish' !== $post->post_status ) {
			return null;
		}

		$location = $this->resolve_location_terms( $post->ID );

		if ( empty( $location['country'] ) ) {
			return null;
		}

		$priority = $this->get_integer_meta( $post->ID, 'ys_priority', 0 );

		return [
			'id'           => (int) $post->ID,
			'title'        => get_the_title( $post ),
			'url'          => get_permalink( $post ),
			'image'        => $this->resolve_image_url( $post->ID ),
			'country'      => $location['country'],
			'port'         => $location['port'],
			'booked'       => $this->get_booked_months( $post->ID ),
			'priority'     => $priority,
			'model'        => $this->get_text_meta( $post->ID, 'ys_model' ),
			'length'       => $this->get_numeric_meta( $post->ID, 'ys_length' ),
			'beam'         => $this->get_numeric_meta( $post->ID, 'ys_beam' ),
			'engine'       => $this->get_text_meta( $post->ID, 'ys_engine' ),
			'build_year'   => $this->get_integer_meta( $post->ID, 'ys_build_year' ),
			'refit_year'   => $this->get_integer_meta( $post->ID, 'ys_refit_year' ),
			'cabins'       => $this->get_integer_meta( $post->ID, 'ys_cabins' ),
			'guests'       => $this->get_integer_meta( $post->ID, 'ys_guests' ),
			'crew'         => $this->get_integer_meta( $post->ID, 'ys_crew' ),
			'features'     => $this->get_array_meta( $post->ID, 'ys_extra_features' ),
			'cta'          => $this->resolve_cta( $post->ID ),
		];
	}

	/**
	 * Resolve taxonomy terms for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return array|null
	 */
	public function resolve_location_terms( $post_id ) {
		$taxonomy_slug = $this->get_taxonomy_slug();

		if ( '' === $taxonomy_slug || ! taxonomy_exists( $taxonomy_slug ) ) {
			return null;
		}

		$terms = get_the_terms( $post_id, $taxonomy_slug );

		if ( is_wp_error( $terms ) || empty( $terms ) || ! is_array( $terms ) ) {
			return null;
		}

		return $this->resolve_country_port( $terms );
	}

	/**
	 * Resolve country and port from taxonomy terms.
	 *
	 * @param WP_Term[] $terms Term objects.
	 * @return array|null
	 */
	public function resolve_country_port( $terms ) {
		$child_terms  = [];
		$parent_terms = [];

		foreach ( $terms as $term ) {
			if ( ! $term instanceof WP_Term ) {
				continue;
			}

			if ( $term->parent > 0 ) {
				$child_terms[] = $term;
			} else {
				$parent_terms[] = $term;
			}
		}

		$sort_terms = static function( &$items ) {
			usort(
				$items,
				static function( $left, $right ) {
					$name_compare = strcasecmp( $left->name, $right->name );

					if ( 0 !== $name_compare ) {
						return $name_compare;
					}

					return $left->term_id <=> $right->term_id;
				}
			);
		};

		if ( empty( $child_terms ) && empty( $parent_terms ) ) {
			return null;
		}

		$port_term    = null;
		$country_term = null;

		if ( ! empty( $child_terms ) ) {
			$sort_terms( $child_terms );
			$port_term    = $child_terms[0];
			$country_term = get_term( $port_term->parent, $port_term->taxonomy );
		} else {
			$sort_terms( $parent_terms );
			$country_term = $parent_terms[0];
		}

		if ( ! $country_term instanceof WP_Term || $country_term->term_id <= 0 ) {
			return null;
		}

		$flag_url = $this->taxonomy instanceof YS_Taxonomy ? $this->taxonomy->get_term_flag_url( $country_term->term_id, 'thumbnail' ) : '';

		return [
			'country' => [
				'id'   => (int) $country_term->term_id,
				'name' => $country_term->name,
				'slug' => $country_term->slug,
				'flag' => $flag_url,
			],
			'port'    => [
				'id'   => $port_term instanceof WP_Term ? (int) $port_term->term_id : 0,
				'name' => $port_term instanceof WP_Term ? $port_term->name : '',
				'slug' => $port_term instanceof WP_Term ? $port_term->slug : '',
			],
		];
	}

	/**
	 * Resolve CTA values with override fallback.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	public function resolve_cta( $post_id ) {
		return [
			'watch_video' => $this->resolve_cta_value( $post_id, 'ys_watch_video_url', 'ys_global_watch_video_url' ),
			'video_call'  => $this->resolve_cta_value( $post_id, 'ys_video_call_url', 'ys_global_video_call_url' ),
			'schedule'    => $this->resolve_cta_value( $post_id, 'ys_schedule_url', 'ys_global_schedule_url' ),
		];
	}

	/**
	 * Resolve image URL with fallback chain.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public function resolve_image_url( $post_id ) {
		$card_image_id = absint( get_post_meta( $post_id, 'ys_card_image_id', true ) );

		if ( $card_image_id ) {
			$card_image_url = wp_get_attachment_image_url( $card_image_id, 'full' );

			if ( $card_image_url ) {
				return $card_image_url;
			}
		}

		$featured_image_url = get_the_post_thumbnail_url( $post_id, 'full' );

		if ( $featured_image_url ) {
			return $featured_image_url;
		}

		return $this->get_placeholder_image_url();
	}

	/**
	 * Resolve and normalize booked month values.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	public function get_booked_months( $post_id ) {
		$values = $this->get_array_meta( $post_id, 'ys_booked' );

		if ( empty( $values ) ) {
			$values = $this->get_array_meta( $post_id, 'ys_availability' );
		}

		$values = array_filter(
			$values,
			static function( $value ) {
				return 1 === preg_match( '/^\d{4}-\d{2}$/', $value );
			}
		);

		sort( $values, SORT_STRING );

		return array_values( array_unique( $values ) );
	}

	/**
	 * Backward-compatible wrapper for older availability-based callers.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	public function resolve_availability( $post_id ) {
		return $this->get_booked_months( $post_id );
	}

	/**
	 * Get numeric meta value as int/float/null.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key Meta key.
	 * @return int|float|null
	 */
	public function get_numeric_meta( $post_id, $key ) {
		$value = get_post_meta( $post_id, $key, true );

		if ( '' === $value || null === $value || ! is_scalar( $value ) || ! is_numeric( $value ) ) {
			return null;
		}

		return (float) $value;
	}

	/**
	 * Get text meta value.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key Meta key.
	 * @return string|null
	 */
	public function get_text_meta( $post_id, $key ) {
		$value = get_post_meta( $post_id, $key, true );

		if ( ! is_scalar( $value ) ) {
			return null;
		}

		$value = trim( sanitize_text_field( (string) $value ) );

		return '' !== $value ? $value : null;
	}

	/**
	 * Get integer meta value.
	 *
	 * @param int         $post_id Post ID.
	 * @param string      $key Meta key.
	 * @param int|null    $default Default value.
	 * @return int|null
	 */
	public function get_integer_meta( $post_id, $key, $default = null ) {
		$value = get_post_meta( $post_id, $key, true );

		if ( '' === $value || null === $value || ! is_scalar( $value ) ) {
			return $default;
		}

		if ( 1 !== preg_match( '/^-?\d+$/', (string) $value ) ) {
			return $default;
		}

		return (int) $value;
	}

	/**
	 * Get sanitized array meta values.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key Meta key.
	 * @return array
	 */
	public function get_array_meta( $post_id, $key ) {
		$values = get_post_meta( $post_id, $key, true );

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
	 * Get placeholder image URL.
	 *
	 * @return string
	 */
	public function get_placeholder_image_url() {
		return '';
	}

	/**
	 * Sort normalized items.
	 *
	 * @param array $items Items array.
	 * @return array
	 */
	public function sort_items( $items ) {
		usort(
			$items,
			static function( $left, $right ) {
				$priority_compare = ( $right['priority'] ?? 0 ) <=> ( $left['priority'] ?? 0 );

				if ( 0 !== $priority_compare ) {
					return $priority_compare;
				}

				return strcasecmp( $left['title'] ?? '', $right['title'] ?? '' );
			}
		);

		return $items;
	}

	/**
	 * Resolve a single CTA value with fallback.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $post_meta_key Post-level meta key.
	 * @param string $setting_key Global setting key.
	 * @return string|null
	 */
	private function resolve_cta_value( $post_id, $post_meta_key, $setting_key ) {
		$post_value = esc_url_raw( (string) get_post_meta( $post_id, $post_meta_key, true ) );

		if ( '' !== $post_value ) {
			return $post_value;
		}

		$global_value = esc_url_raw( (string) $this->settings->get_setting( $setting_key, '' ) );

		return '' !== $global_value ? $global_value : null;
	}
}
