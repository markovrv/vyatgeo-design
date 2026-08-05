# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Stack

Vue 3 + Vite — Composition API с `<script setup>`, JavaScript (не TypeScript). SPA на vue-router. Стили: scoped CSS с дизайн-токенами через CSS custom properties (`var(--color-ochre)` и т.д.). Сборка через Vite, порт 5173.

Проект находится в `naslediye-vyatki/` (отдельная кодовая база от старого прототипа `project/*.dc.html`). Старый dc-runtime/React-прототип сохранён для референса, но не используется в продакшене.

## Users

Primary audience: residents of Kirov Oblast (краеведы / local historians), tourists planning visits, and students/researchers studying the region's heritage. All segments share the goal of discovering, exploring, and learning about Vyatka's historical and cultural legacy.

## Product Purpose

Наследие Вятки (Heritage of Vyatka) is an educational web project commemorating the 90th anniversary of Kirov Oblast (1936–2026). It collects, preserves, and makes accessible unique materials about the history, culture, and nature of the Vyatka region across seven thematic modules: Victory Faces, Historic Cities, Archeology Sites, Ethnography, Architecture of Kirov, Natural Monuments, and an About section.

## Positioning

The only digital archive that unifies all seven heritage domains of Kirov Oblast in one place — from military glory and archeology to ethnography, architecture, and natural monuments — presenting them through a cohesive visual identity rooted in Vyatka's own artistic traditions (Dymkovo toy meets Vyatka northern Art Nouveau).

## Operating Context

Visitors browse the site on desktop and mobile. The landing page serves as a navigational hub with parallax hero, timeline, module grid, video, and crafts carousel. Each module offers a filterable catalog with map and list views, linking to individual detail pages. Two modules — Ethnography and Architecture of Kirov — are wired to their live WordPress REST APIs (see `src/composables/useFindings.js`, `useAttractions.js`, and the shared module patterns documented in `CLAUDE.md`); the remaining modules (Victory Faces, Historic Cities, Nature, Archeology) still run on static prototype data.

## Capabilities and Constraints

- 7 thematic modules with catalog/map views and detail pages
- Filtering by era, type, ethnicity, style, preservation status
- Fully responsive: desktop (1024px+) and mobile (<1024px hamburger nav)
- WCAG AA accessibility (4.5:1 contrast, focus-visible, min 44px targets)
- Design token system via CSS custom properties in `tokens.css`
- SPA architecture — 15 routes, lazy-loaded
- Shared components: FilterBar, CatalogCard, CatalogMap, CatalogLayout, KremlinVideo, Pagination, ImageLightbox, MultiSelectFilter, TypeFilterTiles, plus module-specific ones for Architecture (AttractionMap, AttractionControls, AttractionGallery, AttractionPopup, AttractionClusterIcon)
- Composables: useScrollFadeIn, useHeaderScroll (UI-only) alongside useFindings.js and useAttractions.js (live WP REST API data for Ethnography/Architecture — list, single item, adjacent nav, nearby/similar, image-size thumbnails)
- Ethnography and Architecture have live WordPress backends (see `CLAUDE.md` for the established integration patterns); the other 4 catalog modules are still static-only
- Design mockup phase — largely complete for Ethnography/Architecture; remaining modules still deferred

## Brand Commitments

- Name: Наследие Вятки / Heritage of Vyatka
- Logo: SVG circle with stylized human figure + "Наследие Вятки" text
- Design concept: "Дымковская игрушка встречает вятский северный модерн"
- Colors: --color-bg #FAF9F4, --color-teal #3C7A8C, --color-ochre #C27E3A, --color-ink #2A2118, --color-oak #E8DFC8
- Fonts: Playfair Display (headings), Roboto Slab (body)
- Voice: educational, warm, authoritative — aligned with institutional heritage
- Partners: Government of Kirov Oblast, Kirov Regional Museum of Local Lore, Vyatka State University, Kirov branch of the Union of Architects of Russia
- Contact: info@vyatka-heritage.ru

## Project Structure

