# TSEMOU Policy Engine v1.2.1

Purpose:
- Centralize rules used by Engines.
- Avoid hardcoded weights scattered across code.
- Allow admin governance without code edits.

Admin:
TSEMOU OS → Policy Engine

Current policy groups:
- severity
- evidence_type
- source_type
- verification
- legal
- time_decay
- trust

Evidence Intelligence reads Policy Engine where available and falls back to internal defaults.
