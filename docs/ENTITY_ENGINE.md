# Entity Engine

## Purpose
The Entity Engine provides a central internal service layer for representing TSEMOU system objects as normalized entities.

It does not create public UI, does not change frontend behavior, and does not introduce database schema changes.

## Status
Implemented initial core for v5.0.2.

## Supported Entity Types
- company
- evidence
- story
- topic
- source
- claim
- event
- person
- investigation

## Normalized Entity Format
```php
[
  'entity_type' => '',
  'entity_id' => 0,
  'source' => 'wordpress_post',
  'post_type' => '',
  'title' => '',
  'status' => '',
  'permalink' => '',
  'meta' => []
]
```

## Initial Capabilities
- Register supported entity types in one central configuration.
- Validate whether an entity type is supported.
- Normalize entity references into a stable structure.
- Resolve WordPress posts into TSEMOU entities when possible.
- Return stable entity metadata for future engines.

## Notes
- Current implementation is internal-service only.
- No admin UI is introduced by this phase.
- No new tables, migrations, or runtime rendering changes are included.