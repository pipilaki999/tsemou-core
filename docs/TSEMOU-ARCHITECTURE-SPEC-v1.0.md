# TSEMOU Architecture Specification v1.0

## 1. Executive Summary

TSEMOU is a modular WordPress-based intelligence operating system that ingests discovery inputs, transforms them into structured story and proof objects, computes trust outcomes, and maintains contextual knowledge graph relationships for admin and public surfaces.

MVT (Minimum Viable Trust) is the stabilization phase that prioritizes runtime reliability and canonical consistency over feature expansion.

The frozen MVT architecture is:

- Runtime-first, proof-first pipeline.
- Canonical Evidence/Proof Object: `tsemou_proof`.
- Legacy Compatibility Evidence Object: `evidence` CPT (fallback/compatibility only).
- Canonical company entity: `company` CPT.
- Canonical trust score writer: Trust Engine.
- Canonical runtime graph commit path: Knowledge Graph Update Engine writing into `Knowledge_Graph` option store.

Implementation note:

- There is no dedicated RSS module file in the current codebase.
- RSS/Discovery is represented as an ingestion entry path that feeds Discovery Orchestrator and the acquisition chain.

---

## 2. Architecture Layers

### Layer 1: Discovery / Acquisition

- RSS / Discovery runtime orchestration
- Source Discovery
- Scraping Engine
- Discovery Engine

Note: RSS/Discovery at MVT is a logical ingestion layer, while concrete runtime execution is implemented by Discovery Orchestrator + Source Discovery + Scraping + Discovery Engine paths.

### Layer 2: Content / Story Processing

- Story Module
- Evidence Processing Engine
- Proof Engine

### Layer 3: Intelligence

- Company Resolution / Company Engine
- Evidence Engine
- Trust Engine
- Automatic Linking
- Entity Engine
- Relationship Engine

### Layer 4: Knowledge

- Knowledge Graph
- Knowledge Graph Engine
- Knowledge Graph Update Engine

### Layer 5: Admin / Public Presentation

- Entity Evidence Links
- Company Section Engine
- Admin pages
- Public/company views

---

## 3. Canonical Runtime Pipeline

Canonical MVT runtime pipeline:

RSS / Discovery
-> Story/Post
-> Company Resolution
-> Evidence Processing
-> Proof Engine
-> tsemou_proof
-> Trust Engine
-> Knowledge Graph
-> Admin/Public

Operationally, the pipeline is event-driven and queue-driven, with Orchestrator stages and save hooks controlling transitions.

Implementation note: the first runtime-controlled transition in the current codebase starts from Story/Post save hooks and Orchestrator queue stages.

---

## 4. Single Source of Truth Rules

1. `tsemou_proof` is the canonical evidence/proof object.
2. `evidence` CPT is legacy/fallback/compatibility.
3. Story is the canonical content unit.
4. `company` CPT is the canonical company entity.
5. Trust Engine is the canonical writer for trust scores.
6. Knowledge Graph runtime canonical path is `Knowledge_Graph` option store through KG Update Engine.
7. `Knowledge_Graph_Engine` CPT graph is post-MVT technical debt/compatibility/admin graph, not the MVT runtime canonical graph store.

---

## 5. Module Specification

## Discovery Orchestrator

- Responsibility: Runtime queue orchestration, stage execution, scheduling, logging.
- Canonical Input: Stage payloads and queue items.
- Canonical Output: Executed stage results and next stage queue jobs.
- Reads: Runtime state, queue, pipeline options.
- Writes: Runtime state, queue, logs options.
- CPTs: None (direct).
- Meta keys: Indirect via called stages.
- Options: `tsemou_discovery_orchestrator_runtime_state`, `..._queue`, `..._pipeline`, `..._logs`.
- Called by: Story save hooks, cron tick hooks, admin actions.
- Calls: Story processing, source discovery, scraping, automatic evidence, automatic linking, KG update.
- Consumers: Entire runtime pipeline.
- MVT status: Frozen.

## Source Discovery

