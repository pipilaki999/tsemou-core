# v2.3.8 Evidence Metadata Cleanup

Fixes Evidence display quality before UI/UX work.

Changes:
- Evidence source URL now checks multiple source URL meta fields.
- Credibility now checks multiple credibility fields and falls back to "Not rated".
- Evidence excerpt prefers summary fields and filters template/hero noise.
- Sentiment, kind and status are centralized as helper methods.
- Clean TSEMPORT renderer uses the same helpers where available.
