#!/usr/bin/env python3
"""Package the normal clinic site and its referenced assets, excluding originals."""

import argparse
import hashlib
import json
from html.parser import HTMLParser
from pathlib import Path
import re
import shutil
import subprocess
from urllib.parse import unquote, urlsplit
from zipfile import ZIP_DEFLATED, ZipFile


ROOT = Path(__file__).resolve().parents[1]
parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument("--production", action="store_true")
parser.add_argument("--output", type=Path,
                    default=Path("output/sakura-migration-20261015"))
args = parser.parse_args()
DEST = (ROOT / args.output).resolve()
if not DEST.is_relative_to(ROOT / "output"):
    parser.error("The output directory must be inside this project's output directory.")
PUBLIC = DEST / "www"
CANONICAL = "https://www.okubo-naika-cl.com/"
PAGES = ["index.html", "access.html", "doctor.html", "guide.html",
         "emergency.html", "endoscopy.html", "pylori.html"]


class References(HTMLParser):
    def __init__(self):
        super().__init__()
        self.refs = []

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        for key in ("src", "href", "poster"):
            if attrs.get(key):
                self.refs.append(attrs[key])
        if attrs.get("srcset"):
            self.refs.extend(part.strip().split()[0]
                             for part in attrs["srcset"].split(","))


def local_path(ref, source):
    parts = urlsplit(ref)
    if parts.scheme or parts.netloc or not parts.path:
        return None
    path = (ROOT / unquote(parts.path).lstrip("/") if parts.path.startswith("/")
            else source.parent / unquote(parts.path)).resolve()
    if not path.is_relative_to(ROOT):
        raise ValueError(f"Reference outside project: {source.name}: {ref}")
    if path.is_dir():
        path /= "index.html"
    if not path.is_file():
        raise FileNotFoundError(f"Missing reference: {source.name}: {ref}")
    return path


queue = [ROOT / page for page in PAGES]
if args.production:
    queue += [ROOT / "published-content.php", ROOT / "published-content.json"]
    queue += list((ROOT / "cms").rglob("*.php")) + list((ROOT / "cms").glob("*.js")) + list((ROOT / "cms").glob("*.css")) + list((ROOT / "cms").rglob(".htaccess"))
files = set()
while queue:
    source = queue.pop()
    if source in files:
        continue
    files.add(source)
    if source.is_relative_to(ROOT / "cms"):
        continue
    if source.suffix not in (".html", ".css", ".js"):
        continue
    content = source.read_text()
    refs = []
    if source.suffix == ".html":
        parser = References()
        parser.feed(content)
        refs.extend(parser.refs)
    refs.extend(re.findall(r"url\(\s*['\"]?([^'\")]+)", content))
    if source.suffix == ".js":
        refs.extend(re.findall(r"['\"]((?:\./)?assets/[^'\"]+)['\"]", content))
    for ref in refs:
        dependency = local_path(ref, source)
        if dependency:
            queue.append(dependency)

PUBLIC.mkdir(parents=True, exist_ok=True)
manifest = []


def record(target):
    data = target.read_bytes()
    manifest.append({"path": target.relative_to(PUBLIC).as_posix(), "bytes": len(data),
                     "sha256": hashlib.sha256(data).hexdigest()})


for source in sorted(files):
    rel = source.relative_to(ROOT)
    if source.is_relative_to(ROOT / "cms"):
        rel = Path("admin") / source.relative_to(ROOT / "cms")
    target = PUBLIC / rel
    target.parent.mkdir(parents=True, exist_ok=True)
    shutil.copyfile(source, target)
    if args.production and target.suffix == ".html":
        html = target.read_text()
        html = html.replace('src="./review-data.js"', 'src="./review-data.js?v=20261020-2"')
        html = re.sub(r'\s*<meta\s+name="robots"\s+content="noindex,nofollow"\s*/>',
                      "", html)
        url = CANONICAL + ("" if rel.name == "index.html" else rel.as_posix())
        html = html.replace("</head>", f'  <link rel="canonical" href="{url}" />\n  </head>')
        target.write_text(html)
    record(target)

