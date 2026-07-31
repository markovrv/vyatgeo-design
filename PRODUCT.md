# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Stack

React / Next.js — confirmed for production migration. Current prototype uses a custom dc-runtime (React-based). Design iteration continues on the static prototype; production build will use Next.js with real API data.

## Users

Primary audience: residents of Kirov Oblast (краеведы / local historians), tourists planning visits, and students/researchers studying the region's heritage. All segments share the goal of discovering, exploring, and learning about Vyatka's historical and cultural legacy.

## Product Purpose

Наследие Вятки (Heritage of Vyatka) is an educational web project commemorating the 90th anniversary of Kirov Oblast (1936–2026). It collects, preserves, and makes accessible unique materials about the history, culture, and nature of the Vyatka region across seven thematic modules: Victory Faces, Historic Cities, Archeology Sites, Ethnography, Architecture of Kirov, Natural Monuments, and an About section.

## Positioning

The only digital archive that unifies all seven heritage domains of Kirov Oblast in one place — from military glory and archeology to ethnography, architecture, and natural monuments — presenting them through a cohesive visual identity rooted in Vyatka's own artistic traditions (Dymkovo toy meets Vyatka northern Art Nouveau).

## Operating Context

Visitors browse the site on desktop and mobile. The landing page serves as a navigational hub with parallax hero, timeline, and module grid. Each module offers a filterable catalog with map and list views, linking to individual detail pages. Modules are designed to eventually connect to a WordPress REST API for real data; currently populated with representative mock content.

## Capabilities and Constraints

- 7 thematic modules with catalog/map views and detail pages
- SVG-based interactive maps (prototype; production may use Yandex.Maps API v3)
- Filtering by era, type, ethnicity, style, preservation status
- Fully responsive: desktop (1024px+) and mobile (<1024px hamburger nav)
- WCAG AA accessibility (4.5:1 contrast, focus-visible, min 44px targets)
- Custom design token system via CSS custom properties
- Design mockup phase — API integration is deferred
- No live backend; all data is static prototype content

## Brand Commitments

- Name: Наследие Вятки / Heritage of Vyatka
- Logo: SVG circle with stylized human figure + "Наследие Вятки" text
- Design concept: "Дымковская игрушка встречает вятский северный модерн"
- Colors: --color-bg #FDFBF7, --color-teal #3C7A8C, --color-ochre #C27E3A, --color-ink #2A2118, --color-oak #E8DFC8
- Fonts: Playfair Display (headings), Roboto Slab (body)
- Voice: educational, warm, authoritative — aligned with institutional heritage
- Partners: Government of Kirov Oblast, Kirov Regional Museum of Local Lore, Vyatka State University, Kirov branch of the Union of Architects of Russia
- Contact: info@vyatka-heritage.ru

## Evidence on Hand

- 17 `.dc.html` pages with complete UI prototypes
- 14 location/cultural images in `project/img/`
- Custom SVG logo
- Full design token system in CSS variables
- About page with real stats (7 modules, ~150 archeology sites, 100+ nature sites, 5 ethnic groups)

## Product Principles

1. Design-first: perfect the visual identity and UX before connecting real data — the prototype is the design authority.
2. Heritage authenticity: every visual choice must feel rooted in Vyatka's material culture (birch bark, Dymkovo patterns, northern Art Nouveau).
3. Modular by nature: each of the 7 modules is independently navigable yet visually unified under the same design system.
4. Accessible and inclusive: WCAG AA is not optional — the site serves a broad public including older residents and students.
5. Progressive enhancement: static prototype works fully; API integration adds real content without changing the interface.

## Accessibility & Inclusion

WCAG AA target: 4.5:1 contrast, focus-visible outlines, 44px minimum touch targets, skip-link navigation, screen-reader friendly markup. Font sizes use `clamp()` for fluid scaling. Animations respect `prefers-reduced-motion`.
