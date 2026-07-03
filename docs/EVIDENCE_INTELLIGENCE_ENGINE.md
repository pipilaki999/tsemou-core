# Evidence Intelligence Engine v1.2.0

This completes the first full Evidence Engine layer.

Stored output:
`_tsemou_evidence_intelligence`

The output is separate from `_tsemou_evidence_profile`.

Principle:
- Evidence Profile = input snapshot / structured evidence data.
- Evidence Intelligence = computed engine output.
- Trust Engine reads Intelligence output first.
- If Intelligence output is missing, Trust Engine falls back to older meta/profile logic.

Output includes:
- impact
- confidence
- weights
- categories
- explainability reasons
- included_in_trust
