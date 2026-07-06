# Phase D1.1 - TSEMOU Homepage Product Specification

## 1. Vision

TSEMOU homepage is the first public experience of a civic intelligence platform, not a traditional news portal.

The homepage must make users feel:
- informed by verified facts
- connected to real human impact
- invited to participate in constructive progress

Core public motto:

See.
Feel.
Fix.

Institutional line:

Verified Information. Connected Knowledge. Better Decisions.

## 2. User Promise

TSEMOU promises that the homepage will:
- surface what matters most, not what is most sensational
- keep important stories alive while people are still affected
- show evidence and impact context before opinion noise
- provide a clear path to participate through TSEMIT actions
- support daily return behavior with meaningful updates

Time-to-understand promises:
- within 3 seconds: user sees this is not another news site
- within 15 seconds: user finds at least one Living Story worth opening
- within 60 seconds: user understands TSEMIT is participation, not passive reaction

## 3. Homepage Layout

Desktop default layout is a three-column structure:
- left column: Community Intelligence
- center column: Living Stories Feed
- right column: Live News Stream

Global page structure order:
1. Header
2. Hero
3. Three-column content area

Header includes:
- TSEMOU logo
- search
- categories/topics
- submit
- language
- account/login

Hero includes exactly:

See.
Feel.
Fix.

Then:

Verified Information. Connected Knowledge. Better Decisions.

No extra slogan text is inserted between the three core words.

## 4. Left Column Specification

Column name: Community Intelligence

Purpose:
- show collective civic signal
- reveal positive and negative public impact patterns
- highlight where community attention is asking for solutions

Required sections:
- Best Companies
- Worst Companies
- Best Celebrities / Public Figures
- Worst Celebrities / Public Figures
- Community Discoveries
- Most Wanted Solutions
- Active TSEMITs

Behavioral rules:
- concise ranked lists with compact context
- each list item should expose why it appears (e.g., evidence trend, community activity, solution momentum)
- left column is scan-first and decision-supporting, not long-form reading

## 5. Center Feed Specification

Column name: Living Stories Feed

Purpose:
- serve as the primary homepage experience
- present continuously evolving civic stories with evidence and participation context

Concept:
A story remains alive while any of these are true:
- people are still affected
- evidence is still evolving
- accountability is unresolved
- solutions are still needed

Feed behavior:
- familiar infinite-feed mental model
- ranked by civic value, not outrage
- cards prioritize clarity and update visibility

Ranking dimensions for center feed:
- social importance
- human impact
- evidence strength
- freshness
- community participation
- solution potential

## 6. Right Live Stream Specification

Column name: Live News Stream

Purpose:
- preserve user familiarity with standard daily news consumption
- ingest broad incoming updates, including lower-impact items

Required item fields:
- headline/title
- timestamp
- source name
- small impact indicator
- option to open or follow

Promotion rule:
- right-column items can be promoted into center Living Stories when they gain social importance, new evidence, or meaningful community activity

Design intent:
- right stream is high-frequency and lightweight
- center feed remains high-value and context-rich

## 7. TSEMIT Interaction Model

TSEMIT is the core participation action for TSEMOU.

Definition:
TSEMIT means the user joins a story constructively.

Initial TSEMIT options:
- Add information
- Add evidence
- Connect related story
- Report update
- Propose solution
- Discuss
- Support a solution

Placement:
- visible on center story cards
- clear primary action label

Behavior principles:
- action-first, not reaction-first
- contribution options should map to real civic progress pathways
- participation should be understandable in one interaction

## 8. Story Card Anatomy

Each center feed story card must include:
- title
- short summary
- importance label
- last updated
- affected people / communities
- evidence count
- connected companies
- connected countries
- open questions
- solution count
- TSEMIT button

Card communication hierarchy:
1. What happened / what is changing
2. Why it matters to people
3. What evidence exists
4. What can be done next

Card quality rules:
- no clickbait headlines
- no rage bait framing
- no gossip-first prioritization

