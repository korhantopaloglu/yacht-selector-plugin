# Data Model & Admin Contract

Defines CPTs, meta fields, and admin data structure.

## Document Purpose

This document defines the v1.0 data model and admin-side contract for Yacht Selector.

It locks:
- yacht data structure
- crew data structure
- contact tool data structure
- admin settings structure
- meta field behavior
- URL priority rules
- placeholder rules
- admin UI expectations

This document is a source of truth for how data is created, stored, and interpreted.

---

## 1. Main Content Source

Frontend yacht cards are built from the Custom Post Type selected in plugin settings.

Rules:
- the plugin must not hardcode a post type
- the selected CPT is the source of yacht items
- all render and import logic must align with the selected CPT

---

## 2. Yacht Core Data Model

Each yacht item should be able to expose the following core values:

- post_id
- title
- description
- permalink
- image
- country
- port
- booked months
- model
- length
- beam
- engine
- build year
- refit year
- cabins
- guests
- crew count
- priority
- extra features
- assigned crew members
- CTA-related values when relevant

---

## 3. Yacht Meta Keys

Expected yacht-side meta keys include:

- ys_booked
- ys_model
- ys_length
- ys_beam
- ys_engine
- ys_build_year
- ys_refit_year
- ys_cabins
- ys_guests
- ys_crew
- ys_priority
- ys_extra_features
- ys_card_image_id
- ys_watch_video_url
- ys_video_call_url
- ys_schedule_url

If current code already uses equivalent existing keys, keep them aligned consistently and do not create duplicate systems.

---

## 4. Availability Model

Availability is stored as booked month values.

Format:
- YYYY-MM

Examples:
- 2026-03
- 2026-04
- 2026-06

Rules:
- this is a monthly model
- booked values represent months already occupied
- frontend determines available or unavailable state from this monthly structure
- current month selection is evaluated against this data

Admin UI:
- should show a forward-looking monthly selection set
- should be easy to update
- should store clean YYYY-MM values

---

## 5. Decimal Fields

The following fields support decimal values:

- ys_length
- ys_beam

Examples:
- 28
- 28.5
- 6
- 6.2

Rules:
- these values must not be forced to integer
- admin inputs must allow decimals
- import must preserve decimals
- output should remain natural and not force unnecessary trailing zeroes

---

## 6. Location Model

Location is hierarchical.

Structure:
- country = parent
- port = child

Rules:
- country is the main frontend country filter source
- port is the yacht-level location detail
- if only country exists, frontend may still function
- preferred content structure is country plus port

---

## 7. Location Taxonomy Rules

Country and port values should resolve through the plugin location taxonomy.

Rules:
- country terms are parent terms
- port terms are child terms
- country and port should use stable slugs
- import may auto-create missing terms
- missing country may fallback to:
  - name: Unknown
  - slug: unknown

---

## 8. Priority Model

Priority controls ordering.

Meta key:
- ys_priority

Rules:
- higher priority should appear earlier
- priority affects frontend dataset ordering
- priority influences selected card resolution after dataset load
- if priority is equal, secondary stable ordering may be used, such as title

---

## 9. Extra Features Model

Extra features are stored as a list/array.

Meta key:
- ys_extra_features

Rules:
- values come from admin-defined options
- import may auto-add missing values
- duplicate feature labels should be avoided
- values should remain clean and consistent

---

## 10. Image Model

Card image resolution priority:

1. yacht-specific card image override
2. featured image
3. placeholder or empty fallback

Primary override meta key:
- ys_card_image_id

Rules:
- if override exists, use it
- if no override exists, use featured image
- if neither exists, use safe fallback

---

## 11. CTA Model

The yacht card contains these actions:

- Watch Video
- Call the Crew
- Book Now

### Watch Video
Behavior:
- leads to yacht permalink / single page

### Call the Crew
Behavior:
- opens crew panel
- intended as live-contact entry

### Book Now
Behavior:
- opens crew panel
- remains available as a main action entry point

Rules:
- the actual tool actions inside the panel depend on crew/tool state
- Watch Video is not the same as external tool URL routing

---

## 12. Global Settings Model

Expected admin option keys include:

- ys_selected_post_type
- ys_location_taxonomy_slug
- ys_watch_video_text
- ys_book_now_text
- ys_all_label_text
- ys_empty_state_text
- ys_connect_with_top_title
- ys_crew_group_title
- ys_crew_group_subtitle
- ys_currently_on_board_text
- ys_currently_offline_text
- ys_online_status_icon
- ys_model_options
- ys_extra_feature_options
- ys_json_data

If the codebase already uses stable equivalent keys, keep naming consistent and avoid duplicate settings.

---

## 13. Frontend Text Settings

Admin-managed text fields should support at least:

- Watch Video text
- Book Now text
- All label text
- Empty state text
- crew top title
- crew title
- crew subtitle
- currently on board text
- currently offline text

Placeholder support:
- online and offline texts should support:
  - {crew_member}

Example:
- Currently on board: {crew_member}
- Currently offline: {crew_member}

