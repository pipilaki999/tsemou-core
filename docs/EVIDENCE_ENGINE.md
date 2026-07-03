# Evidence Engine v0.9.8

Scope:
- Backend only.
- No frontend template changes.
- No new plugin.
- No snippets.
- Runs on `save_post_tsemou_proof`.

Behavior:
- Finds related Company IDs from direct evidence meta or from the connected TSEMOU File.
- Stores `_tsemou_evidence_company_ids` on Evidence.
- Updates company evidence counters:
  - `_tsemou_evidence_total`
  - `_tsemou_evidence_positive`
  - `_tsemou_evidence_negative`
  - `_tsemou_evidence_neutral`
  - `_tsemou_evidence_high_credibility`
  - `_tsemou_evidence_avg_credibility`
  - `_tsemou_evidence_last_sync`
- Adds one Company Timeline event per Evidence/Company pair when supported.


## v0.9.9 Evidence Counters Panel
Shows Evidence Engine counters inside the Company editor. Backend/admin display only.
