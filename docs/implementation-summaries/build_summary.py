#!/usr/bin/env python3
"""Build a DILEEP MS implementation-summary .docx from _template.docx.

Usage: python3 build_summary.py spec.json [output.docx]

spec.json:
{
  "date": "2026-10-17",                      # date the changes took effect
  "fixes":    [["Issue", "Description", "✔ Fixed"], ...],   # optional section
  "features": [["Feature", "What it does", "Benefit"], ...], # optional section
  "screenshots": [{"file": "docs/implementation-summaries/screenshots/2026-10-23/x.png",
                   "caption": "What the picture shows"}, ...]    # optional, paths relative to the repo root
}
Header (DOLE letterhead + logos), footer, fonts, margins, bullet headings and the
grey-header bordered tables all come from _template.docx, so the look never drifts.
"""
import json, re, struct, sys, zipfile, datetime
from pathlib import Path
from xml.sax.saxutils import escape

HERE = Path(__file__).parent
TEMPLATE = HERE / "_template.docx"
ROOT = HERE.parent.parent
TEXT_WIDTH_EMU = int(6.5 * 914400)   # 8.5in page, 1in side margins

def run(text, bold=False, size=20, center=False, italic=False, unicode_font=False):
    b = '<w:b w:val="1"/><w:bCs w:val="1"/>' if bold else ''
    i = '<w:i w:val="1"/><w:iCs w:val="1"/>' if italic else ''
    f = ('<w:rFonts w:ascii="Arial Unicode MS" w:cs="Arial Unicode MS" '
         'w:eastAsia="Arial Unicode MS" w:hAnsi="Arial Unicode MS"/>') if unicode_font else ''
    rpr = f'{f}{b}{i}<w:sz w:val="{size}"/><w:szCs w:val="{size}"/>'
    return f'<w:r><w:rPr>{rpr}<w:rtl w:val="0"/></w:rPr><w:t xml:space="preserve">{escape(text)}</w:t></w:r>'

def cell(text, bold=False, center=False, unicode_font=False):
    jc = '<w:jc w:val="center"/>' if center else ''
    b = '<w:b w:val="1"/><w:bCs w:val="1"/>' if bold else ''
    return ('<w:tc><w:tcPr><w:vAlign w:val="center"/></w:tcPr><w:p><w:pPr><w:widowControl w:val="0"/>'
            f'<w:spacing w:line="240" w:lineRule="auto"/>{jc}<w:rPr>{b}<w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr></w:pPr>'
            f'{run(text, bold=bold, unicode_font=unicode_font)}</w:p></w:tc>')

def row(a, b, c, status_col):
    return ('<w:tr><w:trPr><w:cantSplit w:val="0"/><w:tblHeader w:val="0"/></w:trPr>'
            + cell(a, bold=status_col) + cell(b)
            + cell(c, bold=status_col, center=status_col, unicode_font=status_col) + '</w:tr>')

def empty_p():
    return '<w:p><w:pPr><w:rPr><w:b w:val="1"/><w:bCs w:val="1"/></w:rPr></w:pPr></w:p>'

def png_size(path):
    with open(path, "rb") as f:
        head = f.read(24)
    if head[:8] != b"\x89PNG\r\n\x1a\n":
        raise SystemExit(f"{path} is not a PNG")
    return struct.unpack(">II", head[16:24])

def find_shot(name):
    for base in (Path.cwd(), ROOT, HERE):
        p = (base / name)
        if p.is_file():
            return p
    raise SystemExit(f"screenshot not found: {name}")

def picture(rid, n, w_px, h_px):
    cx = TEXT_WIDTH_EMU
    cy = int(cx * h_px / w_px)
    return ('<w:p><w:pPr><w:keepNext w:val="0"/><w:jc w:val="center"/></w:pPr><w:r><w:drawing>'
            f'<wp:inline distT="0" distB="0" distL="0" distR="0"><wp:extent cx="{cx}" cy="{cy}"/>'
            f'<wp:docPr id="{100 + n}" name="Screenshot {n}"/>'
            '<wp:cNvGraphicFramePr><a:graphicFrameLocks noChangeAspect="1"/></wp:cNvGraphicFramePr>'
            '<a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:pic>'
            f'<pic:nvPicPr><pic:cNvPr id="{100 + n}" name="shot{n}.png"/><pic:cNvPicPr/></pic:nvPicPr>'
            f'<pic:blipFill><a:blip r:embed="{rid}"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill>'
            f'<pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="{cx}" cy="{cy}"/></a:xfrm>'
            '<a:prstGeom prst="rect"><a:avLst/></a:prstGeom><a:ln w="9525"><a:solidFill><a:srgbClr val="BFBFBF"/></a:solidFill></a:ln></pic:spPr>'
            '</pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing></w:r></w:p>')

