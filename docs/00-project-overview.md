# Naviga WP Plugin — Project Overview

## Project Name
Naviga WP Plugin (working name)  
Final Product Name: Yacht Selector

---

## Purpose

This plugin is designed to transform static yacht listing data into a dynamic, filterable, and interactive yacht selection experience inside WordPress.

The core goal is to:

- Allow users to browse yachts by country and month
- Display availability visually
- Provide direct contact options via crew members
- Enable structured and scalable data import via JSON
- Maintain a clean separation between data, logic, and UI

---

## Core Concept

The system revolves around:

- A selected Custom Post Type (Yacht)
- Structured metadata for yachts
- Crew-based communication layer
- Contact tools with dynamic URL mapping
- A shortcode-based frontend rendering system

---

## Scope

### Included in this plugin

- Yacht data management via CPT
- JSON-based bulk data import
- Automatic taxonomy creation (country, port, models, features)
- Crew management and assignment
- Contact tools system (dynamic + scalable)
- Online/offline crew availability logic
- Frontend shortcode rendering (structure only)
- Dynamic filtering (country / month)
- Contact interaction logic (tool-based)

---

### Explicitly NOT included

- Final UI/UX styling (CSS polish)
- Advanced animations
- External API integrations
- Booking/payment systems
- Authentication systems
- Complex scheduling engines

---

## Architecture Overview

The plugin is structured into 3 main layers:

### 1. Data Layer
- Yacht CPT + meta fields
- Crew CPT + meta fields
- Contact tools taxonomy + meta
- JSON import pipeline
- Normalization and mapping rules

---

### 2. Logic Layer
- Availability logic (monthly)
- Crew online/offline evaluation
- Contact tool enable/disable logic
- URL resolution:
  - crew-specific URL
  - fallback to default tool URL
- Filtering logic (country / month)
- State handling per yacht card

---

### 3. Presentation Layer
- Shortcode-based rendering
- Structured HTML output
- Data attributes for JS interaction
- Class-based UI states
- Minimal JS-driven interactivity

IMPORTANT:
This layer defines structure only — visual styling will be handled later.

---

## Key Features

### Yacht Filtering
- Country-based filtering
- Month-based filtering
- Combined filtering logic

---

### Availability Visualization
- Monthly availability representation
- Selected month highlighting
- Card visibility control via JS

---

### Crew System
- Each yacht can have one or multiple crew members
- Crew can have working/online hours
- Crew selection supported (multi-crew scenario)
- Crew-based contact behavior

---

### Contact Tools System

Dynamic and scalable system:

- Tools are not hardcoded
- Each tool has:
  - identifier (slug/id)
  - online-sensitive flag
  - optional default URL
- Crew members can:
  - select tools
  - assign tool-specific URLs

---

### URL Resolution Logic

When a tool is used:

Priority:

1. Crew-specific tool URL
2. Default tool URL
3. No action (if neither exists)

---

### Online / Offline Logic

Crew availability determines behavior:

- Online:
  - class="crew-online"
  - data-crew-online="1"
  - online text shown

- Offline:
  - no class
  - data-crew-online="0"
  - offline text shown

---

### Tool Behavior Rules

Two categories:

#### Online-sensitive tools
- Disabled when crew is offline
- Enabled when crew is online

#### Non-online-sensitive tools
- ALWAYS enabled
- Use default URL if crew URL is missing

---

### Admin Panel Features

- Global settings (texts, defaults)
- Contact tools management
- Crew management
- Dynamic URL inputs per tool
- JSON import interface
- Help documentation (markdown-based)

---

### Help System

- Markdown-based help documents
- Popup/modal display
- Examples and usage guides
- Extensible for future docs

---

## Design Principles

### 1. Scalability
- No hardcoded tools
- Dynamic data structures
- Easy extension without code changes

---

### 2. Separation of Concerns
- Data ≠ Logic ≠ UI
- Each layer is independently maintainable

---

### 3. Declarative Structure
- Frontend driven by data attributes and classes
- JS only enhances behavior

---

### 4. Minimal JS Dependency
- Vanilla JS preferred
- No heavy frameworks

---

### 5. Admin Flexibility
- Texts configurable from settings
- Tools configurable without code
- Help accessible inline

---

## Current Phase

✅ Data model defined  
✅ Admin architecture defined  
✅ Import system defined  
✅ Contact system defined  
✅ Interaction logic defined  

⏳ Next Phase:
Frontend UI/UX (CSS + JS refinement)

---

## Source of Truth

This document and accompanying docs in `/docs` folder define:

- System behavior
- Data contracts
- UI structure
- Interaction logic

All future development must align with these documents.

---

## Versioning Note

This document represents the locked baseline for:

Naviga WP Plugin — Core System v1.0

Further changes must be versioned and documented explicitly.