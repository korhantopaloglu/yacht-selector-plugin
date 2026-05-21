#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Build languages/yacht-selector-tr_TR.po and yacht-selector-tr_TR.mo from the POT catalog
plus languages/yacht-selector-tr_TR.msgrecords.txt (newline-ordered Turkish msgstrs).

Maintain strings in ``tools/emit_msgrecords_txt.py`` (tuple ``LINES_TR``), then regenerate
the msgrecords newline file:

    python3 tools/emit_msgrecords_txt.py

Usage (repo root):

    python3 tools/build_tr_pack.py
"""

from __future__ import annotations

import re
import sys
from datetime import datetime, timezone
from pathlib import Path

_SCRIPT_DIR = Path(__file__).resolve().parent
_REPO_ROOT = _SCRIPT_DIR.parent
if str(_SCRIPT_DIR) not in sys.path:
    sys.path.insert(0, str(_SCRIPT_DIR))

from write_gnu_mo import write_gnu_mo  # noqa: E402 pylint: disable=wrong-import-position


def parse_po_header_date(pot_text: str) -> str:
    """Return POT-Creation-Date value preserved from the POT header block."""
    m = re.search(r"POT-Creation-Date:\s*([^\"\\]+)", pot_text)
    if m:
        return m.group(1).strip()
    return datetime.now(timezone.utc).strftime("%Y-%m-%d %H:%M+0000")


def parse_po_string_fragment(fragment: str) -> tuple[str, str]:
    fragment = fragment.lstrip()
    if not fragment.startswith('"'):
        raise ValueError(fragment[:60])
    i = 1
    chunks: list[str] = []
    n = len(fragment)
    while i < n:
        c = fragment[i]
        if c == "\\" and i + 1 < n:
            nxt = fragment[i + 1]
            if nxt == "n":
                chunks.append("\n")
            elif nxt == "t":
                chunks.append("\t")
            elif nxt == "r":
                chunks.append("\r")
            elif nxt == '"':
                chunks.append('"')
            elif nxt == "\\":
                chunks.append("\\")
            else:
                chunks.append(nxt)
            i += 2
            continue
        if c == '"':
            return "".join(chunks), fragment[i + 1 :].strip()
        chunks.append(c)
        i += 1
    raise ValueError("Unterminated gettext string")


def parse_pot_catalog(text: str) -> list[tuple[list[str], str]]:
    refs: list[str] = []
    rows: list[tuple[list[str], str]] = []
    header_block = False

    for ln in text.splitlines():
        s = ln.rstrip("\n")

        if header_block:
            if not s.strip():
                header_block = False
            continue

        if s.startswith("#:"):
            refs.extend(bit for bit in s[2:].strip().split() if bit)
            continue

        if s.startswith("msgid"):
            tail = s[len("msgid") :].strip()
            if tail.startswith('""'):
                refs.clear()
                header_block = True
                continue
            mid, rest = parse_po_string_fragment(tail)
            if rest:
                raise RuntimeError(f"Unexpected after msgid: {rest!r} ({s})")
            rows.append((list(dict.fromkeys(refs)), mid))
            refs.clear()

    return rows


def escape_po_atom(msg: str) -> str:
    return (
        msg.replace("\\", "\\\\")
        .replace('"', '\\"')
        .replace("\n", "\\n")
        .replace("\r", "\\r")
        .replace("\t", "\\t")
    )


def load_ordered_translations(text: str) -> list[str]:
    lines = [ln for ln in text.splitlines()]
    while lines and not lines[-1]:
        lines.pop()
    return lines


def translation_map_from_lines(
    catalog: list[tuple[list[str], str]], lines: list[str]
) -> dict[str, str]:
    mids = [m for _, m in catalog]
    if len(mids) != len(lines):
        raise SystemExit(
            f"POT catalog has {len(mids)} msgids but msgrecords file has {len(lines)} lines"
        )
    return dict(zip(mids, lines))


def emit_po(
    po_path: Path,
    pot_date: str,
    catalog: list[tuple[list[str], str]],
    translations: dict[str, str],
) -> list[tuple[str, str]]:
    """Write .po bytes and return tuples for MO: first pair is (“”, metadata string)."""

    now = datetime.now(timezone.utc).strftime("%Y-%m-%d %H:%M+0000")

    header_atoms = (
        "Project-Id-Version: Yacht Selector\n",
        "Report-Msgid-Bugs-To:\n",
        f"POT-Creation-Date: {pot_date}\n",
        f"PO-Revision-Date: {now}\n",
        "Last-Translator: Yacht Selector contributors\n",
        "Language-Team: Turkish\n",
        "Language: tr_TR\n",
        "MIME-Version: 1.0\n",
        "Content-Type: text/plain; charset=UTF-8\n",
        "Content-Transfer-Encoding: 8bit\n",
        # WordPress-compatible Turkish catalogs often use singular-only plural formula.
        "Plural-Forms: nplurals=1; plural=0;\n",
        "X-Domain: yacht-selector\n",
    )

    header_val = "".join(header_atoms)
    pairs: list[tuple[str, str]] = [("", header_val)]

    lines: list[str] = [
        "# Turkish translation for Yacht Selector.",
        "# Copyright (C) YEAR Yacht Selector Plugin contributors",
        "# Distributed under GPLv2+, same license as Yacht Selector.",
        "",
        'msgid ""',
        'msgstr ""',
    ]

    for atom in header_atoms:
        lines.append(f'"{escape_po_atom(atom)}"')

    lines.append("")

    for refs, mid in catalog:
        if mid not in translations:
            missing = repr(mid)[:200]
            raise SystemExit(f"Missing Turkish translation for msgid {missing}")

        mt = translations[mid]

        pairs.append((mid, mt))

        for r in refs:
            lines.append(f"#: {r}")

        escaped_id = escape_po_atom(mid)
        escaped_tr = escape_po_atom(mt)

        if "\n" in mid:
            lines.append('msgid ""')
            for part in mid.split("\n"):
                lines.append(f'"{escape_po_atom(part)}\n"')
        else:
            lines.append(f'msgid "{escaped_id}"')

        if "\n" in mt:
            lines.append('msgstr ""')
            for part in mt.split("\n"):
                lines.append(f'"{escape_po_atom(part)}\n"')
        else:
            lines.append(f'msgstr "{escaped_tr}"')

        lines.append("")

    body = "\n".join(lines).rstrip() + "\n"
    po_path.parent.mkdir(parents=True, exist_ok=True)
    po_path.write_text(body, encoding="utf-8")

    return pairs


def main() -> int:
    pot_path = _REPO_ROOT / "languages" / "yacht-selector.pot"
    po_path = _REPO_ROOT / "languages" / "yacht-selector-tr_TR.po"
    mo_path = _REPO_ROOT / "languages" / "yacht-selector-tr_TR.mo"

    text = pot_path.read_text(encoding="utf-8")
    pot_date = parse_po_header_date(text)
    catalog = parse_pot_catalog(text)

    mids_catalog = [m for _, m in catalog]
    if len(set(mids_catalog)) != len(mids_catalog):
        raise SystemExit("Duplicate msgids in POT — fix extractor or POT.")

    mids_set = set(mids_catalog)

    records_path = _REPO_ROOT / "languages" / "yacht-selector-tr_TR.msgrecords.txt"
    lines_tr = load_ordered_translations(records_path.read_text(encoding="utf-8"))
    translations = translation_map_from_lines(catalog, lines_tr)

    if set(translations.keys()) != mids_set:
        raise SystemExit(
            "Translation map differs from POT msgid set "
            "(msgrecords count must match POT in exact walk order)."
        )

    pairs = emit_po(po_path, pot_date, catalog, translations)
    write_gnu_mo(mo_path, pairs)
    print("Wrote", po_path)
    print("Wrote", mo_path)
    print("messages:", len(catalog))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
