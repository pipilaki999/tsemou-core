# Relationship Engine

## Purpose
The Relationship Engine provides a minimal internal service layer for normalizing and validating relationships between TSEMOU entities.

It is designed for the first usable TSEMOU beta and does not introduce public UI, frontend changes, new tables, or persistence.

## Status
Implemented as a Phase C.5 MVT internal engine.

## Supported Relationship Types
- company_has_evidence
- story_has_evidence
- evidence_mentions_company
- evidence_supports_claim
- story_mentions_company
- story_mentions_topic
- source_published_story

## Normalized Relationship Format
```php
[
  'from' => [
    'entity_type' => '',
    'entity_id' => 0
  ],
  'to' => [
    'entity_type' => '',
    'entity_id' => 0
  ],
  'relationship_type' => '',
  'confidence' => 1.0,
  'source' => 'manual',
  'created_at' => '',
  'meta' => []
]
```

## MVT Scope
- Register supported relationship types centrally.
- Validate relationship type support.
- Validate normalized `from` and `to` entity references.
- Normalize relationship data for future engines.
- Avoid persistence until a clear storage contract is approved.

## Not Implemented In MVT
- Database persistence
- Public UI
- Frontend rendering
- Reputation logic
- Gamification
- Comments or reactions
- Advanced AI behavior
- Complex orchestration framework