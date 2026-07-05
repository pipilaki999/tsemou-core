# TSEMOU MASTER PROJECT MEMORY

## 1. Vision
TSEMOU is being developed as a global platform for collecting, verifying, connecting, and explaining public-interest information through structured evidence, civic participation, and future AI-assisted workflows.

The long-term goal is to become a citizen-powered intelligence and verification system, not just a news site.

## 2. Core Principles
- Production-ready development only
- No temporary or throwaway code
- Modular architecture
- Documentation before expansion
- Verification before publication
- Evidence-first model
- Citizen participation as a core system layer
- AI as assistant, not uncontrolled publisher
- Every release must be installable, testable, and reversible

## 3. Current Stable State
Current stable version: v5.0.0

Status:
- Phase A completed
- Plugin installs correctly
- ZIP packaging issue fixed
- PowerShell build script works
- WordPress plugin ZIP builds correctly
- Admin menus work
- Company, Evidence, and all CPTs appear correctly
- Basic smoke test passed

## 4. Development Roadmap
Phase A: Completed
Phase B: Completed
Phase C: Completed
Phase C.5: Interactive Citizen Experience
Phase C.6: Integration
Phase D: Next major phase after C.5

Updated sequence:
1. Phase A
2. Phase B
3. Phase C
4. Phase C.5
5. Phase C.6
6. Phase D

Phase C.6 - Integration
Goal:
Unify Discovery, Story, Evidence Processing, tsemou_proof, Knowledge Graph and Company Page.

## 5. Phase C.5 - Interactive Citizen Experience
Phase C.5 has been added before Phase D.

Purpose:
To introduce the architectural foundation for citizen interaction before moving into the next major engine layer.

This phase defines how users will interact with information, evidence, claims, companies, topics, and future AI-assisted workflows.

Current documentation files:
- DESIGN_MODEL.md
- INTERACTIVE_CITIZEN_EXPERIENCE.md

No PHP implementation is currently required for v5.0.1.

Current implementation target after v5.0.1:
v5.0.2

## 6. Version History

### v5.0.0
Stable plugin version.

Included:
- Correct WordPress plugin packaging
- Working build process
- Admin menu validation
- CPT visibility validation
- ZIP installation confirmed

### v5.0.1
Documentation release.

Included:
- Phase C.5 added to roadmap
- Interactive Citizen Experience documented
- Master Project Memory introduced

No PHP changes.

### v5.0.2
Entity Engine initial core release.

Included:
- Entity Engine added
- Status: implemented initial core
- Version target: v5.0.2
- Relationship Engine added
- Relationship Engine MVT implemented
- Knowledge Graph Update Engine added
- Knowledge Graph Update Engine MVT implemented
- Evidence Processing Engine added
- Evidence Processing Engine MVT implemented

No frontend UI changes.

### v5.0.3
Integration release.

Included:
- Discovery Orchestrator story-processing bridge added
- Discovery Orchestrator can now queue and execute Story-based Evidence Processing Engine jobs
- Story processing now flows into canonical `tsemou_proof` and downstream relationship/graph coordination

No new CPTs, no new tables, no frontend UI changes.

### Canonical Evidence Model Decision
Canonical Evidence Model: `tsemou_proof`

Meaning:
- `evidence` is a conceptual name only.
- `tsemou_proof` is the persistent WordPress CPT for beta.
- All new Phase C.6 integration must write to `tsemou_proof`.
- Legacy evidence paths may remain temporarily but must not be extended.
- No migration is performed in this step.
- This decision supports the MVT by removing duplicate evidence lifecycle ambiguity.

## 7. Architectural Decisions

### ADR-001: Evidence-first architecture
Evidence is treated as a core independent entity.

Reason:
Evidence may connect to companies, people, claims, events, investigations, and future citizen contributions.

Status:
Accepted

### ADR-002: Interactive Citizen Experience before Phase D
Phase C.5 is implemented before Phase D.

Reason:
The future engine layer must be built on top of a clear citizen interaction model.

Status:
Accepted

### ADR-003: AI is assistant, not uncontrolled publisher
AI may support discovery, classification, explanation, and verification assistance.

AI must not become an uncontrolled publishing authority.

Status:
Accepted

### ADR-004: No temporary code in production releases
Every committed version must remain clean, stable, and installable.

Status:
Accepted

