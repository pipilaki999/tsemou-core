# TSEMOU Canonical Object Model — MVT v1.0

## 1. Purpose

This document defines the canonical objects, active WordPress post types, legacy compatibility objects, and the official MVT information lifecycle.

The goal is to remove confusion between Evidence, Proof, Story, Company and Knowledge Graph objects.

TSEMOU transforms isolated information into connected public knowledge.

## 2. MVT Rule

The MVT does not add new architecture.

It completes the existing pipeline:

RSS / Source
→ WordPress post / Story
→ Company resolution
→ Canonical Evidence object
→ Trust / Evidence Intelligence metadata
→ Knowledge Graph
→ Functional admin/public visibility

## 3. Canonical Objects

| Concept | Canonical Object | WordPress CPT / Storage | Status | Role |
|---|---|---|---|---|
| Imported Article | WordPress Post | post | Active input | Raw imported RSS article |
| Story | Story | story or post bridge | Active | Public information container |
| Evidence | Proof | tsemou_proof | Canonical MVT object | Materialized evidence record |
| Evidence Profile | Metadata | _tsemou_evidence_profile on tsemou_proof | Active | Structured evidence profile |
| Evidence Intelligence | Metadata | _tsemou_evidence_intelligence on tsemou_proof | Active | Trust/intelligence calculation |
| Company | Company | company | Active | Entity connected to stories/evidence |
| Source | Source | tsemou_source | Active | Origin of information |
| Event | Event | tsemou_event if present | Secondary / future | Real-world occurrence |
| Knowledge Graph Entity | Graph Entity | tsemou_entity / graph storage | Active | Normalized entity node |
| Knowledge Graph Relationship | Graph Relation | tsemou_relation / graph storage | Active | Typed relationship |
| Evidence CPT | Evidence | evidence | Legacy/fallback | Not canonical for MVT |

## 4. Canonical Decision

For MVT, `tsemou_proof` is the canonical Evidence object.

The term “Evidence” means the conceptual evidence object.

The stored WordPress object for that concept is:

`tsemou_proof`

The old `evidence` CPT is legacy/fallback compatibility only.

## 5. Naming Contract

| Name | Meaning |
|---|---|
| proof_id | Canonical runtime ID of the stored Evidence object |
| tsemou_proof | Canonical CPT for MVT Evidence |
| evidence_id | Compatibility alias only, if used |
| evidence_post_id | Should be 0 unless a legacy evidence CPT post exists |
| stage_8_evidence_created | Means Evidence was materialized as tsemou_proof, not necessarily evidence CPT |

## 6. Official MVT Information Lifecycle

1. RSS feed is pulled.
2. Imported article is stored as WordPress post.
3. Story bridge accepts post/story as processable story input.
4. Story Processing starts.
5. Company Resolution connects companies to the story.
6. Evidence payload is built.
7. Proof Engine creates or updates `tsemou_proof`.
8. Evidence Profile and Evidence Intelligence metadata are stored on `tsemou_proof`.
9. Relationships are built.
10. Knowledge Graph is updated.
11. Admin/public screens must read from `tsemou_proof` first.
12. Legacy `evidence` CPT may be used only as fallback, never as MVT source of truth.

## 7. Active Engines

| Engine | Role in MVT |
|---|---|
| Story Module | Bridges imported posts/stories into the pipeline |
| Discovery Orchestrator | Queues and runs processing jobs |
| Evidence Processing Engine | Builds evidence payload and relationships |
| Proof Engine | Materializes canonical Evidence as tsemou_proof |
| Company Engine | Resolves and connects companies |
| Trust Engine | Evaluates trust/intelligence on tsemou_proof |
| Knowledge Graph Update Engine | Writes graph entities and relationships |
| Source Object / Source Intelligence | Maintains source context |

## 8. Legacy Compatibility Rule

Legacy references to `evidence` CPT are allowed only if they are explicitly fallback-compatible.

Any admin counter, diagnostic, query, or report used for MVT readiness must be proof-first:

Primary:
`tsemou_proof`

Fallback:
`evidence`

Wrong for MVT:
counting only `evidence` and reporting that Evidence was not created.

## 9. Admin Visibility Rule

MVT admin evidence counts must show canonical evidence records.

Therefore admin/debug counters should count:

`tsemou_proof`

They may optionally display legacy `evidence` separately as:

Legacy Evidence CPT count

## 10. Forbidden Changes

Do not create a second canonical Evidence object.

Do not dual-write to `evidence` and `tsemou_proof` unless a strict sync contract is approved later.

Do not redesign UI.

Do not create duplicate engines.

Do not change the working Story → Companies → Proof/Evidence → KG pipeline unless a failing runtime test proves it is necessary.

## 11. Minimum Cleanup Direction

Future cleanup should proceed in this order:

1. Clarify diagnostics and log names.
2. Make admin counters proof-first.
3. Make evidence-only queries proof-first with legacy fallback.
4. Mark old evidence CPT references as legacy/fallback.
5. Verify runtime logs.
6. Only then commit.

## 12. Current Architectural Status

The MVT pipeline is considered aligned if:

- RSS import creates/updates posts.
- Story bridge processes imported posts.
- Proof ID increases or is reused correctly.
- `tsemou_proof` exists.
- Evidence profile metadata exists on `tsemou_proof`.
- Evidence intelligence metadata exists on `tsemou_proof`.
- Companies are linked.
- Knowledge Graph update runs.
- Admin/public screens read canonical evidence from `tsemou_proof`.

The legacy `evidence` CPT count is not a valid MVT success metric.
