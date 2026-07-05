# 1. Current Architecture

## Canonical Evidence Model Decision

Canonical Evidence Model: `tsemou_proof`

Meaning:
- `evidence` is a conceptual name only.
- `tsemou_proof` is the persistent WordPress CPT for beta.
- All new Phase C.6 integration must write to `tsemou_proof`.
- Legacy evidence paths may remain temporarily but must not be extended.
- No migration is performed in this step.
- This decision supports the MVT by removing duplicate evidence lifecycle ambiguity.

Current module inventory from [includes/class-core.php](c:/TSEMOU/tsemou-core/includes/class-core.php) and [modules](c:/TSEMOU/tsemou-core/modules).

| Module / Engine | Purpose | Status | Used by current pipeline? | Needed for MVT? |
|---|---|---|---|---|
| `configuration-os` | Stores/configures discovery wave tasks and acquisition settings | Partial | Yes, seeds Discovery Orchestrator | Important |
| `discovery-orchestrator` | Queue/runtime coordinator for Phase A acquisition steps | Partial but active | Yes | Critical |
| `source-discovery` | Resolves candidate sources from discovery tasks | Partial but active | Yes | Critical for acquisition |
| `scraping-engine` | Fetches/parses/normalizes source content into raw runtime records | Partial but active | Yes | Critical for acquisition |
| `automatic-evidence-creation` | Converts raw acquisition output into runtime evidence drafts | Prototype-to-partial | Yes | Critical if acquisition is in MVT |
| `automatic-linking` | Creates runtime links between evidence/company/source and triggers graph context update | Partial but active | Yes | Critical if acquisition is in MVT |
| `entity-engine` | Normalizes internal entities into a stable contract | Partial, newly implemented core | No, not yet used by Discovery Orchestrator | Critical |
| `relationship-engine` | Normalizes and validates inter-entity relationships | Partial, newly implemented MVT core | No, not yet used by Discovery Orchestrator | Critical |
| `knowledge-graph` | Stores/administers entity relationships using option-based persistence | Partial but active | Yes, via Automatic Linking and other paths | Critical |
| `knowledge-graph-update-engine` | Builds/validates graph update payloads without persistence changes | Partial, newly implemented MVT core | No, not yet used by Discovery Orchestrator | Critical |
| `entity-evidence-links` | Stores evidence↔entity links on evidence metadata | Partial but active | Indirectly used | Important |
| `evidence-engine` | Normalizes evidence records and company evidence retrieval | Partial but active | Indirectly used by public/company views | Critical |
| `evidence-processing-engine` | Story → structured evidence/relationships/graph payload orchestration | Partial, newly implemented MVT core | No, not yet wired into orchestrator | Critical |
| `proof-engine` | Registers and manages `tsemou_proof` evidence/proof CPT | Active | Yes, evidence data source | Critical |
| `story` | Registers and manages `story` CPT as internal file/investigation workspace | Active | Yes, Story is core editorial object | Critical |
| `company-engine` | Company relationships, company page shortcodes, company page behavior | Active | Yes, public-facing company experience depends on it | Critical |
| `company-section-engine` | Evidence/event linking and section payload support around companies | Partial but active | Indirectly yes | Important |
| `company-discovery` | Company directory/discovery admin and shortcode support | Partial | Public/admin support, not core processing | Important |
| `company-sensor` | Company monitoring / import / sensor-style acquisition support | Prototype/partial | Not central today | Future |
| `company-intelligence-engine` | Company intelligence layer | Partial/prototype | Not central in current runtime path | Important |
| `company-intelligence` | Company intelligence admin/public helpers | Partial/prototype | Indirect/public support | Important |
| `source-object` | Canonical source object handling from URLs/evidence | Partial but active | Yes, used in discovery/evidence flows | Important |
| `source-intelligence` | Source credibility/publisher intelligence | Partial but active | Yes, used by evidence/discovery | Important |
| `discovery-engine` | Admin-managed discovery candidates and evidence creation from candidates | Partial | Separate/manual pipeline, not main runtime path | Important |
| `developer-console` | Diagnostics and architecture visibility | Active | No production pipeline role | Important for ops |
| `policy-engine` | Policy decisions / rule layer | Partial | Used by Event Intelligence, not core public beta path yet | Important |
| `trust-engine` | Trust scoring and community vote mechanics | Partial | Public shortcodes exist; not central to current acquisition runtime | Important |
| `event-identity` | Story-level event identity analysis | Partial but active | Yes, on story save | Important |
| `event-resolver` | Event resolution/duplication logic | Partial | Used by Event Intelligence | Important |
| `event-timeline` | Timeline structures for resolved events | Partial | Used by Event Intelligence / company timeline | Important |
| `event-intelligence` | Orchestrates story → event intelligence stages | Partial but substantial | Yes, on story save | Important |
| `event-intelligence-phase-c` | Phase C story/living story/question/explanation/AI-adapter classes | Prototype / not loader-integrated | No | Future |
| `automatic-linking` graph update path | Writes context relationships into Knowledge Graph | Partial | Yes | Critical |
| `trust-engine` community moderation | Admin/community moderation and voting mechanics | Partial | Public surface exists, but not beta-critical | Future |

