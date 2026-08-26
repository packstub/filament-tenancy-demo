# filament-tenancy-demo

Minimal Laravel 13 + Filament 5 app showing database-per-tenant multi-tenancy with `packstub/filament-tenancy`. Public example app; also used for screenshots and manual QA of the plugin.

- Served locally at `http://filament-tenancy-demo.test` (Herd).
- The plugin is installed from Packagist/the Packstub store (see `auth.json`), not a path repo — bump it deliberately.
- `composer run dev` for the dev stack, `composer test` for the suite.
- Keep the app minimal: it is the "getting started" reference; anything plugin-specific belongs in the plugin docs.