---

## 14. Contact Tool Model

Contact tools are dynamic and must not be hardcoded into the frontend.

Each tool may include:

- name
- description
- icon or image
- online-sensitive flag
- default URL

Rules:
- tools are rendered from registered tool data
- frontend tool matching must use stable identifier
- visible label alone must not be the main key

---

## 15. Tool Identifier Rule

Every tool must have a stable identifier.

Preferred identifier order:
1. tool id
2. tool slug
3. tool key

Do not rely only on the visible title.

This identifier is used for:
- selected tool storage
- tool URL storage
- frontend tool matching
- fallback URL resolution

---

## 16. Tool Default URL

Each contact tool may define a default URL.

Purpose:
- fallback action when no crew-specific tool URL exists

Rules:
- default tool URL belongs to the tool definition
- it is not a crew-specific value
- crew-specific value overrides it when present

---

## 17. Crew Model

A yacht may have:
- no crew
- one crew member
- multiple crew members

Multi-crew support is part of v1.0.

Each crew member may define:
- selected contact tools
- tool-specific URLs
- online hours
- identity data displayed on frontend

---

## 18. Crew Contact Tool Data Model

Crew contact data must be scalable.

Use a dynamic structure based on selected tool identifiers and URL map.

Recommended concept:

selected_tools = list of selected tool identifiers

tool_urls = map:
- tool_identifier => url

Example concept:

selected_tools:
- whatsapp
- google-meet
- email

tool_urls:
- whatsapp => https://wa.me/905551112233
- google-meet => https://meet.google.com/abc-defg-hij
- email => mailto:hello@example.com

Rules:
- do not create one hardcoded meta field per tool
- newly added tools must work without schema redesign
- only selected tools should save relevant URLs

---

## 19. Crew Meta Behavior

When editing a crew item:

- selected tool inputs must be dynamic
- if a tool is selected, its URL field appears immediately
- URL field must appear in the same tool group/row
- save should persist only selected tool URLs
- unselected tool URLs should not remain active input targets

This is an admin UX contract.

---

## 20. Crew Online Availability Model

Crew may define online or working hours.

These values drive runtime frontend behavior.

When active crew is online:
- add crew-online
- set data-crew-online to 1

When active crew is offline:
- remove crew-online
- set data-crew-online to 0

Rules:
- online status is runtime state
- frontend should update per active crew
- multi-crew cards should update when selected crew changes

---

## 21. Tool Enable and Disable Rules

There are two categories of tools.

### Online-sensitive tools
These may be disabled when:
- no crew is assigned
- active crew is offline

### Non-online-sensitive tools
These remain enabled even when:
- no crew is assigned
- active crew is offline

Rules:
- non-sensitive tools should not be disabled merely because crew is missing or offline
- online-sensitive tools follow crew live state

---

## 22. URL Resolution Priority

When a user activates a tool on the frontend, use this order:

1. active crew tool URL
2. tool default URL
3. no action or disabled-safe behavior

Rules:
- crew-specific URL overrides default URL
- fallback must remain deterministic
- matching must use stable tool identifiers

---

## 23. Placeholder Replacement Support

Tool URLs may include placeholders that are replaced before redirect.

Recommended supported placeholders:
- {post_id}
- {yacht_id}
- {permalink}

Optional future placeholders may include:
- {title}
- {country}
- {port}
- {model}
- {month}

Rules:
- replacement happens at click time
- replacement uses current active yacht context
- frontend JS should resolve the final URL before opening it

---

## 24. JSON Import Data Contract

The JSON import layer should support at least these concepts:

- title
- description
- url
- image
- country object
- port object
- availability or booked months
- model
- length
- beam
- engine
- build year
- refit year
- cabins
- guests
- crew
- priority
- features
- CTA object when relevant

Import rules:
- root should be an array
- each item should be an object
- decimals must be preserved
- missing taxonomies may be created
- missing country may fallback to unknown
- model and features may be auto-added if absent from settings

---

## 25. Data Preparation Rule

Frontend templates must not be responsible for business logic.

Data should be prepared before render.

That includes:
- resolved country and port
- resolved image
- resolved tool definitions
- resolved crew options
- resolved tool URLs
- resolved online/offline state hooks
- resolved CTA values

---

## 26. Admin UX Contract Summary

Admin should be able to manage:

- selected CPT
- models
- extra features
- frontend texts
- JSON import
- yacht meta fields
- crew assignments
- crew contact tools
- crew-specific tool URLs
- tool default URLs
- help links and help docs

Admin UI should prefer:
- grouped sections
- predictable field placement
- dynamic field visibility where appropriate
- no unnecessary duplication

---

## 27. Summary

Yacht Selector v1.0 data and admin contract is built around:

- selected CPT yacht source
- hierarchical location data
- monthly booked availability data
- decimal-safe yacht dimensions
- dynamic contact tools
- scalable crew tool URL mapping
- crew live availability
- deterministic URL fallback
- admin-manageable texts and help
- import-compatible normalized structure

All future implementation should align with this contract.