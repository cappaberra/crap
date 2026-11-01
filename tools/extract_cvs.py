#!/usr/bin/env python3
"""Extract full trunk HEAD texts from CVS/RCS archives, preserving binary bytes."""
import pathlib, re, sys
source, dest = map(pathlib.Path, sys.argv[1:3])
def string(data, start):
    assert data[start:start+1] == b'@'
    out = bytearray(); i = start + 1
    while i < len(data):
        if data[i] == 64:
            if data[i:i+2] == b'@@': out.append(64); i += 2; continue
            return bytes(out), i + 1
        out.append(data[i]); i += 1
    raise ValueError('Unterminated RCS string')
for path in sorted(source.rglob('*,v')):
    data = path.read_bytes()
    head = re.match(rb'head\s+([\d.]+);', data).group(1)
    state = re.search(rb'\n' + re.escape(head) + rb'\s+date\s+[^;]+;\s*author\s+[^;]+;\s*state\s+(\w+);', data).group(1)
    if state == b'dead': continue
    marker = re.search(rb'\ndesc\s*\n', data)
    _, pos = string(data, data.index(b'@', marker.end()))
    rev = re.match(rb'\s*([\d.]+)\s+log\s*', data[pos:])
    assert rev.group(1) == head, path
    _, pos = string(data, pos + rev.end())
    text = re.match(rb'\s*text\s*', data[pos:])
    content, _ = string(data, pos + text.end())
    target = dest / str(path.relative_to(source))[:-2]
    target.parent.mkdir(parents=True, exist_ok=True)
    target.write_bytes(content)
    print(path.name, head.decode(), len(content))
