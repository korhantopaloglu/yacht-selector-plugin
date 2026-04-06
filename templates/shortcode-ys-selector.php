<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$template_assets_url = YS_PLUGIN_URL . 'templates/assets/';
$active_item         = is_array( $active_item ) ? $active_item : [];
$initial_items       = is_array( $initial_items ) ? $initial_items : [];
$countries           = is_array( $countries ) ? $countries : [];
$months              = is_array( $months ) ? $months : [];
$selected_country    = is_string( $selected_country ) ? $selected_country : '';
$selected_month      = is_string( $selected_month ) ? $selected_month : '';
$ui_class            = isset( $ui_class ) ? sanitize_html_class( (string) $ui_class ) : '';
$root_classes        = 'ys-yacht-selector' . ( '' !== $ui_class ? ' ' . $ui_class : '' );
$country_label       = '';
$month_label         = '';
$month_stats         = [
	'booked'     => 0,
	'total'      => count( $initial_items ),
	'percentage' => 0,
];
foreach ( $countries as $country ) {
	if ( isset( $country['slug'] ) && $country['slug'] === $selected_country ) {
		$country_label = isset( $country['name'] ) ? (string) $country['name'] : '';
		break;
	}
}

foreach ( $months as $month ) {
	if ( isset( $month['value'] ) && $month['value'] === $selected_month ) {
		$month_label = isset( $month['label'] ) ? (string) $month['label'] : '';
		break;
	}
}

foreach ( $initial_items as $item ) {
	$booked = isset( $item['booked'] ) && is_array( $item['booked'] ) ? $item['booked'] : [];
	if ( in_array( $selected_month, $booked, true ) ) {
		$month_stats['booked']++;
	}
}

