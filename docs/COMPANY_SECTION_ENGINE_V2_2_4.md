# Company Section Engine v2.2.4

Purpose:
Connect visible Company TSEMPORT sections to internal engine layers without creating many frontend shortcodes.

No shortcode explosion:
- Keeps [tsemou_company_directory]
- Company TSEMPORT uses internal PHP section engine components.

Active foundations:
- TSEMIDENCE -> Evidence posts linked by _tsemou_entity_type=company and _tsemou_entity_id={company_id}
- Timeline -> TSEMOU Events + evidence dates
- Related Entities -> _tsemou_related_entities fallback + profile-derived relations
- TSEMIT -> _tsemou_tsemit_votes meta placeholder
- TSEMScore -> existing score meta, future Entity Trust

Admin:
TSEMOU OS -> Section Engine
Evidence -> Add New -> TSEMOU Evidence Links
TSEMOU OS -> TSEMOU Events
