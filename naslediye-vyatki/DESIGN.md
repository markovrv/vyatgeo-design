---
name: Наследие Вятки
description: Историко-культурное наследие Кировской области — статический сайт к 90-летию региона
colors:
  primary: "#C27E3A"
  primary-dark: "#8B5E2B"
  teal: "#3C7A8C"
  ink: "#2A2118"
  oak: "#E8DFC8"
  birch: "#F5F2EC"
  muted: "#5C5954"
  border: "#D4C9AA"
  surface: "#FFFFFF"
  neutral-bg: "#FDFBF7"
  page-bg: "#FAF9F4"
typography:
  display:
    fontFamily: "'Playfair Display', Georgia, serif"
    fontWeight: 700
    lineHeight: 1.2
  body:
    fontFamily: "'Roboto Slab', Georgia, serif"
    fontWeight: 400
    lineHeight: 1.75
rounded:
  sm: "4px"
  md: "8px"
  full: "999px"
spacing:
  xs: "8px"
  sm: "16px"
  md: "24px"
  lg: "40px"
  xl: "64px"
  xxl: "96px"
components:
  button-primary:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.page-bg}"
    rounded: "{rounded.sm}"
    padding: "14px 32px"
  button-primary-hover:
    backgroundColor: "{colors.teal}"
    textColor: "{colors.page-bg}"
  button-sm:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.page-bg}"
    padding: "10px 24px"
  filter-chip:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    rounded: "{rounded.full}"
  filter-chip-active:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.page-bg}"
    rounded: "{rounded.full}"
  card:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    rounded: "{rounded.sm}"
    border: "1.5px dashed {colors.border}"
  tag-teal:
    backgroundColor: "{colors.teal}"
    textColor: "{colors.page-bg}"
    rounded: "{rounded.full}"
  tag-ochre:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.page-bg}"
    rounded: "{rounded.full}"
  tag-muted:
    backgroundColor: "{colors.muted}"
    textColor: "{colors.page-bg}"
    rounded: "{rounded.full}"
---

# Design System: Наследие Вятки

## Overview

**Creative North Star: "Берестяная летопись"**

The visual world of «Наследие Вятки» is a birch-bark chronicle — layered, tactile, and handcrafted, as if each page were peeled from a birch tree and inscribed with the history of the Vyatka land. The system marries the festive ornament of Dymkovo toys with the restrained elegance of Vyatka northern Art Nouveau, creating a quiet yet unmistakable regional identity.

Surfaces feel matte and material — warm off-whites, muted ochre accents, and a deep teal that recalls the Vyatka river. Dashed borders evoke the stitched edges of birch-bark manuscripts. The ochre clip-path buttons reference traditional wooden bracket carving. Density is comfortable with generous whitespace; the layout breathes at every breakpoint.

Imagery is treated with sepia and reduced brightness — not as a technical limitation but as a conscious archival gesture, as if every photograph were a found artifact.

**Key Characteristics:**
- Warm, low-contrast palette rooted in natural pigments (ochre, birch, baked milk, teal)
- Dashed 1.5px borders as a recurring "birch-bark stitch" motif
- Signature clip-path polygon on buttons — an 8px notched bracket shape
- Pill-shaped chips with active ochre fill
- Sepia-toned hero images with gradient overlays
- Generous vertical rhythm (64–96px section padding, 8px grid)
- Scroll-triggered staggered entrance animations
- A single accent color (ochre) used sparingly — its rarity is the point

## Colors

The palette is drawn from the Vyatka landscape and material culture: clay, birch bark, river turquoise, rye flour, and archival ink.