## Status notes
- `event-intelligence-phase-c` exists in code but is not loaded by [includes/class-core.php](c:/TSEMOU/tsemou-core/includes/class-core.php). That makes it present but not active.
- The Knowledge Graph module has overlapping implementations:
  - [modules/knowledge-graph/class-knowledge-graph.php](c:/TSEMOU/tsemou-core/modules/knowledge-graph/class-knowledge-graph.php) is the active one loaded by core.
  - [modules/knowledge-graph/class-knowledge-graph-engine.php](c:/TSEMOU/tsemou-core/modules/knowledge-graph/class-knowledge-graph-engine.php) appears to be an alternate/legacy implementation surface.
- Current versioning is inconsistent:
  - [MASTER_PROJECT_MEMORY.md](c:/TSEMOU/tsemou-core/MASTER_PROJECT_MEMORY.md) says `v5.0.x`
  - [tsemou-core.php](c:/TSEMOU/tsemou-core/tsemou-core.php) still declares `3.0.11`

That inconsistency is release-critical.

# 2. Processing Pipeline

## Current real pipeline

There is not yet one unified end-to-end pipeline from Source to Story to Public Page.

There are two real, separate paths:

## A. Acquisition path
Source task  
↓  
Configuration OS task data  
↓  
Discovery Orchestrator queue  
↓  
Company Discovery step  
↓  
Source Discovery  
↓  
Scraping Engine  
↓  
Raw Evidence runtime record  
↓  
Automatic Evidence Creation  
↓  
Evidence Draft runtime file  
↓  
Automatic Linking  
↓  
Knowledge Graph context relation update  
↓  
Stop

This is the current real runtime path coordinated by [modules/discovery-orchestrator/class-discovery-orchestrator.php](c:/TSEMOU/tsemou-core/modules/discovery-orchestrator/class-discovery-orchestrator.php).

### What is missing here
- No automatic Story creation from acquired source material
- No automatic persistent Evidence post creation in the main Phase A runtime
- No editorial approval bridge
- No public-page publication step
- No final page assembly step

## B. Story/editorial path
Story post  
↓  
Story save  
↓  
Story metadata + company relationships saved  
↓  
Event Identity analysis  
↓  
Optional Event Intelligence orchestration  
↓  
Optional graph writes through Event Identity / Event Graph Adapter  
↓  
Now also available: Evidence Processing Engine  
↓  
Graph payload preparation through Knowledge Graph Update Engine  
↓  
Return internal result

### What is missing here
- No unified orchestrator between Story and Evidence Processing
- No persistent evidence materialization from Story processing
- No canonical handoff to public rendering
- No single publish-ready story/evidence/graph lifecycle

## C. Public page path
Company post / company route  
↓  
Company Engine shortcodes and template hooks  
↓  
Company Section / Evidence / Timeline / Related content assembly  
↓  
Public company page render

### What is missing here
- No unified public evidence explorer
- No interactive civic interface
- No graph-backed public explanation layer
- No integrated Story → Evidence → Graph → Public Page assembly path

## Missing steps in the intended Source → Story → ... → Public Page chain
If the intended chain is:

Source  
↓  
Story  
↓  
...  
↓  
Public Page

then the currently missing or incomplete steps are:

- Source → Story bridge
- Story → persistent structured Evidence bridge
- persistent Evidence → canonical graph update bridge
- graph state → public-page assembly bridge
- editorial approval / publish workflow normalization
- one canonical orchestrator for business flow across acquisition and editorial paths

# 3. Existing Orchestrators

## Discovery Orchestrator
File:
- [modules/discovery-orchestrator/class-discovery-orchestrator.php](c:/TSEMOU/tsemou-core/modules/discovery-orchestrator/class-discovery-orchestrator.php)

Responsibility:
- Phase A acquisition runtime
- queue/state/log management
- step dispatch
- scheduled/manual execution

