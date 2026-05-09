# Yacht Selector Plugin — CHANGELOG

## Documentation

- Added initial user-facing help documentation structure under `docs/help/`.
- Expanded `docs/help/user-guide.md` with full end-user documentation for v1.0 frontend experience and interaction flow.
- Expanded `docs/help/admin-guide.md` with operational administration documentation for Yacht Selector v1.0.
- Expanded `docs/help/json-import-guide.md` with full JSON import documentation and practical dataset examples for Yacht Selector v1.0.
- Expanded `docs/help/contact-tools-guide.md` with complete contact routing, placeholder, and URL configuration documentation for Yacht Selector v1.0.
- Expanded `docs/help/frontend-guide.md` with frontend selector behaviour and UX documentation.
- Expanded `docs/help/troubleshooting.md` with operational troubleshooting and debugging guidance for Yacht Selector v1.0.
- Polished help docs for GitHub: consistent headers, tables of contents, terminology (**frontend selector**, **country selector**, **month selector**, **yacht cards**, **crew panel**, **contact tools**, **availability**, **booked months**), NOTE/TIP/WARNING blockquotes, navigation blocks, and cross-links across `docs/help/*.md`.
- Restructured `docs/help/README.md` into a documentation hub (welcome, reading paths, overview table, scope reminder) and clarified separation between technical contracts and user help in `docs/README.md`.

## Release Cleanup — v1.1.0 polish

- `readme.txt`: corrected shortcode in installation instructions from `[ys-selector-block]` to `[ys_yacht_selector]`.
- `templates/shortcode-ys-selector-block.php`: fixed `esc_attr_e()` → `esc_html_e()` for the `.ys-card-crew-close-button` label (button inner text must use HTML escaping, not attribute escaping).
- `assets/js/frontend.js`: removed unused variable `ENABLE_MONTH_TRACK_MOTION`; fixed `.ys-card-crew-close-button` click handler from `classList.toggle('show')` to `classList.remove('show')` (close button must always close, never re-open).
- `assets/css/frontend.css`: merged split `.ys-months-track` rule blocks into one with a clarifying comment.
- `includes/class-ys-meta-boxes.php`: removed trailing space from one `class="ys-admin-card-header "` attribute.

---

## v1.1.0 — Month Rail & Slider Interaction Overhaul

### Month Rail — Infinite Scroll Architecture
- Replaced single-track month rail with a 3-track cloned layout (pre / original / post)
- Implemented `normalizeMonthRailScroll()`: invisible instant re-centre into the canonical track-2 zone using `getBoundingClientRect`-based positioning
- Threshold covers full mask width so first/last month centering never triggers a spurious jump
- `is-normalizing-scroll` class added to mask before every direct `scrollLeft` assignment, forcing `scroll-behavior: auto !important` and cancelling any in-flight CSS smooth scroll
- Scroll handler normalization runs synchronously (before browser paint) to prevent clone content rendering even for a single frame
- Re-entry guard (`isNormalizing` flag) prevents recursive normalization cycles

### Month Rail — Drag & Momentum Scrolling
- Replaced cumulative `startScrollLeft − deltaX` formula with per-frame relative deltas (`scrollLeft -= (clientX − lastX)`) — immune to normalization jumps mid-drag
- Pointer-event–based drag: `pointerdown` / `pointermove` / `pointerup` / `pointercancel` / `lostpointercapture`
- Drag threshold (6 px horizontal + horizontal-intent check) before pointer capture to preserve click events
- `suppressMonthClickOnce` flag prevents synthetic click firing after drag release
- Velocity sampling over a 100 ms rolling window (`velSamples` array of `{t, x}` pairs)
- Friction-decay momentum loop (`FRICTION = 0.94` per 60 fps frame, time-normalised)

