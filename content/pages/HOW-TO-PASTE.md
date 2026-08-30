# Page content

Six files of WordPress block markup, one per page. Paste each into the page
that already exists where it does; Accessibility and Privacy have to be created.
The menu is now built from relative URLs rather than page IDs, so creating pages
no longer risks breaking it — but the slugs must match exactly.

## For each page

1. **Pages → All Pages** → open the page (Services, Approach, About, Contact).
2. Top-right options menu (⋮) → **Code editor**.
3. Select everything in the box and delete it — the pages are empty, so there
   should be nothing there anyway.
4. Paste the whole contents of the matching file.
5. Options (⋮) → **Visual editor** to check it rendered as blocks rather than
   as one lump of text. If it looks like raw markup, you were in the visual
   editor when you pasted — undo and try again from step 2.
6. **Update**.

| File | Page |
| --- | --- |
| `page-services.html` | Services |
| `page-approach.html` | Approach |
| `page-about.html` | About |
| `page-contact.html` | Contact |
| `page-accessibility.html` | Accessibility — create it, slug `accessibility` |
| `page-privacy.html` | Privacy — create it, slug `privacy` |

## The two footer pages

The footer links to `/privacy/` and `/accessibility/`. Both 404 until you create
them. The slugs must be exactly `privacy` and `accessibility`.

- **Accessibility** — written from what the theme actually does, so it stays
  accurate as long as the theme is not heavily modified.
- **Privacy** — written for the site as it currently is: no analytics, no
  tracking, no cookies for visitors, contact by email. Three claims in it are
  only true while that remains the case. **Re-read it and change it the day you
  add any of the following:**
  - an analytics tool of any kind, including a self-hosted one
  - a hosted contact form (Gravity Forms, Formspree, HubSpot, a Typeform embed)
  - an embedded video, map or chat widget — each one sets its own cookies
  - a newsletter signup

  It also names no legal entity and no Information Officer. If Zaaka is a
  registered company, add the registered name and the Information Officer's
  name under **Who we are** — POPIA requires a designated Information Officer,
  and by default that is the head of the organisation. This was written to be
  accurate and readable, not to be legal advice; if you take on clients whose
  own compliance depends on yours, have someone qualified read it.

## Before you publish

- Check `hello@zaaka.io` is a mailbox that actually receives mail. It appears on
  the Contact page, in the footer, and in the accessibility statement.
- The Contact page promises a reply "within one working day" and the
  accessibility statement makes the same commitment. Change both if that is not
  a promise you want to make.
- The Approach page says two rounds of design revisions are included. Make sure
  that matches what you actually quote.
