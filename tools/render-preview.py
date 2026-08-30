#!/usr/bin/env python3
"""
Render a static preview of the Zaaka block theme's front page.

The theme's patterns are real WordPress block markup: HTML wrapped in
<!-- wp:... --> comments. Stripping the comments leaves valid HTML that only
needs (a) the CSS custom properties theme.json would emit, (b) the handful of
core layout rules WordPress prints for groups/columns/grids/buttons, and
(c) the theme's own stylesheet. That is what this script assembles, so the
preview is a faithful render rather than a redrawn mock-up.
"""
import json
import re
import pathlib

ROOT = pathlib.Path(__file__).resolve().parent.parent
THEME = ROOT / "wp-content" / "themes" / "zaaka"
OUT = ROOT / "tools" / "out"
OUT.mkdir(parents=True, exist_ok=True)

tj = json.loads((THEME / "theme.json").read_text())

# ----------------------------------------------------------------- presets --
props = []
for c in tj["settings"]["color"]["palette"]:
    props.append(f'--wp--preset--color--{c["slug"]}: {c["color"]};')
for f in tj["settings"]["typography"]["fontFamilies"]:
    props.append(f'--wp--preset--font-family--{f["slug"]}: {f["fontFamily"]};')
for s in tj["settings"]["typography"]["fontSizes"]:
    fl = s.get("fluid")
    if isinstance(fl, dict):
        size = f'clamp({fl["min"]}, 0.75rem + 2.2vw, {fl["max"]})'
    else:
        size = s["size"]
    props.append(f'--wp--preset--font-size--{s["slug"]}: {size};')
for s in tj["settings"]["spacing"]["spacingSizes"]:
    props.append(f'--wp--preset--spacing--{s["slug"]}: {s["size"]};')

colour_classes = []
for c in tj["settings"]["color"]["palette"]:
    colour_classes.append(
        f'.has-{c["slug"]}-color{{color:var(--wp--preset--color--{c["slug"]})}}'
        f'.has-{c["slug"]}-background-color{{background-color:var(--wp--preset--color--{c["slug"]})}}'
    )
size_classes = [
    f'.has-{s["slug"]}-font-size{{font-size:var(--wp--preset--font-size--{s["slug"]})}}'
    for s in tj["settings"]["typography"]["fontSizes"]
]

CONTENT = tj["settings"]["layout"]["contentSize"]
WIDE = tj["settings"]["layout"]["wideSize"]