### Month Rail — Snap System
- `animateScrollToCenter()`: shared easeOutCubic RAF animation for both drag-snap and click-centre
- Direction-aware snap via `findSnapTarget(direction)`: searches all three tracks using visual centre positions only — never compares calendar month numbers
  - `direction > 0` (content moving left): picks first item at or right of mask centre
  - `direction < 0` (content moving right): picks first item at or left of mask centre
  - `direction === 0` (slow/tap): picks nearest item
  - ±1 px slack prevents wrong-side snaps at exact centre
- `is-snap-animating` class suspends scroll-handler normalization during the snap animation, allowing clone-track targets (e.g. track-3 January after December) to animate without being yanked back mid-flight
- Post-animation cross-track correction in `snapToItem`: detects clone target via `data-track-role`, computes exact `getBoundingClientRect` delta between target track and track-2, applies instant `scrollLeft` correction — prevents the 12-month backward smooth scroll that previously occurred when the animation ended inside a clone's "safe zone"
- `resolveToTrack2()` ensures state update always uses the canonical track-2 element after correction
- Click-to-centre uses `animateScrollToCenter()` with normalization first, always resolving to the track-2 canonical copy

### Month Rail — Year Boundary Fix
- December → January and January → December swipes now advance correctly
- Direction filter is purely visual (DOM centre vs mask centre): no calendar arithmetic
- Clone tracks provide the year-boundary continuity; normalization restores canonical state invisibly after snap completes

### Card Slider — Side Navigation
- Clicking any visible side card triggers `goToRelativeCard(±1)` — identical to Prev/Next buttons
- Active card clicks no-op correctly
- `is-pos-1` through `is-pos-5` and `is-neg-1` through `is-neg-5` position classes
- Side cards show `cursor: pointer` via `.is-bg-card`

### Card Slider — Extended Window (5 cards)
- Visible side-card window expanded from 3 to 5 per side
- `applySliderWindowState()` assigns position classes up to `is-pos-5` / `is-neg-5`
- Progressive CSS layering: z-index, opacity, saturate filter, translateX and scale transforms for `is-pos-4/5` / `is-neg-4/5`
- Matching image height rules for outer cards in both base and responsive breakpoints

### Card Slider — Right-Bias Distribution
- When total visible slots is odd, the extra slot is assigned to the right side
- `leftCount = Math.floor(visibleSideSlots / 2)`, `rightCount = Math.ceil(visibleSideSlots / 2)`
- Forward-distance cards with `forwardDistance <= rightCount` get priority with a tie-breaker bias to the right

### Live Yacht Counter
- `.ys-slider-counter` element in `.ys-card-nav-container` between Prev/Next buttons
- Shows `current / total` (1-based, zero-padded) from filtered visible cards
- `updateSliderCounter(scope)` called at end of `applySliderWindowState()`
- `aria-live="polite"` and `aria-atomic="true"` for screen reader announcements
- Fades in on `.ys-cards-container:hover`; `font-variant-numeric: tabular-nums` for stable digit width

### CSS
- `.ys-months-mask.is-normalizing-scroll { scroll-behavior: auto !important }` — cancels in-flight smooth scroll during normalization
- `.ys-months-mask.is-snap-animating` — marker class used by JS scroll handler to suspend normalization during snap animation
- `.ys-month.active, .ys-month.selected` pointer-events restored to `auto`
- Side-card CSS for positions 4 and 5 added

---

## v1.0.5 — Frontend Interaction Stabilization

### AJAX & State Management
- Stabilized AJAX country dataset reload flow; prevented stale card sets persisting after filter changes
- Fixed selected card resolution after AJAX response: active card re-evaluated from returned dataset order
- Prevented double-initialization of drag and scroll handlers on AJAX-refreshed containers

### Card Slider
- Improved slider/card state synchronization after filter changes (country + month combined)
- Fixed edge case where `selected` class remained on a card hidden by the location filter
- Corrected `applySliderWindowState()` re-run timing after dataset reload
- Improved `getVisibleCards()` to exclude both `location-hide` and `booked-hide` cards consistently

### Month & Availability Filtering
- Month filter correctly recomputes booked/available state after country change
- Availability class (`booked`, `booked-hide`) correctly cleared and reapplied on month selection change
- Edge case fixed: months with no availability data no longer incorrectly mark cards as booked

