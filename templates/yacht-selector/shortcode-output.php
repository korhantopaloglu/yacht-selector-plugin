<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$template_assets_url = YS_PLUGIN_URL . 'templates/assets/';
?>

<!-- Three.js background — dot wave -->
<canvas id="bgCanvas"></canvas>
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>

<div class="page">
<div class="page-inner">

  <!-- LOGO -->
  <div class="logo">
    <img src="<?php echo esc_url( $template_assets_url . 'logo.svg' ); ?>" alt="Naviga Yachting">
  </div>

  <!-- YACHT PICKER — horizontal drag/snap strip behind the card -->
  <div class="yacht-picker-wrapper" id="yachtPickerWrapper">
    <div class="yacht-picker-track" id="yachtPickerTrack"></div>
  </div>

  <!-- COUNTRY WATERMARK -->
  <div class="country-watermark" id="countryWatermark"></div>

  <!-- ═══ MAIN CARD ═══ -->
  <div class="main-card">

    <!-- Yacht Image Area -->
    <div class="yacht-visual" id="yachtVisual">
      <img class="yacht-visual-img" id="yachtImgA" src="" alt="">
      <img class="yacht-visual-img" id="yachtImgB" src="" alt="">
      <canvas id="transitionCanvas"></canvas>

      <!-- Frosted Header: Tabs + Picker (180px) -->
      <div class="frosted-header">
        <div class="country-tabs" id="countryTabs">
          <button class="country-tab active" data-country="all">All</button>
          <button class="country-tab" data-country="greece">
            <img class="flag-icon" src="<?php echo esc_url( $template_assets_url . 'flags/greece.png' ); ?>" alt=""> Greece
          </button>
          <button class="country-tab" data-country="turkey">
            <img class="flag-icon" src="<?php echo esc_url( $template_assets_url . 'flags/turkey.png' ); ?>" alt=""> Turkey
          </button>
          <button class="country-tab" data-country="croatia">
            <img class="flag-icon" src="<?php echo esc_url( $template_assets_url . 'flags/croatia.png' ); ?>" alt=""> Croatia
          </button>
        </div>

        <div class="picker-wrapper" id="monthPicker">
          <div class="center-overlay">
            <div class="center-month" id="centerMonth">July</div>
            <div class="center-bar-track"><div class="center-bar-fill" id="centerBar"></div></div>
            <div class="center-pct-row">
              <span class="center-pct" id="centerPct">95%</span>
              <span class="center-pct-label" id="centerPctLabel">Full</span>
            </div>
          </div>
          <div class="picker-track"></div>
        </div>
      </div>

      <!-- Yacht Name Overlay -->
      <div class="yacht-name-area">
        <div class="yacht-name-row">
          <div class="yacht-name-line"></div>
          <div class="yacht-name-text" id="yachtName">Dea Del Mare</div>
          <div class="yacht-name-line"></div>
        </div>
      </div>

      <!-- Navigation Arrows -->
      <button class="yacht-nav yacht-nav-left" id="yachtNavLeft" aria-label="Previous yacht">
        <svg viewBox="0 0 24 24" fill="none"><path d="M15 6L9 12L15 18" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </button>
      <button class="yacht-nav yacht-nav-right" id="yachtNavRight" aria-label="Next yacht">
        <svg viewBox="0 0 24 24" fill="none"><path d="M9 6L15 12L9 18" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </button>
    </div>

    <!-- Dashboard -->
    <div class="dashboard">
      <div class="specs-left" id="specsLeft"></div>
      <div class="specs-center" id="specsCenter"></div>
      <div class="specs-right" id="specsRight"></div>
    </div>

    <!-- CTA -->
    <div class="cta-area">
      <div class="cta-bar">
        <button class="cta-section">Watch Video</button>
        <div class="cta-divider"></div>
        <button class="cta-section cta-primary">Video Call Now</button>
        <div class="cta-divider"></div>
        <button class="cta-section">Schedule</button>
      </div>
    </div>

  </div><!-- /main-card -->

</div><!-- /page-inner -->
</div><!-- /page -->
