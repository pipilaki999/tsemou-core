# Canonical Evidence Model

## Canonical Evidence Model: `tsemou_proof`

This documentation decision defines the canonical evidence model for the TSEMOU beta.

## Meaning
- `evidence` is a conceptual name only.
- `tsemou_proof` is the persistent WordPress CPT for beta.
- All new Phase C.6 integration must write to `tsemou_proof`.
- Legacy evidence paths may remain temporarily but must not be extended.
- No migration is performed in this step.
- This decision supports the MVT by removing duplicate evidence lifecycle ambiguity.

## Phase C.6 - Integration
Goal:
Unify Discovery, Story, Evidence Processing, `tsemou_proof`, Knowledge Graph and Company Page.
