# TSEMOU Evidence Engine v2.4.0

Adds the first normalized Evidence Engine.

Public API:
- Evidence_Engine::get($evidence_id)
- Evidence_Engine::get_for_company($company_id, $limit)
- Evidence_Engine::validate($evidence_id)

Normalizes:
- title
- summary
- source url / label
- credibility score / label
- sentiment
- kind
- status
- date
- company relationships
- warnings

Admin:
- Tools -> TSEMOU Evidence Engine
- TSEMOU OS -> Evidence Engine, when parent menu is available

Purpose:
Renderers, Trust, Timeline, Discovery and AI should read evidence through this engine instead of raw ACF/meta.
