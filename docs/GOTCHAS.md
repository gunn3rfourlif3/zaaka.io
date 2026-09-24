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

---

## 9. Reusing a `zk-` class name silently inherits another component

**Symptom.** The Brief Builder's question steps each drew a 3px volt rule under
the heading, and every answer chip rendered as a pill inside a pill.

**Cause.** Two class names were already taken. `.zk-step` belongs to the process
pattern, which draws a volt `::before` rule; `.zk-chips` belongs to the sector
band, whose rule is `.zk-chips span { border; border-radius: 999px; padding }`
— it styles *every* span inside it, and the new chips had a span for their
label. Neither showed up when inspecting the new CSS, because neither rule is
in it.

**Lesson.** The theme's classes are a flat namespace with no build step to warn
about collisions. Before naming a component, grep `assets/css/theme.css` for
the name. A `::before` on someone else's class is invisible in the DOM — it
does not appear in `querySelectorAll`, so a script that looks for stray
elements finds nothing. `getComputedStyle(el, '::before')` is what finds it.

---

## 10. `hidden` loses to any rule that sets `display`

**Symptom.** The Brief Builder's submit button and its completion stamp were
visible on every step, although the script had set `hidden` on both.

**Cause.** The `hidden` attribute is a presentation hint with roughly the weight
of a type selector, so `.zk-btn { display: inline-block }` and
`.zk-stamp { display: flex }` both beat it. Nothing looks wrong in the
JavaScript, which is why this survives code review.

**Lesson.** Any component that sets `display` and is ever toggled with `hidden`
needs an explicit `[hidden] { display: none !important }` in scope.

---

## 11. WordPress constrains layout children with `!important`

**Symptom.** The Brief Builder was meant to run edge to edge. Live, it was the
right width but started a third of the way across the page and overflowed off
the right edge. It had rendered correctly in the preview harness.

**Cause.** Core ships:

```css
.is-layout-constrained > :where(:not(.alignleft):not(.alignright):not(.alignfull)) {
  max-width: var(--wp--style--global--content-size);
  margin-left: auto !important;
  margin-right: auto !important;
}
```

The block sits inside two constrained containers — `main` and post-content. The
`max-width` was overridden normally (`:where()` contributes no specificity, so
it is a plain 0,1,0 selector and a later rule wins), but the **auto margins
were not**, because they are `!important`. So the block took the viewport width
inside a 780px column and the auto margins pushed it right: full width, wrong
place. Half the rule applying is what made it look like a sizing bug rather
than a cascade one.

**Lesson.** To break a block out of a constrained container, the negative
margins must be `!important` too — or the element needs `alignfull`, which that
`:where()` deliberately excludes. Note that `alignfull` alone is not enough
here: an `alignfull` child of a 780px-wide post-content is still 780px wide.

**Also the harness's fault.** The brief preview wrapped the form in a plain
centered div instead of WordPress's real chain, so the constrained rules never
applied and the bug could not appear. `build.py` emits `is-layout-constrained`
for exactly this reason. Any harness that does not is testing a different page.

---

## 12. Scoping a component's rules to its form loses the states outside it

**Symptom.** After a brief was submitted, the confirmation heading — "That is
your brief, and it is with us." — was all but invisible: dark text on the dark
band. Body text on the same screen was fine.

**Cause.** The component's typography was scoped to `.zk-brief-form`, and the
confirmation state is not inside a form — it *replaces* it. So the `<h2>` fell
through to `theme.json`'s `elements.heading.color.text`, which is `ink`, on a
`void` ground: about 1.4:1.

Body copy looked fine on the same screen and hid the problem, because a `<p>`
has no colour of its own and inherited `paper` from the wrapper. Headings carry
an explicit colour from `theme.json` and never inherit, so they are the element
that breaks when a dark band is introduced.

**Lesson.** Scope a component's colour rules to its outermost wrapper, not to
the interactive part. And when adding a dark band anywhere in this theme, set
heading colour explicitly — `elements.heading` in `theme.json` means every
heading arrives `ink` regardless of what it sits on.

---

## 13. A harness without the preset classes ignores every colour you set

**Symptom.** The rewritten Contact page measured 20:1 for text that was set to
`mist` — the number for `paper`. Everything "passed", and none of it was the
colour the markup asked for.

**Cause.** WordPress generates a utility class per `theme.json` palette entry
(`.has-mist-color`, `.has-xl-font-size`, and so on) and block markup refers to
them by class alone. A harness that emits the custom properties but not those
classes drops every `textColor` and `fontSize` in the markup silently — the
text just inherits, which usually looks plausible.

**Lesson.** Rendering the tokens is not the same as rendering the theme. A
harness has to emit what WordPress emits: the layout classes (GOTCHAS 11), the
preset utility classes, and `theme.json`'s element styles (GOTCHAS 12). Any one
of the three missing means the preview is a different page from the site, and
the measurements are worth nothing.

---

## 14. A fixed element cannot ask what is behind it

**Symptom.** The floating brief button's pulse ring was invisible over dark
sections. `.zk-dark .zk-ping::after` had been written to handle exactly that,
and did nothing.

**Cause.** Two compounding mistakes.

The selector was dead: the button is printed on `wp_footer` at body level, so it
has no `.zk-dark` ancestor regardless of what it is floating over. A fixed
element's DOM position has nothing to do with what is behind it on screen, and
CSS has no way to ask.

The fade made it worse. The ring faded by animating `opacity`, which scales
everything in the element down together — so a "dark hairline for light
backgrounds" was never darker than the ring's current opacity allowed. Measured
at 1.66:1 on white.

**Lesson.** Anything `position: fixed` has to be legible unaided, because it
will pass over every ground the site has. The fix is a volt ring with an ink
hairline on both edges: volt carries it on dark, the hairline carries it on
light, and neither depends on knowing the backdrop. Peak opacity is 1 and the
fade starts from there, so contrast is not divided by the animation.

**Measuring it needs a differential test.** Contrast against an assumed
background is not enough when a drop shadow, an antialiased edge and the ring
all sit in the same few pixels — the first three attempts measured the button's
own shadow and its Z glyph and reported confident nonsense. Render the same
frame with the ring and without it, and compare each pixel to itself: 15.26:1 on
paper, 16.52:1 on void, 14.71:1 on surface.