if ( $month_stats['total'] > 0 ) {
	$month_stats['percentage'] = (int) round( ( $month_stats['booked'] / $month_stats['total'] ) * 100 );
}
?>
<div id="<?php echo esc_attr( $container_id ); ?>" class="<?php echo esc_attr( $root_classes ); ?>">
	<script type="application/json" class="ys-selector-data"><?php echo wp_json_encode( $payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); ?></script>

	<div class="page">
		<div class="page-inner">

			<!-- LOGO -->
			<div class="logo">
				<img src="<?php echo esc_url( $template_assets_url . 'logo.svg' ); ?>" alt="<?php echo esc_attr__( 'Naviga Yachting', 'yacht-selector' ); ?>">
			</div>

			<!-- YACHT PICKER — horizontal drag/snap strip behind the card -->
			<div class="yacht-picker-wrapper" id="<?php echo esc_attr( $container_id ); ?>-picker-wrapper">
				<div class="yacht-picker-track is-static" id="<?php echo esc_attr( $container_id ); ?>-picker-track">
					<?php if ( ! empty( $initial_items ) ) : ?>
						<?php foreach ( $initial_items as $item ) : ?>
							<?php
							$is_booked    = ! empty( $selected_month ) && ! empty( $item['booked'] ) && in_array( $selected_month, $item['booked'], true );
							$item_classes = 'yacht-picker-item ys-card ' . ( $is_booked ? 'booked' : 'available' );
							?>
							<article
								class="<?php echo esc_attr( $item_classes ); ?>"
								data-country="<?php echo esc_attr( $item['country']['slug'] ?? '' ); ?>"
								data-port="<?php echo esc_attr( $item['port']['slug'] ?? '' ); ?>"
								data-id="<?php echo esc_attr( $item['id'] ?? '' ); ?>"
								data-priority="<?php echo esc_attr( $item['priority'] ?? 0 ); ?>"
								data-month="<?php echo esc_attr( $selected_month ); ?>"
								data-booked="<?php echo esc_attr( $is_booked ? 'booked' : 'available' ); ?>"
							>
								<img src="<?php echo esc_url( $item['image'] ?? '' ); ?>" alt="<?php echo esc_attr( $item['title'] ?? '' ); ?>">
							</article>
						<?php endforeach; ?>
					<?php else : ?>
						<div class="ys-empty-state">
							<?php esc_html_e( 'No yachts match the current filters yet.', 'yacht-selector' ); ?>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<!-- COUNTRY WATERMARK -->
			<div class="country-watermark" id="<?php echo esc_attr( $container_id ); ?>-country-watermark"><?php echo esc_html( strtoupper( $country_label ) ); ?></div>

			<!-- ═══ MAIN CARD ═══ -->
			<div class="main-card">

				<!-- Yacht Image Area -->
				<div class="yacht-visual" id="<?php echo esc_attr( $container_id ); ?>-yacht-visual">
					<img class="yacht-visual-img" id="<?php echo esc_attr( $container_id ); ?>-yacht-img-a" src="<?php echo esc_url( $active_item['image'] ?? '' ); ?>" alt="<?php echo esc_attr( $active_item['title'] ?? '' ); ?>">
					<img class="yacht-visual-img" id="<?php echo esc_attr( $container_id ); ?>-yacht-img-b" src="" alt="">
					<canvas id="<?php echo esc_attr( $container_id ); ?>-transition-canvas"></canvas>

					<!-- Frosted Header: Tabs + Picker (180px) -->
					<div class="frosted-header">
						<div class="country-tabs" id="<?php echo esc_attr( $container_id ); ?>-country-tabs">
							<?php foreach ( $countries as $country ) : ?>
								<?php
								$is_active = isset( $country['slug'] ) && $country['slug'] === $selected_country;
								$flag_url  = isset( $country['flag'] ) ? (string) $country['flag'] : '';
								?>
								<button class="country-tab<?php echo $is_active ? ' active' : ''; ?>" data-country="<?php echo esc_attr( $country['slug'] ?? '' ); ?>" type="button">
									<span class="ys-country-option__flag-wrap">
										<?php if ( '' !== $flag_url ) : ?>
											<img class="ys-country-option__flag" src="<?php echo esc_url( $flag_url ); ?>" alt="<?php echo esc_attr( sprintf( __( '%s flag', 'yacht-selector' ), $country['name'] ?? '' ) ); ?>">
										<?php else : ?>
											<span class="ys-country-option__flag ys-country-option__flag--placeholder" aria-hidden="true"></span>
										<?php endif; ?>
									</span>
									<span class="ys-country-option__label"><?php echo esc_html( $country['name'] ?? '' ); ?></span>
								</button>
							<?php endforeach; ?>
						</div>

						<div class="picker-wrapper" id="<?php echo esc_attr( $container_id ); ?>-month-picker">
							<div class="center-overlay">
								<div class="center-month" id="<?php echo esc_attr( $container_id ); ?>-center-month"><?php echo esc_html( $month_label ); ?></div>
								<div class="center-bar-track"><div class="center-bar-fill" id="<?php echo esc_attr( $container_id ); ?>-center-bar" style="width: <?php echo esc_attr( $month_stats['percentage'] ); ?>%;"></div></div>
								<div class="center-pct-row">
									<span class="center-pct" id="<?php echo esc_attr( $container_id ); ?>-center-pct"><?php echo esc_html( $month_stats['percentage'] ); ?>%</span>
									<span class="center-pct-label" id="<?php echo esc_attr( $container_id ); ?>-center-pct-label">
										<?php esc_html_e( 'Booked', 'yacht-selector' ); ?>
									</span>
								</div>
							</div>
							<div class="picker-track">
								<?php foreach ( $months as $month ) : ?>
									<?php
									$month_booked_count = 0;
									foreach ( $initial_items as $item ) {
										if ( ! empty( $item['booked'] ) && in_array( $month['value'], $item['booked'], true ) ) {
											$month_booked_count++;
										}
									}
									$month_percentage = ! empty( $initial_items ) ? (int) round( ( $month_booked_count / count( $initial_items ) ) * 100 ) : 0;
									?>
									<div class="picker-item<?php echo $month['value'] === $selected_month ? ' selected' : ''; ?>" data-month="<?php echo esc_attr( $month['value'] ); ?>">
										<div class="month-name"><?php echo esc_html( wp_date( 'M', strtotime( $month['value'] . '-01' ) ) ); ?></div>
										<div class="occ-bar-track"><div class="occ-bar-fill" style="--occ-fill-width: <?php echo esc_attr( max( 10, $month_percentage ) ); ?>%; --occ-fill-color: #0d9488;"></div></div>
										<div class="occ-pct"><?php echo esc_html( $month_percentage ); ?>%</div>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					</div>

					<!-- Yacht Name Overlay -->
					<div class="yacht-name-area">
						<div class="yacht-name-row">
							<div class="yacht-name-line"></div>
							<div class="yacht-name-text" id="<?php echo esc_attr( $container_id ); ?>-yacht-name"><?php echo esc_html( $active_item['title'] ?? '' ); ?></div>
							<div class="yacht-name-line"></div>
						</div>
					</div>

					<!-- Navigation Arrows -->
					<button class="yacht-nav yacht-nav-left" id="<?php echo esc_attr( $container_id ); ?>-yacht-nav-left" aria-label="<?php echo esc_attr__( 'Previous yacht', 'yacht-selector' ); ?>" type="button">
						<svg viewBox="0 0 24 24" fill="none"><path d="M15 6L9 12L15 18" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
					</button>
					<button class="yacht-nav yacht-nav-right" id="<?php echo esc_attr( $container_id ); ?>-yacht-nav-right" aria-label="<?php echo esc_attr__( 'Next yacht', 'yacht-selector' ); ?>" type="button">
						<svg viewBox="0 0 24 24" fill="none"><path d="M9 6L15 12L9 18" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
					</button>
				</div>

				<!-- Dashboard -->
				<div class="dashboard">
					<div class="specs-left" id="<?php echo esc_attr( $container_id ); ?>-specs-left">
						<div class="spec-row"><span class="spec-label"><?php esc_html_e( 'Model', 'yacht-selector' ); ?></span><span class="spec-value"><?php echo esc_html( $active_item['model'] ?? '' ); ?></span></div>
						<div class="spec-row"><span class="spec-label"><?php esc_html_e( 'Ölçüler', 'yacht-selector' ); ?></span><span class="spec-value"><?php echo esc_html( isset( $active_item['length'], $active_item['beam'] ) && null !== $active_item['length'] && null !== $active_item['beam'] ? $active_item['length'] . 'm x ' . $active_item['beam'] . 'm' : '' ); ?></span></div>
						<div class="spec-row"><span class="spec-label"><?php esc_html_e( 'Motor', 'yacht-selector' ); ?></span><span class="spec-value"><?php echo esc_html( $active_item['engine'] ?? '' ); ?></span></div>
						<div class="spec-row"><span class="spec-label"><?php esc_html_e( 'Üretim /R', 'yacht-selector' ); ?></span><span class="spec-value"><?php echo esc_html( isset( $active_item['build_year'], $active_item['refit_year'] ) && $active_item['build_year'] ? trim( $active_item['build_year'] . ( $active_item['refit_year'] ? ' / ' . $active_item['refit_year'] : '' ) ) : '' ); ?></span></div>
						<div class="spec-row"><span class="spec-label"><?php esc_html_e( 'Liman', 'yacht-selector' ); ?></span><span class="spec-value"><?php echo esc_html( $active_item['port']['name'] ?? '' ); ?></span></div>
					</div>
					<div class="specs-center" id="<?php echo esc_attr( $container_id ); ?>-specs-center">
						<div class="stat-block"><div class="stat-number"><?php echo esc_html( isset( $active_item['cabins'] ) && null !== $active_item['cabins'] ? (int) $active_item['cabins'] : '' ); ?></div><div class="stat-label"><?php esc_html_e( 'Kabin', 'yacht-selector' ); ?></div></div>
						<div class="stat-block"><div class="stat-number"><?php echo esc_html( isset( $active_item['guests'] ) && null !== $active_item['guests'] ? (int) $active_item['guests'] : '' ); ?></div><div class="stat-label"><?php esc_html_e( 'Misafir', 'yacht-selector' ); ?></div></div>
						<div class="stat-block"><div class="stat-number"><?php echo esc_html( isset( $active_item['crew'] ) && null !== $active_item['crew'] ? (int) $active_item['crew'] : '' ); ?></div><div class="stat-label"><?php esc_html_e( 'Mürettebat', 'yacht-selector' ); ?></div></div>
					</div>
					<div class="specs-right" id="<?php echo esc_attr( $container_id ); ?>-specs-right">
						<?php if ( ! empty( $active_item['features'] ) ) : ?>
							<?php foreach ( array_slice( $active_item['features'], 0, 4 ) as $feature ) : ?>
								<div class="amenity"><?php echo esc_html( $feature ); ?></div>
							<?php endforeach; ?>
							<?php if ( count( $active_item['features'] ) > 4 ) : ?>
								<div class="amenity more">+<?php echo esc_html( count( $active_item['features'] ) - 4 ); ?> <?php esc_html_e( 'Fazlası', 'yacht-selector' ); ?></div>
							<?php endif; ?>
						<?php endif; ?>
					</div>
				</div>

				<!-- CTA -->
				<div class="cta-area">
					<div class="cta-bar">
						<button class="cta-section" type="button" data-cta="watch_video" data-url="<?php echo esc_attr( $active_item['cta']['watch_video'] ?? '' ); ?>"><?php esc_html_e( 'Watch Video', 'yacht-selector' ); ?></button>
						<div class="cta-divider"></div>
						<button class="cta-section cta-primary" type="button" data-cta="video_call" data-url="<?php echo esc_attr( $active_item['cta']['video_call'] ?? '' ); ?>"><?php esc_html_e( 'Video Call Now', 'yacht-selector' ); ?></button>
						<div class="cta-divider"></div>
						<button class="cta-section" type="button" data-cta="schedule" data-url="<?php echo esc_attr( $active_item['cta']['schedule'] ?? '' ); ?>"><?php esc_html_e( 'Schedule', 'yacht-selector' ); ?></button>
					</div>
				</div>

			</div><!-- /main-card -->

			<?php if ( empty( $items ) ) : ?>
				<div class="ys-empty-state ys-empty-state-inline">
					<?php esc_html_e( 'No yacht data found yet.', 'yacht-selector' ); ?>
				</div>
			<?php endif; ?>

		</div><!-- /page-inner -->
	</div><!-- /page -->
</div>
