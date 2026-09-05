# Backlog

State as at theme v1.4.3. Ordered by what blocks the site being finished.

---

## Blocking — the site is not complete without these

- [ ] **Apply the updated About copy.** `content/pages/page-about.html` has the
      new lede, the removed *What we have built* section and the restored
      `/work/` link. None of it is live until it is pasted into the page.
- [ ] **Create the Privacy and Accessibility pages.** Slugs must be exactly
      `privacy` and `accessibility` — the footer links to both and both
      currently 404. Content is in `content/pages/`.
- [ ] **Set the site tagline** in Settings → General to the positioning line:
      *We turn ideas into production systems businesses run on, and build the
      brands around them.* The Organization schema reads
      `get_bloginfo('description')`, so this is the machine-readable copy of
      the claim and is not set by the theme.
- [ ] **www → non-www 301.** Decide the canonical host and redirect the other.
- [ ] **Confirm `hello@zaaka.io` receives mail.** It appears on Contact, in
      the footer, in the accessibility statement and in the privacy policy,
      and every one of them promises a reply within one working day.

## Content

- [ ] **Point the Contact page at a real form**, or make explicit that email is
      the only route. Note that adding a hosted form changes what the site
      collects and therefore invalidates the privacy policy as written — see
      the conditions listed in `content/pages/HOW-TO-PASTE.md`.
- [ ] **Name the legal entity and Information Officer** in the privacy policy
      if Zaaka is a registered company. POPIA requires a designated
      Information Officer; by default that is the head of the organisation.
- [ ] **Review the Approach page's revision promise** — it says two rounds of
      design revisions are included. Make sure that matches what is actually
      quoted.
- [ ] **More case studies.** Two is thin for an agency portfolio, and several
      layout compromises exist purely because the related-work list is always
      exactly one item.
- [ ] **Edit the capability marquee and sector chips** to match what the studio
      actually takes on — `patterns/marquee.php`, `patterns/sectors.php`.

## Technical

- [ ] **Run PageSpeed Insights** and a full keyboard pass before launch.
- [ ] **Screen-reader pass.** Contrast, keyboard operation and reduced motion
      have been tested directly; screen-reader testing has not, and the
      accessibility statement says so honestly. Closing that gap would let the
      statement make a stronger claim.
- [ ] **Verify the case-study hero reaches both edges on the live site.** The
      full-bleed `alignfull` band depends on behaviour the preview harness
      approximates rather than reproduces.
- [ ] **Push the repo.** Ten or more commits sit ahead of `origin/main`; the
      cloud session has no GitHub credentials, so this has to be run locally.
- [ ] **Consider self-hosting a display face.** Type is a system stack at
      display sizes — zero font bytes, no layout shift, but also no
      distinctiveness. If a branded face is wanted, register it under
      `settings.typography.fontFamilies` with `fontFace` entries and put the
      files in `assets/fonts/`.

## Known compromises worth revisiting

- **`.is-layout-grid` is forced to one column below 600px** across the whole
  site. Correct for every current grid, but it is a blunt rule; if a future
  section genuinely wants two columns on a phone, it will need an override.
- **Project meta fields are registered but unrendered.** `zaaka_client`,
  `zaaka_role`, `zaaka_year`, `zaaka_stack`, `zaaka_url` and `zaaka_outcome`
  still exist and still hold data; only `zaaka_status` displays. The role field
  in particular was carrying credibility ("Product, Design, Engineering") and
  could return in a lighter form.
- **The preview harness is not an emulator.** `alignfull`, the responsive
  navigation and query loops are approximations or fixtures. It catches
  composition errors; it does not replace looking at the live site.
