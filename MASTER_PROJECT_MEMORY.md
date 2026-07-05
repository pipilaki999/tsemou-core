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
Phase D: Next major phase after C.5

Updated sequence:
1. Phase A
2. Phase B
3. Phase C
4. Phase C.5
5. Phase D

## 5. Phase C.5 - Interactive Citizen Experience
Phase C.5 has been added before Phase D.

Purpose:
To introduce the architectural foundation for citizen interaction before moving into the next major engine layer.

This phase defines how users will interact with information, evidence, claims, companies, topics, and future AI-assisted workflows.

Current documentation files:
- DESIGN_MODEL.md
- INTERACTIVE_CITIZEN_EXPERIENCE.md

No PHP implementation is currently required for v5.0.1.

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
v5.0.1

Current task:
Commit documentation release.

Commit message:
v5.0.1 - Add Phase C.5 Interactive Citizen Experience docs

Files expected in commit:
- DESIGN_MODEL.md
- INTERACTIVE_CITIZEN_EXPERIENCE.md
- MASTER_PROJECT_MEMORY.md

Important:
No PHP files should be included in this commit.

Next development focus after commit:
Begin the intermediate engine layer for Phase C.5 before moving to Phase D.
