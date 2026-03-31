# Architecture & File Map

Describes plugin structure and responsibilities.

## Document Purpose

This document defines the plugin architecture and file structure for Yacht Selector.

It explains:
- how the plugin is structured
- which files are responsible for what
- where logic should live
- how layers interact

This document is a source of truth for development.

---

## Plugin Root Structure

yacht-selector/
|
|-- yacht-selector.php
|
|-- includes/
|   |-- admin/
|   |-- post-types/
|   |-- taxonomies/
|   |-- meta/
|   |-- import/
|   |-- helpers/
|   |-- ajax/
|
|-- templates/
|   |-- selector/
|
|-- assets/
|   |-- css/
|   |-- js/
|   |-- img/
|
|-- docs/

---

## 1. Main Plugin File

File: yacht-selector.php

Responsibility:
- plugin bootstrap
- constants
- includes loader
- hooks initialization

Includes:
- activation/deactivation hooks
- loading includes
- enqueue assets
- shortcode registration

Rule:
Keep minimal and clean.

---

## 2. Includes Layer

All backend logic lives here.

Path:
includes/

Subfolders:
- admin
- post-types
- taxonomies
- meta
- import
- ajax
- helpers

---

## 2.1 Admin

Path:
includes/admin/

Responsibility:
- settings page
- admin UI
- validation
- settings storage
- help links

---

## 2.2 Post Types

Path:
includes/post-types/

Responsibility:
- register CPTs

Example:
- crew CPT

---

## 2.3 Taxonomies

Path:
includes/taxonomies/

Responsibility:
- register taxonomies

Examples:
- location (country -> port)
- contact tools

---

## 2.4 Meta

Path:
includes/meta/

Responsibility:
- meta boxes
- save logic
- grouped admin UI

Examples:
- yacht fields
- crew fields
- availability
- CTA fields

---

## 2.5 Import

Path:
includes/import/

Responsibility:
- JSON import
- parsing
- normalization
- mapping to WP

Behavior:
- create/update posts
- assign taxonomies
- create missing terms
- fallback to "unknown"
- sanitize all inputs

---

## 2.6 Ajax

Path:
includes/ajax/

Responsibility:
- AJAX endpoints
- dynamic data responses

---

## 2.7 Helpers

Path:
includes/helpers/

Responsibility:
- shared utilities

Examples:
- URL sanitize
- placeholder replace
- formatting
- fallback logic

---

## 3. Templates Layer

Path:
templates/selector/

Responsibility:
- HTML structure
- reusable partials
- server-rendered output

Rules:
- no business logic
- minimal conditionals
- data must be prepared before render

---

## 4. Assets Layer

Path:
assets/

Subfolders:
- css
- js
- img

---

## 4.1 CSS

Responsibility:
- layout
- components
- states

Rules:
- no inline styles
- class-based styling

---

## 4.2 JS

Responsibility:
- state management
- interactions
- AJAX
- DOM updates

Examples:
- country change
- month selection
- card selection
- slider control
- crew toggle
- tool actions
- placeholder replacement

Rule:
JS is state-driven.

---

## 4.3 Images

Responsibility:
- static assets
- icons
- fallbacks

---

## 5. Docs Layer

Path:
docs/

Responsibility:
- documentation
- contracts
- specs

Rule:
Docs define behavior.
Code must follow docs.

---

## 6. Data Flow

Admin:
- define yachts
- define crew
- define tools

Stored as:
- posts
- meta
- taxonomy

Frontend:
- load dataset
- render UI
- manage state

---

## 7. Rendering Flow

Initial:
- shortcode renders base
- dataset loaded
- first item selected

Country change:
- reload dataset
- reset UI

Month change:
- client-side update only

---

## 8. Separation of Concerns

PHP:
- data
- rendering
- persistence

JS:
- interaction
- state

CSS:
- visuals

Templates:
- structure only

---

## 9. Forbidden Patterns

Do NOT:
- add logic in templates
- use inline JS
- duplicate logic
- manipulate DOM without state

---

## 10. Future Ready

This structure supports:
- more filters
- larger datasets
- API integration
- advanced flows

---

## Summary

Yacht Selector consists of:

- main file
- includes (logic)
- templates (view)
- assets (UI)
- docs (rules)

Clear separation:
- PHP = data
- JS = behavior
- CSS = visuals
- Templates = structure