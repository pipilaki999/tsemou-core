# Event Intelligence Core – Technical Design

## Status
- Proposed design only.
- No existing code will be modified as part of this document.
- The design is intentionally additive and built around reuse of the current TSEMOU architecture.

## 1. Proposed architecture

The Event Intelligence Core is a new orchestration layer that sits above the existing TSEMOU modules and turns a TSEMOU story into a structured, policy-aware event intelligence object.

### Design goal
Create one central intelligence service that can:
- interpret the context of a story,
- understand the entities, evidence, and semantics around it,
- estimate public importance,
- apply policy rules,
- enrich the event through search and knowledge graph relationships,
- expose a consistent event intelligence payload for future UI and automation layers.

### Architectural position
The new core should not replace the existing modules. Instead, it should act as a coordinator that reuses:
- Story module as the event workspace and human context source.
- Evidence and Proof modules as evidence carriers.
- Company and Entity modules as identity and relationship anchors.
- Policy Engine as the weighting and gating authority.
- Discovery and Source Discovery as retrieval/search entry points.
- Knowledge Graph as the structured relationship layer.

### High-level layers
1. Intake Layer
   - Receives a story, company, evidence set, or manual event request.
   - Normalizes the event trigger into a canonical event request object.

2. Context Assembly Layer
   - Pulls story fields, connected companies, proof objects, related evidence, and prior event state.
   - Builds the event context payload.

3. Semantic Understanding Layer
   - Resolves entities, aliases, domains, evidence types, and source characteristics.
   - Produces a semantic profile for the event.

4. Importance Layer
   - Calculates public importance using evidence strength, story urgency, graph influence, and policy weights.
   - Produces a score and a rationale breakdown.

5. Policy Layer
   - Applies central policy weights and gates.
   - Determines whether an event is actionable, low priority, or requires review.

6. Search and Retrieval Layer
   - Expands the event using discovery/search workflows.
   - Finds supporting sources, related articles, and potential evidence candidates.

7. Knowledge Graph Layer
   - Adds or updates relationships between the event, companies, entities, and evidence.
   - Marks relationships as context-only, influence, responsibility, or trust-relevant.

8. Output Layer
   - Exposes an event intelligence payload to the Story UI, future dashboards, and automation services.

### Core design principle
The Event Intelligence Core should be thin, orchestration-focused, and policy-driven. It must not duplicate logic already implemented in the current modules.

---

## 2. New classes

The following classes are proposed as new additions under the plugin’s module structure.

### 2.1 Event_Intelligence_Core
Purpose:
- Main orchestrator for all event intelligence workflows.
- Entry point for analysis requests.
- Coordinates the other services and produces the final event intelligence payload.

Responsibilities:
- Accept an event request.
- Run the analysis pipeline.
- Persist derived results.
- Return a consistent JSON-like event intelligence payload.

### 2.2 Event_Intelligence_Workflow
Purpose:
- Encapsulate the event analysis pipeline as a reusable workflow object.

Responsibilities:
- Sequence the operations.
- Enable partial execution and retries.
- Track progress, warnings, and failures.

### 2.3 Event_Context_Service
Purpose:
- Assemble event context from story, connected companies, proof objects, and evidence.

Responsibilities:
- Read story metadata from the Story module.
- Resolve connected companies and relationships.
- Gather supporting evidence and related objects.
- Produce a stable context snapshot.

### 2.4 Event_Semantic_Service
Purpose:
- Convert raw event data into a semantic event profile.

Responsibilities:
- Normalize names and aliases using company intelligence logic.
- Normalize evidence and source characteristics.
- Infer event themes, topic clusters, and entity relevance.
- Produce a semantic summary object.

### 2.5 Event_Importance_Service
Purpose:
- Estimate the public significance of the event.

Responsibilities:
- Score importance using evidence quality, entity influence, story urgency, and relationship depth.
- Produce an importance score plus confidence and evidence rationale.
- Distinguish public relevance from internal relevance.

### 2.6 Event_Policy_Service
Purpose:
- Provide a policy-aware adapter around the existing Policy Engine.

Responsibilities:
- Map event factors to policy weights.
- Apply policy gates such as minimum evidence threshold, trust band, or source credibility.
- Return structured policy decisions.

### 2.7 Event_Search_Service
Purpose:
- Expand context through search and discovery workflows.

Responsibilities:
- Use discovery and source discovery mechanisms as search entry points.
- Query for candidate sources and related articles.
- Prioritize expansions based on event semantics.
- Return candidate evidence and source suggestions.

