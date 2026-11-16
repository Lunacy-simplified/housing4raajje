# Design rules

H4R follows Apple's [Human Interface Guidelines](https://developer.apple.com/design/human-interface-guidelines).
Every page uses one stylesheet, `public/css/h4r.css`, and three layouts. Read this before building a page.

## The four principles

1. **Clarity.** Every element has a job. If you can't say what it's for, leave it out.
2. **Deference.** The content (the listing, the form, the table) is the hero. No decoration, gradients,
   icons for the sake of icons, or extra borders.
3. **Depth.** Show hierarchy with surfaces: grey page, white card, filled field. Not with lines everywhere.
4. **Consistency.** Use the existing classes. A new page should look like it was always there.

## Minimalism

- Build only what the feature needs. No placeholder widgets, fake stats, or "coming soon" boxes.
- One primary button per screen or card. Everything else is secondary.
- Add a CSS rule only when a page uses it. Delete rules nobody uses.
- No CSS frameworks, no JavaScript frameworks, no web fonts, and no inline `style="..."`.

## Tokens

Defined once in `:root` in `h4r.css`. Always use the variable, never a raw value.

| What | Values |
|---|---|
| Font | System font (`--font`, SF Pro on Apple devices); `--mono` for codes and IDs |
| Text sizes | 12 caption · 13 footnote/labels · 15 body · 17 inputs · 20 section titles · 28–34 page titles |
| Spacing | 4/8 grid: `--s-1` 4 · `--s-2` 8 · `--s-3` 12 · `--s-4` 16 · `--s-5` 20 · `--s-6` 24 · `--s-8` 32 · `--s-12` 48 |
| Corners | `--r-sm` 8 (sidebar rows, segments) · `--r-md` 12 (fields, banners) · `--r-lg` 20 (cards) · `--r-full` (buttons, pills) |
| Colours | `--accent` (actions), `--link`, `--green` / `--orange` / `--red` (status), `--label` / `--label-2` / `--label-3` (text), `--bg` / `--surface` / `--fill` (layers), `--sep` (hairlines) |

Rules:
- **Touch targets are at least 44px tall.** Buttons, fields, table rows, page numbers.
- **Concentric corners.** Inner radius + padding = outer radius (an 8px item inside a card with 12px padding sits in a 20px card).
- **Hairlines, not borders.** Separators are `.5px solid var(--sep)`.
- **Light and dark mode** both come from the tokens. Never hard-code `#fff` or `#000` for text or surfaces.
- **Status is always words.** "Active", "Not verified"; colour is the second cue, never the only one.

## Components

| Need | Use |
|---|---|
| Page title | `.ph` > `.crumb` + `h1.t` + `p.sub`; actions in `.acts` |
| Card | `.card` (add `.flush` for a table inside, `.limit` for a narrow form) |
| Buttons | `.btn` + `.btn-p` primary · `.btn-s` secondary (tinted) · `.btn-n` neutral · `.btn-d` destructive; `.act` for a text link-button in a table |
| Status pill | `.b` + `.b-ok` / `.b-warn` / `.b-off` |
| Banner | `.al` + `.al-ok` / `.al-err` (success messages come from `partials/status`) |
| Field | `.fld` > `label` + `.in` + `.hint` + `.err`; add `.bad` to `.in` on error; `.pre` for a "+960" prefix; `.code` for 6-digit codes |
| Form grid | `.fgrid` (auto columns); filter bar `.flt` |
| Checkbox | `label.chk` with the input first. For an on/off setting use `label.chk.switch` with the text first |
| Two options | `.seg` segmented control with `a.on` for the current one |
| Table | `.card.flush` > `.sx` > `table.tbl`; `.r` right-aligns a cell; empty state `td.empty` |
| Layout | `.split` > `.wide` + `.narrow`; `.stack` for a vertical column; `.row` for a label and value on one line |

## Layouts

- `layouts.auth` for signed-out pages and single-task pages (sign in, codes, verification).
- `layouts.portal` for staff (sidebar). Add menu items in `partials/nav-side`.
- `layouts.portal-top` for owners and clients (top menu). Add menu items in `partials/nav-top`.

## Before opening a PR

- [ ] Only existing classes, or new rules added to `h4r.css` and actually used
- [ ] No inline styles, no new fonts or libraries
- [ ] Checked in light **and** dark mode, and at phone width
- [ ] Everything clickable is at least 44px tall and works with the keyboard (visible focus ring)
- [ ] `docker compose exec app php artisan test` passes
