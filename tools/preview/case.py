"""Static render of single-project.html with the real published content,
so the case-study CSS can be checked without a WordPress install."""
import json, pathlib, sys, re
import build as B   # reuses the token, colour and size class generation

THEME = pathlib.Path(__file__).resolve().parents[2] / "wp-content" / "themes" / "zaaka-studio"
HERE  = pathlib.Path(__file__).resolve().parent
DATA  = {p["slug"]: p for p in json.loads((HERE / "case-data.json").read_text())}
slug  = sys.argv[1] if len(sys.argv) > 1 else "locare"
proj  = DATA[slug]
other = "uberfi" if slug == "locare" else "locare"

TERMS  = {"locare": ["Platform", "Product"], "uberfi": ["Engineering", "Mobile", "Product"]}
STATUS = {"locare": "Live", "uberfi": ""}

def terms_html(s):
    sep = '<span class="wp-block-post-terms__separator">, </span>'
    return sep.join(f'<a href="#">{t}</a>' for t in TERMS[s])

status = STATUS[slug]
head_css = (
    ":root{" + "".join(B.props) + "}\n" + B.CORE_CSS + "\n"
    + "".join(B.colour_classes) + "".join(B.size_classes) + "\n"
    + (THEME / "assets" / "css" / "theme.css").read_text() + "\n"
    + "".join(B._layout_css)
)

html = f"""<!DOCTYPE html><html lang="en-ZA"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{proj['title']} — Zaaka</title>
<style>{head_css}
.zk-lcase{{display:flex;flex-wrap:wrap;align-items:center;gap:var(--wp--preset--spacing--20)}}
.zk-lnext{{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:var(--wp--preset--spacing--30)}}
.zk-case__body.is-layout-constrained > *{{max-width:720px;margin-left:auto;margin-right:auto}}
.zk-case .alignwide{{max-width:1180px;margin-left:auto;margin-right:auto}}
/* emulate useRootPaddingAwareAlignments: root padding moves off the wrapper and
   onto every child except the full-bleed ones, which carry their own. */
.zk-case{{padding-left:0;padding-right:0}}
.zk-case > :not(.alignfull){{padding-left:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30)}}
</style>
<script>document.documentElement.classList.add("zk-js");</script></head><body>
{B.header}
<main class="wp-block-group zk-case">
 <div class="wp-block-group alignfull zk-case__hero zk-dark has-global-padding" style="padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--70);padding-left:var(--wp--preset--spacing--30)">
  <div class="wp-block-group alignwide zk-case__eyebrow is-layout-flex zk-lcase">
   <div class="zk-meta has-xs-font-size wp-block-post-terms">{terms_html(slug)}</div>
   {'<p class="zk-status has-xs-font-size">' + status + '</p>' if status else ''}
  </div>
  <h1 class="wp-block-post-title alignwide zk-case__title has-huge-font-size">{proj['title']}</h1>
  <div class="wp-block-post-excerpt alignwide zk-case__lede has-xl-font-size"><p class="wp-block-post-excerpt__excerpt">{proj['excerpt']}</p></div>
 </div>
 <figure class="wp-block-post-featured-image alignwide zk-case__media"><img src="{slug}-featured.png" alt=""></figure>
 <div class="entry-content wp-block-post-content zk-case__body is-layout-constrained">{proj['content']}</div>
 <div class="wp-block-group alignwide zk-case__next is-layout-flex zk-lnext" style="margin-top:var(--wp--preset--spacing--60);padding-top:var(--wp--preset--spacing--50)">
  <h2 class="wp-block-heading has-xxl-font-size">Something like this to build?</h2>
  <div class="wp-block-buttons"><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/contact/">Start a project</a></div></div>
 </div>
 <div class="wp-block-group alignwide zk-work zk-case__more" style="margin-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
  <p class="is-style-eyebrow">Keep reading</p>
  <ul class="wp-block-post-template is-layout-flow">
   <li class="wp-block-post"><figure class="wp-block-post-featured-image"><a href="#"><img src="{other}-featured.png" alt=""></a></figure><div class="zk-meta has-xs-font-size wp-block-post-terms">{terms_html(other)}</div><h3 class="wp-block-post-title has-xl-font-size"><a href="#">{other.title()}</a></h3></li>
  </ul>
 </div>
</main>
{B.footer}
</body></html>"""

(B.OUT / f"case-{slug}.html").write_text(html)
print(f"out/case-{slug}.html written: {len(html)} bytes")
