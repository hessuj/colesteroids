#!/usr/bin/env python3
"""
Colesteroids OG+ – sivuston tarkistukset ennen julkaisua.

Ajetaan GitHub Actionsissa ennen FTP-julkaisua ja voi ajaa myös omalla koneella:
    python3 tools/check_site.py

Tarkistukset:
  1. HTML-rakenne: sulkemattomat ja väärin suljetut tagit, päällekkäiset id:t
  2. JavaScript: syntaksi (node --check) ja että koodin $("id")-viittaukset löytyvät HTML:stä
  3. Kielet: suomen ja englannin sanastoissa samat avaimet, kaikki käytetyt avaimet olemassa
  4. Kentät: 50 kenttää, oikea koko, seinät, vapaa aloituspaikka, nimet molemmilla kielillä
  5. PHP: syntaksi (php -l)

Käyttää vain Pythonin vakiokirjastoa, Nodea ja PHP:tä (valmiina GitHubin ubuntu-palvelimilla).
Virheet tulostetaan GitHubin annotaatiomuodossa, jolloin ne näkyvät suoraan Actions-näkymässä.
MIT License, (c) 2026 Hessu Järvinen
"""
from __future__ import annotations

import json
import re
import shutil
import subprocess
import sys
import tempfile
from html.parser import HTMLParser
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
WEB = ROOT / "web"
INDEX = WEB / "index.html"

errors: list[str] = []
notes: list[str] = []


def error(msg: str, file: Path | None = None, line: int | None = None) -> None:
    loc = ""
    if file is not None:
        loc = f" file={file.relative_to(ROOT)}" + (f",line={line}" if line else "")
    errors.append(f"::error{loc}::{msg}")


def ok(msg: str) -> None:
    notes.append(f"  ✓ {msg}")


# ---------- 1. HTML-rakenne ----------
VOID = {"area", "base", "br", "col", "embed", "hr", "img", "input", "link", "meta", "source", "track", "wbr", "path", "circle", "rect"}
# SVG:n itsestään sulkeutuvat tagit käsitellään handle_startendtagissa


class Checker(HTMLParser):
    def __init__(self) -> None:
        super().__init__(convert_charrefs=True)
        self.stack: list[tuple[str, int]] = []
        self.ids: dict[str, int] = {}

    def handle_starttag(self, tag, attrs):
        line = self.getpos()[0]
        for k, v in attrs:
            if k == "id" and v:
                if v in self.ids:
                    error(f'Päällekkäinen id="{v}" (myös rivillä {self.ids[v]})', INDEX, line)
                self.ids[v] = line
        if tag not in VOID:
            self.stack.append((tag, line))

    def handle_startendtag(self, tag, attrs):
        line = self.getpos()[0]
        for k, v in attrs:
            if k == "id" and v:
                if v in self.ids:
                    error(f'Päällekkäinen id="{v}" (myös rivillä {self.ids[v]})', INDEX, line)
                self.ids[v] = line

    def handle_endtag(self, tag):
        line = self.getpos()[0]
        if tag in VOID:
            return
        if not self.stack:
            error(f"Ylimääräinen sulkeva tagi </{tag}>", INDEX, line)
            return
        if self.stack[-1][0] == tag:
            self.stack.pop()
            return
        # etsi lähin vastaava avaus; välissä olevat jäivät sulkematta
        for i in range(len(self.stack) - 1, -1, -1):
            if self.stack[i][0] == tag:
                for t, l in self.stack[i + 1:]:
                    error(f"Tagi <{t}> jäi sulkematta ennen </{tag}>", INDEX, l)
                del self.stack[i:]
                return
        error(f"Sulkeva tagi </{tag}> ilman avausta", INDEX, line)


html = INDEX.read_text(encoding="utf-8")
parser = Checker()
parser.feed(html)
parser.close()
for t, l in parser.stack:
    if t not in ("html", "body", "head"):
        error(f"Tagi <{t}> jäi sulkematta", INDEX, l)
html_ids = set(parser.ids)
ok(f"HTML-rakenne tarkistettu ({len(html_ids)} id:tä)")

# ---------- 2. JavaScript ----------
scripts = re.findall(r"<script(?![^>]*\bsrc=)[^>]*>(.*?)</script>", html, flags=re.S)
main_js = max(scripts, key=len) if scripts else ""
node = shutil.which("node")
if not main_js:
    error("Pelin skriptiä ei löytynyt index.html:stä", INDEX)
elif node:
    with tempfile.NamedTemporaryFile("w", suffix=".js", delete=False, encoding="utf-8") as f:
        f.write(main_js)
        tmp = f.name
    r = subprocess.run([node, "--check", tmp], capture_output=True, text=True)
    if r.returncode != 0:
        # näytä node:n virhe ja yritä kohdistaa rivi index.html:ään
        m = re.search(r":(\d+)\n", r.stderr)
        offset = html[: html.find(main_js)].count("\n")
        msg = next((l for l in r.stderr.splitlines() if "Error" in l), r.stderr.strip().splitlines()[0])
        error("JavaScript-syntaksivirhe: " + msg.strip(),
              INDEX, (int(m.group(1)) + offset) if m else None)
    else:
        ok("JavaScriptin syntaksi kunnossa (node --check)")
