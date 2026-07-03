# Evidence Engine v2 - Data Model v1.1.3

Adds structured evidence metadata:

- Evidence Type
- Source Type
- Verification Level
- Legal Status
- Severity
- Trust Include / Exclude
- Manual Adjustment Note

Purpose:
- Prepare Evidence to feed Trust Engine, Source Reliability Engine and AI Engine.
- No frontend changes.
- No Trust Score algorithm change yet.


## v1.1.4 Evidence → Trust Integration

Trust Engine now uses:
- Trust Include / Exclude
- Evidence Type
- Source Type
- Verification Level
- Legal Status
- Severity
- Credibility

Admin can adjust multipliers from TSEMOU OS → Trust Engine.