- Responsibility: Resolve candidate sources and build fetch payloads.
- Canonical Input: Discovery task payload.
- Canonical Output: Source candidate list and source fetch payloads.
- Reads: Source registry JSON configuration.
- Writes: None persistent (returns payloads).
- CPTs: None.
- Meta keys: None.
- Options: None mandatory.
- Called by: Discovery Orchestrator source_discovery stage.
- Calls: Source Resolver, Source Queue Builder, Source Prioritizer.
- Consumers: Scraping stage.
- MVT status: Frozen.

## Scraping Engine

- Responsibility: Fetch and normalize raw source content.
- Canonical Input: Source fetch payload.
- Canonical Output: Raw evidence runtime record.
- Reads: Source URL content via HTTP.
- Writes: Runtime storage files (`raw-evidence`).
- CPTs: None.
- Meta keys: None.
- Options: None mandatory.
- Called by: Discovery Orchestrator scraping stage.
- Calls: Fetcher, Parser, Normalizer, Storage.
- Consumers: Automatic Evidence Creation stage.
- MVT status: Frozen.

## Story Module

- Responsibility: Story CPT lifecycle and bridge into processing pipeline.
- Canonical Input: Story/post save events.
- Canonical Output: Story metadata updates and queued story processing.
- Reads: Story meta (`_tsemou_*`).
- Writes: Story lifecycle/status/scheduling metadata.
- CPTs: `story`.
- Meta keys: `_tsemou_call_sign`, `_tsemou_status`, `_tsemou_executive_summary`, `_tsemou_lifecycle_*`, `_tsemou_discovery_last_scheduled_*`.
- Options: None.
- Called by: WP save hooks.
- Calls: Company Engine company-link persistence; Orchestrator enqueue.
- Consumers: Evidence Processing pipeline and lifecycle UI.
- MVT status: Frozen.

## Company Engine

- Responsibility: Canonical company entity and story-company linkage.
- Canonical Input: Company saves and story relationship payloads.
- Canonical Output: Persisted company state and connected company IDs for downstream engines.
- Reads: Company and story meta.
- Writes: Company intelligence/meta, connected companies, relationship maps.
- CPTs: `company`.
- Meta keys: `_tsemou_connected_companies`, `_tsemou_company_relationships`, `_tsemou_company_*`, trust/evidence counters.
- Options: None mandatory.
- Called by: Story Module, downstream readers.
- Calls: Public page update events.
- Consumers: Evidence Processing, Proof Engine sync, Trust fallback resolution.
- MVT status: Frozen.

## Evidence Processing Engine

- Responsibility: Transform story into evidence payload, relationships, proof materialization, graph commit.
- Canonical Input: `story_id`.
- Canonical Output: Processing result with `proof_id`, relationship set, graph status.
- Reads: Story post + story/company metadata.
- Writes: Indirect through Proof Engine and KG Update Engine.
- CPTs: Reads `story`; writes via `tsemou_proof` path.
- Meta keys: `_tsemou_executive_summary`, `_tsemou_status`, `_tsemou_call_sign`, `_tsemou_connected_companies`.
- Options: None.
- Called by: Discovery Orchestrator story_processing stage.
- Calls: Proof Engine, Entity Engine, Relationship Engine, KG Update Engine.
- Consumers: Trust and KG stages.
- MVT status: Frozen.

## Proof Engine

- Responsibility: Canonical proof writer and evidence profile/intelligence materialization.
- Canonical Input: Story-derived payloads and proof admin save.
- Canonical Output: Created/updated `tsemou_proof` plus profile/intelligence and company sync metadata.
- Reads: Story, proof, company link metadata.
- Writes: `tsemou_proof` post/meta and company evidence cache counters.
- CPTs: `tsemou_proof`.
- Meta keys: `_tsemou_related_file`, `_tsemou_proof_*`, `_tsemou_evidence_type`, `_tsemou_source_type`, `_tsemou_verification_level`, `_tsemou_legal_status`, `_tsemou_trust_include`, `_tsemou_evidence_company_ids`, `_tsemou_evidence_profile`, `_tsemou_evidence_intelligence`.
- Options: None mandatory.
- Called by: Evidence Processing Engine; save hook lifecycle.
- Calls: Company sync methods and profile/intelligence builders.
- Consumers: Trust Engine, Evidence Engine, Company/Admin/Public surfaces.
- MVT status: Frozen.

## Evidence Engine

