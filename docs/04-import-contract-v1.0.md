# Import Contract

Defines JSON import structure and normalization rules.

## Document Purpose

This document defines how Yacht Selector imports external data into WordPress.

It locks:
- JSON structure expectations
- parsing rules
- normalization rules
- mapping to WP (post, meta, taxonomy)
- fallback logic
- update vs create behavior

This is the single source of truth for all import operations.

---

## 1. Import Scope

The import system is responsible for:

- reading JSON data
- validating structure
- mapping fields to WP
- creating or updating posts
- assigning taxonomy terms
- saving meta fields
- handling missing or invalid data safely

---

## 2. Supported Input Format

### Root Structure

Input must be:

- a JSON array
- each item must be an object

Valid example:

[
  { ... },
  { ... }
]

Invalid:
- single object
- nested arrays
- mixed structures

---

## 3. Minimum Required Fields

Each item should ideally include:

- title
- country OR location data

If missing:
- item may still import
- but will fallback to safe defaults

---

## 4. Recommended Full JSON Structure

Each item may include:

- title
- description
- url
- image
- country
- port
- booked
- model
- length
- beam
- engine
- build_year
- refit_year
- cabins
- guests
- crew
- priority
- features
- cta

---

## 5. Field Mapping Rules

### Title

- maps to post_title

---

### Description

- maps to post_content

---

### URL

- optional
- may be stored or ignored depending on use case

---

### Image

- expected as URL
- should be downloaded or assigned as attachment
- fallback to empty if invalid

---

### Country

Expected structure:

country:
- name
- slug (optional)

Rules:
- maps to parent term
- if not exists, create
- if missing, fallback:
  - name: Unknown
  - slug: unknown

---

### Port

Expected structure:

port:
- name
- slug (optional)

Rules:
- maps to child term
- must be assigned under correct country
- if not exists, create

---

### Booked

Format:

booked:
- array of YYYY-MM strings

Example:
- 2026-03
- 2026-04

Rules:
- must be stored as array
- must not be converted to other formats
- invalid values should be ignored

---

### Model

- maps to ys_model
- must match or be added to model options

---

### Length and Beam

- decimal allowed
- must be stored as numeric values
- no forced rounding

---

### Engine

- plain text
- maps to ys_engine

---

### Build Year and Refit Year

- integer
- maps to ys_build_year and ys_refit_year

---

### Cabins, Guests, Crew

- integer values
- maps to:
  - ys_cabins
  - ys_guests
  - ys_crew

---

### Priority

- integer
- maps to ys_priority
- default may be 0 if missing

---

### Features

- array of strings

Rules:
- maps to ys_extra_features
- missing values may be auto-added to settings

---

### CTA

Optional object:

cta:
- watch_video
- video_call
- schedule

Rules:
- maps to post-level CTA meta
- values must be valid URLs

---

## 6. Post Matching Strategy

To avoid duplicates, importer should try to match existing posts.

Preferred matching order:

1. unique external ID (if available in future)
2. title match
3. fallback: always create new

Rule:
- do not overwrite unrelated posts

---

## 7. Create vs Update Behavior

If a match is found:
- update existing post
- update meta
- update taxonomy

If no match:
- create new post

---

## 8. Taxonomy Handling

### Country

- must exist or be created
- stored as parent term

### Port

- must exist or be created
- stored as child term

Rules:
- correct parent-child relation must be enforced
- duplicate terms must be avoided

---

## 9. Meta Saving Rules

For each mapped field:

- sanitize input
- cast to correct type
- store only valid values

Examples:

- arrays must be arrays
- integers must be integers
- decimals must remain decimals
- URLs must be sanitized

---

## 10. Missing Data Behavior

If a field is missing:

- title → required fallback or skip item
- country → fallback to unknown
- port → optional
- booked → empty array
- features → empty array
- image → fallback to empty
- priority → default 0

Rule:
Importer must not break on missing data.

---

## 11. Invalid Data Handling

If data is invalid:

- ignore invalid values
- do not crash import
- continue processing other fields

Examples:

- invalid date format → skip that entry
- invalid URL → ignore field
- wrong type → attempt safe cast

---

## 12. Data Normalization

Before saving:

- trim strings
- normalize casing where needed
- remove duplicates from arrays
- ensure consistent formats

Examples:

- features must not contain duplicates
- booked must not contain duplicates

---

## 13. Image Handling

If image URL exists:

- attempt to download
- assign as attachment
- store attachment ID

If fails:
- fallback to empty
- do not block import

---

## 14. Import Idempotency

Repeated import of same dataset should:

- not create duplicates unnecessarily
- update existing items where possible

---

## 15. Performance Expectations

v1.0 rules:

- no batching required
- no async processing required
- no queue system required

Future versions may include:
- batching
- background processing

---

## 16. Logging (Optional but Recommended)

Importer may log:

- created items
- updated items
- skipped items
- errors

This helps debugging and validation.

---

## 17. Admin UX Expectations

Admin should be able to:

- paste JSON
- validate before import
- run import safely
- see result summary

Optional:
- dry run mode
- preview parsed structure

---

## 18. Validation Rules Summary

Importer must:

- validate JSON structure
- ensure root is array
- ensure each item is object
- sanitize all inputs
- handle missing data safely
- avoid breaking flow on errors

---

## 19. Future Ready Notes

Future enhancements may include:

- external API integration
- scheduled imports
- delta updates
- mapping UI
- import templates

---

## 20. Summary

The import system:

- accepts structured JSON arrays
- maps fields to WP posts, meta, taxonomy
- supports decimals and arrays
- creates missing taxonomy terms
- avoids breaking on bad data
- supports update and create logic
- prepares clean normalized data for frontend use

All import logic must follow this contract.