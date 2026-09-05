# Preview harness

Renders the theme's patterns and templates to static HTML so layout, spacing
and colour contrast can be checked without a WordPress install. It reads
`theme.json` for the design tokens and emits WordPress's own layout classes
(`is-layout-grid`, `is-layout-flex`), so rules written against those classes
are exercised exactly as they will be live.

This is what caught the grid-overflow bug: `minmax(23rem, 1fr)` never goes
below its floor, so the service panels rendered 368px wide inside 278px of
available space at phone width. The harness reported 27 overflowing elements
before the fix and 3 after — the 3 being the desktop nav, which the harness
cannot collapse into a hamburger.

## Use

    python3 build.py            # front page  -> out/index.html
    python3 case.py locare      # case study  -> out/case-locare.html
    python3 case.py uberfi

Open the files directly; no server needed.

## What it is not

An emulator. It approximates WordPress's constrained and flex layouts well
enough to catch composition errors, but `alignfull` behaviour, the responsive
navigation and anything driven by a query loop are approximations or fixtures.
Always confirm those against the live site.

`case-data.json` holds the published case-study copy, pulled from the REST API
(`/wp-json/wp/v2/project`). Refresh it if the case studies change materially.
