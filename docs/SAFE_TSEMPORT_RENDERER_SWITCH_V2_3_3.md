# v2.3.3 Safe TSEMPORT Renderer Switch

Adds safe renderer switching without dangerous template override.

Modes:
- legacy: old company report remains live.
- preview: only admins can preview the new TSEMPORT using ?tsemou_tsemport_preview=1.
- new: new TSEMPORT renderer becomes live for single company pages.

Admin:
Developer Console shows renderer controls and preview link.

Principle:
Engines first. Renderer only after diagnostics pass.