## 9. Ranking Philosophy

Ranking objective:
maximize public value and constructive action potential, not short-term engagement spikes.

Core ranking philosophy:
- verification outranks virality
- social harm/benefit outranks celebrity noise
- unresolved impact outranks novelty
- solution paths outrank outrage loops

Anti-manipulation principles:
- no popularity-only ranking
- no endless emotional escalation mechanics
- no hidden pressure loops optimized for addictive scrolling

## 10. First Version Scope

Phase D1.1 scope includes specification targets for:
- homepage information architecture
- three-column behavior model
- hero and header messaging model
- story card mandatory fields
- TSEMIT action taxonomy (initial set)
- center feed ranking dimensions (conceptual, not algorithm implementation)
- promotion rule from right stream to center feed

This scope is product-definition only.

## 11. What Is Intentionally NOT Included Yet

Not included in D1.1 specification delivery:
- runtime implementation code
- final ranking algorithm weights
- advanced personalization logic
- notification systems
- reputation mechanics expansion
- moderation workflow implementation details
- full mobile interaction specification
- frontend visual system implementation (CSS/JS)
- backend API contracts

## 12. Future Dynamic Integrations

Planned follow-on integrations after D1.1 specification approval:
- dynamic ranking engine configuration
- real-time evidence signal updates
- community action analytics for TSEMIT outcomes
- adaptive topic/category routing
- cross-story relationship surfacing from graph signals
- progressive localization and language adaptation
- trust and policy overlays where already stable

These are roadmap integrations and are out of implementation scope for this document.

## 13. Success Criteria

Homepage success criteria for early product validation:
- user can identify TSEMOU as distinct from a standard news site within 3 seconds
- user can discover at least one compelling Living Story within 15 seconds
- user can understand TSEMIT as participation within 60 seconds
- users can reliably see what changed since last visit
- users can identify at least one constructive next action from the homepage
- homepage avoids clickbait, rage bait, and gossip-first behavior patterns
- center feed consistently prioritizes socially important, evidence-backed stories
- right stream preserves daily habit relevance without dominating the page mission

## 14. Community Engine - Seed to Living Case System

### Product Decision

TSEMOU Community Engine defines how public submissions become mature civic cases.

Users can submit:
- Stories
- Posts
- Evidence
- Questions
- Corrections
- Solutions
- general information

All submissions first appear as Community Feed items in the right column.

The right column is not a simple chronological stream. It is a live repeated flow ranked by importance and popularity signals.

Each feed item can receive:
- TSEMIT
- UNTSEMIT
- comments
- added evidence
- discussion
- support
- user credibility signals

The objective is not likes. The objective is helping important stories rise.

### Seed Lifecycle

Each community submission is treated as a Seed with this lifecycle:

1. Seed
2. Growing
3. Trending
4. Rising
5. Living Case
6. Global Case

### Column Logic

System interpretation by column:
- LEFT = what is rising
- RIGHT = what is being created now
- CENTER = what has matured into Living Case

Left column shows Community Rankings for Seeds/Stories rising in value.

Right column shows live community activity and active submissions.

Center column shows promoted Living Cases that have matured beyond raw submission state.

### Promotion Rule (Initial Product Concept)

Top ranked community items can be promoted into the center Living Case feed.

Initial visibility rotation:
- #1 ranked item: promoted/surfaced every 1 hour
- #2 ranked item: promoted/surfaced every 2 hours
- #3 ranked item: promoted/surfaced every 4 hours

This is an initial product rule, not the final ranking algorithm.

### Collective Value Principle

A story must not rise from popularity alone. It must rise from collective value.

Community Value may include:
- TSEMIT count
- UNTSEMIT count
- evidence added
- number of unique participants
- comment quality
- user credibility
- freshness
- growth velocity
- social importance
- source reliability

### Scope Clarification

This section is product blueprint documentation only.

No backend logic is implemented in this step.

Existing UI baseline decisions remain preserved.