if args.production:
    # Previews use the exact production template/assets with the private browser draft.
    for page in ("index.html", "guide.html"):
        html = (PUBLIC / page).read_text()
        html = html.replace('"./', '"/')
        html = html.replace('src="/review-store.js"', 'src="/admin/preview-store.js"')
        gate = "<?php require dirname(__DIR__) . '/lib.php'; okubo_cms_session(); okubo_cms_require_login(); ?>\n"
        target = PUBLIC / "admin" / "preview" / page.replace(".html", ".php")
        target.parent.mkdir(parents=True, exist_ok=True)
        target.write_text(gate + html)
        record(target)
    legacy = {"original.html": "access.html", "original2.html": "doctor.html",
              "original3.html": "guide.html", "original7.html": "emergency.html",
              "original8.html": "endoscopy.html", "original9.html": "pylori.html"}
    rules = ["Options -Indexes", "", "<IfModule mod_setenvif.c>",
             'SetEnvIfNoCase Host "^bantex-okubo\\.sakura\\.ne\\.jp(?::[0-9]+)?$" okubo_staging',
             "</IfModule>", "<IfModule mod_headers.c>",
             'Header set X-Robots-Tag "noindex, nofollow" env=okubo_staging',
             "</IfModule>", "", "RewriteEngine On",
             "# Deployment archives are retained locally and are not public downloads.",
             r"RewriteRule ^okubo-(?:normal-site|cms-install)\.zip$ - [F,L]", "",
             "# Preserve the old clinic page addresses when the official domain is used."]
    for old, new in {**legacy, "index.html": ""}.items():
        rules += [r"RewriteCond %{HTTP_HOST} ^(?:www\.)?okubo-naika-cl\.com$ [NC]",
                  f"RewriteRule ^{re.escape(old)}$ {CANONICAL}{new} [R=301,L]"]
    rules += ["", "# Canonical official hostname and HTTPS; staging remains independent.",
              r"RewriteCond %{HTTP_HOST} ^(?:www\.)?okubo-naika-cl\.com$ [NC]",
              "RewriteCond %{HTTPS} !=on [OR]",
              r"RewriteCond %{HTTP_HOST} !^www\.okubo-naika-cl\.com$ [NC]",
              "RewriteRule ^ " + CANONICAL.rstrip("/") + "%{REQUEST_URI} [R=301,L,NE]", "",
              "# Serve only approved content through the read-only publication bridge.",
              r"RewriteRule ^review-data\.js$ published-content.php [L]", ""]
    (PUBLIC / ".htaccess").write_text("\n".join(rules))
    (PUBLIC / "robots.txt").write_text("User-agent: *\nAllow: /\n\nSitemap: "
                                       + CANONICAL + "sitemap.xml\n")
    urls = [CANONICAL + ("" if page == "index.html" else page) for page in PAGES]
    (PUBLIC / "sitemap.xml").write_text(
        '<?xml version="1.0" encoding="UTF-8"?>\n'
        '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n'
        + "".join(f"  <url><loc>{url}</loc></url>\n" for url in urls) + "</urlset>\n")
    for generated in (".htaccess", "robots.txt", "sitemap.xml"):
        record(PUBLIC / generated)

archive = DEST / "okubo-normal-site.zip"
with ZipFile(archive, "w", compression=ZIP_DEFLATED) as zipped:
    for entry in manifest:
        zipped.write(PUBLIC / entry["path"], entry["path"])

(DEST / "manifest.json").write_text(json.dumps({
    "source_head": subprocess.check_output(
        ["git", "rev-parse", "HEAD"], cwd=ROOT, text=True).strip(),
    "site_mode": "production" if args.production else "staging-noindex-preserved",
    "files": manifest,
}, ensure_ascii=False, indent=2) + "\n")
print(json.dumps({"files": len(manifest), "bytes": sum(e["bytes"] for e in manifest),
                  "archive": str(archive), "archive_bytes": archive.stat().st_size},
                 ensure_ascii=False))
