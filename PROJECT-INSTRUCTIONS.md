# Project instructions — zaaka.io

*Paste the section below into the Claude project's custom instructions. Connect
this folder to the project so the theme, the content files and these docs are
all in scope.*

---

## What this project is

Maintaining and improving **zaaka.io**, the site for Zaaka — a design and
engineering studio in Johannesburg run by Vernon Venter. The site is a shop
window for winning work, so every change is judged on whether it makes the
studio more credible to someone deciding whether to hire it. Improve SEO.

WordPress 6.8 on PHP 8.2, Namecheap shared hosting (cPanel, `server313`),
document root `/public_html`. The site runs a custom block theme, **Zaaka
Studio**, which lives in this repo. There is no page builder, no framework, no
web fonts and no build step.

## Where things live

| Path | What |
| --- | --- |
| `wp-content/themes/zaaka-studio/` | The whole theme. This is the project. |
| `content/pages/*.html` | Block markup for the static pages. **These are not live** — page content lives in the WordPress database. These files are the source of truth for that copy; changes here have to be pasted into the page in the admin. `HOW-TO-PASTE.md` explains it. |
| `content/zaaka-projects.wxr.xml` | Importable case studies. |
| `tools/preview/` | Static render harness. Read its README. |
| `docs/` | Maintenance guide, the gotchas log, the backlog. |
| `brand/` | Logo source and derived assets. |

WordPress core, uploads and plugins are deliberately not tracked.

## How to work

1. **Edit the theme in this repo**, never through the WordPress admin. The
   admin's edits are stored in the database and silently override template
   files, which is how a template part ends up "Customised" and stops
   reflecting the theme.
2. **Render before you claim.** `tools/preview/build.py` renders the front page
   to static HTML. Look at it, measure it with Playwright, and check phone
   width. Do not describe a visual change you have not seen.
3. **Measure contrast rather than eyeballing it.** Every colour pairing must
   clear WCAG 2.2 AA — 4.5:1 for body text, 3:1 for large text. Three separate
   contrast failures shipped in this theme before they were caught by
   measurement; none of them looked wrong in a comment.
4. **Bump the version on every change** — `style.css` and `ZAAKA_VERSION` in
   `functions.php`. The stylesheet is enqueued with that version as its cache
   buster, and WordPress caches parsed `theme.json` keyed on it. Ship without
   bumping and the change appears not to have worked.
5. **Commit with a message that explains the cause**, not just the symptom.
   The gotchas log is built from these.

## Deploying

Zip the theme folder, then **Appearance → Themes → Add New → Upload Theme →
Replace current with uploaded**. Delete-and-reinstall only when `theme.json`
changes — see `docs/MAINTENANCE.md`.

## Before finishing any visual change

- Rendered and looked at it, at 1440px and at 390px.
- No horizontal overflow at 390px.
- Contrast measured on any new colour pairing.
- Version bumped.
- Committed.

## House style

The site's voice is plain, direct and slightly anti-jargon — "Say the hard
thing early", "Measure what you claim". Match it. Do not write marketing
filler, and do not introduce words like *scalable*, *enterprise-grade*,
*seamless* or *cutting-edge*; the site is implicitly arguing against them.

The design system lives in `theme.json`. Change a token there rather than
writing a one-off value in CSS. The accent is volt `#CBF94C`, always as
dark-text-on-light-accent, never the reverse.

## Read the gotchas log first

`docs/GOTCHAS.md` records WordPress behaviours that have already cost real
debugging time on this site — preset slugs being mangled, core stylesheets
out-specifying theme rules, grid layouts that refuse to reflow. Check it before
diagnosing anything that looks impossible.
