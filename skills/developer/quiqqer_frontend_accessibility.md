---
name: quiqqer_frontend_accessibility
description: Use when changing HTML templates, markup, controls, or JavaScript that creates or modifies DOM in QUIQQER packages. Accessibility is part of every frontend change, not a separate step. Covers semantic HTML, targeted ARIA usage, keyboard and focus handling, and content rules.
category: developer
---

# QUIQQER Frontend Accessibility

Use this skill for every frontend change in QUIQQER packages: HTML templates, controls, and JavaScript modules
that create or modify DOM. Accessibility is not a separate work step. Consider it with every change.

Load `quiqqer_frontend_javascript` as well when the change includes JavaScript that manages state or creates DOM.

## Working Mode

- New code: implement accessibility requirements directly, without asking.
- Existing code: when touching a file, report accessibility problems you find (briefly, in the handover),
  but do not change them unprompted, unless the task itself is an accessibility improvement.

## Semantic HTML First

- Interactive elements are `<button type="button">` or `<a href>`, never clickable `<div>` or `<span>`
  elements. Use buttons for actions on the page and links for navigation.
- Use structural elements: `<nav>`, `<section>`, `<ul>`/`<li>` for lists. Keep the heading hierarchy of the
  produced output free of skipped levels. Reusable controls and bricks do not hardcode `<h1>`: they render
  inside existing pages, so choose a heading level that fits the typical embedding context.
- Prefer native elements over ARIA: a `<button>` does not need `role="button"`.

## Targeted ARIA Usage

- Set `aria-hidden="true"` on purely decorative elements: icons (`<span class="fa fa-…">`), separators
  (`|`, `•`), decorative graphics. When an icon is hidden, give the element itself an accessible name
  (`aria-label` or visible text).
- Toggle and filter buttons: represent state with `aria-pressed` (or `aria-expanded` for expand/collapse)
  and keep it updated in JavaScript when the state changes. A CSS class alone is not enough.
- Name groups: filter bars, tab bars, and similar containers as `<nav>` or a container with `aria-label`.
  Translate the label (in templates through `{locale}`, in JavaScript through `QUILocale`).
- `title` is not a substitute for an accessible name. It is only a mouse tooltip.

## Keyboard And Focus

- Everything clickable is reachable and operable by keyboard (automatic with native buttons and links,
  which is one more reason to avoid clickable `div` elements).
- Visible focus: define a `:focus-visible` style. Never remove `outline` without a replacement.
- Focus order follows DOM order. No `tabindex` greater than 0.

## Content

- `alt` texts: descriptive for content images, empty (`alt=""`) for decorative ones.
- Announce state and status changes that are only visible visually (loading, filter results) with
  `aria-live="polite"` where needed.
- Mind color contrast. Never convey information through color alone (an active filter needs color plus
  shape, background, or `aria-pressed`).

## Checklist

- [ ] Interactive elements are `<button>`/`<a>`, not `div`.
- [ ] Decorative elements (icons, separators) have `aria-hidden="true"`.
- [ ] Icon-only buttons have a translated `aria-label`.
- [ ] States (active/open) are ARIA attributes, kept in sync in JavaScript.
- [ ] `:focus-visible` is styled and keyboard operation works.
- [ ] Existing accessibility problems in touched files are reported briefly.
