# Story To Proof Integration

## Purpose
This integration step connects the existing Evidence Processing Engine to the canonical evidence model: `tsemou_proof`.

## Scope
- No new engines
- No new CPTs
- No new database tables
- No Discovery Orchestrator changes
- No Company Engine changes
- No public page changes

## Integration Rule
When Evidence Processing Engine processes a Story, it must create or update the canonical `tsemou_proof` record for that Story instead of stopping at an internal evidence payload.

## Duplicate Prevention
- Existing proofs for the Story are resolved through `_tsemou_related_file`
- If a proof already exists, it is updated
- If no proof exists, a new `tsemou_proof` post is created

## Returned Processing Result
The processing result now includes:
- `proof_id`

## Reused Components
- Proof Engine for proof creation/update and proof-side evidence intelligence/profile refresh
- Evidence Processing Engine for Story validation, normalization, relationship building, and graph coordination
- Knowledge Graph Update Engine for graph payload handling