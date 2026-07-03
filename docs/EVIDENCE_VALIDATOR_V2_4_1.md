# TSEMOU Evidence Validator v2.4.1

Adds validation above Evidence Engine.

Public API:
- Evidence_Validator::validate($evidence_id)
- Evidence_Validator::validate_for_company($company_id, $limit)

Checks:
- company relationship
- source URL/domain
- credibility
- evidence date
- sentiment
- evidence kind
- clean summary
- possible duplicates

Output:
- valid
- ready_for_trust
- score
- errors
- warnings
- checks
- normalized evidence

Purpose:
Trust Engine should only use validated evidence, not raw posts.