# ------------------------------------------------------------ core layout --
CORE_CSS = f"""
*,*::before,*::after{{box-sizing:border-box}}
html{{-webkit-text-size-adjust:100%}}
body{{margin:0;background:var(--wp--preset--color--paper);color:var(--wp--preset--color--ink);
 font-family:var(--wp--preset--font-family--sans);font-size:var(--wp--preset--font-size--md);
 line-height:1.6;letter-spacing:-.005em}}
img{{max-width:100%;height:auto;display:block}}

h1,h2,h3,h4,h5,h6{{font-family:var(--wp--preset--font-family--display);font-weight:400;
 line-height:1.12;letter-spacing:-.02em;color:var(--wp--preset--color--ink);margin:0 0 .5em}}
h1{{font-size:var(--wp--preset--font-size--4xl);line-height:1.04}}
h2{{font-size:var(--wp--preset--font-size--3xl)}}
h3{{font-size:var(--wp--preset--font-size--xl);line-height:1.2}}
h4{{font-family:var(--wp--preset--font-family--sans);font-size:var(--wp--preset--font-size--sm);
 font-weight:600;letter-spacing:.09em;text-transform:uppercase;color:var(--wp--preset--color--muted)}}
p{{margin:0 0 1.05rem}}
a{{color:var(--wp--preset--color--accent-deep)}}
strong{{font-weight:600;color:var(--wp--preset--color--ink)}}

/* constrained layout */
.wp-block-group,.wp-block-columns{{margin-left:auto;margin-right:auto}}
main > .wp-block-group,
body > .wp-block-group{{padding-left:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30)}}
.alignfull{{width:100%}}
.alignfull:not([class*="zk-l"]) > *{{max-width:{WIDE};margin-left:auto;margin-right:auto}}
.alignfull > .alignwide{{max-width:{WIDE}}}
main .wp-block-group:not(.alignfull):not([class*="zk-l"]) > *{{max-width:{CONTENT};margin-left:auto;margin-right:auto}}
main .wp-block-group:not(.alignfull) > .alignwide{{max-width:{WIDE}}}
.alignwide{{max-width:{WIDE}}}

/* flex + grid layouts WP emits */
.wp-block-columns{{display:flex;gap:var(--wp--preset--spacing--40);flex-wrap:wrap;align-items:flex-start}}
.wp-block-column{{flex:1 1 0;min-width:0}}
.wp-block-column[style*="flex-basis"]{{flex-grow:0}}
@media (max-width:781px){{
 .wp-block-columns{{flex-direction:column}}
 .wp-block-column[style*="flex-basis"]{{flex-basis:100%!important}}
}}
.wp-block-buttons{{display:flex;gap:var(--wp--preset--spacing--20);flex-wrap:wrap}}
.wp-block-button__link{{display:inline-block;background:var(--wp--preset--color--volt);
 color:var(--wp--preset--color--void);border-radius:999px;padding:.95rem 1.9rem;
 font-family:var(--wp--preset--font-family--sans);font-size:var(--wp--preset--font-size--sm);
 font-weight:700;text-decoration:none;border:1px solid transparent}}
.wp-block-button__link:hover{{background:var(--wp--preset--color--paper);color:var(--wp--preset--color--void)}}
.wp-block-separator{{border:0;border-top:1px solid currentColor;opacity:.35}}
ul.wp-block-list{{padding-left:1.15rem;margin:0 0 1rem}}
.wp-block-list li{{margin-bottom:.35rem}}
@media (max-width:781px){{ .zk-header .wp-block-navigation{{display:none}} .zk-header .zk-burger{{display:block}} }}
.zk-header .zk-burger{{display:none;width:24px;height:2px;background:#fff;box-shadow:0 7px 0 #fff,0 -7px 0 #fff}}
.zk-preview-grid{{display:grid;grid-template-columns:1fr 1fr;gap:var(--wp--preset--spacing--40);margin-top:var(--wp--preset--spacing--40)}}
.zk-preview-grid h3{{margin:.4rem 0 .4rem}}
.zk-thumb--uberfi{{background:#0B0E11 center/cover no-repeat url(uberfi-featured.png)!important}}
.zk-preview-grid h3 .zk-status{{vertical-align:middle;margin-left:.5rem}}
.zk-thumb{{aspect-ratio:16/10;background:linear-gradient(135deg,#E9E4DA,#D8D2C6);border:1px solid var(--wp--preset--color--line);margin-bottom:.9rem}}
"""

WORDMARK = (THEME / "assets" / "img" / "logo-wordmark.svg").read_text()

# --------------------------------------------------------------- assemble --
_layout_css = []
_n = [0]

def _inject(markup: str) -> str:
    """Emulate the per-block layout CSS WordPress generates server-side."""
    out, pos = [], 0
    pat = re.compile(r'<!--\s*wp:([a-z0-9-]+/[a-z0-9-]+|[a-z0-9-]+)\s*(\{.*?\})?\s*-->\s*(<[a-z0-9]+)',
                     re.S)
    for m in pat.finditer(markup):
        attrs = m.group(2)
        if not attrs:
            continue
        try:
            a = json.loads(attrs)
        except Exception:
            continue
        lay = a.get('layout') or {}
        gap = ((a.get('style') or {}).get('spacing') or {}).get('blockGap')
        rules = []
        if lay.get('type') == 'grid':
            if lay.get('minimumColumnWidth'):
                rules.append(f"display:grid;grid-template-columns:repeat(auto-fill,minmax({lay['minimumColumnWidth']},1fr))")
            else:
                cc = lay.get('columnCount', 3)
                rules.append(f"display:grid;grid-template-columns:repeat({cc},minmax(0,1fr))")
        elif lay.get('type') == 'flex':
            rules.append('display:flex;flex-wrap:' + ('wrap' if lay.get('flexWrap') != 'nowrap' else 'nowrap'))
            jc = {'space-between': 'space-between', 'right': 'flex-end', 'left': 'flex-start', 'center': 'center'}
            if lay.get('justifyContent'):
                rules.append('justify-content:' + jc.get(lay['justifyContent'], 'flex-start'))
            va = {'center': 'center', 'bottom': 'flex-end', 'top': 'flex-start'}
            if lay.get('verticalAlignment'):
                rules.append('align-items:' + va.get(lay['verticalAlignment'], 'stretch'))
        if gap:
            def px(v):
                return re.sub(r'var:preset\|spacing\|(\w+)', r'var(--wp--preset--spacing--\1)', v)
            if isinstance(gap, dict):
                top, left = px(gap.get('top', '1rem')), px(gap.get('left', gap.get('top', '1rem')))
                rules.append(f'gap:{top} {left}')
            else:
                rules.append('gap:' + px(gap))
        if rules and not gap:
            rules.append('gap:var(--wp--preset--spacing--30)')
        if not rules:
            continue
        _n[0] += 1
        cls = f'zk-l{_n[0]}'
        _layout_css.append('.' + cls + '{' + ';'.join(rules) + '}')
        tag_start = m.start(3)
        tag_end = markup.index('>', tag_start)
        tag = markup[tag_start:tag_end]
        if 'class="' in tag:
            tag = tag.replace('class="', f'class="{cls} ', 1)
        else:
            tag = tag + f' class="{cls}"'
        out.append(markup[pos:tag_start]); out.append(tag); pos = tag_end
    out.append(markup[pos:])
    return ''.join(out)


