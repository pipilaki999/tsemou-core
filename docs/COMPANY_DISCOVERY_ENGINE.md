# TSEMOU Company Discovery Engine v1.0 / Plugin v1.2.5

Purpose:
- Import, normalize, deduplicate and store Company Objects.
- Does not calculate Trust.
- Does not create Evidence.
- Does not scrape.
- Stores Company Profile JSON, Discovery Metadata and Pipeline Status.

Admin:
TSEMOU OS → Company Discovery

Current:
- Import Company JSON
- Duplicate detection by domain, registration number, ISIN and title
- Create/update company posts
- Import logs

Next:
- Consume Orchestrator queue tasks
- Add connectors
- Add CSV import
- Add discovery source policies
