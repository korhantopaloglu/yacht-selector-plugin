#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Scan PHP gettext calls and write languages/yacht-selector.pot.

    python3 tools/make-pot.py

Scans recursively from plugin root (parent of tools/). Skips directories:
vendor, node_modules, .git, languages, docs, tools.
"""

from __future__ import annotations

import os
import re
import sys
from dataclasses import dataclass, field
from datetime import datetime, timezone
from pathlib import Path
from typing import Dict, Iterable, List, Tuple, Union

EXCLUDE_DIRS = frozenset(
    {"vendor", "node_modules", ".git", "languages", "docs", "tools"}
)

PHP_STR_SQ = r"'(?:[^'\\]|\\.)*'"
PHP_STR_DQ = r'"(?:[^"\\]|\\.)*"'
PHP_STRING = rf"(?:{PHP_STR_SQ}|{PHP_STR_DQ})"

FUNCS_TWO_ARG = ("__", "_e", "esc_html__", "esc_html_e", "esc_attr__", "esc_attr_e")
FUNCS_CTX = ("_x", "esc_html_x", "esc_attr_x")

# After the closing domain quote WordPress commonly uses `)` or `);` immediately.
# `\b` is wrong there (not a word boundary between `'` and `)`).

_RE_SIMPLE = re.compile(
    rf"\b(?:{'|'.join(re.escape(n) for n in FUNCS_TWO_ARG)})\s*\(\s*({PHP_STRING})\s*,\s*(['\"])yacht-selector\2\s*[),]",
    flags=re.DOTALL,
)
_RE_CTX = re.compile(
    rf"\b(?:{'|'.join(re.escape(n) for n in FUNCS_CTX)})\s*\(\s*({PHP_STRING})\s*,\s*({PHP_STRING})\s*,\s*(['\"])yacht-selector\3\s*[),]",
    flags=re.DOTALL,
)
_RE_N = re.compile(
    rf"\b_n\s*\(\s*({PHP_STRING})\s*,\s*({PHP_STRING})\s*,\s*[^,)]+?\s*,\s*(['\"])yacht-selector\3\s*[),]",
    flags=re.DOTALL,
)
_RE_NX = re.compile(
    rf"\b_nx\s*\(\s*({PHP_STRING})\s*,\s*({PHP_STRING})\s*,\s*[^,)]+?\s*,\s*({PHP_STRING})\s*,\s*(['\"])yacht-selector\4\s*[),]",
    flags=re.DOTALL,
)


def php_unescape_string(raw: str) -> str:
    if len(raw) < 2:
        return ""
    delim = raw[0]
    body = raw[1:-1]
    esc_sq = {"\\": "\\", "'": "'", "n": "\n"}
    esc_dq = {"\\": "\\", '"': '"', "n": "\n", "t": "\t", "r": "\r"}
    mapping = esc_sq if delim == "'" else esc_dq
    chunks: List[str] = []
    i, nlen = 0, len(body)
    while i < nlen:
        c = body[i]
        if c == "\\" and i + 1 < nlen:
            nxt = body[i + 1]
            if nxt in mapping:
                chunks.append(mapping[nxt])
                i += 2
                continue
            chunks.append(nxt)
            i += 2
            continue
        chunks.append(c)
        i += 1
    return "".join(chunks)


def line_number(content: str, idx: int) -> int:
    return content.count("\n", 0, idx) + 1


def quote_pot(s: str) -> str:
    return s.replace("\\", "\\\\").replace('"', '\\"')


def pot_multiline_parts(s: str) -> List[str]:
    if "\n" not in s:
        return [quote_pot(s)]
    return [quote_pot(part + "\n") for part in s.split("\n")]


def write_msg_simple(msgid: str) -> List[str]:
    lines: List[str] = []
    parts = pot_multiline_parts(msgid)
    if len(parts) == 1:
        lines.append(f'msgid "{parts[0]}"')
    else:
        lines.append('msgid ""')
        for p in parts:
            lines.append(f'"{p}"')
    lines.append('msgstr ""')
    return lines


def write_msg_ctxt(msgctxt: str, msgid: str) -> List[str]:
    lines: List[str] = [f'msgctxt "{quote_pot(msgctxt)}"']
    parts = pot_multiline_parts(msgid)
    if len(parts) == 1:
        lines.append(f'msgid "{parts[0]}"')
    else:
        lines.append('msgid ""')
        for p in parts:
            lines.append(f'"{p}"')
    lines.append('msgstr ""')
    return lines


@dataclass
class EntrySimple:
    msgid: str
    refs: List[str] = field(default_factory=list)


@dataclass
class EntryCtx:
    msgctxt: str
    msgid: str
    refs: List[str] = field(default_factory=list)


@dataclass
class EntryPlural:
    singular: str
    plural: str
    refs: List[str] = field(default_factory=list)


@dataclass
class EntryPluralCtx:
    singular: str
    plural: str
    msgctxt: str
    refs: List[str] = field(default_factory=list)


PotEntry = Union[EntrySimple, EntryCtx, EntryPlural, EntryPluralCtx]


def add_ref(ent: PotEntry, ref: str) -> None:
    if ref not in ent.refs:
        ent.refs.append(ref)


def process_file(repo_root: Path, php_path: Path, store: Dict[Tuple, PotEntry]) -> None:
    rel = php_path.relative_to(repo_root).as_posix()
    txt = php_path.read_text(encoding="utf-8", errors="surrogateescape")

    def bump(key: Tuple, factory: PotEntry, line: int) -> None:
        ref = f"#: {rel}:{line}"
        if key not in store:
            store[key] = factory
        else:
            add_ref(store[key], ref)
            return
        add_ref(store[key], ref)

    for m in _RE_SIMPLE.finditer(txt):
        mid = php_unescape_string(m.group(1))
        ln = line_number(txt, m.start())
        bump(("simple", mid), EntrySimple(msgid=mid), ln)

    for m in _RE_CTX.finditer(txt):
        msgid_v = php_unescape_string(m.group(1))
        ctx_v = php_unescape_string(m.group(2))
        ln = line_number(txt, m.start())
        bump(("ctx", ctx_v, msgid_v), EntryCtx(msgctxt=ctx_v, msgid=msgid_v), ln)

    for m in _RE_N.finditer(txt):
        sgl = php_unescape_string(m.group(1))
        plu = php_unescape_string(m.group(2))
        ln = line_number(txt, m.start())
        bump(("plural", sgl, plu), EntryPlural(singular=sgl, plural=plu), ln)

    for m in _RE_NX.finditer(txt):
        sgl = php_unescape_string(m.group(1))
        plu = php_unescape_string(m.group(2))
        cx = php_unescape_string(m.group(3))
        ln = line_number(txt, m.start())
        bump(
            ("plural_ctx", cx, sgl, plu),
            EntryPluralCtx(singular=sgl, plural=plu, msgctxt=cx),
            ln,
        )


def iter_php(repo_root: Path) -> Iterable[Path]:
    for dirpath, dirnames, filenames in os.walk(repo_root):
        dirnames[:] = [d for d in dirnames if d not in EXCLUDE_DIRS]
        for name in filenames:
            if name.endswith(".php"):
                yield Path(dirpath, name)


def build_pot(repo_root: Path, out_path: Path) -> str:
    repo_root = repo_root.resolve()
    store: Dict[Tuple, PotEntry] = {}

    out_path.parent.mkdir(parents=True, exist_ok=True)

    for php in sorted(iter_php(repo_root)):
        try:
            process_file(repo_root, php, store)
        except OSError as exc:
            print(f"Warning: skipped {php}: {exc}", file=sys.stderr)

    ts = datetime.now(timezone.utc).strftime("%Y-%m-%d %H:%M+0000")

    bom: List[str] = [
        "# Yacht Selector POT file.",
        "# Copyright (C) YEAR Yacht Selector Plugin contributors",
        "",
        'msgid ""',
        'msgstr ""',
        '"Project-Id-Version: Yacht Selector\\n"',
        '"Report-Msgid-Bugs-To:\\n"',
        f'"POT-Creation-Date: {quote_pot(ts)}\\n"',
        '"MIME-Version: 1.0\\n"',
        '"Content-Type: text/plain; charset=UTF-8\\n"',
        '"Content-Transfer-Encoding: 8bit\\n"',
        '"X-Generator: tools/make-pot.py\\n"',
        '"X-Domain: yacht-selector\\n"',
        "",
    ]

    plain: List[EntrySimple] = []
    ctxd: List[EntryCtx] = []
    plur: List[EntryPlural] = []
    plc: List[EntryPluralCtx] = []

    for ent in store.values():
        if isinstance(ent, EntrySimple):
            plain.append(ent)
        elif isinstance(ent, EntryCtx):
            ctxd.append(ent)
        elif isinstance(ent, EntryPlural):
            plur.append(ent)
        else:
            plc.append(ent)

    plain.sort(key=lambda e: (e.msgid.lower(), e.msgid))
    ctxd.sort(
        key=lambda e: (e.msgctxt.lower(), e.msgid.lower(), e.msgctxt, e.msgid)
    )
    plur.sort(
        key=lambda e: (e.singular.lower(), e.plural.lower(), e.singular, e.plural)
    )
    plc.sort(
        key=lambda e: (
            e.msgctxt.lower(),
            e.singular.lower(),
            e.plural.lower(),
            e.msgctxt,
            e.singular,
            e.plural,
        )
    )

    emit: List[str] = bom[:]

    def dump_refs(ent: PotEntry) -> None:
        for r in sorted(ent.refs):
            emit.append(r)

    for e in plain:
        dump_refs(e)
        emit.extend(write_msg_simple(e.msgid))
        emit.append("")

    for e in ctxd:
        dump_refs(e)
        emit.extend(write_msg_ctxt(e.msgctxt, e.msgid))
        emit.append("")

    for e in plur:
        dump_refs(e)
        sparts = pot_multiline_parts(e.singular)
        pparts = pot_multiline_parts(e.plural)

        emit.append(f'msgid "{sparts[0]}"' if len(sparts) == 1 else 'msgid ""')
        if len(sparts) > 1:
            for seg in sparts:
                emit.append(f'"{seg}"')

        if len(pparts) == 1:
            emit.append(f'msgid_plural "{pparts[0]}"')
        else:
            emit.append("msgid_plural \"\"")
            for seg in pparts:
                emit.append(f'"{seg}"')

        emit.append('msgstr[0] ""')
        emit.append('msgstr[1] ""')
        emit.append("")

    for e in plc:
        dump_refs(e)
        emit.append(f'msgctxt "{quote_pot(e.msgctxt)}"')
        sparts = pot_multiline_parts(e.singular)
        pparts = pot_multiline_parts(e.plural)

        if len(sparts) == 1:
            emit.append(f'msgid "{sparts[0]}"')
        else:
            emit.append('msgid ""')
            for seg in sparts:
                emit.append(f'"{seg}"')

        if len(pparts) == 1:
            emit.append(f'msgid_plural "{pparts[0]}"')
        else:
            emit.append('msgid_plural ""')
            for seg in pparts:
                emit.append(f'"{seg}"')

        emit.append('msgstr[0] ""')
        emit.append('msgstr[1] ""')
        emit.append("")

    text_out = "\n".join(emit).rstrip() + "\n"
    out_path.write_text(text_out, encoding="utf-8")
    return text_out


def main() -> int:
    script_dir = Path(__file__).resolve().parent
    repo_root = script_dir.parent
    pot_path = repo_root / "languages" / "yacht-selector.pot"
    print(f"POT repo root: {repo_root}")
    print(f"POT output:    {pot_path}")
    build_pot(repo_root, pot_path)
    print("Done.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
