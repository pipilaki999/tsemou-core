# Phase C.6.4 Integration Verification

## Pipeline Status
PARTIAL

## Verified Handoffs
- [modules/discovery-orchestrator/class-discovery-orchestrator.php](../modules/discovery-orchestrator/class-discovery-orchestrator.php) now contains a `story_processing` executor branch and an `enqueue_story_processing()` helper.
- The orchestrator can call [modules/evidence-processing-engine/class-evidence-processing-engine.php](../modules/evidence-processing-engine/class-evidence-processing-engine.php) through `Evidence_Processing_Engine::process_story($story_id)`.
- Evidence Processing Engine materializes the story into canonical [tsemou_proof](../modules/proof-engine/class-proof-engine.php) via `Proof_Engine::create_or_update_for_story()`.
- `proof_id` is returned from the proof materialization step and propagated back through the evidence processing result.
- Relationship payloads are created with `Relationship_Engine::normalize_relationship()` for `story_has_evidence`, `story_mentions_company`, and `company_has_evidence`.
- Knowledge Graph Update Engine receives the graph payload through `build_graph_payload()` and validates it with `validate_graph_payload()`.
- The old acquisition flow still exists in the orchestrator switch and continues to route `company_discovery`, `source_discovery`, `scraping_engine`, `automatic_evidence_creation`, `automatic_linking`, and `knowledge_graph_update`.

## Missing or Broken Handoffs
- The relationship engine is used as a normalization layer only; it does not persist relationships.
- The knowledge graph update engine receives and validates payloads, but it does not write to a canonical graph store.
- The story-processing branch is additive and must be explicitly queued; it is not automatically seeded by the Phase A acquisition flow.

## Blocking Issues Before Phase D
- Graph persistence is still not canonical. The current MVT chain stops at payload validation instead of committing graph state to a single write path.
- Because graph write is not persisted, the end-to-end MVT pipeline is not fully closed yet.

## Old Acquisition Flow Status
- PASS
- The original Phase A queue lifecycle and executor chain remain intact.
- No acquisition engines were renamed or replaced.
- Existing acquisition steps still dispatch through the same orchestrator and continue to terminate at the acquisition graph-update step.

## Recommendation
Can Phase C.6 be closed?
NO

## Quick Manual Test Steps
1. Open the Discovery Orchestrator admin page and confirm the Phase A pipeline still renders.
2. Queue a story-processing job with `Discovery_Orchestrator::enqueue_story_processing($story_id)`.
3. Run one orchestrator step and confirm the log records story processing completion.
4. Confirm the result contains a non-zero `proof_id`.
5. Confirm the returned relationships include story and company links.
6. Confirm the graph payload is produced and validated.
7. Run a normal acquisition item and confirm the Phase A flow still advances to source discovery and scraping.