#!/usr/bin/env python3
"""Sync languages/yacht-selector-tr_TR.msgrecords.txt from POT walk order + TR map."""

from __future__ import annotations

import json
import sys
from pathlib import Path

_SCRIPT = Path(__file__).resolve().parent
_REPO = _SCRIPT.parent
sys.path.insert(0, str(_SCRIPT))

from build_tr_pack import parse_pot_catalog  # noqa: E402

_MAP_PATH = _SCRIPT / "tr_translations.json"
_POT = _REPO / "languages" / "yacht-selector.pot"
_OUT = _REPO / "languages" / "yacht-selector-tr_TR.msgrecords.txt"


def _normalize_apostrophe(text: str) -> str:
    return text.replace("\u2019", "'").replace("\u2018", "'")


def main() -> int:
    catalog = [m for _, m in parse_pot_catalog(_POT.read_text(encoding="utf-8"))]
    raw = json.loads(_MAP_PATH.read_text(encoding="utf-8"))
    tr: dict[str, str] = {}
    for key, val in raw.items():
        tr[key] = val
        tr[_normalize_apostrophe(key)] = val

    lines: list[str] = []
    missing: list[str] = []
    for mid in catalog:
        hit = tr.get(mid)
        if hit is None:
            hit = tr.get(_normalize_apostrophe(mid))
        if hit is None:
            missing.append(mid)
            hit = mid
        lines.append(hit)

    if missing:
        print("Warning: untranslated msgids:", len(missing), file=sys.stderr)
        for m in missing:
            print(" ", repr(m), file=sys.stderr)

    if len(lines) != len(catalog):
        raise SystemExit(f"Expected {len(catalog)} lines, got {len(lines)}")

    _OUT.write_text("\n".join(lines) + "\n", encoding="utf-8")
    print("Wrote", _OUT.relative_to(_REPO), "(", len(lines), "lines )")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
