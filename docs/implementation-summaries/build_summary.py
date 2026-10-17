#!/usr/bin/env python3
"""Build a DILEEP MS implementation-summary .docx from _template.docx.

Usage: python3 build_summary.py spec.json [output.docx]

spec.json:
{
  "date": "2026-10-17",                      # date the changes took effect
  "fixes":    [["Issue", "Description", "✔ Fixed"], ...],   # optional section
  "features": [["Feature", "What it does", "Benefit"], ...] # optional section
}
Header (DOLE letterhead + logos), footer, fonts, margins, bullet headings and the
grey-header bordered tables all come from _template.docx, so the look never drifts.
"""
import json, re, sys, zipfile, datetime
from pathlib import Path
from xml.sax.saxutils import escape

HERE = Path(__file__).parent
TEMPLATE = HERE / "_template.docx"

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
    xml = head + "".join(body) + sect
    with zipfile.ZipFile(out, "w", zipfile.ZIP_DEFLATED) as o:
        for item in z.infolist():
            o.writestr(item, xml.encode("utf8") if item.filename == "word/document.xml" else z.read(item.filename))

if __name__ == "__main__":
    spec = json.load(open(sys.argv[1], encoding="utf8"))
    d = datetime.date.fromisoformat(spec["date"])
    out = sys.argv[2] if len(sys.argv) > 2 else str(HERE / f"DILEEPSYS-Updates-and-Improvements-{d:%m-%d-%y}.docx")
    build(spec, out); print(out)
