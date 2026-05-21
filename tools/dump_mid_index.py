from pathlib import Path


def PF(t):
    t = t.lstrip()
    assert t.startswith('"')
    i = 1
    o = []
    n = len(t)
    while i < n:
        c = t[i]
        if c == "\\" and i + 1 < n:
            nxt = t[i + 1]
            if nxt == "n":
                o.append("\n")
            elif nxt == "t":
                o.append("\t")
            elif nxt == "r":
                o.append("\r")
            elif nxt == '"':
                o.append('"')
            elif nxt == "\\":
                o.append("\\")
            else:
                o.append(nxt)
            i += 2
            continue
        if c == '"':
            return "".join(o)
        o.append(c)
        i += 1
    raise ValueError()


def mids(text):
    rows, refs, hb = [], [], False
    for ln in text.splitlines():
        s = ln.rstrip("\n")
        if hb:
            if not s.strip():
                hb = False
            continue
        if s.startswith("#:"):
            refs.extend(x for x in s[2:].strip().split() if x)
            continue
        if s.startswith("msgid"):
            tail = s[len("msgid") :].strip()
            if tail.startswith('""'):
                refs.clear()
                hb = True
                continue
            rows.append(PF(tail))
            refs.clear()
    return rows


def main():
    ROOT = Path(__file__).resolve().parents[1]
    mlist = mids(ROOT.joinpath("languages/yacht-selector.pot").read_text(encoding="utf-8"))
    ROOT.joinpath("languages/.mid_index.tsv").write_text(
        "\n".join(f"{i+1}\t{r}" for i, r in enumerate(mlist)),
        encoding="utf-8",
    )
    print(len(mlist))


if __name__ == "__main__":
    main()
