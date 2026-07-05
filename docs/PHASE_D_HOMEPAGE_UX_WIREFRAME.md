# Phase D1.2 - Homepage UX Wireframe

## Context

This UX wireframe translates the approved homepage product specification into concrete user navigation, visual hierarchy, and interaction expectations.

TSEMOU homepage is not a news website front page. It is the first experience of a living knowledge platform where users can:

See.
Feel.
Fix.

## 1. Desktop Layout

### Overall width
- Primary content container targets a wide desktop canvas with comfortable line lengths for reading and scanning.
- The layout should avoid edge-to-edge text blocks; leave generous outer gutters to reduce cognitive fatigue.

### Three-column proportions
- Left sidebar (Community Intelligence): about 22% to 25%
- Center column (Living Stories Feed): about 50% to 56%
- Right sidebar (Live News Stream): about 22% to 25%
- Center column remains visually dominant at all times.

### Header
- Full-width top header with logo, search entry point, topics/categories, submit, language, and account/login.
- Header remains stable and predictable; no aggressive animations.

### Hero
- Sits directly under the header.
- Minimal vertical block with stacked motto and institutional line.
- Search is directly below hero statement for immediate action.

### Center feed
- Main reading lane with continuous story cards.
- Strong vertical rhythm and clear card boundaries.

### Left sidebar
- Ranked civic intelligence modules.
- Denser information density than center feed, but still easy to scan.

### Right sidebar
- Fast, chronological live stream.
- Lightweight card style with timestamp and source prominence.

### Footer
- Quiet utility area only.
- Should not compete with feed attention.

### Scrolling behavior
- Standard page scroll behavior; no custom scroll interactions.
- Center feed drives long-session usage.
- Sidebars continue updating in-place while preserving user orientation.

### Sticky elements
- Header is sticky.
- Left and right sidebar internal headings may stick while their content scrolls.
- TSEMIT action remains visible within each story card without forcing floating overlays.

## 2. Mobile Layout

### How columns collapse
- Collapse into a single primary column.
- Center Living Stories Feed is first.
- Community Intelligence and Live News become stacked sections below the first set of stories, with quick jump navigation.

### Priority order
1. Header (compact)
2. Hero
3. Living Stories Feed
4. TSEMIT actions
5. Community Intelligence highlights
6. Live News Stream
7. Footer utilities

### Story cards
- Full-width card presentation.
- Larger touch targets and fewer simultaneous metadata chips per line.
- Key metrics prioritized over dense detail.

### Sidebars
- Presented as collapsible blocks or tab-like sections below initial feed cards.
- Show top highlights first, with option to expand.

### TSEMIT
- Persistent action inside each story card.
- One-tap entry into contribution options.

### Search
- Persistent search icon in header and a full search bar under hero.

### Navigation
- Compact top navigation with essential items only.
- Categories available via expandable drawer.

### Bottom navigation
- Recommended for mobile:
  - Home
  - Stories
  - Live
  - TSEMIT
  - Account
- Keep icon labels explicit to reduce ambiguity.

## 3. Tablet Layout

- Tablet behaves as an intermediate between desktop and mobile.
- Prefer two-column emphasis:
  - Center feed as primary
  - Secondary column toggles between Community Intelligence and Live News
- In landscape, allow a 2.5-column feel with narrower tertiary panel if space permits.
- In portrait, stack secondary modules below every few center cards to maintain flow.

## 4. Hero Section

Hero contains only:

See.

Feel.

Fix.

Each word is stacked vertically with strong typographic clarity.

Below hero words:

Verified Information.
Connected Knowledge.
Better Decisions.

Search sits immediately below this text.

Hero style principles:
- minimal
- clean
- high contrast
- no marketing copy
- no decorative distraction

## 5. Living Story Feed

### Feed behavior
- Continuous scrolling list of Living Story cards.
- New content loads progressively without breaking reading momentum.

### Card spacing
- Comfortable vertical spacing to distinguish stories.
- Tight enough for momentum, loose enough for comprehension.

### Image ratio
- Consistent visual thumbnail ratio across cards to stabilize scanning.
- Prefer landscape ratio that supports context imagery without dominating text.

### Required card content
- Headline
- Summary
- Evidence signals
- Connected companies
- Connected countries
- Solution count
- Last update
- Importance badge
- Timeline indicator
- TSEMIT action

### Feed objective
- Encourage continuous scrolling through meaningful updates, not addictive manipulation loops.

## 6. Live News Stream

- Located in right column on desktop.
- Continuous chronological flow with compact cards.
- Designed for fast scan behavior and daily habit continuity.

Each live item highlights:
- source
- timestamp
- short title
- small impact indicator
- quick open/follow action

