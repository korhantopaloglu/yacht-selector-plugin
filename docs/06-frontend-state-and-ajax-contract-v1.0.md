# Frontend State & AJAX Contract

Defines frontend logic, state, and interactions.

## Document Purpose

This document defines the shortcode output structure and template contract for Yacht Selector.

It locks:
- shortcode responsibility
- base wrapper structure
- frontend template sections
- card markup expectations
- crew panel markup expectations
- tool card markup expectations
- required classes
- required data attributes

This document is the source of truth for HTML structure.

---

## 1. Shortcode Entry

Primary shortcode:
[ys_yacht_selector]

Responsibilities:
- enqueue required frontend assets
- read normalized yacht dataset
- prepare frontend state payload
- render selector wrapper
- render country filter
- render month filter
- render yacht cards
- render helper data/hooks needed by JS

Shortcode must not contain heavy business logic.
Data must be prepared before final template output.

---

## 2. Render Strategy

v1.0 render strategy:

- full dataset is prepared server-side
- frontend receives normalized data
- base HTML is rendered by PHP
- JS controls interaction after render

Rules:
- avoid mixing PHP business logic deeply into templates
- templates should focus on structure
- templates should expose predictable hooks for JS and CSS

---

## 3. Main Frontend Wrapper

The whole frontend selector should render inside a single main wrapper.

Recommended root class:
- ys-selector

Optional inner wrapper:
- ys-selector-inner

This root wrapper is the frontend scope boundary.

All JS and CSS should stay scoped inside this selector system.

---

## 4. Main Template Sections

The selector frontend is composed of these main areas:

1. countries area
2. months area
3. cards area
4. optional status / summary area
5. optional empty state area

These areas should be rendered in a predictable order.

---

## 5. Country Filter Container

Required container:
- ys-countries-container

Rules:
- horizontal structure
- contains country items derived from dataset
- includes default "All" option
- selected state handled by JS
- no hardcoded country list

Each item should expose:
- data-country
- visible label

Recommended item class:
- ys-country-item

Selected item receives:
- selected

---

## 6. Month Filter Container

Required container:
- ys-months-container

Rules:
- month items are generated dynamically
- starts from current month
- covers next 12 months
- selected state handled by JS

Each item should expose:
- data-month = YYYY-MM
- visible label

Recommended item class:
- ys-month-item

Selected month receives:
- selected

Optional sub-elements:
- month label
- density / percentage
- bar / fill structure

These sub-elements are allowed but must remain structurally simple.

---

## 7. Cards Container

Required container:
- ys-cards-container

Purpose:
- contains all yacht card items for current render
- acts as the main frontend item list / slider area

Rules:
- cards are ordered according to normalized dataset order
- cards must expose stable identifiers
- selected card state is controlled by JS

---

## 8. Yacht Card Wrapper

Each yacht card must have:

Base class:
- ys-card

State class:
- available OR unavailable

Optional selected class:
- selected

Recommended attributes:
- data-id
- data-country
- data-availability
- data-priority

Rules:
- every card must be individually targetable by JS
- one card may be selected at a time
- card markup must remain consistent across all items

---

## 9. Yacht Card Content Order

Each yacht card should follow this structural order:

1. media area
2. primary identity area
3. data groups
4. CTA area
5. crew panel area

This order must remain stable.

---

## 10. Media Area

Recommended wrapper:
- ys-card-media

Purpose:
- holds card image
- supports image fallback logic

Rules:
- one visible main image area
- safe fallback if image missing
- must not break layout when empty

---

## 11. Identity Area

Recommended wrapper:
- ys-card-identity

Typical content:
- yacht title
- short description or excerpt
- optional location summary

Rules:
- title must always be visible if available
- description may be optional
- this area should be the main textual identity of the card

---

## 12. Specifications Group

Required wrapper:
- ys-card-specifications-group

Purpose:
- technical and structural yacht information

Typical fields:
- model
- dimensions
- engine
- build / refit
- port

Recommended item structure:
- ys-card-spec-item
- ys-card-spec-label
- ys-card-spec-value

Rules:
- compact and scannable
- grouped consistently

---

## 13. Capacity Group

Required wrapper:
- ys-card-capacity-group

Purpose:
- hospitality / capacity information

Typical fields:
- cabins
- guests
- crew

Recommended item structure:
- ys-card-capacity-item
- ys-card-capacity-label
- ys-card-capacity-value

Rules:
- visually parallel to specifications group
- should remain compact

---

## 14. Features Group

Required wrapper:
- ys-card-features-group

Purpose:
- extra features list

Recommended list structure:
- ys-card-features-list
- ys-card-feature-item

Rules:
- list should be lightweight
- values should come from normalized features array
- missing features should not break the group

---

## 15. CTA Area

Required wrapper:
- ys-card-actions

