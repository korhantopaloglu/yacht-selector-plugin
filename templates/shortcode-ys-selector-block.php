<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$items          = is_array( $items ?? null ) ? $items : [];
$countries      = is_array( $countries ?? null ) ? $countries : [];
$months         = is_array( $months ?? null ) ? $months : [];
$contact_tools  = is_array( $contact_tools ?? null ) ? $contact_tools : [];
$container_id   = isset( $container_id ) ? (string) $container_id : '';
$watch_text     = isset( $watch_text ) ? (string) $watch_text : 'Watch Me';
$call_text      = isset( $call_text ) ? (string) $call_text : 'Call the Crew';
$book_text      = isset( $book_text ) ? (string) $book_text : 'Book Now';
$booked_text    = isset( $booked_text ) ? (string) $booked_text : 'Booked';
$all_label_text = isset( $all_label_text ) ? (string) $all_label_text : 'All';
$empty_text     = isset( $empty_text ) ? (string) $empty_text : 'No results found';
$crew_top_title = isset( $crew_top_title ) ? (string) $crew_top_title : 'Connect with';
$crew_title     = isset( $crew_title ) ? (string) $crew_title : 'The Crew';
$crew_subtitle  = isset( $crew_subtitle ) ? (string) $crew_subtitle : 'See the yacht live - choose how you’d like to connect. The crew is currently on board.';
$on_board_text  = isset( $on_board_text ) ? (string) $on_board_text : 'Currently on board: {crew_member}';
$offline_text   = isset( $offline_text ) ? (string) $offline_text : 'Currently offline: {crew_member}';
$online_icon    = isset( $online_icon ) ? (string) $online_icon : '';
$ui_class       = isset( $ui_class ) ? sanitize_html_class( (string) $ui_class ) : '';
$root_classes   = 'ys-selector-block-container' . ( '' !== $ui_class ? ' ' . $ui_class : '' );