### 2.8 Event_Graph_Service
Purpose:
- Interface with the Knowledge Graph module for event relationships.

Responsibilities:
- Create or update event nodes.
- Link the event to companies, entities, evidence, sources, and other events.
- Tag relationships with relevance and trust flags.

### 2.9 Event_Intelligence_Store
Purpose:
- Persist event analysis results in a structured and query-friendly form.

Responsibilities:
- Store event payloads, scores, policy decisions, and search metadata.
- Keep a revision history for the event intelligence analysis.
- Support future re-analysis and debugging.

### 2.10 Event_Resolver
Purpose:
- Classify incoming events as NEW_EVENT, UPDATE, DUPLICATE, MERGE, or CORRECTION using deterministic rules.

Responsibilities:
- Reuse the Event Identity Engine, Signature Engine, and Repository.
- Evaluate similarity and merge readiness without AI or LLM inference.
- Return a deterministic resolver decision and confidence score.

### 2.11 Event_Timeline
Purpose:
- Build a deterministic timeline for resolved events and event updates.

Responsibilities:
- Create a timeline node for each resolved event update.
- Sort nodes chronologically.
- Group nodes by event_id.
- Distinguish first report, update, correction, duplicate, merge, and follow-up states.

---

## 3. Modified classes

The following existing classes should be extended through non-invasive hooks and adapter methods rather than duplicated logic.

### 3.1 Story module
File:
- modules/story/class-story-module.php

Planned extension:
- Expose event intelligence hooks after story save.
- Add lightweight metadata fields for last analysis timestamp, intelligence status, importance score, and policy decision.
- Provide a helper to retrieve connected companies, proofs, and relationships for the event core.

### 3.2 Evidence Engine
File:
- modules/evidence-engine/class-evidence-engine.php

Planned extension:
- Add reusable helpers for event-aware evidence normalization.
- Expose evidence summaries and credibility in a format that the Event Intelligence Core can consume directly.
- Support linking evidence to an event intelligence record.

### 3.3 Company / Company Intelligence modules
Files:
- modules/company-engine/class-company-engine.php
- modules/company-intelligence/class-company-intelligence-engine.php

Planned extension:
- Add an adapter layer that can resolve company identity and entity relevance for event analysis.
- Expose company relationship retrieval in a consistent way for the new core.

### 3.4 Policy Engine
File:
- modules/policy-engine/class-policy-engine.php

Planned extension:
- Add event-specific policy groups such as event.importance, event.search, and event.graph.
- Provide a helper that returns policy values for event intelligence workflows.

### 3.5 Knowledge Graph
File:
- modules/knowledge-graph/class-knowledge-graph.php

Planned extension:
- Add event-aware relationship helpers.
- Support event nodes and event-to-entity relationships without changing the graph model’s core design.

### 3.6 Discovery / Search-related modules
Files:
- modules/discovery-engine/class-discovery-engine.php
- modules/source-discovery/class-source-discovery.php

Planned extension:
- Add a search adapter so the Event Intelligence Core can request candidate sources and evidence without owning search logic itself.

---

## 4. File structure

Proposed file layout:

```text
modules/
  event-intelligence/
    class-event-intelligence-core.php
    class-event-intelligence-workflow.php
    class-event-context-service.php
    class-event-semantic-service.php
    class-event-importance-service.php
    class-event-policy-service.php
    class-event-search-service.php
    class-event-graph-service.php
    class-event-intelligence-store.php
    views/
      event-intelligence-admin.php
      event-intelligence-summary.php
```

Supporting documentation:
```text
docs/
  architecture/
    EVENT_INTELLIGENCE_CORE.md
```

This keeps the implementation isolated and avoids scattering the new functionality across unrelated modules.

---

## 5. Data flow

### Flow A – Story-driven analysis
1. A TSEMOU story is created or updated.
2. The Story module triggers an event intelligence analysis hook.
3. The Event Intelligence Core requests context from the Story module, connected companies, and linked evidence.
4. The Semantic service normalizes entities, evidence, and source signals.
5. The Policy service evaluates the event against the central policy weights.
6. The Importance service calculates a public importance score.
7. The Search service expands the event with candidate evidence and sources.
8. The Graph service links the event to companies, entities, and evidence.
9. The Store persists the event intelligence snapshot.
10. The Story UI or future dashboards consume the final payload.

