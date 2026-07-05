# Knowledge Graph Engine Foundation / Plugin v2.0.2

Admin:
TSEMOU OS -> Knowledge Graph

Adds admin-controlled Entity -> Entity relationships with confidence, relevance flags, status and logs.

A relationship is context unless policies and evidence make it Trust-relevant.

No frontend or Trust change yet.

## Knowledge Graph Update Engine MVT

Purpose:
Provide the minimum internal coordination layer that accepts normalized entities and normalized relationships, validates them, and builds graph update payloads.

This engine is not a graph database and does not add traversal, search, AI, background jobs, or new database tables.

### Public API
- `update_entity_graph($entity)`
- `update_relationship_graph($relationship)`
- `build_graph_payload($entity, $relationships)`
- `validate_graph_payload($payload)`
- `get_graph_statistics()`

### Graph Payload Format
```php
[
	'entity' => [...],
	'relationships' => [...],
	'updated_at' => '',
	'status' => 'ready'
]
```

### MVT Scope
- Accept normalized entity input from Entity Engine.
- Accept normalized relationship input from Relationship Engine.
- Validate payload shape and embedded records.
- Return normalized update results for future graph persistence or graph orchestration.

### Not Implemented
- Persistence layer changes
- Graph traversal algorithms
- Search
- AI
- Public UI
- Cron or background jobs
