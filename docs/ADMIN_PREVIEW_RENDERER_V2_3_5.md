# v2.3.5 Admin Preview Renderer

Fix:
Frontend clean preview can show blank for draft company pages because of permalink/theme/template routing.

Solution:
Render the Clean TSEMPORT preview inside Developer Console.

Use:
Developer Console -> Company ID 7723 -> Run Diagnostics -> Open Admin Clean Preview

This avoids:
- draft permalink problems
- frontend template_redirect conflicts
- theme routing issues
- blank frontend preview