Current usage:
- active
- central coordinator for acquisition/runtime queue only

Future usage:
- strongest candidate to become the central multi-phase coordinator

## Event Intelligence Orchestrator
File:
- [modules/event-intelligence/class-event-intelligence-orchestrator.php](c:/TSEMOU/tsemou-core/modules/event-intelligence/class-event-intelligence-orchestrator.php)

Responsibility:
- story-level event analysis pipeline
- story → evidence → policy → importance → trust → identity → resolver → timeline → graph

Current usage:
- active on `save_post_story`
- internal story intelligence orchestration

Future usage:
- remain a domain orchestrator for event/story intelligence
- not ideal as whole-platform coordinator

## Phase C Orchestrator
Files exist under:
- [modules/event-intelligence-phase-c](c:/TSEMOU/tsemou-core/modules/event-intelligence-phase-c)

Responsibility:
- Phase C narrative/intelligence pipeline

Current usage:
- not loader-integrated
- not active in real runtime flow

Future usage:
- optional later domain orchestrator
- not current MVT path

## Can Discovery Orchestrator become the central pipeline?
Yes.

Why:
- it already owns queueing, dispatch, runtime lifecycle, and payload handoff
- it already models pipeline stages explicitly
- it already coordinates multiple engines

What would change minimally:
- generalize from “Phase A only” to multi-phase
- add business-engine executors such as `evidence_processing`
- broaden queue payload conventions beyond acquisition-only fields
- keep engine logic inside each engine and use Discovery Orchestrator only for coordination

# 4. Knowledge Graph

## What already works
Current active implementation:
- [modules/knowledge-graph/class-knowledge-graph.php](c:/TSEMOU/tsemou-core/modules/knowledge-graph/class-knowledge-graph.php)

Working today:
- relationship type registry
- relevance flags
- relationship object builder
- persisted relationship storage in WordPress options
- add/update relationship
- status update
- graph stats
- logs
- admin management UI

Additional graph-related layers now exist:
- [modules/knowledge-graph/class-knowledge-graph-update-engine.php](c:/TSEMOU/tsemou-core/modules/knowledge-graph/class-knowledge-graph-update-engine.php)
- [modules/event-intelligence/class-event-graph-adapter.php](c:/TSEMOU/tsemou-core/modules/event-intelligence/class-event-graph-adapter.php)
- [modules/automatic-linking/class-automatic-linking.php](c:/TSEMOU/tsemou-core/modules/automatic-linking/class-automatic-linking.php)

## What is still missing
- no canonical single write path
- no graph query API for downstream modules
- no graph traversal
- no search
- no public graph view
- no stable abstraction between normalized relationships and persisted graph relationships
- no unified relationship persistence strategy between:
  - `Entity_Evidence_Links`
  - `Knowledge_Graph`
  - new `Relationship_Engine`
  - new `Knowledge_Graph_Update_Engine`

## Is it sufficient for MVT?
Yes, with constraints.

Sufficient if MVT means:
- internal relationship persistence
- basic graph coordination
- no advanced traversal/search
- no public graph explorer

Not sufficient if MVT requires:
- public graph navigation
- cross-entity graph querying
- performant graph-backed recommendations/search
- unified graph semantics across all engines

# 5. Evidence Flow

## Current flow
There are several evidence paths:

### Phase A acquisition evidence path
Source Discovery  
→ Scraping Engine  
→ Raw runtime record  
→ Automatic Evidence Creation  
→ runtime Evidence Draft  
→ Automatic Linking  
→ optional graph context relation

### Discovery Engine admin path
Discovery candidate  
→ admin create evidence  
→ persistent `evidence` or `tsemou_proof` post  
→ company/source metadata linked  
→ optional knowledge graph link helper

### Existing Proof Engine path
Manual/admin creation of `tsemou_proof`  
→ metadata saved  
→ evidence profile/intelligence generation  
→ company trust recalculation

### New Evidence Processing Engine path
Story  
→ validation  
→ Entity normalization  
→ evidence payload creation  
→ relationship creation  
→ graph payload coordination  
→ internal result only

## Missing parts
- no single canonical evidence creation path
- two evidence models are still present in the codebase, but only one is now canonical for beta:
  - conceptual/legacy: `evidence`
  - canonical persistent CPT: `tsemou_proof`
- runtime drafts and persistent evidence posts are not unified
- Story processing does not yet materialize persistent evidence
- evidence normalization is present, but lifecycle ownership is split across multiple modules

# 6. Company Flow

## Current flow
Company data today is built from:

