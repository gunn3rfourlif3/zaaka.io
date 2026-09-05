# Gotchas

WordPress behaviours that have already cost real debugging time on this site.
Each one shipped, looked impossible, and had a mundane cause. Check here before
concluding that something is broken.

---

## 1. Preset slugs beginning with a digit are silently mangled

**Symptom.** Every heading collapsed to body size on the live site. The rule
`h1 { font-size: var(--wp--preset--font-size--5xl) }` was present and correct
in the generated stylesheet, but resolved to nothing.

**Cause.** WordPress runs preset slugs through `_wp_to_kebab_case()` before
generating custom properties, and that inserts a hyphen at every digit→letter
boundary. The slug `5xl` became `--wp--preset--font-size--5-xl`, while every
hand-written class in the theme said `has-5xl-font-size`. The two never met.
Diagnosing it was slow because the properties *did* exist — under names nothing
referenced.

**Fix.** Font-size slugs are alphabetic only: `xs sm md lg xl xxl xxxl huge
mega`. Never start a preset slug with a digit. Spacing slugs (`10`–`70`) are
safe because they contain no letters.

---

## 2. Core stylesheets out-specify theme rules by doubling a class

**Symptom.** The header navigation was invisible — measured at **1.08:1**
against the header. The theme rule setting the link colour was in the
stylesheet, matched the element, and lost anyway.

**Cause.** Core ships

```css
.wp-block-navigation .wp-block-navigation-item__content.wp-block-navigation-item__content { color: inherit }
```

The class is deliberately doubled, giving specificity (0,3,0). Any descendant
rule written against `a` — `.zk-header .wp-block-navigation a`, at (0,2,1) —
loses, and the link falls back to the inherited root text colour.

**Fix.** Set the colour on the navigation *container* so `inherit` resolves to
the right value, **and** on the item content at matching specificity. Both,
because core has changed this selector before.

**General lesson.** When a rule that should apply doesn't, enumerate the
matching rules from the CSSOM rather than reasoning about it. Beware:
in current Chrome every `CSSStyleRule` has a `cssRules` property (empty, for
CSS nesting), so a naive `if (rule.cssRules) { recurse; continue }` walker
skips every rule in the document and reports that nothing matched.

---

## 3. Grid layouts do not reflow on narrow screens

**Symptom.** The whole page scrolled sideways on a phone.

**Cause.** WordPress generates `repeat(auto-fill, minmax(<minimumColumnWidth>,
1fr))` for grid layouts, and `minmax()` never goes below its floor. A 23rem
column renders 368px wide inside 278px of available space at a 390px viewport.

**Fix.** `@media (max-width: 600px) { .is-layout-grid.is-layout-grid {
grid-template-columns: 1fr } }` — the class is doubled to out-specify the
generated `.wp-container-core-*-is-layout-*` rule without `!important`.

Prefer `minimumColumnWidth` over `columnCount` in block attributes; a fixed
column count cannot reflow at all.

---

## 4. Constrained layout re-centres anything you left-align

**Symptom.** Measured paragraphs and section headings drifted to the centre.

**Cause.** WordPress's constrained layout sets `margin-left: auto` on children
at the same specificity as a plain class rule, and prints later.

**Fix.** Double the class: `.zk-measure.zk-measure { margin-left: 0 }`. The
same trick is used by `.is-style-measure`, `.zk-work__title` and
`.zk-process__title`.

Related: an *inline-level* box ignores layout max-width and auto margins
entirely, so `display: inline-flex` on a heading-like element lets it escape to
the padding edge. Use block-level `flex`.

---

## 5. A query loop does not know what page it is on

**Symptom.** The Locare case study recommended Locare.

**Cause.** A core query loop with `inherit: false` has no awareness of the
current post, and there is no UI setting for excluding it.

**Fix.** `zaaka_exclude_current_project()` in `functions.php` filters
`query_loop_block_query_vars` on singular project views.

---

## 6. `theme.json` is cached against the theme version

**Symptom.** `defaultPalette: false` appeared to be ignored — core's `black`,
`cyan-bluish-gray` and the default font sizes were still in the live CSS.

**Cause.** WordPress caches parsed `theme.json` keyed on the theme's version
string. The version had not changed between uploads, so the stale parse stuck.

**Fix.** Bump `Version:` in `style.css` on every release. The same bump busts
the stylesheet cache, since assets are enqueued with `ZAAKA_VERSION`. When
`theme.json` itself changes, delete the theme before re-uploading rather than
replacing in place.

---

## 7. An empty navigation block invents a menu

**Symptom.** The header rendered no links at all, and page content pasted into
the site editor once surfaced inside the mobile overlay on every page.

**Cause.** A self-closing `<!-- wp:navigation /-->` with no inner blocks and no
`ref` makes WordPress auto-create an empty `wp_navigation` post, which is then
shared everywhere and editable by accident.

**Fix.** Ship the links as inner blocks of the navigation in
`parts/header.html`, as custom links with root-relative URLs. Relative URLs
survive page-ID changes, and `/work/` is a post-type archive that can never be
selected from the page picker anyway.

---

## 8. A missing tag looks exactly like a spacing bug

**Symptom.** One footer column was looser and larger than the one beside it.

**Cause.** The list had lost its opening `<ul>`. The orphaned `<li>` elements
inherited none of the block classes — no `wp-block-list`, no
`has-sm-font-size` — and picked up paragraph block spacing instead of
list-item spacing.

**Lesson.** Before tuning a spacing value, confirm the two things you are
comparing actually have the same classes.

---

## Non-WordPress: the featured image trap

Uploading an image to the Media Library does **not** attach it to anything.
`featured_media: 0` on a post means the image exists but was never set as the
featured image. Check
`/wp-json/wp/v2/project?_fields=id,slug,featured_media` before assuming the
template is at fault.
