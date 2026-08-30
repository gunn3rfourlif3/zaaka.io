# Page content

Five files of WordPress block markup, one per page. Paste each into the page
that already exists — do not create new pages, because your navigation menu is
bound to the existing page IDs and new ones would break it.

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
| `page-accessibility.html` | Accessibility — you'll need to create this one |

## Two pages the footer links to that don't exist yet

The footer links to `/privacy/` and `/accessibility/`. Both currently 404.

- **Accessibility** — content supplied. Create the page with the slug
  `accessibility` and paste it in. It is written from what the theme actually
  does, so it is accurate as long as the theme is not heavily modified.
- **Privacy** — not supplied. A privacy policy has to describe what *your* site
  collects: analytics, form submissions, cookies, hosting location, retention.
  Getting that wrong is worse than not having one. Generate it once you know
  what you are running, or remove the footer link until you do.

## Before you publish

- Check `hello@zaaka.io` is a mailbox that actually receives mail. It appears on
  the Contact page, in the footer, and in the accessibility statement.
- The Contact page promises a reply "within one working day" and the
  accessibility statement makes the same commitment. Change both if that is not
  a promise you want to make.
- The Approach page says two rounds of design revisions are included. Make sure
  that matches what you actually quote.
