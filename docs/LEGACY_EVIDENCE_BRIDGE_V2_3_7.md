# v2.3.7 Legacy Evidence Bridge

Fixes old Company Report Latest Evidence showing 0 while Developer Console shows evidence.

Changes:
- Old Company Engine evidence feed now checks:
  - _tsemou_company_id
  - _tsemou_entity_id
  - _tsemou_evidence_company_ids
  - _tsemou_evidence_company
  - tsemou_evidence_company
  - related_company
  - company serialized and direct values
- Old hero evidence total now falls back to bridge count.
- Old stats total/positive/negative/neutral fall back to bridge items.
- Elementor Query ID company_evidence is also bridged.
