# TSEMOU Homepage Prototype (Phase D1.3)

## Project structure

- `index.html`: homepage structure and semantic layout
- `styles.css`: visual design, responsiveness, spacing, typography, motion
- `script.js`: fake realistic data rendering, feed generation, search filtering, small interactions

## How to open

1. Open `prototype/homepage/index.html` directly in any modern browser.
2. No build step or dependency installation is required.

## Design decisions

- Three-column desktop layout:
  - Left: Community Intelligence
  - Center: Living Stories Feed
  - Right: Live News Stream
- Sticky header for persistent navigation and search access.
- Sticky sidebars on larger screens for constant civic context and live updates.
- Center feed prioritized for deep reading and participation.
- Hero message is exactly:
  - See.
  - Feel.
  - Fix.
  - Verified Information.
  - Connected Knowledge.
  - Better Decisions.
- TSEMIT button appears prominently on every story card.
- Data is fake but realistic:
  - 12 Living Story cards
  - 40 Live News items
  - Full Community Intelligence side modules

## Known placeholders

- Story and news data are static demo objects in `script.js`.
- Images are external placeholder photos and may differ per load source.
- Topic chips currently provide visual state only.
- TSEMIT buttons are intentionally non-functional in this prototype.
- No backend, identity, moderation, or analytics integration yet.

## Future WordPress integration plan

1. Keep this prototype as the visual baseline.
2. Map each section to existing data sources and modules gradually.
3. Replace fake data with API endpoints or server-rendered payloads.
4. Connect TSEMIT actions to approved participation flows.
5. Add caching, pagination, and security controls before production.
6. Perform accessibility and performance validation after integration.

No PHP, shortcode, or engine integration is included in this prototype.
