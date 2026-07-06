# TSEMOU Information Lifecycle

## 1. Purpose
Every piece of information inside TSEMOU follows one deterministic lifecycle: from isolated submission to connected public knowledge.
The goal is consistency, traceability, and evidence-first progression across all content types.
This lifecycle connects existing architecture and engine responsibilities rather than replacing them.

## 2. Lifecycle Diagram

```text
Submission
  ↓
Seed
  ↓
Community Feed
  ↓
Community Intelligence
  ↓
Evidence Growth
  ↓
Trust Evaluation
  ↓
Public Importance
  ↓
Community Value
  ↓
Ranking
  ↓
Promotion
  ↓
Living Case
  ↓
Knowledge Graph Update
  ↓
Continuous Evolution
  ↓
Historical Archive
```

## 3. Stage Descriptions
### Submission
A user or system provides new information (story, post, evidence, question, correction, or solution).
At this point, information is unverified and context-limited.

### Seed
The submission is normalized into a seed object with consistent metadata and identity anchors.
Seeds are the minimum unit for deterministic lifecycle processing.

### Community Feed
The seed becomes visible as an actionable community item.
It is open to interaction and evidence contribution, not immediate promotion.

### Community Intelligence
Community interactions are collected as structured signals.
These signals are treated as inputs, not final truth.
Initial interaction inputs:
- TSEMIT
- UNTSEMIT
- comments
- evidence
- corrections
- questions
- solutions
- follows

### Evidence Growth
Supporting or conflicting evidence is added and linked.
The information quality improves through validation and relationship expansion.

### Trust Evaluation
Trust and credibility signals are computed from evidence quality, source reliability, and interaction integrity.
Suspicious or low-confidence patterns are constrained.

### Public Importance
The system evaluates societal impact and relevance.
This stage ensures significance is considered beyond engagement volume.

### Community Value
Signals are combined into a deterministic value model.
Evidence quality and trust-weighted participation drive the score.
Initial signal groups:
- TSEMIT / UNTSEMIT
- evidence strength
- unique participants
- trust signals
- freshness
- growth velocity
- public importance

### Ranking
Seeds are ordered for visibility using policy-governed scoring.
Ranking remains dynamic and recalculates as new signals arrive.

### Promotion
Top seeds become promotion candidates on defined cadence and thresholds.
Promotion is a controlled transition, not an automatic popularity jump.
Initial promotion policy:
- Top candidate: eligible every 1 hour
- Second candidate: eligible every 2 hours
- Third candidate: eligible every 4 hours

### Living Case
A promoted seed becomes a structured Living Case.
It gains persistent state, timeline continuity, and ongoing evidence integration.

### Knowledge Graph Update
Entities, relationships, and case links are committed to the graph.
This creates connected public knowledge with traceable provenance.

### Continuous Evolution
Living Cases remain open to new evidence, corrections, and re-evaluation.
State, confidence, and ranking can change deterministically over time.

### Historical Archive
Stabilized case history is preserved as auditable record.
Archive state retains provenance and prior transitions for long-term accountability.

## 4. Engine Responsibilities
- Discovery Orchestrator:
  Coordinates deterministic stage progression, queue execution, and promotion flow control.
- Evidence Engine:
  Normalizes, validates, and provides canonical evidence data for lifecycle decisions.
- Trust Engine:
  Computes trust/credibility and community interaction integrity for scoring inputs.
- Public Importance Engine:
  Evaluates societal impact and significance signals used in ranking/promotion decisions.
- Policy Engine:
  Defines deterministic weighting, thresholds, and promotion cadence.
- Knowledge Graph:
  Persists connected entities/relations and case links as auditable public knowledge.
- Story Module / rendering layer:
  Materializes promoted information as Living Cases and presents lifecycle state to users.

## 5. Design Rules
- Every submission follows the same lifecycle.
- Popularity alone never determines promotion.
- Evidence outweighs popularity.
- AI never changes deterministic evaluations.
- Every stage must remain traceable.
- Do not create duplicate engines when an existing engine can be reused.

## 6. Architecture Decision
Community Engine is not a standalone duplicate stack.
It is an orchestration-first capability using existing engines plus thin missing policy/scoring capabilities.

## 7. Final Principle
Information is never static.
Every contribution can evolve from an isolated submission into connected public knowledge.
