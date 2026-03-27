<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$assets_url      = YS_PLUGIN_URL . 'templates/assets/';
$logo_url        = $assets_url . 'logo.svg';
$greece_flag_url = $assets_url . 'flags/greece.png';
$turkey_flag_url = $assets_url . 'flags/turkey.png';
$croatia_flag_url = $assets_url . 'flags/croatia.png';
$hero_image_url  = $assets_url . 'yachts/dea-del-mare.jpg';
?>

<canvas id="bgCanvas"></canvas>

<div class="page">
<div class="page-inner">

	<div class="logo">
		<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr__( 'Naviga Yachting', 'yacht-selector' ); ?>">
	</div>

	<div class="yacht-picker-wrapper" id="yachtPickerWrapper">
		<div class="yacht-picker-track" id="yachtPickerTrack"></div>
	</div>

	<div class="country-watermark" id="countryWatermark"></div>

	<div class="main-card">

		<div class="yacht-visual" id="yachtVisual">
			<img class="yacht-visual-img" id="yachtImgA" src="<?php echo esc_url( $hero_image_url ); ?>" alt="<?php echo esc_attr__( 'Dea Del Mare', 'yacht-selector' ); ?>">
			<img class="yacht-visual-img" id="yachtImgB" src="" alt="" style="opacity:0">
			<canvas id="transitionCanvas" style="position:absolute;top:0;left:0;width:100%;height:100%;z-index:2;pointer-events:none;opacity:0;border-radius:40px 40px 0 0;"></canvas>

			<div class="frosted-header">
				<div class="country-tabs" id="countryTabs">
					<button class="country-tab active" data-country="all">All</button>
					<button class="country-tab" data-country="greece">
						<img class="flag-icon" src="<?php echo esc_url( $greece_flag_url ); ?>" alt=""> Greece
					</button>
					<button class="country-tab" data-country="turkey">
						<img class="flag-icon" src="<?php echo esc_url( $turkey_flag_url ); ?>" alt=""> Turkey
					</button>
					<button class="country-tab" data-country="croatia">
						<img class="flag-icon" src="<?php echo esc_url( $croatia_flag_url ); ?>" alt=""> Croatia
					</button>
				</div>

				<div class="picker-wrapper" id="monthPicker">
					<div class="center-overlay">
						<div class="center-month" id="centerMonth">July</div>
						<div class="center-bar-track"><div class="center-bar-fill" id="centerBar" style="width:95%;background:#ff0000;"></div></div>
						<div class="center-pct-row">
							<span class="center-pct" id="centerPct" style="color:#ff0000;">95%</span>
							<span class="center-pct-label" id="centerPctLabel" style="color:#ff0000;">Full</span>
						</div>
					</div>
					<div class="picker-track"></div>
				</div>
			</div>

			<div class="yacht-name-area">
				<div class="yacht-name-row">
					<div class="yacht-name-line"></div>
					<div class="yacht-name-text" id="yachtName">Dea Del Mare</div>
					<div class="yacht-name-line"></div>
				</div>
			</div>

			<button class="yacht-nav yacht-nav-left" id="yachtNavLeft" aria-label="<?php echo esc_attr__( 'Previous yacht', 'yacht-selector' ); ?>">
				<svg viewBox="0 0 24 24" fill="none"><path d="M15 6L9 12L15 18" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</button>
			<button class="yacht-nav yacht-nav-right" id="yachtNavRight" aria-label="<?php echo esc_attr__( 'Next yacht', 'yacht-selector' ); ?>">
				<svg viewBox="0 0 24 24" fill="none"><path d="M9 6L15 12L9 18" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</button>
		</div>

		<div class="dashboard">
			<div class="specs-left" id="specsLeft">
				<div class="spec-row"><span class="spec-label">Model</span><span class="spec-value">Gulet</span></div>
				<div class="spec-row"><span class="spec-label">Ölçüler</span><span class="spec-value">28m x 6m</span></div>
				<div class="spec-row"><span class="spec-label">Motor</span><span class="spec-value">2x280hp</span></div>
				<div class="spec-row"><span class="spec-label">Üretim /R</span><span class="spec-value">2009 /21</span></div>
				<div class="spec-row"><span class="spec-label">Liman</span><span class="spec-value">Bodrum</span></div>
			</div>
			<div class="specs-center" id="specsCenter">
				<div class="stat-block"><div class="stat-number">5</div><div class="stat-label">Kabin</div></div>
				<div class="stat-block"><div class="stat-number">10</div><div class="stat-label">Misafir</div></div>
				<div class="stat-block"><div class="stat-number">4</div><div class="stat-label">Mürettebat</div></div>
			</div>
			<div class="specs-right" id="specsRight">
				<div class="amenity">İnternet, TV</div>
				<div class="amenity">Bot (Joker 90hp)</div>
				<div class="amenity">Minder, Duş</div>
				<div class="amenity">Knee, Kano, Jetski</div>
				<div class="amenity more">+2 Fazlası</div>
			</div>
		</div>

		<div class="cta-area">
			<div class="cta-bar">
				<button class="cta-section">Watch Video</button>
				<div class="cta-divider"></div>
				<button class="cta-section cta-primary">Video Call Now</button>
				<div class="cta-divider"></div>
				<button class="cta-section">Schedule</button>
			</div>
		</div>

	</div>

</div>
</div>
