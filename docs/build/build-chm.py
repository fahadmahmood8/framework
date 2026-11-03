#!/usr/bin/env python3
"""Build an offline HTML Help (.chm) from docs/*.md.

Usage: python3 docs/build/build-chm.py [outdir]
Needs: pip install markdown ; and `chmcmd` (apt: fp-utils) to compile.
Without chmcmd it still writes the .hhp/.hhc/.hhk + HTML project to outdir.
"""
import json, re, shutil, subprocess, sys, pathlib, html
import markdown

root = pathlib.Path(__file__).resolve().parents[1]          # docs/
out = pathlib.Path(sys.argv[1] if len(sys.argv) > 1 else 'chm-build').resolve()
man = json.loads((root / 'manifest.json').read_text(encoding='utf-8'))
if out.exists(): shutil.rmtree(out)
out.mkdir(parents=True)

CSS = """body{font:14px/1.6 Segoe UI,Arial,sans-serif;color:#3d3f41;margin:24px 32px;max-width:860px}
h1,h2,h3{font-family:Georgia,serif}a{color:#487020}code{background:#f7f8f4;padding:1px 4px;color:#c2475a}
pre{background:#f7f8f4;border:1px solid #dcdfd6;padding:12px;overflow:auto}pre code{background:none;color:inherit}
table{border-collapse:collapse}th,td{border:1px solid #dcdfd6;padding:6px 10px;text-align:left}"""
(out / 'style.css').write_text(CSS, encoding='utf-8')

ids = {p['file']: p['id'] for p in man['pages']}
idx_terms = []
for p in man['pages']:
    md = (root / p['file']).read_text(encoding='utf-8')
    body = markdown.markdown(md, extensions=['tables', 'fenced_code'])
    # .md cross links -> .html
    body = re.sub(r'href="([^"#]+)\.md(#[^"]*)?"', lambda m: 'href="%s.html%s"' % (ids.get(m.group(1) + '.md', m.group(1)), m.group(2) or ''), body)
    (out / (p['id'] + '.html')).write_text(
        '<!DOCTYPE html><html><head><meta charset="utf-8"><title>%s</title><link rel="stylesheet" href="style.css"></head><body>%s</body></html>'
        % (html.escape(p['title']), body), encoding='utf-8')
    for h in re.findall(r'<h2>(.*?)</h2>', body): idx_terms.append((re.sub('<[^>]+>', '', h), p['id'] + '.html'))
    for c in set(re.findall(r'<code>((?:gp_|GP::)[A-Za-z_]+)', body)): idx_terms.append((c, p['id'] + '.html'))

def li(name, url): return '<LI><OBJECT type="text/sitemap"><param name="Name" value="%s"><param name="Local" value="%s"></OBJECT>' % (html.escape(name, True), url)
toc = '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML//EN"><HTML><BODY><UL>' + ''.join(li(p['title'], p['id'] + '.html') for p in man['pages']) + '</UL></BODY></HTML>'
(out / 'toc.hhc').write_text(toc, encoding='utf-8')
idx = '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML//EN"><HTML><BODY><UL>' + ''.join(li(n, u) for n, u in sorted(set(idx_terms))) + '</UL></BODY></HTML>'
(out / 'index.hhk').write_text(idx, encoding='utf-8')
name = 'guava-pattern-docs'
files = ''.join(p['id'] + '.html\n' for p in man['pages']) + 'style.css\n'
(out / (name + '.hhp')).write_text(
    '[OPTIONS]\nCompatibility=1.1 or later\nCompiled file=%s.chm\nContents file=toc.hhc\nIndex file=index.hhk\n'
    'Default topic=%s.html\nDisplay compile progress=No\nLanguage=0x409 English (United States)\nTitle=Guava Pattern %s Documentation\n\n[FILES]\n%s'
    % (name, man['pages'][0]['id'], man['version'], files), encoding='utf-8')
print('project written to', out)
if shutil.which('chmcmd'):
    r = subprocess.run(['chmcmd', name + '.hhp'], cwd=out)
    print('chm:', out / (name + '.chm') if r.returncode == 0 else 'chmcmd failed')
else:
    print('chmcmd not found: install fp-utils (Linux) or open the .hhp in HTML Help Workshop (Windows).')
