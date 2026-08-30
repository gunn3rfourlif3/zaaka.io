# Zaaka — WordPress block theme

A full-site-editing block theme for zaaka.io — a dark, high-contrast studio site
built for a general design-and-engineering practice, not one sector. No page
builder, no JavaScript framework, no web fonts. Everything that can live in
`theme.json` lives there, so the site is editable from the admin without code.

---

## Install

1. Zip the `zaaka` folder (or use the supplied `zaaka-theme.zip`).
2. **Appearance → Themes → Add New → Upload Theme**, choose the zip, activate.
3. **Settings → Permalinks** → click *Save* once. This flushes rewrite rules so
   `/work/` (the Projects archive) resolves.
4. **Settings → Reading** → set *Your homepage displays* to a static page and
   choose the page you want as home. The `front-page.html` template takes over
   automatically and renders the studio patterns.
5. **Appearance → Editor → Navigation** → build the primary menu:
   Work · Services · Approach · About · Contact.
6. **Tools → Import → WordPress** → upload `zaaka-projects.wxr.xml`. This
   creates the Locare and Uberfi case studies with their taxonomy terms and
   project fields. Then open Uberfi and set `uberfi-featured.png` as its
   featured image (the importer does not carry local images).

Requires WordPress 6.5+ and PHP 8.0+. Tested against 6.8.

---

## What's in it

### Templates
| File | Used for |
| --- | --- |
| `front-page.html` | Home — composed entirely of the Zaaka patterns |
| `page.html` | Standard pages |
| `page-wide.html` | Full-width page (selectable per page) |
| `index.html` / `archive.html` | Blog listing |
| `single.html` | Blog post |
| `archive-project.html` | `/work/` — the project grid |
| `single-project.html` | A single case study, with the meta bar |
| `search.html`, `404.html` | Search results, not-found |

### Patterns (Inserter → Zaaka)
`hero` · `marquee` · `flagship` · `services` · `work-grid` · `sectors` ·
`process` · `cta` — in that order on the front page.

Each one is a normal block pattern. Insert it on any page, then edit it like
any other content — patterns are copied in, not linked, so changing one page
never changes another.

### Content types
- **Projects** (`project`) — the case-study post type, archived at `/work/`.
  Taxonomies: **Discipline** and **Sector**, both filterable.
- Six project fields, editable in the sidebar and rendered by block bindings on
  the single template: `zaaka_client`, `zaaka_role`, `zaaka_year`,
  `zaaka_stack`, `zaaka_url`, `zaaka_outcome`, `zaaka_status`.
- `zaaka_status` renders as a coral pill beside the discipline terms — use it
  for honest labels like *In development*, *Live*, *Archived*. Leave it empty
  and the pill disappears.

### Brand assets
`assets/img/` holds the logo, derived from the supplied `zaaka-logo.svg`:

| File | Use |
| --- | --- |
| `logo-wordmark.svg` | The Zaaka.io wordmark, header and footer |
| `logo-mark.svg` | The Z alone — same paths, cropped by viewBox |
| `favicon.svg` + `favicon-32.png` + `apple-touch-icon.png` + `favicon-512.png` | Browser and home-screen icons |

The supplied original was white letterforms on a solid black plate. The plate is
gone: only the letterforms remain, filled with `currentColor` and inlined by
`zaaka_svg()`, so the same file serves the dark header and any light section,
costs no extra request, and stays sharp at every size. Path precision was
rounded to one decimal — 29% smaller, and pixel-identical at 800px wide.

Favicon links are printed by `zaaka_favicons()` and skipped entirely once a
Site Icon is set in **Settings → General**, so the admin choice always wins.

### Block styles
Applied from the block sidebar under *Styles*:
- Group → **Card**, **Panel (dark)**, **Rule above**
- Button → **Ghost**
- List → **Ticks**
- Paragraph → **Eyebrow**
- Heading → **Narrow measure**

---

## Design system

All tokens live in `theme.json`. Change a value there and it updates everywhere,
including the editor.

| Token | Value | Use |
| --- | --- | --- |
| Void | `#07080A` | Hero, services, closing sections |
| Ink | `#101319` | Secondary dark sections |
| Surface / raised | `#171B23` / `#212734` | Cards on dark, hover state |
| Hairline | `#2C3341` | Borders on dark |
| Paper / Chalk | `#FFFFFF` / `#F4F4F0` | Light sections |
| Muted / Mist | `#5C6472` / `#A7B0C0` | Secondary text, light and dark |
| **Volt** | `#CBF94C` | The accent — buttons, numerals, highlights |
| Moss | `#3F5400` | Accent on light backgrounds, link hover |
| Coral / Ember | `#FF6A3D` / `#B33A15` | Secondary accent, alert states |

Every foreground/background pairing clears **WCAG 2.2 AA**, most of them AAA:
body on void 20.0:1, volt on void 16.4:1, muted on paper 6.0:1, mist on ink
8.5:1. Volt is dark-text-on-light-accent, never the reverse.

**Type** is a system stack at display sizes — up to 6.5rem, weight 800, tracking
−0.035em. Zero font bytes, no FOIT, no layout shift. To add a branded face
later, register it under `settings.typography.fontFamilies` with `fontFace`
entries and self-host the files in `assets/fonts/`.

**Motion.** A `zk-reveal` class fades elements up as they enter the viewport,
driven by ~1 KB of vanilla JavaScript in `assets/js/zaaka.js`. It is purely
additive: with JavaScript disabled the page renders complete, and a four-second
failsafe reveals anything the observer misses. The hero gradient drifts, the
capability marquee scrolls and pauses on hover. All of it is disabled under
`prefers-reduced-motion`.

## Performance notes

- No jQuery, no framework, no builder runtime. One stylesheet
  (`assets/css/theme.css`) and one small script (`assets/js/zaaka.js`, deferred).
- WordPress's global SVG duotone filters and the legacy classic-theme stylesheet
  are dequeued in `functions.php`.
- Grid layouts use `minimumColumnWidth` rather than a fixed `columnCount`, so
  every grid reflows on small screens instead of squeezing.
- Featured images are given an explicit aspect ratio, so the project grid does
  not shift as images load.

## Accessibility notes

- A real skip link, rendered first in the body.
- Visible focus on every interactive element, 2px at 3px offset.
- Heading order is enforced by the templates rather than left to editors.
- `prefers-reduced-motion` disables transitions and smooth scrolling.
- The palette passes AA by construction — see the table above.

---

## Before you go live

- [ ] Remove the placeholder case studies from the old site. Pandora Boxchain,
      Hackless and DeHive are not Zaaka's work and must not appear.
- [ ] Add the remaining projects under **Projects**, each with the role field
      completed. Two are supplied in the import file.
- [ ] Set the site title and tagline. The favicon is already handled by the
      theme — only set a Site Icon if you want to override it.
- [ ] Point the Contact page at a real form and confirm it delivers.
- [ ] Run PageSpeed Insights and a keyboard pass before launch.
- [ ] Edit the capability marquee list in `patterns/marquee.php` and the sector
      chips in `patterns/sectors.php` to match what you actually take on.
