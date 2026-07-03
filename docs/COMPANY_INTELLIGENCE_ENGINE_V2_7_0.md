# TSEMOU Company Intelligence Engine v2.7.0

Step 1 of 4.

Adds minimal canonical company identity and matching.

Public API:
- Company_Intelligence_Engine::get_company_identity($company_id)
- Company_Intelligence_Engine::match($input)
- Company_Intelligence_Engine::detect_in_text($text)
- Company_Intelligence_Engine::enrich_company($company_id)
- Company_Intelligence_Engine::enrich_all()

Scope:
- canonical name
- display name
- official name
- aliases
- ticker
- website/domain
- country
- industry
- simple exact/contains matching

No AI.
No external lookup.
No heavy fuzzy matching.

Admin:
- Tools -> TSEMOU Company Intelligence
- TSEMOU OS -> Company Intelligence

Bridge:
Discovery Engine company detection now uses Company Intelligence if loaded.
