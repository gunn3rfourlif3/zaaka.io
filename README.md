# zaaka.io

The Zaaka website: a WordPress block theme, the brand assets it is built from,
and the portfolio content that seeds it.

```
wp-content/themes/zaaka-studio/   The theme — templates, patterns, theme.json, assets
brand/                     Logo and favicon source files
content/                   Portfolio import (WordPress WXR)
tools/                     Static preview renderer
```

## Getting it running

1. Copy `wp-content/themes/zaaka` into a WordPress install (6.5+, PHP 8.0+) and
   activate it under **Appearance → Themes**.
2. **Settings → Permalinks** → *Save* once, so `/work/` resolves.
3. **Settings → Reading** → set a static homepage. `front-page.html` takes over.
4. **Appearance → Editor → Navigation** → build the primary menu.
5. **Tools → Import → WordPress** → `content/zaaka-projects.wxr.xml`, then set
   the featured image on each imported project.

Full detail — design tokens, block styles, project fields, accessibility and
performance notes — is in the theme's own
[README](wp-content/themes/zaaka-studio/README.md).

## Brand

`brand/zaaka-logo-original.svg` is the supplied lockup: white letterforms on a
solid black plate. The web versions in the theme strip the plate and fill the
letterforms with `currentColor`, so one file serves the dark header and the
light sections. Path precision is rounded to one decimal — 29% smaller, and
pixel-identical at 800px wide.

## Previewing without WordPress

`tools/render-preview.py` assembles the theme's real block markup into a single
static HTML file, so the front page can be reviewed in a browser with no
WordPress install:

```bash
python3 tools/render-preview.py        # writes tools/out/index.html
```

It reads `theme.json` for the design tokens and emulates the layout CSS
WordPress generates server-side, so what it renders matches the built site
rather than being a redrawn mock-up.

## Featured images

`brand/locare-featured.png` and `brand/uberfi-featured.png` are the project
card images, both 1600×1000 so the grid never shifts as they load.

- **Locare** — the back-office frame from the product demo
  (`brand/locare-poster-source.jpg`, 1920×1080), padded to 16:10 with the
  video's own black ground rather than cropped, so the caption band and the
  right edge of the composition stay intact.
- **Uberfi** — the four screens of `uberfi-ui-mockup.html`, framed at 16:10.

Set them as the featured image on the matching project after importing
`content/zaaka-projects.wxr.xml`; the WordPress importer does not carry
local files.