Purpose is context continuity, not primary civic decision-making. High-impact items can graduate into Living Stories.

## 7. Community Intelligence

- Located in left column on desktop.
- Structured as ranked civic modules with clear labels and short justifications.

Include:
- Best Companies
- Worst Companies
- Best Public Figures
- Worst Public Figures
- Most Active Community
- Solutions
- Community Discoveries

Visual priority:
- top priority: rankings with strongest verified signal change
- medium priority: active community and solutions momentum
- supporting priority: discoveries and long-tail updates

The section should communicate public accountability and constructive action opportunities at a glance.

## 8. Story Card Anatomy

Exact order:
1. Image
2. Importance
3. Title
4. Summary
5. Evidence
6. Entities
7. Timeline
8. TSEMIT

Behavior notes:
- Importance appears early to frame urgency.
- Evidence and entities appear before timeline and action to establish trust first.
- TSEMIT appears at the end of the card structure as a clear next step.

## 9. TSEMIT Experience

### When users notice TSEMIT
- Immediately after understanding story context and impact signals.
- TSEMIT should appear as a natural progression from reading to action.

### Why they click
- They want to contribute facts, evidence, links, updates, or solutions.
- They want to feel useful, not just reactive.

### Expected emotional response
- Clarity: I know what this action does.
- Agency: My contribution can matter.
- Safety: I can participate without chaos.

### First interaction
- User sees clear participation options, not a blank input wall.
- Options map to practical civic actions.

### Trust and participation
- TSEMIT flow should signal verification discipline and respectful contribution norms.
- The UX should avoid social pressure patterns and reward quality over speed.

## 10. Visual Hierarchy

### Typography
- Strong headline hierarchy for story importance.
- Highly readable body text for summaries and evidence cues.
- Metadata typography is compact but legible.

### White space
- Generous spacing around major structural blocks.
- Consistent card padding to reduce cognitive load.

### Color usage
- Neutral base with purposeful accent colors for importance and action cues.
- Avoid emotional over-amplification color schemes.

### Contrast
- High text/background contrast for readability in long sessions.
- Distinct visual separation between feed and sidebars.

### Eye movement
- Intended flow on desktop:
  - Hero framing
  - Center card headline
  - Left civic context
  - Right live updates
  - Return to center for deeper engagement

### Scrolling rhythm
- Repeating card cadence with subtle variation to prevent monotony.
- No visual interruptions that reset orientation.

## 11. Psychology

### After 3 seconds
User should feel:
- This is not another click-first news homepage.
- This interface is serious, clear, and purpose-driven.

### After 15 seconds
User should feel:
- At least one story appears socially important and worth opening.
- The page helps prioritize what truly matters.

### After 1 minute
User should feel:
- TSEMIT is a practical way to participate.
- Evidence and impact are central, not optional.

### After 5 minutes
User should feel:
- They understand ongoing stories, who is affected, and what actions are possible.
- Returning later will provide meaningful updates, not repetitive noise.

### Why return tomorrow
- Stories evolve visibly.
- Evidence and participation activity changes daily.
- Users can track progress, setbacks, and solution movement.

## 12. Empty States

### No stories
- Show clear empty-state card explaining that Living Stories will appear as verified activity arrives.
- Provide immediate actions: explore Live News, explore Community Intelligence, submit first contribution.

### No community activity
- Show onboarding prompts for first participation actions.
- Explain what counts as a useful contribution.

### No rankings
- Show placeholder ranking blocks with explanatory text about pending signal thresholds.

Empty states must feel calm and purposeful, never broken or abandoned.

## 13. Loading States

- Use skeleton cards matching final card structure.
- Load content progressively in visible priority order:
  - hero and first center cards
  - left highlights
  - right live stream
- Lazy-load lower viewport cards.
- Preserve layout stability while content appears.

The loading experience should communicate reliability and reduce uncertainty.

## 14. Accessibility

- Readable typography and clear typographic scale.
- Keyboard navigation through header, feed cards, and TSEMIT actions.
- Visible focus states for all interactive components.
- Strong contrast for text, badges, and action controls.
- Touch targets sized for mobile usability.
- Screen-reader friendly labels for key story metadata and actions.
- Logical heading structure that mirrors visual hierarchy.

## 15. Success Criteria

Before frontend implementation, UX is considered successful when:
- users can distinguish TSEMOU from a traditional news homepage immediately
- users can identify a Living Story and its civic importance quickly
- users understand where to act through TSEMIT without guidance
- desktop, tablet, and mobile hierarchy is explicit and internally consistent
- sidebars support context without distracting from center feed
- empty and loading states are defined well enough to prevent ambiguous implementation
- accessibility expectations are explicit for design and engineering handoff

---

This wireframe document is UX-only and implementation-neutral.
