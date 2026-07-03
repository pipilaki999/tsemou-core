# TSEMOU Configuration OS v1.0 / Plugin v1.2.3

Purpose:
- Central JSON configuration layer for TSEMOU engines.
- No engine should hardcode countries, industries, waves, source policies or evidence labels.
- Company Discovery Engine and Evidence Discovery Engine will read this layer.

Admin:
TSEMOU OS → Configuration OS

Config folder:
- countries.json
- industries.json
- discovery_waves.json
- discovery_sources.json
- source_policies.json
- company_fields.json
- evidence_categories.json
- evidence_types.json
- legal_status.json
- severity_levels.json
- verification_levels.json
- trust_defaults.json
- languages.json
- ai_prompts.json
- ui_labels.json

Main architectural rule:
Configuration OS defines available options.
Policy Engine defines weights/rules.
Engines produce outputs.
Frontend displays outputs.
