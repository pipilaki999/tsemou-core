

## v0.9.2
Adds Company Timeline Engine inside Company Engine only. No new plugins, no snippets, no new CPTs.


## v0.9.3
Adds Enhanced Related Articles to Company frontend inside Company Engine only. No new plugins, no snippets, no new CPTs.


## v0.9.4
Adds shortcode [tsemou_related_articles_pro] for exact placement of Enhanced Related Articles in existing Company templates.


## v0.9.5
Automatically renders Enhanced Related Articles on single Company pages via wp_footer, independent from Elementor or editor fields.


## v0.9.6
Injects Enhanced Related Articles directly into the existing Company frontend template output, replacing the empty Related Articles heading or inserting before Latest Evidence.


## v0.9.7 Architecture Lock
Related Articles now queries only WordPress posts. Internal `story` CPT remains TSEMOU Files and is not treated as a public article.


## v0.9.8 Evidence Engine
Backend-only sync on save_post_tsemou_proof. No frontend changes.


## v0.9.9 Evidence Counters Panel
Admin-only panel in Company Intelligence showing Evidence Engine counters. No frontend changes.


## v1.0.2 Frontend CSS Fix
Loads TSEMOU Core stylesheet on Company frontend pages so shortcode widgets render with intended design. No frontend logic changes.


## v1.0.3 Company Stats Widget
Adds [tsemou_company_stats] shortcode for frontend evidence counters. No engine changes.


## v1.0.4 Company Content Widgets
Adds summary, related articles and timeline shortcodes for Elementor-based Company templates. No engine changes.


## v1.0.5 Evidence Feed Widget
Adds [tsemou_company_evidence_feed] shortcode for frontend company evidence cards. No engine changes.


## v1.0.6 Trust & Community Engine
Adds Trust Engine module, admin settings, community vote storage, score calculation and frontend trust/vote widgets.


## v1.0.7 Community Engine UX
Adds simple community vote UI and admin moderation for blocking suspicious votes. No frontend template changes.


## v1.0.8 Community Shortcode Fix
Fixes missing quick vote method and keeps Community Moderation available. No new engine behavior.


## v1.0.9 Output Cleanup
Adds [tsemou_company_overview] to replace old Elementor three-column score/country/industry layout. No engine changes.


## v1.1.0 Single Company Page Renderer
Adds [tsemou_company_page] to render the full company page from TSEMOU Core, keeping Elementor as shell only.


## v1.1.1 Clean Company Page v2
Rebuilds [tsemou_company_page] as the single official company page renderer. Elementor should contain only one shortcode.


## v1.1.2 Company Page Polish
Normalizes score display and improves spacing/cards for [tsemou_company_page]. No new sections.


## v1.1.3 Evidence Engine Data Model
Adds structured Evidence metadata fields. No frontend or score algorithm changes.


## v1.1.4 Evidence Trust Integration
Trust Engine uses Evidence Engine v2 metadata and exposes source/verification/legal multipliers in admin settings.


## v1.1.5 Evidence Profile JSON
Adds unified `_tsemou_evidence_profile` JSON object. Trust Engine reads JSON first and falls back to old meta fields.


## v1.2.0 Evidence Intelligence Engine
Adds computed `_tsemou_evidence_intelligence` JSON output with impact, confidence, weights and explainability. Trust Engine reads this output first.


## v1.2.1 Policy Engine Foundation
Adds central Policy Engine with admin page. Evidence Intelligence reads policy weights where available.


## v1.2.2 Evidence Relationships
Adds relationship fields and JSON output for Evidence graph. No frontend changes.


## v1.2.3 Configuration OS Foundation
Adds /config JSON files and Configuration OS admin preview. Foundation for Company Discovery Engine and Evidence Discovery Engine.


## v1.2.4 Discovery Orchestrator Foundation
Adds Discovery Orchestrator admin page, pipeline map, queue foundation and logs. No actual discovery yet.


## v1.2.5 Company Discovery Engine Foundation
Adds Company Discovery module with JSON import, duplicate detection and Company Object storage. No scraping yet.


## v1.2.6 Trust Principles & Responsibility Model
Sets Trust baseline to 5/10, adds Leadership & Influence, Responsibility/Omission and War & Conflict categories, and documents TSEMOU Trust Principles.


## v1.2.7 Company Discovery Visible Results
Adds frontend directory and stats shortcodes for imported company objects.


## v1.2.8 Company Directory UX v2
Upgrades [tsemou_company_directory] into a full UX block with stats, cards and vote CTA. No new shortcode.


## v1.2.9 Force Company Directory UX Renderer
Forces [tsemou_company_directory] to use the v2 renderer.


## v1.3.0 Core Stability
Fixes company directory shortcode registration and forces the new UX renderer with init fallback.


## v1.3.1 Company Discovery Engine Completion
Stabilizes company directory shortcode registration and marks Company Discovery Engine foundation complete.


## v1.3.2 Company Intelligence Engine Foundation
Adds Company Intelligence admin module, intelligence report JSON and candidate evidence queue. No automatic publication or Trust change.