### Flow B – Manual or scheduled re-analysis
1. A user or scheduler requests re-analysis for an existing event.
2. The core re-reads the current story and related objects.
3. The pipeline reruns semantic, policy, importance, and graph steps.
4. The event intelligence snapshot is updated with versioned results.

### Event intelligence payload shape
The core should return a normalized structure similar to:

```json
{
  "event_id": 123,
  "story_id": 456,
  "status": "analyzed",
  "semantic": {
    "entities": [],
    "themes": [],
    "evidence_summary": ""
  },
  "importance": {
    "score": 0,
    "confidence": 0,
    "factors": []
  },
  "policy": {
    "decision": "review",
    "gates": []
  },
  "search": {
    "candidates": [],
    "sources": []
  },
  "graph": {
    "relationships": []
  },
  "last_updated": ""
}
```

---

## 6. Migration strategy

### Phase 0 – Design and contracts
- Finalize the event intelligence payload contract.
- Define which existing modules are consumed by the new core.
- Define the minimal metadata fields needed for integration.

### Phase 1 – Adapter layer
- Introduce the new Event Intelligence Core classes without changing current module behavior.
- Add hooks into the Story module and supporting modules.
- Keep the first version read-only and non-blocking.

### Phase 2 – Backfill and non-invasive enrichment
- Run the new core against existing stories and evidence records.
- Populate initial event intelligence snapshots for historical data.
- Keep the old workflows untouched while new insights are available in parallel.

### Phase 3 – UI integration
- Surface event intelligence in the Story workspace and admin screens.
- Add basic status cards and policy summaries.
- Leave the core behavior available for future automation.

### Phase 4 – Consolidation
- Replace any duplicated event-specific logic with calls to the Event Intelligence Core.
- Keep the existing modules as authoritative data sources.
- Reduce any direct event-scoring logic that has been scattered across the codebase.

### Migration guardrails
- Do not change the meaning of existing story, evidence, or policy fields.
- Avoid creating a second source of truth for company or evidence data.
- Make the new core read from current modules first and write only where necessary.

---

## 7. Risks

### 7.1 Functionality overlap
Risk:
- The new core could duplicate logic already present in the Story, Evidence, Company Intelligence, and Policy modules.

Mitigation:
- Keep the core as an orchestrator only.
- Reuse existing class methods and data access layers wherever possible.

### 7.2 Policy drift
Risk:
- Event-specific policy interpretation could diverge from the central Policy Engine.

Mitigation:
- The Event_Policy_Service should always call into Policy Engine and never maintain independent policy rules.

### 7.3 Search noise
Risk:
- Search expansion could return many irrelevant candidates and lower confidence.

Mitigation:
- Apply strict prioritization and confidence thresholds.
- Use search expansion only as an enrichment step, not as a source of truth.

### 7.4 Graph bloat
Risk:
- Event-to-entity and event-to-evidence links could create excessive graph noise.

Mitigation:
- Limit relation creation to high-confidence links and marked relevance flags.

### 7.5 Performance impact
Risk:
- Running analysis across many stories and evidence records could become slow.

Mitigation:
- Use background-friendly execution and cache intermediate objects.
- Support partial re-analysis only when needed.

---

## 8. Testing strategy

### 8.1 Unit tests
Cover the individual services in isolation:
- context assembly from story and evidence,
- semantic normalization,
- importance scoring,
- policy decision evaluation,
- graph relationship preparation,
- search candidate ranking.

### 8.2 Integration tests
Verify end-to-end behavior across the current modules:
- Story -> Event Intelligence Core -> Policy Engine -> Importance result.
- Story -> Evidence Engine -> Semantic service -> Graph service.
- Story -> Search service -> candidate evidence retrieval.

### 8.3 Regression tests
Ensure existing behavior remains unchanged:
- Story save flow remains intact.
- Evidence and company metadata remain compatible.
- Policy Engine settings still govern the intended values.

### 8.4 Golden dataset tests
Use a curated set of known stories and evidence records to verify:
- stable scoring behavior,
- expected policy decisions,
- consistent graph enrichment.

### 8.5 Admin and UX smoke tests
Validate that the new intelligence summary is readable and useful in the Story and admin interfaces.

---

## Implementation notes

The Event Intelligence Core should be introduced as a new module with a thin orchestration surface. It will be most effective if it:
- reuses existing modules directly,
- keeps its own logic limited to workflow coordination and derived scoring,
- exposes structured output that future UI and automation layers can consume.

This approach preserves the current architecture while creating a centralized intelligence layer for stories, evidence, public importance, policy, search, and knowledge graph enrichment.