Buttons:
- Watch Video
- Call the Crew
- Book Now

Recommended button classes:
- ys-watch-video-button
- ys-call-crew-button
- ys-book-now-button

Rules:
- Watch Video goes to permalink
- Call the Crew opens crew panel
- Book Now opens crew panel
- button text may come from settings where appropriate
- button classes must remain stable for JS binding

---

## 16. Crew Panel Wrapper

Required wrapper:
- ys-card-crew-group

Purpose:
- expandable contact / crew interaction layer

Rules:
- hidden by default
- opened by CTA actions
- belongs to card
- closed via toggle or close button
- should be structurally included in each card

Optional runtime state class:
- is-open

---

## 17. Crew Panel Header

The crew panel should support these text blocks:

- top title
- title
- subtitle

Recommended classes:
- ys-card-crew-top-title
- ys-card-crew-title
- ys-card-crew-subtitle

Rules:
- values may come from admin text settings
- should render even if some sections are empty
- structure must remain stable

---

## 18. Crew Status Area

Required wrapper:
- ys-card-crew-status-group

Purpose:
- shows online/offline state and current crew identity

Recommended sub-elements:
- ys-card-crew-status-icon
- ys-card-crew-status-text
- ys-card-crew-member-single
- ys-card-crew-member-select

Rules:
- must support one or multiple crew
- if one crew exists, plain display is enough
- if multiple crew exist, select input must be available
- runtime online state must support:
  - class crew-online
  - data-crew-online = 1 or 0

---

## 19. Multi-Crew Selector

If multiple crew are assigned:

Recommended element:
- select dropdown

Recommended class:
- ys-card-crew-member-select

Rules:
- must expose active crew choice for JS
- changing it updates:
  - online state
  - tool URLs
  - tool availability

---

## 20. Contact Tools Container

Required wrapper:
- ys-card-contact-tools-container

Purpose:
- contains all rendered contact tool cards

Rules:
- tools must be rendered dynamically from tool definitions
- tool markup must not be hardcoded to a fixed list
- all tool cards must be individually targetable

---

## 21. Tool Card Structure

Recommended tool card class:
- ys-tool-card

Recommended sub-elements:
- ys-tool-card-icon
- ys-tool-card-title
- ys-tool-card-description

Recommended data attributes:
- data-tool-id
- data-tool-slug
- data-online-sensitive
- data-default-url

Rules:
- tool identifiers must be stable
- tool labels alone must not be the primary matching key
- disabled state should be representable by class

Optional runtime state class:
- disabled

---

## 22. Tool URL Hook Requirements

Each tool card must be able to support runtime URL resolution.

This means the DOM must expose enough information for JS to:
- identify the tool
- identify the active crew
- resolve crew-specific URL
- fallback to default URL
- replace placeholders before open

Templates should not hardcode the final URL blindly when runtime replacement is required.

---

## 23. Placeholder Support in Templates

Templates must support the placeholder model indirectly.

They do not need to resolve placeholders themselves.

But they must expose the values needed by JS.

Minimum runtime placeholders:
- post_id
- yacht_id
- permalink

Recommended card-level attributes or JS payload hooks:
- data-post-id
- data-yacht-id
- data-permalink

These may exist on card wrapper or be injected in a global state object.

---

## 24. Close Button

Crew panel should include a close control.

Recommended class:
- ys-card-crew-close

Rules:
- visible inside crew panel
- closes current card panel only
- should be easy for JS to bind

---

## 25. Empty State

If dataset is empty:

Required wrapper:
- ys-empty-state

Rules:
- must render safely
- must not break wrapper structure
- text may come from settings

---

## 26. Data Injection Contract

Templates must receive already prepared normalized data.

Required normalized concepts include:
- id
- title
- permalink
- image
- country
- port
- booked
- priority
- model
- length
- beam
- engine
- build/refit
- cabins
- guests
- crew count
- features
- CTA hooks
- crew list
- contact tools
- tool defaults

Templates must not be responsible for deriving all of this from raw WP data.

---

## 27. Single Source of Truth Rule

The template structure is a stable HTML contract.

JS and CSS depend on:
- class names
- data attributes
- wrapper hierarchy

Therefore:
- markup changes must be deliberate
- classes and hooks must not be renamed casually
- structure should remain predictable

---

## 28. Reference HTML Alignment Rule

The selector template may stay visually close to the existing static reference prototype.

However:
- template structure must follow Yacht Selector contracts
- old prototype assumptions must not override current system behavior
- structure should favor maintainability and JS compatibility

---

## 29. Summary

The shortcode and template contract is built around:

- one shortcode entry
- one normalized dataset
- one stable wrapper hierarchy
- predictable card markup
- predictable crew panel markup
- dynamic tool card rendering
- stable JS/CSS hooks
- support for runtime URL resolution and placeholders

All frontend implementation must follow this structure.