- Responsibility: Compatibility normalization and read model for evidence/proof objects.
- Canonical Input: Evidence/proof ID or company ID.
- Canonical Output: Normalized evidence object(s).
- Reads: `tsemou_proof` and `evidence` CPTs with compatibility meta key surface.
- Writes: None primary.
- CPTs: `tsemou_proof`, `evidence`.
- Meta keys: Multi-key compatibility (`_tsemou_source_url`, `_tsemou_credibility_score`, `_tsemou_company_id`, `company`, `related_company`, etc).
- Options: None mandatory.
- Called by: Company Section Engine, KG Engine helper flows, admin diagnostics.
- Calls: Optional orchestrator handoff helpers.
- Consumers: Company sections and admin readers.
- MVT status: Frozen runtime, Admin-fix allowed for visibility behavior.

## Trust Engine

- Responsibility: Canonical trust score writer based on evidence and community weighting.
- Canonical Input: `save_post_tsemou_proof` and community votes.
- Canonical Output: Company trust score metrics.
- Reads: Proof evidence profile/intelligence and trust include metadata.
- Writes: Company trust and vote metadata.
- CPTs: Reads `tsemou_proof`; writes `company` meta.
- Meta keys: `_tsemou_trust_engine_score`, `_tsemou_trust_engine_evidence_score`, `_tsemou_trust_engine_community_score`, `_tsemou_community_votes`.
- Options: `tsemou_trust_engine_settings`.
- Called by: save hook, init vote submission flow.
- Calls: Recalculation routines.
- Consumers: Public/admin trust views.
- MVT status: Frozen.

## Knowledge Graph

- Responsibility: Runtime graph relationship persistence (option store).
- Canonical Input: Validated graph relationship payloads.
- Canonical Output: Stored graph relationships and graph logs.
- Reads: `tsemou_knowledge_graph_relationships` option.
- Writes: `tsemou_knowledge_graph_relationships`, `tsemou_knowledge_graph_logs`.
- CPTs: None required for runtime persistence.
- Meta keys: None primary.
- Options: Graph relationships and logs options.
- Called by: KG Update Engine, Automatic Linking, admin KG page.
- Calls: Relationship add/update logic.
- Consumers: Runtime graph readers and admin graph UI.
- MVT status: Frozen runtime canonical graph store.

## Knowledge Graph Engine

- Responsibility: CPT-based graph/entity compatibility/admin graph surface.
- Canonical Input: Admin post actions and helper link requests.
- Canonical Output: `tsemou_entity` and `tsemou_relation` records.
- Reads: Entity/relation CPT/meta and Evidence Engine outputs.
- Writes: Entity/relation CPT meta.
- CPTs: `tsemou_entity`, `tsemou_relation`.
- Meta keys: `_tsemou_entity_*`, `_tsemou_relation_*`.
- Options: None mandatory.
- Called by: Admin actions, Discovery Engine helper link path.
- Calls: create_entity, create_relation, link_evidence_company_source.
- Consumers: Admin graph diagnostics and compatibility tooling.
- MVT status: Post-MVT refactor target (compat/admin graph only).

## Knowledge Graph Update Engine

- Responsibility: Build/validate/commit runtime graph payloads.
- Canonical Input: Entity + relationship arrays.
- Canonical Output: Commit result and graph statistics.
- Reads: Entity/Relationship normalized payload.
- Writes: Runtime graph via Knowledge_Graph add_relationship.
- CPTs: None direct.
- Meta keys: None direct.
- Options: Indirect graph options through Knowledge_Graph.
- Called by: Evidence Processing Engine.
- Calls: Entity Engine, Relationship Engine, Knowledge_Graph.
- Consumers: Runtime pipeline and diagnostics.
- MVT status: Frozen.

## Entity Engine

- Responsibility: Entity normalization and entity type inference.
- Canonical Input: Generic entity reference or WP post.
- Canonical Output: Normalized entity object.
- Reads: WP post/meta.
- Writes: None.
- CPTs: Generic post consumption.
- Meta keys: `_tsemou_entity_type`, `_tsemou_entity_id`, `_tsemou_call_sign` (read).
- Options: None.
- Called by: Evidence Processing, Relationship Engine, KG Update Engine.
- Calls: Internal normalization helpers.
- Consumers: Relationship and graph payload builders.
- MVT status: Frozen.

