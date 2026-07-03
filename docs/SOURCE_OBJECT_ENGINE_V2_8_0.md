# TSEMOU Source Object Engine v2.8.0

Step 2 of 4.

Adds reusable Source Objects.

Public API:
- Source_Object_Engine::find_or_create_from_url($url)
- Source_Object_Engine::get_source($source_id)
- Source_Object_Engine::source_from_evidence($evidence_id)
- Source_Object_Engine::attach_evidence_to_source($evidence_id)

Scope:
- tsemou_source post type
- domain normalization
- source object creation
- evidence -> source_id bridge
- source intelligence enrichment where available
- admin diagnostics

No AI.
No scraping.
No external lookup.

Admin:
- Tools -> TSEMOU Source Object Engine
- TSEMOU OS -> Source Object Engine

Bridge:
Evidence Engine source payload now includes source_id/source_object when available.
Discovery-created Evidence now stores _tsemou_source_id.
