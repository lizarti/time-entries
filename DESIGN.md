---
name: Agreements Dashboard
colors:
  primary: "#FF6B35"
  primary-hover: "#E85A24"
  surface: "#FFFFFF"
  background: "#F5F6F8"
  border: "#E8E9EB"
  border-subtle: "#F0F1F3"
  text-primary: "#111827"
  text-secondary: "#6B7280"
  text-muted: "#9CA3AF"
  tag-green: "#12B76A"
  tag-green-bg: "#ECFDF3"
  tag-purple: "#7C3AED"
  tag-purple-bg: "#F5F3FF"
  tag-blue: "#0EA5E9"
  tag-blue-bg: "#F0F9FF"
  tag-gray: "#6B7280"
  tag-gray-bg: "#F3F4F6"
  danger: "#EF4444"
typography:
  heading-xl:
    fontFamily: Inter
    fontSize: 1.5rem
    fontWeight: 700
    color: "{colors.text-primary}"
  body-md:
    fontFamily: Inter
    fontSize: 0.875rem
    fontWeight: 400
    color: "{colors.text-primary}"
  body-sm:
    fontFamily: Inter
    fontSize: 0.75rem
    fontWeight: 400
    color: "{colors.text-secondary}"
  label:
    fontFamily: Inter
    fontSize: 0.75rem
    fontWeight: 500
    color: "{colors.text-secondary}"
  table-header:
    fontFamily: Inter
    fontSize: 0.75rem
    fontWeight: 600
    color: "{colors.text-secondary}"
    textTransform: uppercase
    letterSpacing: 0.04em
spacing:
  xs: 4px
  sm: 8px
  md: 12px
  lg: 16px
  xl: 24px
  2xl: 32px
rounded:
  sm: 4px
  md: 6px
  lg: 8px
  full: 9999px
shadows:
  card: "0 1px 3px rgba(0,0,0,0.08)"
  dropdown: "0 4px 16px rgba(0,0,0,0.12)"
components:
  button-primary:
    backgroundColor: "{colors.primary}"
    textColor: "#FFFFFF"
    fontSize: 0.875rem
    fontWeight: 600
    rounded: "{rounded.md}"
    padding: "8px 16px"
  button-primary-hover:
    backgroundColor: "{colors.primary-hover}"
  button-secondary:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.text-primary}"
    borderColor: "{colors.border}"
    borderWidth: 1px
    fontSize: 0.875rem
    fontWeight: 500
    rounded: "{rounded.md}"
    padding: "8px 16px"
  tag:
    fontSize: 0.75rem
    fontWeight: 500
    rounded: "{rounded.full}"
    padding: "2px 10px"
  table-row:
    borderColor: "{colors.border-subtle}"
    backgroundColor: "{colors.surface}"
    hoverBackground: "#FAFAFA"
  input:
    backgroundColor: "{colors.surface}"
    borderColor: "{colors.border}"
    borderRadius: "{rounded.md}"
    fontSize: 0.875rem
    padding: "8px 12px"
    placeholderColor: "{colors.text-muted}"
---

## Overview

Clean, data-dense B2B SaaS dashboard for managing legal agreements. The aesthetic is **clinical utility** — minimal decoration, maximum information density. Every element serves a function. White canvas with a warm orange accent that anchors all primary actions, keeping the user's eye on what matters: data and actions.

The UI is built for professionals who work with contracts daily. It needs to be scannable, trustworthy, and fast — not flashy. Think: the precision of a spreadsheet, the polish of a modern SaaS product.

## Colors

The palette is restrained by design. White surfaces dominate to keep focus on data. A single accent — a vivid coral orange — handles every primary CTA and interactive link. This creates strong visual hierarchy without noise.

**Primary orange (#FF6B35)** is used exclusively for:
- Primary action buttons ("+ Add Agreement")
- Hyperlinks and interactive footer text
- Never for decorative purposes

**Tags** follow a semantic color system:
- **Green** (`tag-green` / `tag-green-bg`): "All Asset" — positive, inclusive, broad access
- **Purple** (`tag-purple` / `tag-purple-bg`): "Legality Asset" — legal/compliance connotation
- **Blue** (`tag-blue` / `tag-blue-bg`): "Subscription Asset" — service/recurring connotation
- **Gray** (`tag-gray` / `tag-gray-bg`): "Uncategorized" — neutral, undefined state

Tags always use the colored text on a lightly tinted background of the same hue — never solid fills.

## Typography

Single font family throughout: **Inter**. Clean, highly legible at small sizes, purpose-built for interfaces.

- Page title: 1.5rem / 700 weight — establishes hierarchy immediately
- Table headers: 0.75rem / 600 / uppercase / tracked — differentiates from data rows
- Body/cell content: 0.875rem / 400 — comfortable reading density
- Supporting labels and metadata: 0.75rem / muted gray

Never use more than two font weights on a single screen. Hierarchy comes from size and color, not weight variety.

## Layout & Spacing

The layout is structured around a **card-on-background** pattern:
- Light gray page background (`#F5F6F8`) grounds the white content card
- The main table sits on a white surface with a 1px border and subtle shadow
- Internal table rows are separated by 1px `border-subtle` lines only — no zebra striping
- Generous horizontal padding inside cells (16px); tighter vertical padding (12px) for density

Header toolbar: page title (left) + action buttons (right). Always top-aligned, never inline with the table.
Filter bar: below the toolbar, above the table. Contains search, date range picker, row count, and column manager.

## Components

### Buttons

Two button variants exist:
- **Primary** (orange, pill-shaped): for the single most important action per view. One per toolbar maximum.
- **Secondary** (white, bordered, pill-shaped): for supporting actions like "Export All Data". Never competes visually with primary.

Both share the same `rounded-md` border-radius to maintain visual consistency. Icon-only action buttons (edit, delete at row level) use no border — icon-only with a hover state.

### Tags / Badges

Compact pill badges. Colored text on a lightly tinted background of the same hue. No border. Padding: `2px 10px`. Use the semantic color system — never assign colors arbitrarily.

### Table

- Column headers: uppercase, tracked, muted — visually distinct from data
- Sort icons displayed inline with header labels
- Checkboxes in first column for bulk selection
- Row actions (edit/delete) appear at the far right, icon-only
- Pagination: numeric links, left-aligned count metadata, right-aligned "Go to page" control

### Search & Filters

Search input has a leading icon (magnifying glass) inside the input field. Date range picker appears as a single segmented button. All filter controls share the same input border/radius style.

## Do's and Don'ts

- **Do** use the primary orange only for the single most important CTA per view
- **Do** keep tag colors semantically consistent — don't reuse green for a "danger" state
- **Do** maintain tight information density — this is a power-user tool, not a marketing page
- **Don't** add decorative elements, gradients, or illustrations — the data is the design
- **Don't** use more than one primary button per toolbar
- **Don't** apply shadows to individual table rows — only to the container card
- **Don't** use color fills on table rows — hover state is the only permitted row highlight