if ( ! function_exists( 'ys_render_contact_tool_icon_svg' ) ) {
	/**
	 * Render a lightweight inline SVG for supported contact tool icons.
	 *
	 * @param string $icon_key Icon key.
	 * @return string
	 */
	function ys_render_contact_tool_icon_svg( $icon_key ) {
		$svg_map = [
			'phone'          => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.86 19.86 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.86 19.86 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72l.34 2.72a2 2 0 0 1-.57 1.73L7.1 9.9a16 16 0 0 0 7 7l1.73-1.78a2 2 0 0 1 1.73-.57l2.72.34A2 2 0 0 1 22 16.92z"/></svg>',
			'calendar'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4"/><path d="M16 2v4"/><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M3 10h18"/></svg>',
			'message-circle' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 1 12 21a8.96 8.96 0 0 1-4.1-1L3 21z"/></svg>',
			'mail'           => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>',
			'video'          => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 8-6 4 6 4V8Z"/><rect x="2" y="6" width="14" height="12" rx="2"/></svg>',
			'message-square' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
			'link'           => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>',
			'globe'          => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15 15 0 0 1 4 10 15 15 0 0 1-4 10 15 15 0 0 1-4-10 15 15 0 0 1 4-10Z"/></svg>',
		];

		return isset( $svg_map[ $icon_key ] ) ? $svg_map[ $icon_key ] : '';
	}
}
?>
<div id="<?php echo esc_attr( $container_id ); ?>" class="<?php echo esc_attr( $root_classes ); ?>">
	<div class="ys-header-wrapper">
		<div class="ys-countries-container">
			<div class="ys-countries-mask">
				<a href="#" class="ys-country active" data-country="all">
					<span class="ys-country-option__flag-wrap">
						<span class="ys-country-option__flag ys-country-option__flag--placeholder" aria-hidden="true"></span>
					</span>
					<span class="ys-country-option__label"><?php echo esc_html( $all_label_text ); ?></span>
				</a>
				<?php foreach ( $countries as $country ) : ?>
					<?php $flag_url = isset( $country['flag'] ) ? (string) $country['flag'] : ''; ?>
					<a href="#" class="ys-country" data-country="<?php echo esc_attr( $country['slug'] ?? '' ); ?>">
						<span class="ys-country-option__flag-wrap">
							<?php if ( '' !== $flag_url ) : ?>
								<img class="ys-country-option__flag" src="<?php echo esc_url( $flag_url ); ?>" alt="<?php echo esc_attr( sprintf( __( '%s flag', 'yacht-selector' ), $country['name'] ?? '' ) ); ?>">
							<?php else : ?>
								<span class="ys-country-option__flag ys-country-option__flag--placeholder" aria-hidden="true"></span>
							<?php endif; ?>
						</span>
						<span class="ys-country-option__label"><?php echo esc_html( $country['name'] ?? '' ); ?></span>
					</a>
				<?php endforeach; ?>
			</div>
		</div>

		<div class="ys-months-container">
			<div class="ys-months-header-wrapper">
				<div class="ys-months-track">
					<?php foreach ( $months as $month ) : ?>
						<a href="#" class="ys-month<?php echo ! empty( $month['active'] ) ? ' active' : ''; ?>" data-month="<?php echo esc_attr( $month['month'] ?? '' ); ?>" data-month-full="<?php echo esc_attr( $month['full'] ?? '' ); ?>" data-density="<?php echo esc_attr( (string) ( $month['density'] ?? 0 ) ); ?>">
							<span class="ys-month-label"><?php echo esc_html( $month['label'] ?? '' ); ?></span>
							<span class="ys-month-bar">
								<span class="ys-month-bar-fill" style="width: <?php echo esc_attr( (string) ( $month['density'] ?? 0 ) ); ?>%;"></span>
							</span>
							<span class="ys-month-density"><?php echo esc_html( (string) ( $month['density'] ?? 0 ) ); ?>%</span>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
			<div class="ys-month-overlay">
				<span class="ys-month-overlay-label"></span>
				<span class="ys-month-overlay-density"></span>
				<span class="ys-month-overlay-bar">
					<span class="ys-month-overlay-bar-fill"></span>
				</span>
			</div>
		</div>
	</div>

	<?php if ( ! empty( $items ) ) : ?>
		<div class="ys-cards-container">
			<?php foreach ( $items as $index => $item ) : ?>
				<?php
				$month_numbers = [];
				$booked_values = isset( $item['booked'] ) && is_array( $item['booked'] ) ? $item['booked'] : [];

				foreach ( $booked_values as $booked_value ) {
					if ( ! is_string( $booked_value ) || 1 !== preg_match( '/^\d{4}-(\d{2})$/', $booked_value, $matches ) ) {
						continue;
					}

					$month_number = (int) $matches[1];

					if ( $month_number < 1 || $month_number > 12 ) {
						continue;
					}

					$month_numbers[] = $month_number;
				}

				$month_numbers = array_values( array_unique( $month_numbers ) );
				sort( $month_numbers, SORT_NUMERIC );

				$location_slug = isset( $item['country']['slug'] ) ? (string) $item['country']['slug'] : '';
				$title         = isset( $item['title'] ) ? (string) $item['title'] : '';
				$image_url     = isset( $item['image'] ) ? (string) $item['image'] : '';
				$model         = isset( $item['model'] ) ? (string) $item['model'] : '';
				$country_name  = isset( $item['country']['name'] ) ? (string) $item['country']['name'] : '';
				$port          = isset( $item['port']['name'] ) ? (string) $item['port']['name'] : '';
				$engine        = isset( $item['engine'] ) ? (string) $item['engine'] : '';
				$build_year    = isset( $item['build_year'] ) && null !== $item['build_year'] ? (string) $item['build_year'] : '';
				$refit_year    = isset( $item['refit_year'] ) && null !== $item['refit_year'] ? (string) $item['refit_year'] : '';
				$crew          = isset( $item['crew'] ) && null !== $item['crew'] ? (string) $item['crew'] : '';
				$raw_features  = isset( $item['features'] ) && is_array( $item['features'] ) ? $item['features'] : [];
				$features      = [];
				$junk_features = [ '-', '--', 'n/a', 'na', 'none', 'null', 'false' ];

				foreach ( $raw_features as $raw_feature ) {
					if ( ! is_scalar( $raw_feature ) ) {
						continue;
					}

					$feature_text = trim( sanitize_text_field( (string) $raw_feature ) );

					if ( '' === $feature_text ) {
						continue;
					}

					if ( in_array( strtolower( $feature_text ), $junk_features, true ) ) {
						continue;
					}

					if ( in_array( $feature_text, $features, true ) ) {
						continue;
					}

					$features[] = $feature_text;
				}
				$crew_members  = isset( $item['assigned_crew'] ) && is_array( $item['assigned_crew'] ) ? $item['assigned_crew'] : [];
				$priority      = isset( $item['priority'] ) ? (int) $item['priority'] : 0;
				$permalink     = isset( $item['url'] ) ? (string) $item['url'] : '';
				$card_classes  = 'ys-card' . ( 0 === $index ? ' selected' : '' );
				$dimensions    = [];
				$length_value  = isset( $item['length'] ) && null !== $item['length'] ? (string) $item['length'] : '';
				$beam_value    = isset( $item['beam'] ) && null !== $item['beam'] ? (string) $item['beam'] : '';
				$year_value    = trim( $build_year . ( '' !== $build_year && '' !== $refit_year ? ' / ' : '' ) . $refit_year );
				$primary_name  = ! empty( $crew_members[0]['name'] ) ? (string) $crew_members[0]['name'] : '';
				$column_one    = [];
				$column_two    = [];
				$primary_tool_urls = ! empty( $crew_members[0]['tool_urls'] ) && is_array( $crew_members[0]['tool_urls'] ) ? $crew_members[0]['tool_urls'] : [];
				$primary_selected_tool_keys = ! empty( $crew_members[0]['contact_tools'] ) && is_array( $crew_members[0]['contact_tools'] ) ? array_values( array_filter( array_map( static function( $tool ) {
					return isset( $tool['slug'] ) ? (string) $tool['slug'] : '';
				}, $crew_members[0]['contact_tools'] ) ) ) : [];

				if ( '' !== $length_value ) {
					$dimensions[] = $length_value . 'm';
				}

				if ( '' !== $beam_value ) {
					$dimensions[] = $beam_value . 'm';
				}

				foreach ( $contact_tools as $tool ) {
					if ( ! empty( $tool['respects_online_status'] ) ) {
						$column_one[] = $tool;
					} else {
						$column_two[] = $tool;
					}
				}
				?>
				<?php 
				// Check if any crew member is online
				$any_crew_online = false;
				if ( ! empty( $crew_members ) ) {
					foreach ( $crew_members as $crew_member ) {
						$online_start = isset( $crew_member['online_start'] ) ? (string) $crew_member['online_start'] : '';
						$online_end = isset( $crew_member['online_end'] ) ? (string) $crew_member['online_end'] : '';
						
						if ( '' !== $online_start && '' !== $online_end ) {
							$any_crew_online = true;
							break;
						}
					}
				}
				
				$card_classes = 'ys-card';
				if ( $priority === 0 ) {
					$card_classes .= ' is-active';
				}
				if ( $any_crew_online ) {
					$card_classes .= ' crew-online';
				}
				?>
				<article
					class="<?php echo esc_attr( $card_classes ); ?>"
					data-card-id="<?php echo esc_attr( (string) ( $item['id'] ?? 0 ) ); ?>"
					data-has-crew="<?php echo esc_attr( ! empty( $crew_members ) ? '1' : '0' ); ?>"
					data-location="<?php echo esc_attr( $location_slug ); ?>"
					data-priority="<?php echo esc_attr( (string) $priority ); ?>"
					data-booked="<?php echo esc_attr( implode( ',', $booked_values ) ); ?>"
					data-months="<?php echo esc_attr( implode( ',', $month_numbers ) ); ?>"
					data-card-index="<?php echo esc_attr( (string) ( $index + 1 ) ); ?>"
				>

					<div class="ys-card-content-group">
					<div class="ys-card-image-container">
						<?php if ( '' !== $image_url ) : ?>
							<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $title ); ?>">
						<?php endif; ?>
					</div>

					<div class="ys-card-content-container">
						<h3><?php echo esc_html( $title ); ?></h3>
						<span class="booked-text"><?php echo esc_html( $booked_text ); ?></span>
						<div class="ys-card-details-container">
							<div class="ys-card-specifications-group">
							<div class="ys-card-group-title"><?php esc_html_e( 'Specifications', 'yacht-selector' ); ?></div>
							<div class="ys-card-specifications-items">
								<?php if ( '' !== $model ) : ?>
									<div class="ys-card-spec-item"><span class="ys-card-spec-label"><?php esc_html_e( 'Model', 'yacht-selector' ); ?></span><span class="ys-card-spec-value"><?php echo esc_html( $model ); ?></span></div>
								<?php endif; ?>
								<?php if ( ! empty( $dimensions ) ) : ?>
									<div class="ys-card-spec-item"><span class="ys-card-spec-label"><?php esc_html_e( 'Olculer', 'yacht-selector' ); ?></span><span class="ys-card-spec-value"><?php echo esc_html( implode( ' / ', $dimensions ) ); ?></span></div>
								<?php endif; ?>
								<?php if ( '' !== $engine ) : ?>
									<div class="ys-card-spec-item"><span class="ys-card-spec-label"><?php esc_html_e( 'Motor', 'yacht-selector' ); ?></span><span class="ys-card-spec-value"><?php echo esc_html( $engine ); ?></span></div>
								<?php endif; ?>
								<?php if ( '' !== $year_value ) : ?>
									<div class="ys-card-spec-item"><span class="ys-card-spec-label"><?php esc_html_e( 'Uretim / R', 'yacht-selector' ); ?></span><span class="ys-card-spec-value"><?php echo esc_html( $year_value ); ?></span></div>
								<?php endif; ?>
								<?php if ( '' !== $port ) : ?>
									<div class="ys-card-spec-item"><span class="ys-card-spec-label"><?php esc_html_e( 'Liman', 'yacht-selector' ); ?></span><span class="ys-card-spec-value"><?php echo esc_html( $port ); ?></span></div>
								<?php endif; ?>
							</div>
						</div>

						<div class="ys-card-capacity-group">
							<div class="ys-card-group-title"><?php esc_html_e( 'Capacity', 'yacht-selector' ); ?></div>
							<div class="ys-card-capacity-items">
								<?php if ( isset( $item['cabins'] ) && null !== $item['cabins'] ) : ?>
									<div class="ys-card-capacity-item"><span class="ys-card-capacity-value"><?php echo esc_html( (string) (int) $item['cabins'] ); ?></span><span class="ys-card-capacity-label"><?php esc_html_e( 'Kabin', 'yacht-selector' ); ?></span></div>
								<?php endif; ?>
								<?php if ( isset( $item['guests'] ) && null !== $item['guests'] ) : ?>
									<div class="ys-card-capacity-item"><span class="ys-card-capacity-value"><?php echo esc_html( (string) (int) $item['guests'] ); ?></span><span class="ys-card-capacity-label"><?php esc_html_e( 'Misafir', 'yacht-selector' ); ?></span></div>
								<?php endif; ?>
								<?php if ( '' !== $crew ) : ?>
									<div class="ys-card-capacity-item"><span class="ys-card-capacity-value"><?php echo esc_html( $crew ); ?></span><span class="ys-card-capacity-label"><?php esc_html_e( 'Murettebat', 'yacht-selector' ); ?></span></div>
								<?php endif; ?>
							</div>
						</div>

						<div class="ys-card-features-group">
								<div class="ys-card-group-title"><?php esc_html_e( 'Extra Features', 'yacht-selector' ); ?></div>
								<div class="ys-card-features-list">
									<?php 
									$visible_features = [];
									$hidden_features  = [];
									$total_chars      = 0;

									if ( ! empty( $features ) ) :
										
										foreach ( $features as $feature ) :
											$feature_text  = (string) $feature;
											$feature_chars = strlen( $feature_text );
											
											if ( $total_chars + $feature_chars <= 70 ) {
												$visible_features[] = $feature_text;
												$total_chars += $feature_chars;
											} else {
												$hidden_features[] = $feature_text;
											}
										endforeach;
										
										// Display visible features
										foreach ( $visible_features as $feature ) : ?>
										<div class="ys-card-feature-item"><?php echo esc_html( $feature ); ?></div>
									<?php endforeach; 
									endif; ?>
								</div>
								
								<?php // Display overflow indicator if there are hidden features ?>
								<?php if ( ! empty( $hidden_features ) ) : ?>
									<span class="ys-card-feature-overflow" data-features="<?php echo esc_attr( implode( ', ', $hidden_features ) ); ?>">
										+<?php echo count( $hidden_features ); ?> <?php esc_html_e( 'Fazlasi', 'yacht-selector' ); ?>
									</span>
								<?php endif; ?>
							</div>
									</div>

						
					</div>
											<div class="ys-card-actions-container">
							<button type="button" class="ys-watch-button ys-watch-video-button" data-url="<?php echo esc_attr( $permalink ); ?>"><?php echo esc_html( $watch_text ); ?></button>
							<button type="button" class="ys-call-button ys-call-crew-button"><?php echo esc_html( $call_text ); ?></button>
							<button type="button" class="ys-book-button ys-book-now-button"><?php echo esc_html( $book_text ); ?></button>
						</div>
						</div>
						<div class="ys-card-contact-tools-group">
							<div class="ys-card-contact-tools-group-container">
							<button type="button" class="ys-card-crew-close" aria-label="<?php esc_attr_e( 'Close crew panel', 'yacht-selector' ); ?>">&times;</button>
							<div class="ys-card-image-container">
								<?php if ( '' !== $image_url ) : ?>
									<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $title ); ?>">
								<?php endif; ?>
							</div>
							<h3><?php echo esc_html( $title ); ?></h3>
							<div class="ys-card-crew-content-group">
							<div class="ys-card-crew-content-header">
								<div class="ys-card-crew-top-title"><?php echo esc_html( $crew_top_title ); ?></div>
								<div class="ys-card-crew-title"><?php echo esc_html( $crew_title ); ?></div>
								<div class="ys-card-crew-subtitle"><?php echo esc_html( $crew_subtitle ); ?></div>
							</div>

							<?php if ( ! empty( $column_one ) || ! empty( $column_two ) ) : ?>
								<div class="ys-card-contact-tools-container">
									<div class="ys-tool-cards-column-1">
										<?php foreach ( $column_one as $tool ) : ?>
											<a class="ys-tool-card ys-tool-card-online-sensitive" data-tool-key="<?php echo esc_attr( (string) ( $tool['slug'] ?? '' ) ); ?>" data-tool-id="<?php echo esc_attr( (string) ( $tool['id'] ?? 0 ) ); ?>" data-tool-slug="<?php echo esc_attr( (string) ( $tool['slug'] ?? '' ) ); ?>" data-default-url="<?php echo esc_attr( (string) ( $tool['default_url'] ?? '' ) ); ?>" data-tool-url="<?php echo esc_attr( isset( $primary_tool_urls[ (string) ( $tool['slug'] ?? '' ) ] ) ? (string) $primary_tool_urls[ (string) ( $tool['slug'] ?? '' ) ] : '' ); ?>" data-online-sensitive="1" href="<?php echo esc_url( isset( $primary_tool_urls[ (string) ( $tool['slug'] ?? '' ) ] ) ? (string) $primary_tool_urls[ (string) ( $tool['slug'] ?? '' ) ] : '#' ); ?>" target="_blank" rel="noopener noreferrer">
												<?php if ( ! empty( $tool['image'] ) ) : ?>
													<img class="ys-tool-card-icon" src="<?php echo esc_url( $tool['image'] ); ?>" alt="<?php echo esc_attr( (string) ( $tool['name'] ?? '' ) ); ?>">
												<?php elseif ( 'icon' === ( $tool['icon_type'] ?? '' ) && ! empty( $tool['icon'] ) ) : ?>
													<?php $tool_svg = ys_render_contact_tool_icon_svg( (string) $tool['icon'] ); ?>
													<?php if ( '' !== $tool_svg ) : ?>
														<span class="ys-tool-card-icon ys-tool-card-icon-svg" aria-hidden="true"><?php echo $tool_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
													<?php endif; ?>
												<?php endif; ?>
												<div class="ys-tool-card-text-wrapper">
													<div class="ys-tool-card-title"><?php echo esc_html( (string) ( $tool['name'] ?? '' ) ); ?></div>
													<?php if ( ! empty( $tool['description'] ) ) : ?><div class="ys-tool-card-description"><?php echo esc_html( (string) $tool['description'] ); ?></div><?php endif; ?>
												</div>
											</a>
										<?php endforeach; ?>
									</div>
									<div class="ys-tool-cards-column-2">
										<?php foreach ( $column_two as $tool ) : ?>
											<a class="ys-tool-card" data-tool-key="<?php echo esc_attr( (string) ( $tool['slug'] ?? '' ) ); ?>" data-tool-id="<?php echo esc_attr( (string) ( $tool['id'] ?? 0 ) ); ?>" data-tool-slug="<?php echo esc_attr( (string) ( $tool['slug'] ?? '' ) ); ?>" data-default-url="<?php echo esc_attr( (string) ( $tool['default_url'] ?? '' ) ); ?>" data-tool-url="<?php echo esc_attr( isset( $primary_tool_urls[ (string) ( $tool['slug'] ?? '' ) ] ) ? (string) $primary_tool_urls[ (string) ( $tool['slug'] ?? '' ) ] : '' ); ?>" data-online-sensitive="0" href="<?php echo esc_url( isset( $primary_tool_urls[ (string) ( $tool['slug'] ?? '' ) ] ) ? (string) $primary_tool_urls[ (string) ( $tool['slug'] ?? '' ) ] : '#' ); ?>" target="_blank" rel="noopener noreferrer">
												<?php if ( ! empty( $tool['image'] ) ) : ?>
													<img class="ys-tool-card-icon" src="<?php echo esc_url( $tool['image'] ); ?>" alt="<?php echo esc_attr( (string) ( $tool['name'] ?? '' ) ); ?>">
												<?php elseif ( 'icon' === ( $tool['icon_type'] ?? '' ) && ! empty( $tool['icon'] ) ) : ?>
													<?php $tool_svg = ys_render_contact_tool_icon_svg( (string) $tool['icon'] ); ?>
													<?php if ( '' !== $tool_svg ) : ?>
														<span class="ys-tool-card-icon ys-tool-card-icon-svg" aria-hidden="true"><?php echo $tool_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
													<?php endif; ?>
												<?php endif; ?>
												<div class="ys-tool-card-title"><?php echo esc_html( (string) ( $tool['name'] ?? '' ) ); ?></div>
												<?php if ( ! empty( $tool['description'] ) ) : ?><div class="ys-tool-card-description"><?php echo esc_html( (string) $tool['description'] ); ?></div><?php endif; ?>
											</a>
										<?php endforeach; ?>
									</div>
								</div>
							<?php endif; ?>
						</div>
					</div>
					<div class="ys-card-contact-tools-action-group">
								<button type="button" class="ys-card-crew-close-button" aria-label="<?php esc_attr_e( 'Close crew panel', 'yacht-selector' ); ?>"><?php esc_attr_e( 'Go to back', 'yacht-selector' ); ?></button>
							
								<?php if ( ! empty( $crew_members ) ) : ?>
									<div class="ys-card-crew-status-group" data-crew-online="0" data-online-text-template="<?php echo esc_attr( $on_board_text ); ?>" data-offline-text-template="<?php echo esc_attr( $offline_text ); ?>">
										<?php if ( '' !== $online_icon ) : ?>
											<img class="ys-card-crew-status-icon" src="<?php echo esc_url( $online_icon ); ?>" alt="">
										<?php endif; ?>
										<span class="ys-card-crew-status-text"></span>
										<?php if ( count( $crew_members ) === 1 ) : ?>
											<span class="ys-card-crew-member-single" data-crew-id="<?php echo esc_attr( (string) ( $crew_members[0]['id'] ?? 0 ) ); ?>" data-crew-name="<?php echo esc_attr( $primary_name ); ?>" data-online-start="<?php echo esc_attr( (string) ( $crew_members[0]['online_start'] ?? '' ) ); ?>" data-online-end="<?php echo esc_attr( (string) ( $crew_members[0]['online_end'] ?? '' ) ); ?>" data-tool-urls="<?php echo esc_attr( wp_json_encode( $primary_tool_urls ) ); ?>" data-selected-tools="<?php echo esc_attr( wp_json_encode( $primary_selected_tool_keys ) ); ?>"><?php echo esc_html( $primary_name ); ?></span>
										<?php else : ?>
											<select class="ys-card-crew-member-select">
												<?php foreach ( $crew_members as $crew_member ) : ?>
													<option value="<?php echo esc_attr( (string) ( $crew_member['id'] ?? 0 ) ); ?>" data-crew-id="<?php echo esc_attr( (string) ( $crew_member['id'] ?? 0 ) ); ?>" data-crew-name="<?php echo esc_attr( (string) ( $crew_member['name'] ?? '' ) ); ?>" data-online-start="<?php echo esc_attr( (string) ( $crew_member['online_start'] ?? '' ) ); ?>" data-online-end="<?php echo esc_attr( (string) ( $crew_member['online_end'] ?? '' ) ); ?>" data-tool-urls="<?php echo esc_attr( wp_json_encode( isset( $crew_member['tool_urls'] ) && is_array( $crew_member['tool_urls'] ) ? $crew_member['tool_urls'] : [] ) ); ?>" data-selected-tools="<?php echo esc_attr( wp_json_encode( ! empty( $crew_member['contact_tools'] ) && is_array( $crew_member['contact_tools'] ) ? array_values( array_filter( array_map( static function( $tool ) {
														return isset( $tool['slug'] ) ? (string) $tool['slug'] : '';
													}, $crew_member['contact_tools'] ) ) ) : [] ) ); ?>"><?php echo esc_html( (string) ( $crew_member['name'] ?? '' ) ); ?></option>
												<?php endforeach; ?>
											</select>
										<?php endif; ?>
									</div>
								<?php endif; ?>

					</div>

				</article>
			<?php endforeach; ?>

			<div class="ys-card-nav-container">
				<button type="button" class="ys-prev"><svg viewBox="0 0 24 24" fill="none"><path d="M15 6L9 12L15 18" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg></button>
				<button type="button" class="ys-next"><svg viewBox="0 0 24 24" fill="none"><path d="M9 6L15 12L9 18" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg></button>
				<nav class="ys-card-thumbnail-nav" aria-label="<?php esc_attr_e( 'Yacht thumbnails', 'yacht-selector' ); ?>">
					<div class="ys-card-thumbnail-track"></div>
				</nav>
			</div>
		</div>
	<?php else : ?>
		<div class="ys-empty-state-container"><?php echo esc_html( $empty_text ); ?></div>
	<?php endif; ?>
</div>