## v1.3.3 Company Auto Seed Engine
Adds config/company_seed_dataset.json and Auto Seed Companies admin action. No scraping/external calls.


## v2.0.0 Stable Entity Architecture
Adds Entity Engine foundation and fixes company directory shortcode registration. Marks shift from company-only model to entity-based Evaluation OS.


## v2.0.1 Entity Evidence Link Engine
Adds Evidence -> Entity relationship engine. No Trust/frontend changes yet.


## v2.0.2 Knowledge Graph Engine Foundation
Adds admin-controlled Entity -> Entity relationship graph with confidence, flags and logs.


## v2.0.3 Company Sensor Safe Importer
Adds structured company sensor import and seed dataset. No uncontrolled scraping.


## v2.1.0 Company UX System Release
Applies TSEMOU Design System v1 to company directory. No new shortcode.


## v2.1.1 Company UX Layout Fix
Fixes broken narrow center column by replacing fragile grid behavior with stable flex layout and safe text wrapping.


## v2.1.2 Company UX Polish
Moves from unstable 3-column layout to safer 2-column product layout. Timeline, related entities and score explanation move under main content.


## v2.1.3 Company UX Final Layout
Restores balanced three-column layout with wider hero, right sidebar, KPI icons and logo-style company marks. Hero height reduced and width expanded.


## v2.2.0 Company Layout Engine
Introduces real 3-column Company Layout Engine with wider hero, right sidebar, KPI icons, logo-style company marks, responsive behavior and reusable design system components.


## v2.2.1 Company FullWidth Layout Fix
Forces Company module to escape narrow WordPress/Elementor containers using a full viewport wrapper, reduces sidebar widths, widens center column and prevents hero title vertical breaking.


## v2.2.2 Company Import Batches
Adds 60 additional major companies in 3 batches and admin controls: Import Next Batch, per-batch import, force update, reset batch status.


## v2.2.3 Company TSEMPORT Page
Adds custom single company TSEMPORT page renderer via the_content filter for company posts.


## v2.2.4 Company Section Engine Foundation
Adds internal Company Section Engine that connects TSEMPORT sections to evidence, events, related entities and community summary without adding frontend shortcodes.


## v2.2.5 Unified Evidence Relationship Fix
Evidence connection no longer depends only on manual Entity ID. Company relationship is preferred, existing relationship meta is scanned, logs are added and future imports are publish-ready.


## v2.2.6 Force TSEMPORT Renderer Fix
Forces single company pages through new TSEMPORT renderer at priority 9999.


## v2.2.8 Safe Recovery / Diagnostics
Rolls back template override approach, removes dangerous single template replacement and adds diagnostics to Section Engine.


## v2.3.0 Developer Console
Adds central diagnostic console for engines, records, evidence relationships, company payloads and stability checks. This becomes the foundation for stable TSEMOU OS development.


## v2.3.1 Developer Console Admin Router Fix
Adds safety loader, robust admin menu registration and admin router fallback so Developer Console opens reliably.


## v2.3.2 Developer Console Hard Admin Fix
Adds standalone Tools admin page and standalone render fallback for Developer Console.


## v2.3.3 Safe TSEMPORT Renderer Switch
Adds controlled Legacy/Preview/New renderer mode and safe admin-only TSEMPORT preview without dangerous template override.


## v2.3.4 Clean TSEMPORT Renderer
Adds clean engine-driven TSEMPORT renderer with Legacy/Preview/New switch and admin preview link.


## v2.3.5 Admin Preview Renderer
Clean TSEMPORT preview now renders inside Developer Console to avoid draft/permalink/theme routing blank pages.


## v2.3.7 Legacy Evidence Bridge
Old Company Report Latest Evidence now reads the same evidence relationships as Developer Console/Section Engine.


## v2.3.8 Evidence Metadata Cleanup
Centralizes evidence metadata display: source URL, credibility, excerpt, status, sentiment and kind.


## v2.4.0 Evidence Engine Foundation
Adds normalized Evidence Engine with get(), get_for_company(), validation and metadata normalization.


## v2.4.1 Evidence Validator
Adds Evidence_Validator with source, company, credibility, date, sentiment, type, summary and duplicate checks.


## v2.5.0 Source Intelligence Engine
Adds source/domain analysis, credibility baseline, source classification and Evidence Engine enrichment.


## v2.6.0 Discovery Engine Foundation
Adds safe discovery queue, source analysis, company matching and pending evidence creation from candidates.


## v2.7.0 Company Intelligence Engine
Adds canonical identity, aliases, ticker/domain matching and Discovery Engine bridge.


## v2.8.0 Source Object Engine
Adds tsemou_source objects, find-or-create source, Evidence source_id bridge and Discovery source attachment.


## v2.9.0 Knowledge Graph Foundation
Adds entity/relation graph foundation and Evidence/Company/Source linking.


## v2.9.1 Knowledge Graph Inspector Upgrade
Expanded relation output, real counters, graph summary and graph health.
