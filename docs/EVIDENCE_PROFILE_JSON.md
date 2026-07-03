# Evidence Profile JSON v1.1.5

Each Evidence now stores a unified JSON profile in:

`_tsemou_evidence_profile`

Profile structure:

```json
{
  "version": "1.1.5",
  "identity": {},
  "classification": {},
  "impact": {},
  "verification": {},
  "source": {},
  "engine": {}
}
```

Rules:
- Old meta fields remain for compatibility.
- Trust Engine reads JSON first, then falls back to old meta fields.
- Future engines should use the Evidence Profile as the primary data object.
