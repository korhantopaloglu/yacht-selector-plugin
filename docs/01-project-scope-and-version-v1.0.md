# Project Scope v1.0

Defines what is in and out of scope for the Yacht Selector plugin.

## Project Name
Yacht Selector

## Working Project Context
WP Plugin

## Document Purpose
This document defines the scope, intent, and version boundary of the Yacht Selector plugin for v1.0.

It answers:

- what this plugin is
- what this plugin does
- what is included in v1.0
- what is not included in v1.0
- what the plugin is responsible for
- what external tools are responsible for

---

## Core Purpose

Yacht Selector is a WordPress plugin that turns yacht content into a structured browsing and contact experience.

Its main purpose is to allow users to:

- browse yachts by country
- explore yachts through a selector-style card interface
- evaluate yacht availability by month
- access crew-based communication options
- use external tools for inquiry, call, video call, or scheduling

This plugin is not intended to become a full booking platform.

---

## Product Role

The plugin acts as:

- a content orchestration layer
- a selector interface layer
- a frontend interaction layer
- a contact-routing layer

The plugin does not act as:

- a payment system
- a real-time booking engine
- a CRM
- an external API sync platform

---

## v1.0 Goal

The v1.0 goal is to deliver a stable and scalable selector-based yacht browsing system with:

- selected CPT-based yacht source
- structured yacht data
- country-based filtering
- month-based availability state
- priority-aware card ordering
- crew-based communication layer
- contact tools with dynamic and fallback URL support
- admin-manageable text/settings/help/import flows
- frontend selector state logic with AJAX country reload

---

## Included in v1.0

### Content Source
- selected Custom Post Type as yacht source
- WordPress-based content architecture
- yacht data from post/meta/taxonomy structure

### Data Model
- yacht meta fields
- location hierarchy
- crew assignments
- contact tool metadata
- crew tool URL mapping
- decimal support for `length` and `beam`
- availability/booked month model

### Admin Features
- plugin settings
- frontend text settings
- selected CPT settings
- contact tool meta/settings
- crew contact availability UI
- dynamic tool URL inputs
- JSON data import UI
- help documents for admin-facing guidance

### Import / Normalization
- JSON import support
- taxonomy auto-create behavior
- unknown fallback for missing country
- model auto-add
- extra feature auto-add
- decimal-safe field import behavior

### Frontend
- shortcode-based selector rendering
- `block="selector"` render path
- country selector rail
- month selector rail
- cards container and selector card flow
- crew panel
- contact tool cards
- Watch Video / Call the Crew / Book Now action structure

### Frontend Logic
- initial selector state
- AJAX-based country dataset reload
- client-side month state updates on active dataset
- priority-aware selected card logic
- crew online/offline state handling
- tool enable/disable behavior
- default tool URL fallback
- crew-specific URL override
- crew panel open/close logic

---

## Not Included in v1.0

The following are explicitly outside the scope of v1.0:

### External Integrations
- direct third-party API integrations
- CRM sync
- remote booking engine integration
- real-time external availability APIs

### Commerce / Booking
- internal booking engine
- payment flow
- checkout process
- reservation management backend

### Scheduling
- internal scheduling engine
- internal calendar conflict engine
- native timeslot booking system

### User Accounts
- customer authentication
- customer dashboards
- member-only experiences

### Advanced Platform Features
- automation workflows
- background jobs
- advanced analytics productization
- multilingual content system beyond normal WordPress usage
- mobile app / native app scope

### Final Presentation Freeze
- final CSS polish
- motion polish
- production-grade visual tuning
- final animation pass

These belong to later phases after v1.0 core behavior is stable.

---

## External Action Model

All communication and scheduling actions are routed to external tools.

Examples include:

- WhatsApp
- phone call
- email
- Google Meet
- Zoom
- Microsoft Teams
- Calendly
- custom external links

The plugin is responsible for:

- determining which tool is available
- resolving which URL should be used
- optionally injecting yacht-specific placeholders
- opening the final outbound URL

The external service is responsible for:

- the actual communication
- the actual meeting
- the actual scheduling
- the actual booking process

---

## Core UX Intent

The frontend experience should feel like:

- a curated yacht browser
- a premium selector
- a guided contact experience

The plugin should help the user move from:

1. browsing yachts
2. evaluating availability
3. opening crew/contact options
4. leaving the site through the correct external action

This flow is central to v1.0.

---

## Source of Truth Rule

This project is locked through its contract documents.

For v1.0, the `/docs` folder acts as the source of truth for:

- scope
- architecture
- data contract
- import contract
- render contract
- frontend state contract
- help content

When implementation and documentation diverge, the next implementation pass should realign code to the documented contract unless the contract is deliberately revised.

---

## Version Rule

This document set defines:

**Yacht Selector v1.0**

Versioning after this point should follow:

- `v1.01`, `v1.02`, etc. for small updates
- `v1.1` for meaningful but non-breaking contract evolution
- `v2.0` for major structural changes

---

## Current Development Phase

At the time of this document:

- core admin/data architecture is substantially defined
- import direction is defined
- selector render direction is defined
- help document direction is defined
- final frontend state implementation is the next major build step
- visual polish belongs after the selector logic is stabilized

---

## Summary

Yacht Selector v1.0 is a WordPress-based yacht selector and contact-routing plugin.

It is designed to:

- structure yacht data
- present yachts through a selector interface
- support month-aware availability browsing
- expose crew/contact tools
- route users into external communication or scheduling flows

It is not a booking engine, payment system, or CRM.

This distinction must remain clear throughout implementation.