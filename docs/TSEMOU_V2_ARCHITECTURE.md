# TSEMOU v2 Architecture

TSEMOU is now an Evaluation OS.

Core direction:
- Company is the first entity type, not the whole system.
- Evidence relates to entities.
- Trust is calculated for entities.
- Discovery imports entities.
- Intelligence prepares candidate evidence for entities.

Core layers:
1. Core Framework
2. Configuration OS
3. Discovery Orchestrator
4. Entity Engine
5. Modules: Companies, Evidence, Trust, Community, Rankings, Intelligence
6. UI

Development rule:
No new function is added unless it fits the architecture first.
