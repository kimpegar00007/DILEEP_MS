#!/usr/bin/env python3
"""Make the Google Docs batchUpdate requests that turn a COPY of the reference Google Doc into a new summary.

Drive workflow (Claude runs it with the Google Drive / Google Docs tools after build_summary.py):
  1. copy_file  <reference doc id>  title "DILEEPSYS UPDATES & IMPROVEMENTS (MM-DD-YY)"   (same folder as the reference)
  2. read_doc   <copy id>           -> save the JSON the tool writes to disk
  3. python3 drive_requests.py rows <readdoc.json> <spec.json>   -> update_doc <copy id> with these requests (adds table rows)
  4. read_doc   <copy id> again     -> new JSON
  5. python3 drive_requests.py fill <readdoc.json> <spec.json>   -> update_doc <copy id> with these requests (title, date, cell text)
Copying keeps the DOLE header/footer/logos and every style exactly, so the Drive file matches the .docx.
"""
import json, sys, datetime

def load(path):
    d = json.load(open(path, encoding="utf8"))
    d = d.get("content", d)
    return d["tabs"][0]["documentTab"]["body"]["content"]

def tables(body):
    return [e for e in body if "table" in e]

def rows_pass(body, spec):
    t = tables(body); reqs = []
    # last table first so earlier table indices stay valid
    for tbl, key in reversed(list(zip(t, ("fixes", "features")))):
        need = len(spec.get(key, [])) + 1 - tbl["table"]["rows"]
        if need < 0 and spec.get(key):
            # the copy has more rows than the spec: delete the surplus from the bottom up
            for r in range(tbl["table"]["rows"] - 1, len(spec[key]), -1):
                reqs.append({"deleteTableRow": {"tableCellLocation": {
                    "tableStartLocation": {"index": tbl["startIndex"]}, "rowIndex": r, "columnIndex": 0}}})
        for _ in range(max(need, 0)):
            reqs.append({"insertTableRow": {"tableCellLocation": {
                "tableStartLocation": {"index": tbl["startIndex"]},
                "rowIndex": tbl["table"]["rows"] - 1, "columnIndex": 0}, "insertBelow": True}})
    return reqs

def cell_style(key, col):
    bold = key == "fixes" and col in (0, 2)
    return {"bold": bold, "fontSize": {"magnitude": 10, "unit": "PT"}}

def fill_pass(body, spec):
    d = datetime.date.fromisoformat(spec["date"])
    jobs = []  # (start, requests) applied from the highest index down
    for tbl, key in zip(tables(body), ("fixes", "features")):
        data = spec.get(key, [])
        for r, row in enumerate(data, start=1):
            cells = tbl["table"]["tableRows"][r]["tableCells"]
            for c, text in enumerate(row):
                p = cells[c]["content"][0]
                start, end = p["startIndex"], p["endIndex"]
                reqs = []
                if end - start > 1:
                    reqs.append({"deleteContentRange": {"range": {"startIndex": start, "endIndex": end - 1}}})
                reqs.append({"insertText": {"location": {"index": start}, "text": text}})
                reqs.append({"updateTextStyle": {"range": {"startIndex": start, "endIndex": start + len(text)},
                             "textStyle": cell_style(key, c), "fields": "bold,fontSize"}})
                if key == "fixes" and c == 2:
                    reqs.append({"updateParagraphStyle": {"range": {"startIndex": start, "endIndex": start + len(text)},
                                 "paragraphStyle": {"alignment": "CENTER"}, "fields": "alignment"}})
                jobs.append((start, reqs))
    reqs = [q for _, rs in sorted(jobs, key=lambda j: -j[0]) for q in rs]
    # A section the spec leaves out (no fixes, or no features) is removed: its heading paragraph and table.
    # These sit below the title and above the cells already edited, so they go after the cell edits, last table first.
    for key in reversed(("fixes", "features")):
        if spec.get(key):
            continue
        for i, e in enumerate(body):
            if "table" in e and e is tables(body)[("fixes", "features").index(key)]:
                j = i - 1   # step back over blank paragraphs to the heading itself
                while j > 0 and not "".join(x.get("textRun", {}).get("content", "")
                                            for x in body[j].get("paragraph", {}).get("elements", [])).strip():
                    j -= 1
                heading = body[j]
                reqs.append({"deleteContentRange": {"range": {"startIndex": heading["startIndex"], "endIndex": e["endIndex"]}}})
    # title + date line come first in the document, so they go last
    title_old = "DILEEP MONITORING SYSTEM UPDATES & IMPROVEMENTS ("
    for e in body:
        txt = "".join(x.get("textRun", {}).get("content", "") for x in e.get("paragraph", {}).get("elements", []))
        if txt.startswith(title_old):
            s, en = e["startIndex"], e["endIndex"]
            date_line = f"Date of implementation: {d:%B} {d.day}, {d.year}"
            title = f"DILEEP MONITORING SYSTEM UPDATES & IMPROVEMENTS ({d:%m-%d-%y})"
            reqs += [
                {"deleteContentRange": {"range": {"startIndex": s, "endIndex": en - 1}}},
                {"insertText": {"location": {"index": s}, "text": title + "\n" + date_line}},
                {"updateTextStyle": {"range": {"startIndex": s + len(title) + 1, "endIndex": s + len(title) + 1 + len(date_line)},
                    "textStyle": {"bold": False, "italic": True, "fontSize": {"magnitude": 10, "unit": "PT"}},
                    "fields": "bold,italic,fontSize"}}]
            break
    return reqs

if __name__ == "__main__":
    mode, doc, spec = sys.argv[1:4]
    body, spec = load(doc), json.load(open(spec, encoding="utf8"))
    out = rows_pass(body, spec) if mode == "rows" else fill_pass(body, spec)
    json.dump(out, sys.stdout, ensure_ascii=False, separators=(",", ":"))