```
naslediye-vyatki/
  src/
    main.js              # entry: tokens → base.css → Vue app + router + vue-yandex-maps
    App.vue              # SiteHeader + router-view + SiteFooter
    router/index.js      # lazy-loaded routes
    config/
      api.js              # API_BASE_URL (window.__API_BASE_URL__)
      maps.js              # YANDEX_MAPS_API_KEY (window.__YANDEX_MAPS_API_KEY__)
      attraction.js        # ATTRACTION_NEARBY_RADIUS_KM (window.__ATTRACTION_NEARBY_RADIUS_KM__)
    styles/
      tokens.css         # :root — 10 colors, 2 fonts, spacing, shadows, motion
      base.css           # reset, focus-visible, ::selection, prefers-reduced-motion
    assets/img/          # images + 1 video
    layouts/
      SiteHeader.vue     # scroll-aware, mobile sidebar, Escape-close
      SiteFooter.vue     # SVG wave, 3-column grid, 449px breakpoint
    components/
      FilterBar.vue      # pill chips, label, @select
      CatalogCard.vue    # image + tags + title + desc, slots for customization
      CatalogMap.vue     # SVG map wrapper, viewBox prop (static modules)
      CatalogLayout.vue  # hero + filters + map/grid, slot-based (shared by all modules)
      KremlinVideo.vue   # muted, playbackRate 0.5, IntersectionObserver, disconnect
      Pagination.vue, ImageLightbox.vue, MultiSelectFilter.vue, TypeFilterTiles.vue
      AttractionMap/Controls/Gallery/Popup/ClusterIcon.vue  # Architecture module (Yandex map)
    composables/
      useScrollFadeIn.js  # staggered entrance (fadeStyle pattern)
      useHeaderScroll.js  # scrolled + isMobile reactive
      useFindings.js      # live API — Ethnography (findings/v1/*)
      useAttractions.js   # live API — Architecture (attraction/v1/*)
    views/
      IndexView.vue              # hero+parallax, modules, video, timeline, crafts, quote
      VictoryFacesView.vue       # catalog, era filter, 1:1 cards, no map
      VictoryPersonView.vue      # detail: timeline, stat cards, awards
      EthnographyView.vue        # live API: catalog + map view, type tiles, multi-select filters, search
      EthnographyItemView.vue    # live API: detail, gallery+lightbox, adjacent nav, similar items, edit link
      HistoricCitiesView.vue     # static: catalog + SVG map, era + view filters
      CityTimelineView.vue       # static: detail: vertical timeline, image links
      CityEventView.vue          # static: detail: event page, nearby timeline nav
      NatureView.vue             # static: catalog + map, type + view filters
      NatureSiteView.vue         # static: detail: landscape, stats
      ArcheologyView.vue         # static: catalog + map, era + type + view filters
      ArcheologySiteView.vue     # static: detail: research, findings, stats
      ArchitectureView.vue       # live API: catalog + Yandex map (clustering, historical polygons), search
      ArchitectureObjectView.vue # live API: detail, text-parsed gallery+captions, adjacent nav, nearby-by-distance, edit link
      AboutView.vue              # hero, stats, modules, partners, CTA
```

## Evidence on Hand

- 26 `.vue` files with complete UI (15 views + 11 shared)
- 16 location/cultural images + 1 video in `src/assets/`
- Custom SVG logo embedded in SiteHeader/SiteFooter
- Full design token system in CSS custom properties
- About page with real stats (7 modules, ~150 archeology sites, 100+ nature sites, 5 ethnic groups)
- Original prototype in `project/*.dc.html` preserved for reference

## Product Principles

1. Design-first: perfect the visual identity and UX before connecting real data — the prototype is the design authority.
2. Heritage authenticity: every visual choice must feel rooted in Vyatka's material culture (birch bark, Dymkovo patterns, northern Art Nouveau).
3. Modular by nature: each of the 7 modules is independently navigable yet visually unified under the same design system.
4. Accessible and inclusive: WCAG AA is not optional — the site serves a broad public including older residents and students.
5. Progressive enhancement: static prototype works fully; API integration adds real content without changing the interface.

## Accessibility & Inclusion

WCAG AA target: 4.5:1 contrast, focus-visible outlines, 44px minimum touch targets, skip-link navigation, screen-reader friendly markup. Font sizes use `clamp()` for fluid scaling. Animations respect `prefers-reduced-motion`.
