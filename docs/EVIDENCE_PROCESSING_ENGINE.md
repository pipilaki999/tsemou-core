# Evidence Processing Engine

## Purpose
The Evidence Processing Engine is the first business engine of TSEMOU.

Its role in the MVT is to transform an existing Story post into structured internal evidence data, create normalized relationships, and prepare graph update information by orchestrating the existing Entity Engine, Relationship Engine, and Knowledge Graph Update Engine.

## Status
Implemented as a Phase C.5 MVT business engine.

## Processing Pipeline
Story

↓

Validate

↓

Normalize Entity

↓

Build Evidence Payload

↓

Create Relationships

↓

Update Knowledge Graph

↓

Return Processing Result

## Public API
- `process_story($story_id)`
- `validate_story($story)`
- `build_evidence_payload($story, $story_entity, $context)`
- `build_relationships($story, $story_entity, $evidence, $context)`
- `update_graph($evidence, $relationships)`
- `get_processing_statistics()`

## Processing Result Format
```php
[
    'status' => 'success',
    'story' => [...],
    'evidence' => [...],
    'relationships' => [...],
    'graph' => [...],
    'warnings' => [],
    'errors' => []
]
```

## MVT Limitations
- No AI
- No NLP
- No OCR
- No background jobs
- No cron
- No queues
- No new database tables
- No public UI
- No persistence of generated evidence records yet

## MVT Scope
- Use existing Story posts as the source input.
- Resolve the Story through Entity Engine.
- Build an internal evidence payload.
- Create normalized relationships from the Story context.
- Send graph-ready information through Knowledge Graph Update Engine.