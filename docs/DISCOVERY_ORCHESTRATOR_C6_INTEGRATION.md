# Discovery Orchestrator Phase C.6.3 Integration

## 1. What was added

The existing Discovery Orchestrator now accepts a minimal story-processing queue item and dispatches it to the Evidence Processing Engine.

Added in [modules/discovery-orchestrator/class-discovery-orchestrator.php](../modules/discovery-orchestrator/class-discovery-orchestrator.php):
- `enqueue_story_processing($story_id, array $payload = [], $priority = 0)`
- `execute_story_processing($item)`
- a `story_processing` branch in the existing engine switch
- story ID support in the queue key so story jobs can be tracked distinctly

The orchestrator remains the central coordinator. No new orchestrator was introduced.

## 2. What was not changed

- The Discovery Orchestrator name stayed the same.
- The Phase A acquisition pipeline remained intact.
- No new CPTs were created.
- No new database tables were created.
- No frontend UI was added.
- No unrelated engines were redesigned.
- No existing Proof, Entity, Relationship, or Knowledge Graph contracts were replaced.

## 3. How the old discovery flow remains intact

The existing Phase A chain still works as before:

Company Discovery -> Source Discovery -> Scraping Engine -> Automatic Evidence Creation -> Automatic Linking -> Knowledge Graph Update

The new story-processing branch is additive. It only runs when a story job is explicitly queued, so the acquisition flow continues to operate on the same Phase A runtime path.

## 4. How Evidence Processing Engine is called

When the orchestrator receives a `story_processing` queue item, it:

1. Extracts `story_id` from the queue payload.
2. Loads `Evidence_Processing_Engine` if needed.
3. Calls `Evidence_Processing_Engine::process_story($story_id)`.
4. Receives a proof-backed result that includes `proof_id`, evidence payload, normalized relationships, and graph payload data.
5. Logs the result and returns the processed payload to the runtime.

That keeps orchestration in the Discovery Orchestrator and business logic in the Evidence Processing Engine.

## 5. How this prepares the end-to-end MVT smoke test

This integration creates the missing coordination bridge for the MVT smoke path:

Discovery Orchestrator -> Story -> Evidence Processing Engine -> `tsemou_proof` -> Relationship Engine -> Knowledge Graph Update Engine

That means a future smoke test can verify:
- a story job can be queued
- the story is processed into canonical proof evidence
- downstream relationship and graph payloads are produced
- the acquisition runtime remains unaffected

## 6. Files changed

- [modules/discovery-orchestrator/class-discovery-orchestrator.php](../modules/discovery-orchestrator/class-discovery-orchestrator.php)
- [MASTER_PROJECT_MEMORY.md](../MASTER_PROJECT_MEMORY.md)
- [docs/DISCOVERY_ORCHESTRATOR_C6_INTEGRATION.md](DISCOVERY_ORCHESTRATOR_C6_INTEGRATION.md)

## 7. Quick test steps

1. Run PHP syntax validation on [modules/discovery-orchestrator/class-discovery-orchestrator.php](../modules/discovery-orchestrator/class-discovery-orchestrator.php).
2. Confirm the plugin still loads and the Phase A queue admin page still renders.
3. Queue a `story_processing` item from code or a temporary admin hook using `Discovery_Orchestrator::enqueue_story_processing($story_id)`.
4. Run the orchestrator once and confirm the log shows story processing completed.
5. Confirm the result includes a `proof_id` and canonical `tsemou_proof` materialization.
6. Confirm the old acquisition queue still processes company discovery items normally.