## Relationship Engine

- Responsibility: Relationship normalization and validation.
- Canonical Input: from/to/type relationship payload.
- Canonical Output: Validated normalized relationship object.
- Reads: Entity Engine type support.
- Writes: None.
- CPTs: None.
- Meta keys: None persistent.
- Options: None.
- Called by: Evidence Processing, KG Update Engine.
- Calls: Entity Engine normalization.
- Consumers: KG Update Engine commits.
- MVT status: Frozen.

## Automatic Linking

- Responsibility: Map runtime evidence drafts/posts to company/source context and optional graph relation.
- Canonical Input: Draft/raw/evidence payload.
- Canonical Output: Link record plus enriched payload.
- Reads: Existing evidence/company/source signals.
- Writes: Evidence-company/source meta, runtime link file records, option logs, optional entity-evidence link records.
- CPTs: Updates `evidence` or `tsemou_proof`; may reference `tsemou_source` and `company`.
- Meta keys: `_tsemou_company_id`, `_tsemou_entity_id`, `_tsemou_evidence_company_ids`, `_tsemou_source_id`, `company`, `related_company`.
- Options: `tsemou_automatic_linking_logs`.
- Called by: Orchestrator automatic_linking and knowledge_graph_update stages.
- Calls: Proof sync, Entity Evidence Links, Knowledge_Graph.
- Consumers: Admin linking diagnostics and graph context relations.
- MVT status: Frozen runtime, Admin-fix allowed for visibility/diagnostics.

## Entity Evidence Links

- Responsibility: Admin mapping of evidence objects to entities/companies using independent link store.
- Canonical Input: Evidence ID + entity ID + relationship type.
- Canonical Output: JSON link set per evidence.
- Reads: Evidence list (`tsemou_proof` first, then legacy `evidence`) and entity list.
- Writes: `_tsemou_entity_evidence_links`, `_tsemou_entity_evidence_link_count`, logs option.
- CPTs: Reads `tsemou_proof`, `evidence`, `company`, `tsemou_entity`.
- Meta keys: `_tsemou_entity_evidence_links`, `_tsemou_entity_evidence_link_count`.
- Options: `tsemou_entity_evidence_link_logs`.
- Called by: Admin forms and Automatic Linking helper.
- Calls: Internal add/remove/save methods.
- Consumers: Admin evidence-entity visibility and manual linking.
- MVT status: Admin-fix allowed.

## Company Section Engine

- Responsibility: Company-facing evidence/events/related sections with compatibility fallback behavior.
- Canonical Input: Company ID and evidence/event saves.
- Canonical Output: Company section data blocks.
- Reads: Evidence Engine normalized output, fallback evidence scans, event meta.
- Writes: Evidence/event relation metadata.
- CPTs: `evidence`, `tsemou_event`.
- Meta keys: `_tsemou_entity_type`, `_tsemou_entity_id`, `_tsemou_company_id`, `_tsemou_event_date`, `_tsemou_event_type`.
- Options: None mandatory.
- Called by: Admin and company display paths.
- Calls: Evidence Engine.
- Consumers: Company admin/public views.
- MVT status: Admin-fix allowed.

## Discovery Engine

- Responsibility: Admin-driven discovery candidate management and optional candidate-to-evidence creation.
- Canonical Input: Candidate URL/title/text or candidate ID action.
- Canonical Output: Discovery candidate and optional evidence/proof post.
- Reads: Candidate/source/company metadata.
- Writes: Discovery candidate metadata and resulting evidence/proof metadata.
- CPTs: `tsemou_discovery`, plus `evidence` or `tsemou_proof` target.
- Meta keys: `_tsemou_discovery_*`, `_tsemou_source_url`, `_tsemou_discovery_evidence_id`.
- Options: None mandatory.
- Called by: Admin post actions.
- Calls: Source intelligence, company detection, optional KG Engine link helper.
- Consumers: Admin acquisition workflows.
- MVT status: Frozen runtime behavior, Admin-fix allowed for diagnostics.

---

## 6. Runtime Event Flow