def _php_eval(markup: str) -> str:
    """Expand the tiny bit of PHP the marquee pattern uses."""
    if 'zaaka_run' in markup:
        items = ['Web apps','SaaS platforms','WordPress','Design systems','E-commerce',
                 'APIs &amp; integrations','Mobile','Payments','Dashboards','Automation',
                 'Brand &amp; identity','Technical SEO']
        run = ''.join('<span class="zk-marquee__item">%s</span>' % i for i in items)
        markup = re.sub(r'<\?php echo \$zaaka_run.*?\?>', run + run, markup, flags=re.S)
    return markup


def strip_blocks(markup: str) -> str:
    markup = re.sub(r"^<\?php.*?\?>\s*", "", markup, flags=re.S)
    markup = _inject(markup)
    markup = re.sub(r"<!--\s*/?wp:.*?-->", "", markup, flags=re.S)
    return markup

order = ["hero", "marquee", "flagship", "services", "work-grid",
         "sectors", "process", "cta"]
body = "".join(strip_blocks((THEME / "patterns" / f"{n}.php").read_text()) for n in order)

# The work grid is a query loop; with no posts WordPress renders the no-results
# branch, which is what a fresh install shows. Left as-is deliberately.

SAMPLE = """
<div class="zk-preview-grid">
 <article><div class="zk-thumb"></div><p class="zk-meta">Product · Platform</p><h3>Locare</h3><p>A white-label property management platform for rental agencies — leasing, trust accounting and maintenance in one system.</p></article>
 <article><div class="zk-thumb zk-thumb--uberfi"></div><p class="zk-meta">Product · Mobile · Engineering</p><h3>Uberfi</h3><p>A profit copilot for rideshare drivers — prices an incoming trip against real running costs and returns a keep-or-reject verdict inside the acceptance window.</p></article>
</div>
"""
body = re.sub(r'<div class="wp-block-query alignwide"[^>]*>.*?</div>\s*(?=</div>)', SAMPLE, body, flags=re.S, count=1)

footer = strip_blocks((THEME / "parts" / "footer.html").read_text())
footer = footer.replace('<!-- wp:pattern {"slug":"zaaka/logo-footer"} /-->', '')
footer = re.sub(r"^\s*$", "", footer)
footer = footer.replace("<div class=\"wp-block-column\" style=\"flex-basis:32%\">", '<div class="wp-block-column" style="flex-basis:32%"><span class="zk-logo zk-logo--footer">' + WORDMARK + "</span>", 1)

header = """
<header class="wp-block-group zk-header zk-dark" style="padding-top:var(--wp--preset--spacing--20);padding-bottom:var(--wp--preset--spacing--20)">
 <div class="wp-block-group alignwide" style="display:flex;align-items:center;justify-content:space-between;gap:2rem">
  <a class="zk-logo" href="/">""" + WORDMARK + """</a>
  <nav class="wp-block-navigation" style="display:flex;gap:var(--wp--preset--spacing--30);font-family:var(--wp--preset--font-family--sans);font-size:var(--wp--preset--font-size--sm);font-weight:500">
   <a href="/work/">Work</a><a href="/services/">Services</a><a href="/approach/">Approach</a><a href="/about/">About</a><a href="/contact/">Contact</a>
  </nav>
 </div>
</header>
"""

theme_css = (THEME / "assets" / "css" / "theme.css").read_text()
js = (THEME / "assets" / "js" / "zaaka.js").read_text()

html = f"""<!DOCTYPE html>
<html lang="en-ZA">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Zaaka — product &amp; web studio</title>
<style>
:root{{{''.join(props)}}}
{CORE_CSS}
{''.join(colour_classes)}
{''.join(size_classes)}

{theme_css}
{"".join(_layout_css)}
</style>
<script>document.documentElement.classList.add("zk-js");</script>
</head>
<body>
{header}
<main class="wp-block-group">
{body}
</main>
{footer}
<script>{js}</script>
</body>
</html>
"""

(OUT / "index.html").write_text(html)
print(f"preview written: {len(html)} bytes")
