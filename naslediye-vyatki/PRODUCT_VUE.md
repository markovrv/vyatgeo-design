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

Visitors browse the site on desktop and mobile. The landing page serves as a navigational hub with parallax hero, a real-data timeline of Kirov history milestones, module grid, video, and crafts carousel. Each module offers a filterable catalog with map and list views, linking to individual detail pages. All 6 catalog modules — Ethnography, Architecture of Kirov, Historic Cities, Archeology, Nature, and Victory Faces — are wired to their live WordPress REST APIs (see `src/composables/useFindings.js`, `useAttractions.js`, `useHistory.js`, `useArcheology.js`, `useOopt.js`, `useHeroes.js`, and the shared module patterns documented in `CLAUDE.md`). No catalog module runs on static/mock data anymore. Historic Cities has a different shape from the others: a `city` taxonomy groups events by city, a real Yandex map plots cities by their taxonomy-term coordinates, and each city's timeline is server-paginated (10 events per page, infinite scroll) with server-side century filter and search — the dataset (666+ events for Kirov alone) is too large to load and filter client-side like the other catalogs. Nature and Archeology additionally collapse their taxonomy filters (district/profile/species; era/type) into a toggleable "Фильтры" panel next to the map/catalog switch and search, instead of always-visible filter rows.

Beyond the 6 catalog modules plus the "About" section, cross-cutting WordPress features are also wired to the live backend, independent of any single module: WP Pages (`usePage.js` + a shared `PageView.vue` — hero with page title/photo + HTML content + comments; the "About" section is a regular WP page at slug `about`, auto-created on theme activation, not a hardcoded component anymore), WP Posts (a "News" section at `/news`), comments (guest read/write on all 6 catalog modules' object pages plus Pages/Posts), and a WordPress-managed nav menu for both header and footer (`useMenu.js`, custom `nv/v1/menu/{location}` REST route, auto-created default menus on theme activation, falling back to the hardcoded nav list until an editor customizes it in wp-admin).

## Capabilities and Constraints

- 6 catalog modules (thematic sections) with catalog/map views and detail pages, plus a 7th non-catalog section ("About")
- Filtering by era, type, ethnicity, style, preservation status, district, profile, protected species, personality status
- Fully responsive: desktop (1024px+) and mobile (<1024px hamburger nav)
- WCAG AA accessibility (4.5:1 contrast, focus-visible, min 44px targets)
- Design token system via CSS custom properties in `tokens.css`
- SPA architecture — lazy-loaded routes, plus short-form `/archives/*` matchers that resolve slug->ID client-side
- Shared components: FilterBar, MultiSelectFilter, CatalogLayout, KremlinVideo, Pagination, ImageLightbox, TypeFilterTiles, CommentsSection (guest comments, reused across object pages and WP Pages/Posts), plus module-specific map/controls components per module with a map (Architecture: AttractionMap/Controls/Gallery/Popup/ClusterIcon; Historic Cities: HistoryCitiesMap; Archeology: ArcheologyMap/Controls, reusing AttractionClusterIcon; Nature: NatureMap/Controls)
- Composables: useScrollFadeIn, useHeaderScroll (UI-only) alongside useFindings.js, useAttractions.js, useHistory.js, useArcheology.js, useOopt.js, useHeroes.js (live WP REST API data for all 6 catalog modules — list, single item, adjacent nav, nearby/similar, image-size thumbnails); useHtmlContent.js (shared decodeEntities/resolveRelativeUrls/SITE_URL helper); usePage.js, usePosts.js, useComments.js, useMenu.js (cross-cutting WP Pages/Posts/comments/menu, not tied to one catalog module)
- All 6 catalog modules have live WordPress backends (see `CLAUDE.md` for the established integration patterns, including two gotchas found while wiring up Nature/Victory Faces: WP REST caps `per_page` at 100 so datasets over that need pagination, and WP-generated taxonomy slugs are percent-encoded Cyrillic rather than transliterated, so filter options must never be hardcoded). Cross-cutting features (Pages, Posts, comments, menu) are live for all modules.
- Design + live-data integration complete for all 6 catalog modules. Victory Faces' real data covers only WWII Heroes of the Soviet Union — the mock's other historical-period cards (Golden Horde, WWI, etc.) were removed rather than kept as static filler, since the copy would otherwise overclaim coverage the data doesn't have.

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
      CatalogLayout.vue  # hero + filters + map/grid, slot-based (shared by all modules)
      KremlinVideo.vue   # muted, playbackRate 0.5, IntersectionObserver, disconnect
      Pagination.vue, ImageLightbox.vue, MultiSelectFilter.vue, TypeFilterTiles.vue, CommentsSection.vue
      AttractionMap/Controls/Gallery/Popup/ClusterIcon.vue  # Architecture module (Yandex map)
      HistoryCitiesMap.vue  # Historic Cities module (lighter Yandex map, no clustering/polygons/popup)
      ArcheologyMap/Controls.vue  # Archeology module (Yandex map, reuses AttractionClusterIcon)
      NatureMap/Controls.vue      # Nature module (Yandex map)
      HeroMap/Controls.vue        # Victory Faces module (Yandex map)
    composables/
      useScrollFadeIn.js  # staggered entrance (fadeStyle pattern)
      useHeaderScroll.js  # scrolled + isMobile reactive
      useFindings.js      # live API — Ethnography (findings/v1/*)
      useAttractions.js   # live API — Architecture (attraction/v1/*)
      useHistory.js       # live API — Historic Cities (history/v1/*): cities, paginated events, thumbnails
                           # (sessionStorage-backed cache), event detail, adjacent/nearby, homepage milestones
      useArcheology.js    # live API — Archeology (archeology/v1/* for adjacent/nearby, /wp/v2/archeology for catalog)
      useOopt.js           # live API — Nature (oopt/v1/* for adjacent/nearby, /wp/v2/oopt for catalog)
      useHeroes.js         # live API — Victory Faces (hero/v1/* for adjacent/nearby, /wp/v2/hero for catalog,
                           # paginated past WP's 100-per-page cap — 215/216 records)
      useHtmlContent.js   # shared decodeEntities/resolveRelativeUrls/SITE_URL helper
      usePage.js          # WP Pages, incl. "About" (slug "about") -- shared PageView.vue template
      usePosts.js         # WP Posts -- "News" section
      useComments.js      # comments: read approved + guest submit
      useMenu.js           # WP nav menu: useMenu (raw items) + useResolvedNav (render-ready, header+footer)
    views/
      IndexView.vue              # hero+parallax, modules, video, real-data Kirov milestones timeline, crafts, quote
      HeroesView.vue              # live API: catalog + map, hero_status filter, search
      HeroItemView.vue            # live API: detail, adjacent nav, nearby-by-distance, comments
      EthnographyView.vue        # live API: catalog + map view, type tiles, multi-select filters, search
      EthnographyItemView.vue    # live API: detail, gallery+lightbox, adjacent nav, similar items, edit link
      HistoricCitiesView.vue     # live API: city selection, real Yandex map (HistoryCitiesMap) + catalog, "coming soon" for cities with no events yet
      CityTimelineView.vue       # live API: server-paginated timeline per city (10/page, infinite scroll), server-side century filter + search, real event thumbnails
      CityEventView.vue          # live API: event detail, text-parsed gallery+captions, adjacent + nearby-in-time nav (Architecture-style item-nav/nearby-grid), edit link
      NatureView.vue              # live API: catalog + map, collapsible "Фильтры" panel (district/profile/species multi-select), search
      NatureItemView.vue          # live API: detail, protected-species chips w/ status, adjacent nav, nearby-by-distance, edit link, comments
      ArcheologyView.vue          # live API: catalog + map, collapsible "Фильтры" panel (era/type multi-select), search
      ArcheologyItemView.vue      # live API: detail, adjacent nav, nearby-by-distance, edit link, comments
      ArchitectureView.vue       # live API: catalog + Yandex map (clustering, historical polygons), search
      ArchitectureObjectView.vue # live API: detail, text-parsed gallery+captions, adjacent nav, nearby-by-distance, edit link
      PageView.vue               # live API: shared template for any WP page (hero with page title
                                  # + featured photo/fallback + HTML content + comments); used at
                                  # /page/:slug and at /about (slug fixed to "about")
      NewsView.vue                # live API: WP Posts catalog ("News"), server-paginated + search
      NewsItemView.vue            # live API: WP Post detail, comments
```

## Evidence on Hand

- 42 `.vue` files with complete UI (17 views + the rest shared components/layouts)
- 14 location/cultural images + 1 video in `src/assets/img/`
- Custom SVG logo embedded in SiteHeader/SiteFooter
- Full design token system in CSS custom properties
- About page (real stats, module links, partners, CTA) now lives as an editable WP page (slug "about") rendered through the shared PageView.vue template, auto-created on theme activation -- not a standalone component anymore
- Original prototype in `project/*.dc.html` preserved for reference

## Product Principles

1. Design-first: perfect the visual identity and UX before connecting real data — the prototype is the design authority.
2. Heritage authenticity: every visual choice must feel rooted in Vyatka's material culture (birch bark, Dymkovo patterns, northern Art Nouveau).
3. Modular by nature: each of the 7 modules is independently navigable yet visually unified under the same design system.
4. Accessible and inclusive: WCAG AA is not optional — the site serves a broad public including older residents and students.
5. Progressive enhancement: static prototype works fully; API integration adds real content without changing the interface.

## Accessibility & Inclusion

WCAG AA target: 4.5:1 contrast, focus-visible outlines, 44px minimum touch targets, skip-link navigation, screen-reader friendly markup. Font sizes use `clamp()` for fluid scaling. Animations respect `prefers-reduced-motion`.
