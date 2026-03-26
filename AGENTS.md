# Yacht Selector Plugin

## Project Goal
Build a production-ready WordPress plugin that renders a yacht selection frontend via shortcode.

## Branch Rules
- Primary working branch is `develop`
- Create feature branches from `develop`
- Do not target `main` directly for active development

## Frontend Rules
- `templates/frontend-static-reference.html` is the visual source of truth
- Preserve section order, class structure, and general layout unless explicitly asked
- Convert static markup into dynamic PHP output with minimal visual deviation

## Code Rules
- Prefer modular PHP classes under `/includes`
- Avoid heavy dependencies
- Sanitize inputs and escape outputs
- Follow WordPress conventions where practical
- Do not rename public CSS classes unless required

## Task Style
- Explore existing code before editing
- Make minimal, targeted changes
- Summarize changed files after implementation
- Flag assumptions clearly