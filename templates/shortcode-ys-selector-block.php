<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$items          = is_array( $items ?? null ) ? $items : [];
$countries      = is_array( $countries ?? null ) ? $countries : [];
$months         = is_array( $months ?? null ) ? $months : [];
$container_id   = isset( $container_id ) ? (string) $container_id : '';
$watch_text     = isset( $watch_text ) ? (string) $watch_text : 'Watch Me';
$book_text      = isset( $book_text ) ? (string) $book_text : 'Book Now';
$all_label_text = isset( $all_label_text ) ? (string) $all_label_text : 'All';
$empty_text     = isset( $empty_text ) ? (string) $empty_text : 'No results found';
?>
<div id="<?php echo esc_attr( $container_id ); ?>" class="ys-selector-block-container">
	<div class="ys-countries-container">
		<a href="#" class="active" data-country="all">
			<span class="ys-country-option__flag-wrap">
				<span class="ys-country-option__flag ys-country-option__flag--placeholder" aria-hidden="true"></span>
			</span>
			<span class="ys-country-option__label"><?php echo esc_html( $all_label_text ); ?></span>
		</a>
		<?php foreach ( $countries as $country ) : ?>
			<?php $flag_url = isset( $country['flag'] ) ? (string) $country['flag'] : ''; ?>
			<a href="#" data-country="<?php echo esc_attr( $country['slug'] ?? '' ); ?>">
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

	<div class="ys-months-container">
		<?php foreach ( $months as $month ) : ?>
			<a href="#" class="ys-month<?php echo ! empty( $month['active'] ) ? ' active' : ''; ?>" data-month="<?php echo esc_attr( $month['month'] ?? '' ); ?>">
				<span class="ys-month-label"><?php echo esc_html( $month['label'] ?? '' ); ?></span>
				<span class="ys-month-density"><?php echo esc_html( (string) ( $month['density'] ?? 0 ) ); ?>%</span>
				<span class="ys-month-bar">
					<span class="ys-month-bar-fill" style="width: <?php echo esc_attr( (string) ( $month['density'] ?? 0 ) ); ?>%;"></span>
				</span>
			</a>
		<?php endforeach; ?>
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
				$features      = isset( $item['features'] ) && is_array( $item['features'] ) ? array_values( array_filter( $item['features'], 'is_scalar' ) ) : [];
				$priority      = isset( $item['priority'] ) ? (int) $item['priority'] : 0;
				$watch_url     = isset( $item['cta']['watch_video'] ) ? (string) $item['cta']['watch_video'] : '';
				$book_url      = isset( $item['cta']['schedule'] ) ? (string) $item['cta']['schedule'] : '';
				$card_classes  = 'ys-card' . ( 0 === $index ? ' selected' : '' );
				?>
				<article
					class="<?php echo esc_attr( $card_classes ); ?>"
					data-location="<?php echo esc_attr( $location_slug ); ?>"
					data-priority="<?php echo esc_attr( (string) $priority ); ?>"
					data-booked="<?php echo esc_attr( implode( ',', $booked_values ) ); ?>"
					data-months="<?php echo esc_attr( implode( ',', $month_numbers ) ); ?>"
					data-card-index="<?php echo esc_attr( (string) ( $index + 1 ) ); ?>"
				>
					<div class="ys-card-image-container">
						<?php if ( '' !== $image_url ) : ?>
							<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $title ); ?>">
						<?php endif; ?>
					</div>

					<div class="ys-card-content-container">
						<h3><?php echo esc_html( $title ); ?></h3>
						<?php if ( '' !== $model || '' !== $port || '' !== $country_name ) : ?>
							<p><?php echo esc_html( trim( $model . ( '' !== $model && '' !== $port ? ' • ' : '' ) . $port . ( ( '' !== $model || '' !== $port ) && '' !== $country_name ? ' • ' : '' ) . $country_name ) ); ?></p>
						<?php endif; ?>

						<div class="ys-card-meta-container">
							<?php if ( '' !== $model ) : ?>
								<span><?php echo esc_html( sprintf( __( 'Model: %s', 'yacht-selector' ), $model ) ); ?></span>
							<?php endif; ?>
							<?php if ( isset( $item['length'] ) && null !== $item['length' ] ) : ?>
								<span><?php echo esc_html( sprintf( __( 'Length: %sm', 'yacht-selector' ), $item['length'] ) ); ?></span>
							<?php endif; ?>
							<?php if ( isset( $item['beam'] ) && null !== $item['beam'] ) : ?>
								<span><?php echo esc_html( sprintf( __( 'Beam: %sm', 'yacht-selector' ), $item['beam'] ) ); ?></span>
							<?php endif; ?>
							<?php if ( '' !== $engine ) : ?>
								<span><?php echo esc_html( sprintf( __( 'Engine: %s', 'yacht-selector' ), $engine ) ); ?></span>
							<?php endif; ?>
							<?php if ( '' !== $build_year ) : ?>
								<span><?php echo esc_html( sprintf( __( 'Build Year: %s', 'yacht-selector' ), $build_year ) ); ?></span>
							<?php endif; ?>
							<?php if ( '' !== $refit_year ) : ?>
								<span><?php echo esc_html( sprintf( __( 'Refit Year: %s', 'yacht-selector' ), $refit_year ) ); ?></span>
							<?php endif; ?>
							<?php if ( isset( $item['guests'] ) && null !== $item['guests'] ) : ?>
								<span><?php echo esc_html( sprintf( __( 'Guests: %s', 'yacht-selector' ), (int) $item['guests'] ) ); ?></span>
							<?php endif; ?>
							<?php if ( isset( $item['cabins'] ) && null !== $item['cabins'] ) : ?>
								<span><?php echo esc_html( sprintf( __( 'Cabins: %s', 'yacht-selector' ), (int) $item['cabins'] ) ); ?></span>
							<?php endif; ?>
							<?php if ( '' !== $crew ) : ?>
								<span><?php echo esc_html( sprintf( __( 'Crew: %s', 'yacht-selector' ), $crew ) ); ?></span>
							<?php endif; ?>
							<?php if ( '' !== $port ) : ?>
								<span><?php echo esc_html( sprintf( __( 'Port: %s', 'yacht-selector' ), $port ) ); ?></span>
							<?php endif; ?>
							<?php if ( '' !== $country_name ) : ?>
								<span><?php echo esc_html( sprintf( __( 'Country: %s', 'yacht-selector' ), $country_name ) ); ?></span>
							<?php endif; ?>
							<?php if ( ! empty( $booked_values ) ) : ?>
								<span><?php echo esc_html( sprintf( __( 'Booked: %s', 'yacht-selector' ), implode( ', ', $booked_values ) ) ); ?></span>
							<?php endif; ?>
							<?php if ( 0 !== $priority ) : ?>
								<span><?php echo esc_html( sprintf( __( 'Priority: %s', 'yacht-selector' ), $priority ) ); ?></span>
							<?php endif; ?>
						</div>

						<?php if ( ! empty( $features ) ) : ?>
							<div class="ys-card-features-container">
								<?php foreach ( $features as $feature ) : ?>
									<span class="ys-card-feature"><?php echo esc_html( (string) $feature ); ?></span>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>

						<div class="ys-card-actions-container">
							<button type="button" class="ys-watch-button" data-url="<?php echo esc_attr( $watch_url ); ?>"><?php echo esc_html( $watch_text ); ?></button>
							<button type="button" class="ys-book-button" data-url="<?php echo esc_attr( $book_url ); ?>"><?php echo esc_html( $book_text ); ?></button>
						</div>
					</div>
				</article>
			<?php endforeach; ?>

			<div class="ys-card-nav-container">
				<button type="button" class="ys-prev"><?php esc_html_e( 'Previous', 'yacht-selector' ); ?></button>
				<button type="button" class="ys-next"><?php esc_html_e( 'Next', 'yacht-selector' ); ?></button>
			</div>
		</div>
	<?php else : ?>
		<div class="ys-empty-state-container"><?php echo esc_html( $empty_text ); ?></div>
	<?php endif; ?>
</div>
