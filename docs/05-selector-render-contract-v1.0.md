# Selector Render Contract

Defines HTML structure and rendering expectations.

## Document Purpose

This document defines how Yacht Selector renders data on the frontend and how the UI behaves.

It locks:
- shortcode output structure
- dataset injection
- frontend state model
- filtering logic
- availability logic
- DOM contract
- interaction rules
- UI behavior consistency

This document ensures the frontend stays aligned with the reference HTML prototype.

---

## 1. Rendering Entry Point

Frontend is rendered via shortcode.

Shortcode:
[ys_yacht_selector]

Responsibilities:
- enqueue CSS and JS
- render base HTML structure
- inject normalized dataset
- initialize frontend state

---

## 2. Rendering Strategy

v1.0 uses:

- single dataset load
- no AJAX
- client-side filtering

Rules:
- all yachts are loaded once
- filtering happens in JS
- no server calls after initial render

---

## 3. Dataset Injection

The full dataset must be injected into the page.

Recommended:
- JSON embedded in script tag

Example concept:

window.YS_DATA = [ ... ]

Rules:
- dataset must be normalized
- no raw WP structures
- all fallback logic resolved before injection

---

## 4. Initial State

On page load:

- default country selected
- default month selected
- dataset filtered by country
- first available yacht selected

If no available yacht:
- first yacht in list selected

---

## 5. State Model

Frontend must maintain:

- selectedCountry
- selectedMonth
- selectedYachtId
- activeCrewId (if crew exists)

Rules:
- state must be single source of truth
- UI must reflect state
- DOM must not diverge from state

---

## 6. Month Generation

Months are generated dynamically.

Rules:
- start from current month
- generate next 12 months
- format: YYYY-MM

Example:
- 2026-03
- 2026-04
- 2026-05
...

Display:
- localized month name + year

---

## 7. Country Filter Behavior

Country list:

- derived from dataset
- based on country slug

When country changes:

- update selectedCountry
- reset selectedYachtId
- reset selectedMonth (optional or keep)
- re-render yacht list
- select first valid yacht

---

## 8. Yacht List Behavior

List contains all yachts for selected country.

Rules:
- no hiding based on availability
- all yachts remain visible
- availability affects state, not visibility

---

## 9. Availability Logic

For each yacht:

- if selectedMonth exists in ys_booked:
  -> unavailable
- else:
  -> available

Rules:
- availability is computed on the fly
- no stored "available" flag
- always derived from booked data

---

## 10. Card State Classes

Each yacht card must have:

- ys-card
- available OR unavailable

Example:

ys-card available
ys-card unavailable

Rules:
- class must update when month changes
- class must reflect current state only

---

## 11. Card Data Attributes

Each card should include:

- data-id
- data-country
- data-availability
- data-priority (optional)

Example:

data-id = yacht id  
data-country = country slug  
data-availability = available or unavailable  

Rules:
- must be consistent
- used by JS logic
- should not be duplicated elsewhere

---

## 12. Selection Behavior

When a yacht is selected:

- update selectedYachtId
- highlight active card
- update detail panel
- update crew panel

Rules:
- only one active yacht at a time
- selection must be visible in UI

---

## 13. Crew Panel Behavior

When "Call the Crew" or "Book Now" is clicked:

- open crew panel
- show crew members

If no crew:
- show fallback message

If multiple crew:
- allow selection
- update activeCrewId

---

## 14. Crew Online State

Based on runtime evaluation:

If crew is online:
- add class crew-online
- data-crew-online = 1

If offline:
- remove class
- data-crew-online = 0

Rules:
- must update when active crew changes
- must affect tool availability

---

## 15. Contact Tools Rendering

Tools must be rendered dynamically.

Rules:
- do not hardcode tools
- render from tool definitions
- match using tool identifier

Each tool:
- icon or image
- label
- click behavior

---

## 16. Tool Click Behavior

On click:

1. resolve active crew
2. resolve tool identifier
3. resolve URL

Priority:
1. crew tool URL
2. tool default URL
3. no action

---

## 17. Placeholder Replacement

Before redirect:

Replace placeholders in URL.

Supported:
- {post_id}
- {yacht_id}
- {permalink}

Rules:
- replacement must happen at click time
- must use active yacht context

---

## 18. Full Percentage Calculation

For selected country and month:

total = number of yachts  
available = yachts not booked  

full_percentage = floor((total - available) / total * 100)

Rules:
- if total = 0 → result = 0
- must update on:
  - country change
  - month change

---

## 19. Sorting Logic

Sort order:

1. priority DESC
2. date DESC

Rules:
- must be applied before render
- must remain stable

---

## 20. Empty State Behavior

If no yachts exist for selected country:

- show empty state message
- do not break layout

---

## 21. Image Rendering

For each card:

Use priority:

1. override image
2. featured image
3. fallback

Rules:
- must not break layout
- must always render safe image

---

## 22. CTA Behavior Summary

### Watch Video
- open yacht permalink

### Call the Crew
- open crew panel

### Book Now
- open crew panel

Rules:
- consistent behavior
- must not trigger wrong URLs

---

## 23. DOM Stability Rules

Frontend must:

- avoid full re-render when possible
- update only necessary elements
- maintain scroll and interaction state

---

## 24. JS Architecture Rules

JS must:

- be state-driven
- avoid scattered logic
- centralize state updates

Recommended:
- one state object
- controlled render functions

---

## 25. No Hardcoding Rule

Do NOT hardcode:

- country list
- months
- tools
- crew
- availability

Everything must come from data.

---

## 26. Performance Expectations

v1.0:

- small dataset
- no virtualization needed
- no lazy load needed

Future:
- virtualization possible
- API-based loading possible

---

## 27. Error Handling

Frontend must:

- not break on missing data
- fallback gracefully
- keep UI usable

---

## 28. Summary

Frontend system is:

- data-driven
- state-controlled
- deterministic

It:
- loads full dataset once
- filters client-side
- computes availability dynamically
- renders tools dynamically
- manages crew interaction
- resolves URLs safely

All behavior must follow this contract.