=== Yacht Selector ===
Contributors: korhan
Tags: yacht, selector, shortcode
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A shortcode-based yacht selection interface with dynamic filtering, crew contact tools, and an interactive card slider.

== Description ==

Yacht Selector renders a fully interactive yacht browsing experience via shortcode. Features include country and month filtering, an infinite-scroll month rail with momentum and direction-aware snapping, a depth-layered card slider, crew contact tools with online/offline awareness, and JSON-based bulk import.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/yacht-selector-plugin/` directory.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Use the `[ys-selector-block]` shortcode on any page or template.

== Changelog ==

= 1.1.0 =
* Month rail rebuilt as a 3-track infinite-scroll loop with invisible normalization.
* Momentum scrolling with friction-decay and velocity sampling.
* Direction-aware snap: continues forward across year boundary (December → January).
* Cross-track clone correction: instant teleport after snap prevents 12-month backwards smooth scroll.
* Drag system switched to per-frame relative deltas; immune to mid-drag normalization jumps.
* Card slider expanded to 5 visible side cards per side with right-bias distribution.
* Side cards are now clickable and trigger single-step navigation.
* Live yacht counter (current / total) added between Prev/Next buttons.
* CSS: scroll-behavior overrides, snap-animation suspend class, outer card depth styles.

= 1.0.5 =
* AJAX country dataset reload stabilized; stale card states cleared on reload.
* Selected card resolution improved after filter changes.
* Availability filter correctly reapplied after country change.
* Crew panel visibility tied reliably to card selected state.
* Tool card click delegation and enable/disable state fixed.
* Frontend state variables reset on re-initialization.

= 1.0.4 =
* Dynamic contact tools architecture: tools defined as configurable taxonomy, not hardcoded.
* Crew-specific tool URL overrides with priority resolution (crew URL → default URL → no action).
* Online/offline crew state drives tool availability at render time.
* Placeholder replacement system for dynamic URLs (yacht name, crew name, etc.).
* Multi-crew selector support: crew switch re-evaluates tools without reload.

= 1.0.3 =
* JSON bulk import pipeline: parse, validate, normalize, insert/update.
* Non-destructive re-import: existing posts updated by unique identifier.
* Taxonomy auto-creation for country, port, model, and feature terms during import.
* Decimal-safe numeric field parsing for mixed-locale source files.
* Import result summary (inserted / updated / skipped) returned to admin UI.

= 1.0.2 =
* Full meta box system for yacht CPT: dimensions, capacity, pricing, availability, location.
* Admin settings page for all user-facing label strings.
* Model and feature taxonomy management screens.
* Crew assignment meta box with multi-crew and primary crew support.
* Contact tool management screen: label, slug, default URL, online-sensitivity toggle.

= 1.0.1 =
* [ys-selector-block] shortcode renders full yacht selector via template.
* YS_Data_Provider normalizes post/meta into a typed frontend dataset.
* Country filter rail with active state and JS-driven card visibility.
* Month rail with density bars, label overlay, and auto-selected active month.
* Yacht card slider with depth-layered position classes and Prev/Next navigation.
* Availability-aware card states (booked / location-hide) with combined filter logic.
* Crew panel with photo, name, role, online status, and contact tool cards.
* Priority-based yacht ordering with deterministic title fallback.

= 1.0.0 =
* Plugin bootstrap: YS_Plugin orchestrator, activation/deactivation hooks, uninstall.
* yacht CPT registered via YS_Post_Types.
* ys_location taxonomy for country/port classification.
* YS_Settings, YS_Data_Provider, YS_Shortcode service architecture established.
* /templates, /includes, /assets, /docs directory structure defined.