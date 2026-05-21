#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Write GNU gettext MO (revision 0) from UTF-8 (msgid, msgstr) pairs.

MO layout derived from Babel (BSD license):
https://github.com/python-babel/babel/blob/master/babel/messages/mofile.py
"""

from __future__ import annotations

import array
import struct
from pathlib import Path
from typing import List, Tuple

LE_MAGIC = 0x950412DE


def write_gnu_mo(path: Path, pairs: List[Tuple[str, str]]) -> None:
    """
    pairs: Headers first ("", "...metastrings joined with escaped newlines...").
    Drops entries with empty msgstr after the header (gettext convention).
    """
    messages = [pairs[0]]
    messages.extend([(k, v) for k, v in pairs[1:] if v])
    messages[1:] = sorted(messages[1:], key=lambda kv: kv[0])

    blob_ids = b""
    blob_strs = b""
    offsets = []

    for msgid, msgstr in messages:
        msgid_b = msgid.encode("utf-8")
        msgstr_b = msgstr.encode("utf-8")
        offsets.append((len(blob_ids), len(msgid_b), len(blob_strs), len(msgstr_b)))
        blob_ids += msgid_b + b"\x00"
        blob_strs += msgstr_b + b"\x00"

    keystart = 7 * 4 + 16 * len(messages)
    valuestart = keystart + len(blob_ids)

    koffsets: List[int] = []
    voffsets: List[int] = []
    for o1, l1, o2, l2 in offsets:
        koffsets += [l1, o1 + keystart]
        voffsets += [l2, o2 + valuestart]

    flat = koffsets + voffsets
    header = struct.pack(
        "Iiiiiii",
        LE_MAGIC,
        0,
        len(messages),
        7 * 4,
        7 * 4 + len(messages) * 8,
        0,
        0,
    )
    path.write_bytes(header + array.array("i", flat).tobytes() + blob_ids + blob_strs)
