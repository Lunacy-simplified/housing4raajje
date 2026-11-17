# Design rules

H4R follows Apple's [Human Interface Guidelines](https://developer.apple.com/design/human-interface-guidelines).
Every page uses one stylesheet, `public/css/h4r.css`, and three layouts. Read this before building a page.

## The four principles

1. **Clarity.** Every element has a job. If you can't say what it's for, leave it out.
2. **Deference.** The content (the listing, the form, the table) is the hero. No decoration, gradients,
   icons for the sake of icons, or extra borders.
3. **Depth.** Show hierarchy with surfaces: white page, a card with one hairline border, filled field. No shadows.
4. **Consistency.** Use the existing classes. A new page should look like it was always there.

## Minimalism

- Build only what the feature needs. No placeholder widgets, fake stats, or "coming soon" boxes.
- One primary button per screen or card. Everything else is secondary.
- Add a CSS rule only when a page uses it. Delete rules nobody uses.
- No CSS frameworks, no JavaScript frameworks, no web fonts, and no inline `style="..."`.
  The one exception is a width that comes from data, such as the `.meter` bar.
- **Mostly white, one highlight.** The page and cards are white. Blue (`--accent`) is the only highlight and marks four things:
  the current menu item, the main button, links and the focus ring. Green, orange and red are for status only.
  Don't add a colour to decorate.

## Tokens

Defined once in `:root` in `h4r.css`. Always use the variable, never a raw value.

| What | Values |
|---|---|
| Font | System font (`--font`, SF Pro on Apple devices); `--mono` for codes and IDs |
| Text sizes | 12 caption · 13 footnote/labels · 15 body · 17 inputs · 20 section titles · 28–34 page titles |
| Spacing | 4/8 grid: `--s-1` 4 · `--s-2` 8 · `--s-3` 12 · `--s-4` 16 · `--s-5` 20 · `--s-6` 24 · `--s-8` 32 · `--s-12` 48 |
| Corners | `--r-sm` 8 (sidebar rows, segments) · `--r-md` 12 (fields, banners) · `--r-lg` 20 (cards) · `--r-full` (buttons, pills) |
| Colours | `--accent` (actions), `--link`, `--green` / `--orange` / `--red` (status), `--label` / `--label-2` / `--label-3` (text), `--bg` / `--surface` / `--fill` (layers), `--line` (card and table lines) |

Rules:
- **Touch targets are at least 44px tall.** Buttons, fields, table rows, page numbers.
- **Concentric corners.** Inner radius + padding = outer radius (an 8px item inside a card with 12px padding sits in a 20px card).
- **One thin line.** Card edges and separators are `1px solid var(--line)`. No shadows, no thicker or coloured borders.
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
| Numbers at a glance | `.stats` > `.stat` (a hint and a `<b>` number); make it a link when there's a list behind it |
| Facts | `dl.kv` with `dt` and `dd` pairs |
| Share of a total | `.meter` > `i` with a percentage width, plus the number in words next to it |
| Listings and photos | `.grid` of `a.card` with an `img.photo`; `.photos` for a gallery |
| Conversation | `.msgs` > `.msg` (add `.me` for the reader's own messages) |
| Small form in a table row | `form.inline` with bare `.in` controls (give each an `aria-label`), and `@include('partials.errors')` once above the table |
| Rarely used action | `<details>` with a `<summary>`, e.g. `partials/escalate` |

### Blade components

Use these instead of writing the label, control, hint and error by hand:

```blade
<x-field name="road" label="Road" :value="$property->road" required />
<x-field name="monthly_rent" label="Monthly rent (MVR)" type="number" min="1" hint="Per month." />
<x-select name="district_id" label="District" :options="$districts" placeholder="Choose a district" />
<x-textarea name="description" label="What is wrong?" />
<x-pill :status="$lease->status" />   {{-- any status value; picks the colour and the wording --}}
```

They refill from `old()` and show the field's validation error by themselves.

## Small screens

Every page must work from a 360px phone to a desktop. The stylesheet does most of it:

| Width | What changes |
|---|---|
| Below 900px | The menu folds behind the **Menu** button in the top bar. Tables become stacked rows: one block per record, each value beside its column name. |
| Below 640px | Body text is 16px, stat tiles sit two to a row, and a card's main button is full width. |

What you need to do on a new page:
- Put every table in `.card.flush` > `.sx` > `table.tbl` with a `<thead>`. The column names become the labels on a phone,
  so keep them short. Give an actions column a hidden name: `<th scope="col"><span class="sr">Actions</span></th>`.
- Never set a fixed width. Use `.fgrid`, `.split`, `.stats` and `.grid`; they reflow by themselves.
- Check the page at 360px, 768px and 1280px. Nothing may scroll sideways.

`public/js/h4r.js` is the only script (the Menu button and the table labels). Pages still work without it:
the menu stays open and tables scroll sideways. Don't add more scripts for things HTML and CSS can do.

## Layouts

- `layouts.auth` for signed-out pages and single-task pages (sign in, codes, verification).
- `layouts.portal` for staff (sidebar). Add menu items in `partials/nav-side`.
- `layouts.portal-top` for owners and clients (top menu). Add menu items in `partials/nav-top`.
- `layouts.app` for any signed-in page: it picks the sidebar for staff and the top menu for owners and clients. Use this one by default.

## Before opening a PR

- [ ] Only existing classes, or new rules added to `h4r.css` and actually used
- [ ] No inline styles, no new fonts or libraries
- [ ] Checked in light **and** dark mode, at 360px, 768px and 1280px, with nothing scrolling sideways
- [ ] Everything clickable is at least 44px tall and works with the keyboard (visible focus ring)
- [ ] `docker compose exec app php artisan test` passes