### Primary
- **Ржаная охра / Rye Ochre** (#C27E3A): The single accent. Used on primary buttons, active filter chips, key interactive elements, horizontal rules, and timeline dots. Never used as a background surface — only as an accent on ≤10% of any screen.
- **Тёмная охра / Dark Ochre** (#8B5E2B): WCAG AA-safe variant of ochre for small text on light backgrounds (eyeline labels, inline links, section metadata). Use only where #C27E3A would fail the 4.5:1 contrast ratio.

### Secondary
- **Вятская бирюза / Vyatka Turquoise** (#3C7A8C): A supporting accent for hover states (buttons shift from ochre to teal), subtitles, metadata highlights, stat numbers, and tag badges. Also used in hero gradient overlays and river lines on maps.

### Neutral
- **Топлёное молоко / Baked Milk** (#FDFBF7 / #FAF9F4): The foundational page background. Two close variants exist — `#FDFBF7` (formal token) and `#FAF9F4` (actual inline usage). Both are warm off-whites.
- **Чернильный / Ink Black** (#2A2118): Primary text color. Also used for footer backgrounds, header translucency, and overlay gradients.
- **Белёный дуб / Bleached Oak** (#E8DFC8): Text on dark backgrounds (hero, footer), decorative elements, sidebar divider lines.
- **Берёста / Birch Bark** (#F5F2EC): Alternating section backgrounds, card image placeholders, empty-state areas.
- **Глинистый / Clay** (#5C5954): Secondary text, metadata, captions, map legends, breadcrumb inactive segments.
- **Соломенный / Straw** (#D4C9AA): Dashed card borders, chip borders (inactive state), decorative dividers.
- **Белый / White** (#FFFFFF): Card backgrounds, filter chip default backgrounds.

### Named Rules
**The Ochre Rarity Rule.** The primary accent (#C27E3A) appears on ≤10% of any given screen. Its rarity is what makes it read as a deliberate accent rather than visual noise. When every link, border, and icon is ochre, none of them are.

**The Stitch Rule.** Dashed 1.5px borders are the closest thing to a decorative flourish in the system. They appear on cards, chips (inactive), stat blocks, and image frames. They serve as a visual "birch-bark stitch" — nothing else competes for that role. Solid borders are never decorative.

## Typography

**Display Font:** Playfair Display (Georgia, serif fallback) — 400, 700, italic 400, italic 700
**Body Font:** Roboto Slab (Georgia, serif fallback) — 400, 500, 700

**Character:** A refined serif display paired with a warm, readable slab serif. Playfair Display brings a museum-exhibition gravitas to headings; Roboto Slab keeps long-form body text approachable and grounded. The pairing is quietly authoritative without being academic.

### Hierarchy
- **Display** (700, clamp(32px, 4vw, 52px), 1.2): Hero H1 on section pages. Single-line or short two-line headlines only.
- **Headline** (700, clamp(26px, 3vw, 36px), 1.3): Section headings (H2). Always centered.
- **Title** (700, clamp(18px, 1.8vw, 22px), 1.4): Card titles, detail page H2s, timeline years.
- **Body** (400, 16px, 1.75): Primary reading text. Max line length 65–75ch for comfortable reading. Roboto Slab.
- **Small** (400/500, 13–14px, 1.6): Metadata, captions, breadcrumbs, nav links, footer text. Roboto Slab.
- **Label** (500, 13px, 0.08em letter-spacing, uppercase): Filter labels, eyebrow text over hero titles.

### Named Rules
**The Title Trim Rule.** Headings use Playfair Display weight 700 exclusively. Lower weights and italic are reserved for blockquotes and decorative uses only. A Playfair heading in weight 400 looks unintentional.

**The Two-Font Limit Rule.** Every text element uses exactly one of the two loaded families. There is no monospace, no sans-serif fallback, and no third font. Roboto Slab serves all UI text including navigation, buttons, and chips.

## Layout

The layout follows a single-column centered model with a maximum content width of 1200px.

### Grid
- **Content region**: `max-width: 1200px; margin: 0 auto` — centered single column with 24px side padding.
- **Catalog grids**: `grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 24px` — responsive card grids. Cards drop to single column below 767px.
- **Detail pages**: Two-column grid for image + content: `grid-template-columns: 280px 1fr; gap: 32px` on desktop, stacks on mobile.
- **Footer**: 3-column grid (`repeat(3, 1fr); gap: 40px`) — collapses to single column below 767px.
- **Stats**: 3-column grid (`repeat(3, 1fr); gap: 16px`) — collapses to `repeat(2, 1fr)` or single column on mobile.

### Density
- **Section padding**: 64px 24px (standard), 96px 24px (hero sections)
- **Card padding**: 24px
- **Button padding**: 14px 32px (standard), 10px 24px (small)
- **Chip padding**: 8px 20px
- **Filter label margin-bottom**: 8px

### Responsive
- **≥1024px**: Full horizontal nav bar, multi-column grids
- **<1024px**: Hamburger menu with right sidebar overlay (280px / 80vw), stat grids collapse
- **<767px**: Single-column everything, reduced section padding
- Fluid typography via `clamp()` ensures headings scale without breakpoint-specific font sizes

## Elevation & Depth

Surfaces are layered by tonal weight rather than shadow — the "birch-bark" model where depth comes from stacking material layers, not from casting shadows.

- The default state is flat: cards sit on a white surface against a warm off-white page background. No box-shadows at rest.
- On hover, cards lift by 4px with a soft shadow (`0 8px 24px rgba(42,33,24,0.12)`) — a gentle "picked up" response.
- The header transitions from translucent (`rgba(42,33,24,0.28) + backdrop-filter: blur(8px)`) to a solid off-white with a 2px ochre bottom border on scroll — moving from floating to grounded.
- The mobile sidebar sits above a dark overlay (`rgba(42,33,24,0.4)`) with its own shadow (`-4px 0 20px rgba(42,33,24,0.1)`).
- Hero overlays use gradients (teal → ink, 15-20% → 40-50%) to create atmospheric depth between the background image and text.

### Named Rules
**The Layered-Flat Rule.** Surfaces are flat until interaction requires otherwise. Hover is the only trigger for elevation; cards do not cast shadows at rest. The layer model (page → card → text) is purely tonal.

## Shapes

The form language is a deliberate mix of sharp, organic, and pill geometries — reflecting the handcrafted ethos.

- **Buttons**: Notched rectangle via `clip-path: polygon(8px 0%, calc(100% - 8px) 0%, 100% 8px, 100% calc(100% - 8px), calc(100% - 8px) 100%, 8px 100%, 0% calc(100% - 8px), 0% 8px)` — an 8px diagonal cut at each corner. This "figurative bracket" shape is the system's most distinctive geometric signature.
- **Cards**: 4px rounded corners. Dashed 1.5px border in straw (#D4C9AA).
- **Chips**: Pill shape (`border-radius: 999px`) by default. A `pill={false}` prop switches to 4px radius for rectangular chip variants.
- **Tag badges**: Pill shape (`border-radius: 999px`), 11px font, 2px 10px padding. Three color variants: teal, ochre, muted.
- **Stat blocks**: 4px rounded corners with dashed border. City timeline stat blocks use a 3px ochre left border accent instead.
- **Breadcrumb**: No special separators — plain text with ochre links.
- **Timeline dots**: 16px circles with 3px white border and 2px ochre/teal box-shadow ring.
- **Map markers**: 9px (city) / 6px (building) / 5px (site) circles, ochre fill, 2px white stroke.

### Named Rules
**The Bracket Rule.** The 8px notched clip-path polygon is exclusive to action buttons. No other element borrows this shape. It is the system's most recognisable form and must remain unique to primary calls-to-action.

## Components

### Buttons
- **Shape:** Notched bracket (8px clip-path polygon, 4px effective radius)
- **Primary:** Ochre (#C27E3A) background, warm off-white (#FAF9F4) text, 14px/500 Roboto Slab, 14px 32px padding, min-height 44px
- **Hover:** Teal (#3C7A8C) background, same text color, lifts 1px (`translateY(-1px)`), 300ms background transition + 150ms transform
- **Small variant:** 10px 24px padding, 13px font — same shape, same min-height
- **No secondary/ghost variant exists:** All actionable buttons use the primary ochre style. Secondary actions are text links in ochre.

### Chips (FilterBar)
- **Style:** Pill shape (999px), 13px/500 Roboto Slab, 8px 20px padding, min-height 44px, inline-flex
- **Inactive:** White background, dashed straw border (#D4C9AA), ink text (#2A2118)
- **Active:** Ochre fill (#C27E3A), ochre border, warm off-white text (#FAF9F4)
- **Transition:** 150ms background and border-color
- **Rectangular variant:** `pill={false}` switches border-radius to 4px

### Cards
- **Corner Style:** 4px radius (sm)
- **Background:** White (#FFFFFF)
- **Border:** 1.5px dashed straw (#D4C9AA)
- **Shadow Strategy:** None at rest; on hover: 4px lift (`translateY(-4px)`) + shadow (`0 8px 24px rgba(42,33,24,0.12)`)
- **Internal Padding:** 24px
- **Image area:** 16:9 aspect ratio (VictoryFaces uses 1:1), birch-bark (#F5F2EC) placeholder background

### Tags / Badges
- **Style:** Pill shape (999px), 11px font, 2px 10px padding, warm off-white text
- **Teal (#3C7A8C):** Era categories, people types, general metadata
- **Ochre (#C27E3A):** Object types, status badges
- **Muted (#5C5954):** Lost/destroyed status

### Navigation (SiteHeader)
- **Desktop:** Fixed top bar, 80px height, 24px horizontal padding, z-index 1000
- **Initial state:** Translucent ink background (`rgba(42,33,24,0.28)`) with `backdrop-filter: blur(8px)`, oak text (#E8DFC8)
- **Scrolled state:** Solid off-white (#FAF9F4), 1px bottom ochre border, ink text (#2A2118), subtle shadow
- **Nav links:** 14px Roboto Slab, 500 weight. Active link has 2px ochre bottom border. 300ms background/border transition
- **Mobile:** Hamburger burger (3 stacked 2px lines) → right sidebar overlay (280px/80vw, 100vh). Dark overlay (`rgba(42,33,24,0.4)`) behind. 300ms cubic-bezier slide. Links: 16px, block layout with oak divider borders

### Inputs / Fields
- **Status:** No form inputs exist in the current prototype. This section should be populated when search bars or forms are added.

### Footer
- **Background:** Ink (#2A2118), 2px ochre top border
- **Layout:** 3-column grid, 40px gap, 1200px max-width
- **Typography:** 20px Playfair Display for section headings (#FAF9F4), 13px Roboto Slab for links and text (#E8DFC8)
- **Divider:** 1px line at `rgba(232,223,200,0.2)` with decorative SVG wave (6 circles at varying opacities)
- **Decorative SVG wave:** An ochre line with 6 scattered circles — the closest thing to ornament in the system

### Hero Sections
- **Height:** 60vh (index uses 100vh), min-height 560px
- **Background:** Sepia-filtered image (`sepia(50%) brightness(0.8)`), cover, centered
- **Overlay:** Linear gradient — teal (15-20%) at top → ink (40-50%) at bottom
- **Content:** Centered, max-width 800px, 40px 24px padding
- **Eyebrow:** 13px/500 Roboto Slab, uppercase, 0.06em letter-spacing, oak text
- **Heading:** Playfair Display 700, `clamp(32px, 4vw, 52px)`, warm off-white
- **Subtitle:** `clamp(15px, 1.4vw, 18px)`, oak text, max-width 600px

### Detail Pages
- **Layout:** Two-column grid (image sidecar + content), stacks on mobile
- **Breadcrumb:** Horizontal flex, 13px, clay text, ochre links
- **Image frame:** 4px radius dashed border, 4:3 aspect ratio
- **Meta:** 14px, teal (#3C7A8C), below H1
- **Stat cards:** 4px radius dashed border, white background. Number in Playfair Display (22-24px, teal), label in 13px Roboto Slab (clay)

### Map Components (SVG)
- **Region outline:** Ochre stroke, 1.5px, dashed array `6 3`, on oak fill
- **River lines:** Teal stroke, 3px, 0.5 opacity
- **City markers:** 9px ochre circles with 2px white stroke
- **Site markers:** 5-6px ochre circles with 2px white stroke
- **Labels:** 13px Roboto Slab, ink text

## Do's and Don'ts

### Do:
- **Do** use ochre (#C27E3A) sparingly — one accent element per viewport at most.
- **Do** use the 8px clip-path bracket shape for all primary action buttons.
- **Do** use dashed 1.5px borders for cards, chips, stat blocks, and image frames.
- **Do** apply sepia + brightness reduction to hero images — they should read as archival artifacts.
- **Do** use Playfair Display 700 for all headings and Roboto Slab for everything else.
- **Do** keep backgrounds in the warm off-white family (#FAF9F4, #FDFBF7).
- **Do** stagger card entrances with 60ms intervals on scroll.

### Don't:
- **Don't** use solid decorative borders anywhere — the only solid borders are structural (footer top, active nav underline, header bottom).
- **Don't** cast shadows on cards at rest — hover-only elevation.
- **Don't** introduce a third font family — Playfair Display + Roboto Slab is the complete system.
- **Don't** use ochre as a background fill for large areas — accent only.
- **Don't** use the bracket clip-path shape on non-button elements — it belongs to CTAs exclusively.
- **Don't** flatten hero images to pure grayscale — sepia preserves the archival warmth.
- **Don't** add text over hero images without a gradient overlay — readability depends on the ink-to-teal gradient.
