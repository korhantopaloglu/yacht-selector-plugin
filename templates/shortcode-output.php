<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$data_provider = new YS_Data();
$yachts = $data_provider->get_sample_data();
?>

<div class="yacht-selector">

	<h2>Yacht Selector</h2>

	<?php if ( ! empty( $yachts ) ) : ?>
		<ul>
			<?php foreach ( $yachts as $yacht ) : ?>
				<li>
					<strong><?php echo esc_html( $yacht['name'] ); ?></strong><br>
					<?php echo esc_html( $yacht['length'] ); ?> — 
					<?php echo esc_html( $yacht['price'] ); ?>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php else : ?>
		<p>No yachts found.</p>
	<?php endif; ?>

</div>