# Bundled fonts

## `NotoSansCJK.ttf`

Noto Sans CJK (Simplified Chinese face), subsetted to the common CJK range. The
report's `Bahasa Mandarin` pages contain Chinese, and dompdf's built-in font
stack has no CJK coverage, so without this the characters export as empty boxes.

Regenerate from the system package (`fonts-noto-cjk`) with:

```sh
python3 - << 'PY'
from fontTools.ttLib import TTCollection
ttc = TTCollection('/usr/share/fonts/noto-cjk/NotoSansCJK-Regular.ttc')
face = next(f for f in ttc.fonts if 'SC' in f['name'].getDebugName(1))
face.save('/tmp/NotoSansCJK-Regular.ttf')
PY

python3 -c "
ranges = [(0x3000,0x303F),(0x3040,0x30FF),(0x3400,0x4DBF),(0x4E00,0x9FFF),(0xF900,0xFAFF),(0xFE30,0xFE4F),(0xFF00,0xFFEF)]
open('/tmp/cjk.txt','w').write(''.join(chr(c) for lo,hi in ranges for c in range(lo,hi+1)))
"
pyftsubset /tmp/NotoSansCJK-Regular.ttf --text-file=/tmp/cjk.txt \
  --output-file=storage/fonts/NotoSansCJK.ttf \
  --layout-features='' --no-hinting
```

Leave the flags at that: `--desubroutinize` and `--drop-tables+=DSIG` break the
font for dompdf (see below).

The result is ~7 MB covering CJK Unified Ideographs and its common neighbours, so
new Chinese text keeps rendering without regenerating the subset.

## Why the font is left subsetted by hand

dompdf's built-in font subsetter (`enable_font_subsetting`) **corrupts this font**:
the embedded file is rejected by viewers and the glyphs come out shifted and
wrong. Subsetting must therefore stay **off**, which makes dompdf embed the whole
file and sizes every exported report at roughly 12 MB.

Hand-subsetting with fonttools is what keeps that number down, and the subset must
be plain — adding `--desubroutinize` or `--drop-tables+=DSIG` reintroduces the
corruption even with dompdf's subsetter off.

Accepted trade-off: exported PDFs are large, but the Chinese renders correctly.
Switching the export to a Chromium-based renderer would subset properly and shrink
the files, at the cost of a headless-browser dependency.