else:
    error("Node ei ole asennettuna, JavaScriptiä ei voitu tarkistaa")

js_ids = set(re.findall(r'\$\("([A-Za-z][\w-]*)"\)', main_js)) | set(re.findall(r'getElementById\("([\w-]+)"\)', main_js))
missing_ids = sorted(js_ids - html_ids)
for i in missing_ids:
    error(f'Koodi viittaa elementtiin $("{i}"), jota ei ole HTML:ssä', INDEX)
if not missing_ids:
    ok(f"Kaikki {len(js_ids)} koodin elementtiviittausta löytyvät HTML:stä")

# ---------- 3. Kielet ----------
def lang_keys(lang: str) -> set[str]:
    m = re.search(rf"\n    {lang}: \{{\n(.*?)\n    \}}", main_js, flags=re.S)
    if not m:
        error(f"Kielen '{lang}' sanastoa ei löytynyt I18N-oliosta", INDEX)
        return set()
    return set(re.findall(r"^\s+(\w+): ", m.group(1), flags=re.M))


fi_keys, en_keys = lang_keys("fi"), lang_keys("en")
for k in sorted(fi_keys - en_keys):
    error(f"Käännös puuttuu englanniksi: {k}", INDEX)
for k in sorted(en_keys - fi_keys):
    error(f"Avain on englanniksi mutta ei suomeksi: {k}", INDEX)
used = set(re.findall(r'data-i18n(?:-html|-aria|-title)?="(\w+)"', html))
used |= set(re.findall(r'\bt\("(\w+)"', main_js))
help_block = re.search(r"const HELP = \[(.*?)\];", main_js, flags=re.S)
if help_block:
    used |= set(re.findall(r'"(\w+)"', help_block.group(1)))
for k in sorted(used - fi_keys):
    error(f"Käytetty tekstiavain puuttuu sanastosta: {k}", INDEX)
if fi_keys and fi_keys == en_keys and used <= fi_keys:
    ok(f"Kielet täydelliset: {len(fi_keys)} avainta suomeksi ja englanniksi, {len(used)} käytössä")

# ---------- 4. Kentät ----------
W, H = 29, 22
maps = [("Alkuperäinen (KENTTA)", None)]
km = re.search(r"const KENTTA = (\[.*?\]);", main_js, flags=re.S)
if km:
    maps[0] = ("KENTTA", json.loads(re.sub(r",\s*\]", "]", km.group(1))))
for name, raw in re.findall(r'\{ name: ("[^"]*"), map: (\[[^\]]*\]) \}', main_js):
    maps.append((json.loads(name), json.loads(raw)))
for name, rows in maps:
    if rows is None:
        error("Alkuperäistä kenttää (KENTTA) ei löytynyt", INDEX)
        continue
    if len(rows) != H or any(len(r) != W for r in rows):
        error(f"Kenttä '{name}': koko ei ole {W}×{H}", INDEX)
        continue
    if rows[H - 1] != "=" * W or any(rows[y][0] != "=" or rows[y][W - 1] != "=" for y in range(1, H - 1)):
        error(f"Kenttä '{name}': reunaseinät puuttuvat", INDEX)
    if any(rows[y][x] != " " for y in range(17, 21) for x in range(11, 18)):
        error(f"Kenttä '{name}': sydämen aloituspaikka ei ole vapaa", INDEX)
en_names = re.search(r"const LEVEL_NAMES = \{\s*en: (\[.*?\])", main_js, flags=re.S)
if en_names and len(json.loads(en_names.group(1))) != len(maps):
    error(f"Englanninkielisiä kenttänimiä {len(json.loads(en_names.group(1)))}, kenttiä {len(maps)}", INDEX)
if len(maps) >= 50:
    ok(f"{len(maps)} kenttää kunnossa, nimet molemmilla kielillä")
else:
    error(f"Kenttiä löytyi vain {len(maps)}", INDEX)

# ---------- 5. PHP ----------
php = shutil.which("php")
php_files = sorted(WEB.glob("*.php"))
if php:
    bad = 0
    for f in php_files:
        r = subprocess.run([php, "-l", str(f)], capture_output=True, text=True)
        if r.returncode != 0:
            bad += 1
            m = re.search(r"on line (\d+)", r.stdout + r.stderr)
            out = (r.stdout + r.stderr).strip().splitlines()
            msg = next((l for l in out if "error" in l.lower() and "Errors parsing" not in l), out[0])
            error("PHP-syntaksivirhe: " + re.sub(r" in /\S+", "", msg), f, int(m.group(1)) if m else None)
    if not bad:
        ok(f"PHP-syntaksi kunnossa ({len(php_files)} tiedostoa)")
else:
    error("PHP ei ole asennettuna, PHP-tiedostoja ei voitu tarkistaa")

# ---------- Tulos ----------
print("Colesteroids OG+ – tarkistukset")
print("\n".join(notes))
if errors:
    print("\n".join(errors))
    print(f"\n✗ {len(errors)} virhettä – julkaisu pysäytetään.")
    sys.exit(1)
print("\n✓ Kaikki tarkistukset läpi.")