### Crew Panel
- Crew panel interaction improvements: panel visibility tied correctly to card selected state
- Crew online/offline class applied reliably on initial render and after card transitions

### Tool Cards
- Improved runtime behavior of `.ys-tool-card` click delegation
- Tool card `href="#"` links excluded from global anchor `preventDefault` handler
- Fixed tool enable/disable state not reflecting correctly after card change

### General
- Frontend state cleanup: transient JS state variables reset on re-init
- Improved selected-state consistency across page load, filter change, and AJAX reload cycles

---

## v1.0.4 — Crew & Contact System Expansion

### Dynamic Contact Tools Architecture
- Contact tools defined as a configurable taxonomy, not hardcoded elements
- Each tool carries: identifier/slug, online-sensitivity flag, default URL, display label
- Tools rendered dynamically from the dataset — adding a new tool requires no template changes

### Crew-Specific Tool URL Overrides
- Crew members can assign a per-tool URL that overrides the tool's default
- URL resolution priority: crew-specific URL → tool default URL → no action
- Resolution runs at data-provider level; frontend receives the final resolved URL via data attributes

### Online / Offline Crew State
- Crew online/offline status evaluated at render time
- Online-sensitive tools disabled when crew is offline; non-sensitive tools always active
- CSS classes (`crew-online`) and data attributes (`data-crew-online`) applied for JS and styling hooks

### Placeholder Replacement System
- Dynamic URL placeholders (e.g. yacht name, crew name) resolved before output
- Placeholder map extensible without core changes

### Multi-Crew Selector Support
- Yachts supporting multiple crew members render a crew selector UI
- Selected crew drives the contact tool URLs shown in the panel
- Crew switch re-evaluates tool availability and URLs without page reload

### Contact Tool Rendering
- Tool cards rendered with resolved URL, label, and online-state classes
- Non-available tools visually suppressed; DOM structure preserved for JS hooks
- Improved consistency between tool card render and AJAX-refreshed states

---

## v1.0.3 — Import & Normalization Milestone

### JSON Import System
- Bulk yacht data import via structured JSON file upload in admin
- Import pipeline: parse → validate → normalize → insert/update posts and meta
- Existing posts matched by unique identifier; re-import is non-destructive (update, not duplicate)

### Taxonomy Auto-Creation
- Country, port, model, and feature taxonomies created on-the-fly during import if terms do not exist
- Term slugs normalized for consistency (lowercase, trimmed, special-character safe)

### Country & Port Normalization
- Incoming country and port strings mapped to canonical forms before taxonomy assignment
- Handles common variations in source data (case, punctuation, abbreviations)

### Decimal-Safe Import Handling
- Numeric fields (price, length, capacity, etc.) parsed with locale-aware decimal handling
- Prevents corrupt meta values from mixed locale source files

### Model & Feature Auto-Add
- Yacht model and feature terms created automatically if absent; never fail silently
- Features stored as a taxonomy for consistent cross-yacht filtering

### Validation & Resilience
- Required fields validated before insert; malformed records skipped with logged reason
- Partial imports do not roll back valid records
- Import result summary (inserted / updated / skipped counts) returned to admin UI

---

## v1.0.2 — Admin Architecture Expansion

### Yacht Meta Management
- Full meta box system for yacht CPT: dimensions, capacity, pricing, availability months, location
- Meta fields use sanitized save/load with type coercion
- Availability stored as a serialized month array for efficient filtering at render time

### Frontend Text Settings
- Admin settings page exposes all user-facing label strings (button text, status labels, overlay text)
- Strings retrieved via `YS_Settings` at render time; no hardcoded UI copy in templates

### Model & Feature Management
- Dedicated admin UI for managing yacht model taxonomy terms
- Feature term management with display-order support
- Bulk assignment UI for applying features across multiple yachts

### Crew Assignment System
- Crew members assignable to yachts via meta box with search-and-select UI
- Multiple crew supported per yacht; primary crew designation available
- Crew meta: name, role, photo, contact tool URLs, online hours

