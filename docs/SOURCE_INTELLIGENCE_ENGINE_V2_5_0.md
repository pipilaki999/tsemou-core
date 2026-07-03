# TSEMOU Source Intelligence Engine v2.5.0

Adds source/domain intelligence.

Public API:
- Source_Intelligence_Engine::analyze($url_or_domain)
- Source_Intelligence_Engine::credibility_for_url($url)

Normalizes:
- domain
- publisher
- source type
- country
- credibility baseline
- verified status
- bias placeholder
- class
- risk
- warnings

Admin:
- Tools -> TSEMOU Source Intelligence
- TSEMOU OS -> Source Intelligence, when parent menu is available

Bridge:
Evidence Engine now enriches source payload and can use source intelligence as a baseline credibility source.

Purpose:
Foundation for Discovery/Scraper Engine and automatic evidence ingestion.
