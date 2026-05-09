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
