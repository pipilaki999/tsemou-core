# TSEMOU Discovery Engine v2.6.0

Adds safe discovery queue foundation.

This is not full automatic scraping yet.

Features:
- tsemou_discovery post type
- Tools -> TSEMOU Discovery Engine
- Add Discovery Candidate from URL/title/text
- Source Intelligence analysis
- Company matching by text/title
- Candidate diagnostics
- Create pending Evidence from ready candidate
- Admin-controlled workflow

Pipeline:
URL/Text -> Source Intelligence -> Company Matching -> Discovery Candidate -> Pending Evidence -> Human Review -> Trust Engine

Purpose:
Prepare TSEMOU for future automated scraping without unsafe automatic publishing.
