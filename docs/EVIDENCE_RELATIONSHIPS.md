# Evidence Relationships Engine v1.2.2

Adds relationship fields between Evidence objects.

Relationship types:
- supports
- contradicts
- duplicates
- updates
- supersedes
- related

Storage:
- Legacy meta fields:
  - _tsemou_rel_supports
  - _tsemou_rel_contradicts
  - _tsemou_rel_duplicates
  - _tsemou_rel_updates
  - _tsemou_rel_supersedes
  - _tsemou_rel_related

JSON:
- relationships are stored inside _tsemou_evidence_profile
- relationships and relationship_summary are copied into _tsemou_evidence_intelligence

Purpose:
- Prepare future conflict detection, duplicate detection, evidence graph and trust explainability.