| Event/Hook | Triggered By | Module | Method | Result | Runtime/Admin |
|---|---|---|---|---|---|
| plugins_loaded | WordPress plugin bootstrap | Core | Core::instance | Loads/instantiates modules | Runtime |
| init | WordPress | Story Module | register_story_cpt | Registers Story CPT | Runtime/Admin |
| save_post_story | WordPress editor/save | Story Module | enqueue_story_processing_on_story_save | Queues story_processing | Runtime |
| save_post_post | WordPress editor/save | Story Module | enqueue_story_processing_on_post_save | Bridges post to story_processing | Runtime |
| tsemou_phase_a_runtime_tick | WP cron schedule | Discovery Orchestrator | runtime_tick | Consumes queue batch | Runtime |
| tsemou_phase_a_queue_worker | Orchestrator scheduler | Discovery Orchestrator | run_scheduled_worker | Executes queue item | Runtime |
| story_processing queue stage | Orchestrator queue engine | Discovery Orchestrator | execute_story_processing | Calls Evidence Processing | Runtime |
| process_story | Orchestrator story stage | Evidence Processing Engine | process_story | Builds evidence payload, relationships, graph result | Runtime |
| create_or_update_for_story | Evidence Processing Engine | Proof Engine | create_or_update_for_story | Creates/updates canonical `tsemou_proof` | Runtime |
| save_post_tsemou_proof | WordPress save hook | Trust Engine | on_evidence_saved | Recalculates company trust | Runtime |
| KG commit | Evidence Processing Engine | KG Update Engine | commit_graph_payload | Validates and commits graph payload | Runtime |
| add_relationship | KG Update/Automatic Linking/Admin | Knowledge Graph | add_relationship | Persists graph relation in option store | Runtime/Admin |
| admin_post_tsemou_discovery_add_candidate | Admin form submit | Discovery Engine | handle_add_candidate | Creates discovery candidate | Admin |
| admin_post_tsemou_discovery_create_evidence | Admin form submit | Discovery Engine | handle_create_evidence | Creates evidence/proof from candidate | Admin |
| admin_post_tsemou_kg_create_entity | Admin form submit | Knowledge Graph Engine | handle_create_entity | Creates graph entity CPT | Admin |
| admin_post_tsemou_kg_create_relation | Admin form submit | Knowledge Graph Engine | handle_create_relation | Creates graph relation CPT | Admin |
| admin_menu | WordPress admin bootstrap | Multiple modules | admin_menu handlers | Registers module admin pages | Admin |

---

## 7. Dependency Map