- `company` posts
- company meta
- company relationships from Story
- evidence linked by company meta / entity meta / relationship fields
- trust scores from Trust Engine
- timeline/event support from Company Section Engine
- public page rendering via Company Engine shortcodes/template hooks

### Current company lifecycle
Company post  
↓  
manual/admin metadata  
↓  
Story relationships and evidence links  
↓  
evidence counters / trust / related sections  
↓  
public company page rendering

## Missing parts
- no single canonical company ingestion pipeline
- no stable entity-first replacement for company-specific linking yet
- company public page still depends on multiple legacy meta conventions
- graph/entity/relationship systems are not yet the sole source of truth for company connections

# 7. Public Experience

## What a visitor can do today
A visitor can likely:
- view public company pages
- view story pages / files because `story` is public
- view `tsemou_proof` posts because that CPT is public
- see company-related evidence/timeline/related content where page assembly exists
- possibly use trust/community shortcodes if surfaced on public pages
- view company directory output if embedded by shortcode

## What a visitor cannot do today
A visitor cannot:
- interact with Phase C.5 civic intelligence features
- ask follow-up questions
- compare cases in a structured way
- see a public relationship graph explorer
- navigate a canonical evidence graph UI
- receive coordinated story → evidence → graph → explanation output
- contribute through a stable participation workflow
- use a unified public beta experience across Story, Evidence, Company, and Graph

This is still a mixed admin-heavy/internal-heavy platform with partial public surfaces.

# 8. MVT Gap Analysis

## Critical
- Apply the canonical evidence model consistently across all new Phase C.6 integration:
  - persistent beta evidence must be `tsemou_proof`
- Unify Story → Evidence → Graph into one real pipeline
- Decide whether Discovery Orchestrator becomes the central coordinator now
- Add persistent handoff from Evidence Processing Engine to `tsemou_proof`
- Standardize graph write path so new normalized relationship/graph engines become the canonical route
- Resolve release/build/versioning inconsistency:
  - docs say `v5.0.x`
  - plugin header still says `3.0.11`
  - build process is not consistently tracked in repo
- Define one public beta surface:
  - most likely company page as the primary public interface

## Important
- Normalize company linkage to entity/relationship contracts instead of legacy mixed meta
- Make Event Intelligence and Evidence Processing coexist coherently
- Decide whether `story` is internal-only or also part of public beta
- Add quick validation/smoke tests for the new service engines
- Reduce duplicate/parallel graph/evidence link mechanisms

## Future
- Interactive Citizen Experience features
- public graph navigation
- AI-assisted explanation
- advanced search
- background processing
- reputation/community systems
- graph traversal
- queue expansion beyond acquisition runtime

# 9. Phase D Readiness

**NO**

Why:
- the platform does not yet have a single canonical business pipeline from Story to persistent Evidence to graph-backed public output
- core MVT engines exist, but they are not yet integrated into the actual runtime orchestrator
- public experience is still fragmented
- evidence lifecycle is not unified
- graph coordination exists, but graph semantics and persistence routes are still split
- release hygiene is not stable enough for a clean Phase D foundation

Phase D should not start until the MVT foundation is unified and productionized.

# 10. Recommended Roadmap

Shortest path to a public beta, ignoring non-essential future features:

1. **Freeze the canonical content model**
- decide:
  - public Company page is the primary beta page
  - Story is internal editorial workspace
  - one canonical persistent evidence model only: `tsemou_proof`

2. **Promote Discovery Orchestrator into the central coordinator**
- extend it minimally beyond Phase A
- add a business step for Evidence Processing
- keep all business logic in the engines

3. **Connect Story processing to persistent evidence**
- Evidence Processing Engine must hand off into `tsemou_proof`
- not just return internal payloads

8. **Begin Phase C.6 Integration**
- unify Discovery, Story, Evidence Processing, `tsemou_proof`, Knowledge Graph, and Company Page around the canonical evidence model

4. **Standardize graph updates**
- all new business flows should use:
  - Entity Engine
  - Relationship Engine
  - Knowledge Graph Update Engine
- then persist through one graph write path

5. **Stabilize the public beta page**
- company page should consume:
  - company data
  - canonical evidence
  - graph-backed relationships
  - trust/timeline only where already stable

6. **Clean release mechanics**
- fix versioning mismatch
- track a real build process in repo
- verify plugin install/activate/package path

7. **Run a narrow beta smoke cycle**
- install plugin
- activate plugin
- create/update Story
- process Story into evidence
- confirm graph update
- confirm company page reflects connected evidence

That is the shortest architecture-preserving path to a first public beta.