def build(spec, out):
    z = zipfile.ZipFile(TEMPLATE)
    doc = z.read("word/document.xml").decode("utf8")
    head = doc[:doc.index("<w:body>") + 8]
    sect = doc[doc.rindex("<w:sectPr>"):]
    bullet = re.search(r'<w:p [^>]*>(?:(?!</w:p>).)*?<w:numPr>.*?</w:p>', doc, re.S).group(0)
    tables = re.findall(r'<w:tbl>.*?</w:tbl>', doc, re.S)  # [fixes, features]

    def table_xml(tpl, rows, status_col):
        pre = tpl[:tpl.index("<w:tr>")]
        first = tpl[tpl.index("<w:tr>"):tpl.index("</w:tr>") + 7]  # grey header row, verbatim
        return pre + first + "".join(row(*r, status_col) for r in rows) + "</w:tbl>"

    d = datetime.date.fromisoformat(spec["date"])
    title = f"DILEEP MONITORING SYSTEM UPDATES & IMPROVEMENTS ({d:%m-%d-%y})"
    dline = f"Date of implementation: {d:%B} {d.day}, {d.year}"
    body = [empty_p(),
            f'<w:p><w:pPr><w:jc w:val="center"/><w:rPr><w:b w:val="1"/><w:bCs w:val="1"/><w:sz w:val="26"/><w:szCs w:val="26"/></w:rPr></w:pPr>{run(title, True, 26)}</w:p>',
            f'<w:p><w:pPr><w:jc w:val="center"/></w:pPr>{run(dline, False, 20, italic=True)}</w:p>',
            empty_p()]
    for key, label, tpl, sc in (("fixes", "System Fixes & Improvements", tables[0], True),
                                ("features", "New Features", tables[1], False)):
        if spec.get(key):
            h = re.sub(r'<w:t xml:space="preserve">.*?</w:t>', f'<w:t xml:space="preserve">{escape(label)}</w:t>', bullet)
            body += [h, empty_p(), table_xml(tpl, spec[key], sc), empty_p()]
    media, rels = {}, ""
    if spec.get("screenshots"):
        h = re.sub(r'<w:t xml:space="preserve">.*?</w:t>', '<w:t xml:space="preserve">Screenshots</w:t>', bullet)
        body += [h, empty_p()]
        for n, shot in enumerate(spec["screenshots"], start=1):
            src = find_shot(shot["file"])
            w_px, h_px = png_size(src)
            rid = f"rId{100 + n}"
            media[f"word/media/shot{n}.png"] = src.read_bytes()
            rels += (f'<Relationship Id="{rid}" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" '
                     f'Target="media/shot{n}.png"/>')
            cap = f'<w:p><w:pPr><w:keepNext w:val="1"/><w:jc w:val="center"/></w:pPr>{run(shot["caption"], False, 20, italic=True)}</w:p>'
            body += [cap, picture(rid, n, w_px, h_px), empty_p()]
    xml = head + "".join(body) + sect
    with zipfile.ZipFile(out, "w", zipfile.ZIP_DEFLATED) as o:
        for item in z.infolist():
            data = z.read(item.filename)
            if item.filename == "word/document.xml":
                data = xml.encode("utf8")
            elif item.filename == "word/_rels/document.xml.rels" and rels:
                data = data.decode("utf8").replace("</Relationships>", rels + "</Relationships>").encode("utf8")
            o.writestr(item, data)
        for name, data in media.items():
            o.writestr(name, data)

if __name__ == "__main__":
    spec = json.load(open(sys.argv[1], encoding="utf8"))
    d = datetime.date.fromisoformat(spec["date"])
    out = sys.argv[2] if len(sys.argv) > 2 else str(HERE / f"DILEEPSYS-Updates-and-Improvements-{d:%m-%d-%y}.docx")
    build(spec, out); print(out)