### Tool Management System
- Admin screen for defining and configuring contact tools
- Per-tool settings: label, slug, default URL, online-sensitivity toggle
- Tool ordering controls for consistent frontend render sequence

### Admin UI
- Grouped meta box layout for yacht edit screen (dimensions group, availability group, crew group)
- Dynamic field rendering: fields appear/hide based on related field values
- Inline help text on complex fields

---

## v1.0.1 — Initial Selector Frontend System

### Shortcode Rendering
- `[ys-selector-block]` shortcode renders the full yacht selector interface
- Template-based output via `shortcode-ys-selector-block.php`
- `YS_Data_Provider` normalizes post/meta data into a clean frontend dataset before render

### Country Filter Rail
- Horizontal scrollable country list rendered from dataset
- Click selects a country; cards filtered by `location-hide` class toggle
- Active country state tracked via JS; "All" option clears filter

### Month Selector Rail
- 12-month horizontal rail with density bars and label overlay
- Active month drives card availability state (`booked` / `booked-hide` classes)
- Selected month highlighted; overlay shows label, density percentage, and bar fill
- Initial month auto-selected from current calendar month or dataset active flag

### Yacht Card Slider
- Horizontal card slider with Prev/Next navigation
- `applySliderWindowState()` manages position classes (`is-pos-1`, `is-neg-1`, `is-pos-2`, `is-neg-2`, `is-pos-3`, `is-neg-3`) for depth layering
- Cards hidden by country or availability filters excluded from visible window
- `getVisibleCards()` respects combined filter state

### Crew Panel Structure
- Crew panel per card: photo, name, role, online status, contact tools
- Tool cards rendered with resolved URL and state classes
- Panel visibility tied to card selected state

### Availability-Aware Card States
- Cards marked `booked` / `booked-hide` when selected month is in their booked months list
- Cards marked `location-hide` when they do not match the active country filter
- Combined filter: card visible only if it passes both country and month checks

### Priority-Based Ordering
- Yachts sorted by priority meta field at data-provider level
- Secondary sort by post title for deterministic ordering when priorities are equal

### Responsive & Frontend Structure
- CSS custom properties for slider step, card dimensions, and month rail spacing
- Responsive breakpoints for card image heights and rail layout
- Vanilla JS only; no external framework dependencies

---

## v1.0.0 — Project Foundation

### Plugin Bootstrap
- Standard WordPress plugin header and activation/deactivation hooks
- `YS_Plugin` orchestrator class wires all service classes at `plugins_loaded`
- Constants: `YS_PLUGIN_PATH`, `YS_PLUGIN_URL`, `YS_PLUGIN_VERSION`
- Clean `uninstall.php` for data removal on plugin deletion

### Custom Post Type Architecture
- `yacht` CPT registered via `YS_Post_Types`: public, supports title/thumbnail/editor, custom rewrite slug
- Admin columns for key yacht meta fields
- CPT designed for extension without schema changes

### Location Taxonomy Structure
- `ys_location` taxonomy for country/port hierarchical classification
- Taxonomy registered via `YS_Taxonomy`; slug and label configurable via settings
- Terms structured for both filtering (country) and display (port/region)

### Frontend / Backend Separation
- All frontend logic isolated in `assets/js/frontend.js` and `assets/css/frontend.css`
- PHP renders structural HTML with data attributes only; no inline logic in templates
- Assets enqueued conditionally (only when shortcode is present)

### Templates / Assets / Docs Structure
- `/templates` — PHP shortcode templates
- `/includes` — modular PHP service classes
- `/assets/js` — vanilla JS frontend interaction
- `/assets/css` — frontend stylesheet
- `/docs` — project overview and contract documentation

### Foundational Contracts
- `YS_Settings` service: single source for all configurable values
- `YS_Data_Provider`: normalizes raw post/meta into a typed, sanitized dataset array
- `YS_Shortcode`: registers and renders shortcode using data provider output
