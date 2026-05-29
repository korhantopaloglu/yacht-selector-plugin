#!/usr/bin/env python3
"""Deprecated wrapper — use tools/sync_tr_from_pot.py instead."""

from __future__ import annotations

import subprocess
import sys
from pathlib import Path

_REPO = Path(__file__).resolve().parent.parent


def main() -> int:
    script = _REPO / "tools" / "sync_tr_from_pot.py"
    return subprocess.call([sys.executable, str(script)])


if __name__ == "__main__":
    raise SystemExit(main())
