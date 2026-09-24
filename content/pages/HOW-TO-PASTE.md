# Page content

Seven files of WordPress block markup, one per page. Paste each into the page
that already exists where it does; Accessibility, Privacy and Discovery Sprint
have to be created.
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
| `page-discovery-sprint.html` | Discovery Sprint — create it, slug `discovery-sprint` |

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

## The booking link

Every "Book an intro call" button points at `/book/`. The theme redirects that
to whatever is set in **Settings → General → Booking link** (the Calendly
intro-call page). With nothing set, `/book/` goes to `/contact/`, so the
buttons are never dead. The privacy policy names Calendly as a supplier; if the
scheduling tool changes, change that line too.

## Case-study summaries and testimonials

Each project has a **Case study summary** box under the editor: the problem,
what we did, the result, optional result figures (`figure | label`, one per
line) and an optional testimonial. Empty fields render nothing. Only real
numbers and real quotes, used with the person's permission, go in here.

## Banded pages need the full-width template

Services, Approach, About and Contact are built as one full-bleed dark band.
The band paints its own ground and breaks out of the content column on any
template, but the default page template also prints the post title as a large
heading *above* the band, on white. Set each of these four to **"Page — full
width"**.

In a block theme that control is **Settings gear → the `Page` tab → Summary →
Template → Swap template**. There is no Page Attributes box; that is the
classic editor.

Because the full-width template does not print the post title, each of those
pages now carries its own `<h1>` in the markup. Do not delete it — without it
the page has no h1 at all.