## 8. Current Architecture Snapshot
Core system areas:
- Admin framework
- Custom Post Types
- Company model
- Evidence model
- Discovery layer
- Documentation model
- Future citizen interaction layer
- Future verification engines
- Future AI-assisted workflows

## 9. Modules

### Company Module
Status:
Active

Purpose:
Represents companies, organizations, or entities that may be connected to evidence, claims, topics, or investigations.

### Evidence Module
Status:
Active

Purpose:
Stores structured evidence and allows future connection to multiple system objects.

Canonical beta persistence model:
`tsemou_proof`

### Entity Engine
Status:
Implemented initial core

Purpose:
Provides a central service layer for representing normalized system entities for future engines.

### Relationship Engine
Status:
Implemented MVT core

Purpose:
Provides a minimal internal layer for normalizing and validating relationships between entities before persistence and public interaction layers are added.

### Knowledge Graph Update Engine
Status:
Implemented MVT core

Purpose:
Coordinates graph update payloads between Entity Engine, Relationship Engine, and the existing Knowledge Graph layer without introducing a new persistence system.

### Evidence Processing Engine
Status:
Implemented MVT core

Purpose:
Transforms Story posts into structured internal evidence payloads and graph-ready relationships using the existing entity, relationship, and graph update engines.

### Story To Proof Integration
Status:
Implemented integration step

Purpose:
Connects Evidence Processing Engine to the canonical `tsemou_proof` model so Story processing now materializes into the beta evidence CPT.

### Discovery Layer
Status:
Active / evolving

Purpose:
Supports structured discovery and future information mapping.

### Interactive Citizen Experience
Status:
Documented / pending implementation

Purpose:
Defines how citizens will participate, react, contribute, verify, and navigate information.

### Verification Engines
Status:
Next development focus

Purpose:
To create the intermediate engine layer needed before Phase D.

## 10. Release Checklist
Before every release:
- Confirm no unwanted PHP changes
- Confirm documentation changes
- Run build script
- Generate clean ZIP
- Install plugin in WordPress
- Activate plugin
- Check admin menus
- Check CPT visibility
- Run smoke test
- Commit changes
- Push to GitHub
- Optional: create release tag

## 11. Technical Debt
None currently blocking v5.0.1.

Any future shortcuts must be documented here before being accepted.

## 12. Parking Lot
Future ideas not yet scheduled:
- Reputation system
- Citizen scoring
- Public contribution dashboard
- AI-assisted claim explanation
- AI-assisted evidence summarization
- Multi-language citizen interface
- Source reliability graph
- Global topic monitoring
- Public verification queue

## 13. Working Method
The development workflow is:

1. ChatGPT designs the architecture
2. ChatGPT gives exact instructions to Copilot
3. Copilot implements
4. Human approval
5. Testing
6. Commit
7. Push
8. Continue

## 14. Next Session Starting Point
Current target version:
v5.0.3

Current task:
Implement Phase C.6.3 Discovery Orchestrator integration.

Commit message:
v5.0.3 - Add Discovery Orchestrator story-processing bridge

Files expected in commit:
- MASTER_PROJECT_MEMORY.md
- docs/DISCOVERY_ORCHESTRATOR_C6_INTEGRATION.md
- modules/discovery-orchestrator/class-discovery-orchestrator.php

Important:
Do not include unrelated PHP files in this commit.

Next development focus after commit:
Validate the end-to-end MVT smoke path and keep Phase A acquisition stable.

Current integration decision:
Use `tsemou_proof` as the only canonical persistent evidence model for Phase C.6 and beta integration.

### Phase C.6.4 Verification
Status:
PARTIAL

Verified handoffs:
- Discovery Orchestrator can dispatch a story-processing job
- Evidence Processing Engine creates or updates canonical `tsemou_proof`
- `proof_id` is returned
- normalized relationships are created
- Knowledge Graph Update Engine receives a graph payload for validation

Known blocker before Phase D:
- graph persistence is still service-only; the graph update engine validates payloads but does not write to a canonical graph store

### Phase C.6.5 Final Handoff
Status:
PASS

Result:
- Evidence Processing Engine now commits the validated graph payload through the existing Knowledge Graph Update Engine
- Knowledge Graph Update Engine reuses the existing Knowledge_Graph add/update path for canonical relationship writes
- The last missing handoff in the MVT pipeline is now completed

### v5.1.0 Production Review
Status:
BLOCKER CONFIRMED

Blocker:
- version consistency is still broken across the plugin header, docs, and project memory
- the build ZIP version is not tracked in the repository
