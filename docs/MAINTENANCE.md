# Maintenance

## The two places content lives

This trips people up, so it is worth being blunt about it.

**The theme** — templates, patterns, template parts, CSS — lives in this repo
and is deployed as a zip. Editing it in the WordPress admin writes to the
database and silently overrides the files, which is how a template part ends up
marked *Customised* and stops reflecting what the theme actually ships. If that
happens: **Appearance → Editor → Patterns → Manage all template parts →** the
part **→ ⋮ → Clear customisations.**

**Page content** — the words on About, Services, Approach, Contact, Privacy,
Accessibility — lives in the WordPress database. The files in `content/pages/`
are the source of truth for that copy, but editing them changes nothing on the
live site until the block markup is pasted into the page. See
`content/pages/HOW-TO-PASTE.md`.

Case studies are posts of type `project`, created from
`content/zaaka-projects.wxr.xml` via **Tools → Import → WordPress**. The
importer needs the theme active first, or the post type does not exist yet and
every row fails with *Invalid post type project*.

## Making a change

```bash
# 1. edit the theme
#    wp-content/themes/zaaka-studio/

# 2. render and look at it
python3 tools/preview/build.py          # -> tools/preview/out/index.html
python3 tools/preview/case.py locare    # -> tools/preview/out/case-locare.html

# 3. bump the version in BOTH places
#    style.css        Version: x.y.z
#    functions.php    ZAAKA_VERSION

# 4. build the zip
cd wp-content/themes && zip -rq ../../zaaka-studio.zip zaaka-studio -x "*.DS_Store"
```

Then **Appearance → Themes → Add New → Upload Theme**, choose the zip, and
click **Replace current with uploaded** on the comparison screen. No
reactivation, no permalink flush, nothing lost.

**The exception:** when `theme.json` has changed, delete the theme first and
upload fresh. WordPress caches the parsed `theme.json` and a replace-in-place
can serve the stale parse. For template, pattern, PHP or CSS changes,
replace-in-place is correct and safer — deleting a theme is the one operation
that can take site-editor state with it.

## Verifying before you ship

Non-negotiable for any visual change:

- **Look at it rendered**, at 1440px and at 390px.
- **No horizontal overflow at 390px.** Measure it; do not assume.
- **Contrast on any new colour pairing.** 4.5:1 for body text, 3:1 for text
  at 24px+ or 19px+ bold. Three contrast failures shipped here before being
  caught by measurement — none looked wrong while writing them.
- **The version is bumped.** Otherwise the browser and WordPress both serve
  what they already have, and the change appears not to have worked.

A useful pattern for the last two, run against a rendered page in Playwright or
the browser console: read `getComputedStyle(el).color` and the background,
convert to relative luminance, and assert the ratio. Guessing from a hex value
is how `#5C6472` on `#07080A` shipped at 3.36:1.

## Permalinks

`/work/` is the `project` post-type archive. After any change to post-type
registration or rewrite rules, visit **Settings → Permalinks** and click *Save*
once to flush.

## Hosting

Namecheap shared cPanel, `server313.web-hosting.com`. Document root
`/public_html`. PHP 8.2 via the cPanel PHP Selector. The theme requires
WordPress 6.5+ and PHP 8.0+.

## Git

Deletion in the connected folder has to be granted per session before git can
work properly — it leaves a stale `.git/index.lock` after every write that it
cannot clean up itself, and the next commit then fails with *Another git
process seems to be running*. If that happens and deletion is not available,
`mv .git/index.lock` aside rather than fighting it.