| Module | Depends On | Called By | Calls | Reads | Writes | Risk |
|---|---|---|---|---|---|---|
| Discovery Orchestrator | Configuration OS, Source Discovery, Scraping, Auto stages, Evidence Processing | Story hooks, cron hooks, manual runtime controls | execute_story_processing, execute_source_discovery, execute_scraping_engine, execute_automatic_evidence_creation, execute_automatic_linking, execute_knowledge_graph_update | runtime options queue/state/pipeline/logs | runtime options queue/state/logs | Medium |
| Source Discovery | Source Registry, Resolver, Prioritizer, Queue Builder | Orchestrator | Source_Resolver::resolve, Source_Queue_Builder::build_payloads | source registry config | returns payloads (no persistent writes) | Low |
| Scraping Engine | Runtime_Storage, Fetcher, Parser, Normalizer | Orchestrator | Fetcher, Parser, Normalizer, Storage::save | source URL response | runtime raw evidence records | Low |
| Story Module | Company Engine, Discovery Orchestrator, Event Identity | save_post_* hooks | enqueue_story_processing, save company links, lifecycle handlers | story meta | story lifecycle/scheduling meta | Medium |
| Company Engine | WP CPT/meta surfaces | Story Module, downstream readers | connected company helpers, timeline/public update hooks | story/company meta | company relationship/intelligence meta | Medium |
| Evidence Processing Engine | Company Engine, Entity Engine, Relationship Engine, Proof Engine, KG Update Engine | Orchestrator | Proof_Engine::create_or_update_for_story, KG commit | story object/meta and company links | indirect writes via Proof/KG | Low-Medium |
| Proof Engine | Company Engine, Policy Engine | Evidence Processing, save hook | profile/intelligence build, sync evidence to companies | proof/story/company meta | canonical proof meta + company evidence counters | Low |
| Evidence Engine | Evidence Validator, Source intelligence/object helpers | Company Section, KG Engine helpers, admin pages | normalize/get_for_company and optional handoff helpers | evidence+tsemou_proof dual surface | mostly read-only | Medium |
| Trust Engine | Policy settings, Company fallback IDs | save_post_tsemou_proof, init vote flow | recalculate_company_trust, calculate_evidence_impact | proof/profile/intelligence/votes | trust/vote company meta, settings option | Low-Medium |
| Knowledge Graph | WP options | KG Update Engine, Automatic Linking, admin actions | add_relationship, status updates | graph options | graph options and graph logs | Medium |
| Knowledge Graph Engine | Evidence Engine, Source Object | admin_post KG actions, Discovery Engine helper | create_entity, create_relation, link_evidence_company_source | entity/relation CPT/meta | entity/relation CPT/meta | High |
| KG Update Engine | Entity Engine, Relationship Engine, Knowledge Graph | Evidence Processing | validate_graph_payload, commit_graph_payload | normalized entity/relationship payloads | runtime graph via Knowledge_Graph | Medium |
| Entity Engine | WP post/meta | Evidence Processing, Relationship Engine, KG Update Engine | normalize/resolve entity | post/meta | none | Low |
| Relationship Engine | Entity Engine | Evidence Processing, KG Update Engine | normalize/validate relationship | relation payloads | none | Low |
| Automatic Linking | Proof Engine, Entity Evidence Links, Knowledge Graph, Source Object | Orchestrator | sync_evidence_company_relationship, update_knowledge_graph | payload + company/source/evidence lookups | evidence/company/source meta, runtime records, logs | Medium |
| Entity Evidence Links | WP post/meta/options | Admin forms, Automatic Linking | add_link/get_links/save_links | proof/evidence/entity lists | link JSON meta + logs option | Medium |
| Company Section Engine | Evidence Engine, Company Discovery | Admin/company rendering | get_company_evidence and section composers | evidence/events/company meta | evidence/event relation meta | Medium |
| Discovery Engine | Source intelligence, Company intelligence, Source object, KG Engine helper | admin_post discovery actions | create_candidate, create_evidence_from_candidate | candidate/source/company data | discovery meta + evidence/proof creation path | Medium-High |

---

## 8. MVT Freeze Contract

### Frozen for MVT

- Story processing runtime.
- Company resolution runtime.
- Proof Engine canonical writer.
- `tsemou_proof` object model.
- Trust runtime scoring.
- KG runtime commit contract.
- RSS/discovery runtime behavior.

### Allowed before beta

- Admin query isolation.
- Admin visibility fixes.
- Admin fallback clarity.
- Admin diagnostics.
- Proof-first admin display fixes.

### Not allowed before beta

- Redesign.
- New canonical object.
- Dual write introduction.
- Runtime refactor.
- KG storage migration.
- Evidence CPT revival as primary.
- New engines.

### Post-MVT refactor

- KG unification.
- Evidence/proof naming cleanup.
- Meta-key normalization.
- Performance hardening.
- Legacy compatibility cleanup.

---

## 9. Known Technical Debt

- `evidence` CPT compatibility surface remains active for fallback/read compatibility.
- `Knowledge_Graph` (option store) vs `Knowledge_Graph_Engine` (CPT graph) duality.
- Entity Evidence Links independent link store (`_tsemou_entity_evidence_links`) separate from some canonical relation paths.
- Company linkage multi-key compatibility surface across modules.
- Broad meta fallback scans in compatibility readers.
- Deployment/package discipline requirements (single active plugin folder, deterministic ZIP structure, normalized paths).

---

## 10. Beta Readiness Checklist

- [ ] Correct deploy package.
- [ ] One active TSEMOU plugin folder.
- [ ] Proof-first Entity Evidence Links admin visibility.
- [ ] Admin diagnostics confirm proof counts.
- [ ] RSS pull creates Story.
- [ ] Story creates/updates `tsemou_proof`.
- [ ] Trust recalculates.
- [ ] KG update succeeds.
- [ ] Admin/Public views show canonical proof data.
- [ ] Git commit plus versioned release ZIP.

---

## 11. Final Rule

For MVT, TSEMOU must prefer stabilization over expansion.
No new capabilities are allowed until the runtime pipeline and admin proof-first visibility are stable.
