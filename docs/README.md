# Yacht Selector — Documentation

The `/docs` folder separates **technical contracts** (how the plugin is designed to behave) from **user help** (how to configure and support it on a live WordPress site).

---

## Technical documentation

These references describe **architecture**, the **data model**, and behaviour contracts for **import**, **rendering**, and **frontend interaction**. They are intended for developers and integrators—not for day-to-day editors managing yacht posts.

| Topic | Where to read |
| ----- | ---------------- |
| **Architecture** | [Project overview — Architecture overview](00-project-overview.md#architecture-overview) |
| **Data model** | [Project overview — Data layer](00-project-overview.md#1-data-layer) (CPT, meta, taxonomy, normalization) |
| **Import contract** | [Project overview — Included scope](00-project-overview.md#included-in-this-plugin) · JSON pipeline in [Data layer](00-project-overview.md#1-data-layer) |
| **Render contract** | [Project overview — Presentation layer](00-project-overview.md#3-presentation-layer) (shortcode, structured markup) |
| **Frontend contract** | [Project overview — Yacht filtering](00-project-overview.md#yacht-filtering) · [Availability visualization](00-project-overview.md#availability-visualization) · [Contact tools system](00-project-overview.md#contact-tools-system) · [URL resolution](00-project-overview.md#url-resolution-logic) |
| **URL behaviour (legacy / advanced)** | [Contact URL Help (Advanced)](contact-url-help.md) |

Start from **[Project overview](00-project-overview.md)** for the full baseline narrative.

### Internationalization (developers)

**Frontend UI labels** (buttons, card spec headings, crew status templates, empty state, etc.) are **gettext-only** — there is no admin settings screen for them. Source strings live in PHP (`includes/i18n.php` → `ys_frontend_display_strings()`, templates, admin copy) as English **`msgid`s**.

Regenerate the template catalog:

```bash
python3 tools/make-pot.py
```

**Bundled `tr_TR` pack**

- Edit **`tools/tr_translations.json`**, then sync and compile:

```bash
python3 tools/sync_tr_from_pot.py && python3 tools/build_tr_pack.py
```

Preserve every placeholder (`%s`, `%d`, `%1$s`, `{crew_member}`, …) exactly.

**WPML / Polylang** (optional, no dependency)

- Translate frontend/admin UI through the **normal gettext / `.po` workflow** (bundled `tr_TR` pack or your own `.mo` files). `includes/compatibility/multilingual.php` exposes optional **`ys_register_multilingual_string()`** / **`ys_translate_multilingual_string()`** helpers only — it does **not** register frontend text from plugin options.

**Explicit non-goals:** model option rows and extra-feature rows stay **repeatable settings text**, **not taxonomy terms**.

(`package.json` / `composer.json` are not bundled in this repo; if you add aliases there, mirror that command.)

---

## User documentation

Step-by-step guides for **website administrators**, **content managers**, **agencies**, and **support teams**: shortcodes, settings, JSON import, **contact tools**, **frontend selector** behaviour, and troubleshooting—without reading PHP.

**Help hub:** **[Yacht Selector help documentation](help/README.md)**

---

## Plugin release notes

- [CHANGELOG.md](CHANGELOG.md)

---

> **NOTE:**  
> Yacht Selector provides **yacht browsing**, a **frontend selector** UI, and **contact routing**. It is **not** a booking engine, payment system, or CRM.
