# Packstub Tenancy Demo

The smallest complete app that shows **database-per-tenant multi-tenancy in
Filament**: Laravel 13 + Filament 5 + stancl/tenancy v4, wired together by the
[packstub/filament-tenancy](https://packstub.dev/tenancy) plugin.

One login on the central domain, a workspace per tenant on its own subdomain,
and every tenant on its **own database** — no `tenant_id` columns, no global
scopes. Clone it, run one command, log in, switch between two provisioned
tenants, create a third through the onboarding wizard.

> This is a reference, not a framework. It stays deliberately small so every
> file is about one thing: how the plugin plugs into a stock Filament panel.
> The plugin itself is commercial and is **not** in this repo — it installs
> from the Packstub registry with the credentials you get on purchase. You can
> read all of this code before buying.

## What you get

| Where | What |
|---|---|
| `http://filament-tenancy-demo.test/admin` | Central panel: login, tenant switcher, onboarding wizard (`/admin/new`) |
| `http://acme.filament-tenancy-demo.test/admin` | Tenant **Acme Inc.** — its own SQLite database |
| `http://globex.filament-tenancy-demo.test/admin` | Tenant **Globex Corp.** — a different database |
| `…/admin/projects` | An ordinary Filament resource, isolated per tenant |
| `…/admin/profile` | Rename the workspace / change its slug and avatar |

Demo login: **demo@example.com** / **password** (owner of both tenants).

## Requirements

- PHP 8.4+, Composer
- A Packstub Tenancy license → a `pkg_…` access token from your [Packstub dashboard](https://packstub.dev/dashboard)
- Wildcard local DNS for `*.filament-tenancy-demo.test` — [Laravel Herd](https://herd.laravel.com) or Valet do this for every `*.test` site automatically

## Run it

```bash
git clone https://github.com/packstub/filament-tenancy-demo.git
cd filament-tenancy-demo

# 1. Registry credentials (from your dashboard's Install Guide page).
#    Writes auth.json, which is git-ignored.
composer config --auth http-basic.packstub.dev pkg_xxxxxxxxxxxxxxxx your-token-secret

# 2. Install + provision two tenants (central DB, two tenant DBs, demo user).
composer setup

# 3. Serve it with wildcard subdomains.
herd link filament-tenancy-demo      # → http://filament-tenancy-demo.test
```

Open <http://filament-tenancy-demo.test/admin>, sign in, and you land in
`acme.filament-tenancy-demo.test/admin`. Use the switcher in the sidebar to jump
to Globex, or **Create Organization** (`/admin/new`) to add a tenant through the wizard — start a
queue worker first so provisioning runs:

```bash
composer run dev          # server + queue worker + logs + vite, via `php artisan dev`
# or just: php artisan queue:work
```

Not using Herd? Add `127.0.0.1 filament-tenancy-demo.test acme.filament-tenancy-demo.test globex.filament-tenancy-demo.test`
to `/etc/hosts`, set `APP_URL=http://filament-tenancy-demo.test:8000` in `.env`, and
`php artisan serve`. Every tenant you create later needs its own hosts line —
that is why wildcard DNS is the recommended setup.

## Where tenancy lives

Everything specific to multi-tenancy is in these files — the rest is a stock
`laravel new` + `filament:install --panels`:

| File | Role |
|---|---|
| [`app/Providers/Filament/AdminPanelProvider.php`](app/Providers/Filament/AdminPanelProvider.php) | `->plugin(TenancyPlugin::make())` — the one line that wires tenant model, subdomain routing, switcher, onboarding, provisioning page. No stancl middleware in the panel. |
| [`app/Models/User.php`](app/Models/User.php) | `CentralConnection` + `HasPackstubTenants`: users, sessions and auth stay in the **central** database. |
| [`config/packstub-tenancy.php`](config/packstub-tenancy.php) | Plugin config. Only change from the published default: `'seeder' => TenantSeeder::class`. |
| [`config/tenancy.php`](config/tenancy.php) | stancl config as published by `packstub-tenancy:install` — tenant model set, `DatabaseSessionBootstrapper` left disabled, migrations pointed at `database/migrations/tenant`. |
| [`database/migrations/tenant/`](database/migrations/tenant) | Runs against **each tenant database**. Holds `projects` only — never `users`/`sessions`. |
| [`database/seeders/TenantSeeder.php`](database/seeders/TenantSeeder.php) | Tenant-safe seeder run by the provisioning pipeline inside every new tenant DB. |
| [`database/seeders/DatabaseSeeder.php`](database/seeders/DatabaseSeeder.php) | Demo data: the owner + two tenants, created through `TenantOnboarder` exactly like the wizard does. |
| [`app/Models/Project.php`](app/Models/Project.php), [`app/Filament/Resources/Projects/`](app/Filament/Resources/Projects) | A plain model and resource. Nothing tenant-aware in them — the connection is. |
| [`tests/TenantTestCase.php`](tests/TenantTestCase.php), [`tests/Feature/TenantSmokeTest.php`](tests/Feature/TenantSmokeTest.php) | Tenant-aware test harness (sync queue, SQLite tenant DBs in a temp dir) and the end-to-end smoke test. |

### The provisioning pipeline

Creating a tenant (wizard, seeder, or `TenantOnboarder::create()`) commits the
tenant row, its `{slug}.{central-domain}` domain row and the owner pivot in one
transaction, then dispatches **CreateDatabase → MigrateDatabase → SeedDatabase
→ MarkTenantReady** to the queue. Until the last step the tenant is
`provisioning` and every visit is routed to a polling status page; once
`ready`, the switcher lists it. `php artisan tenants:retry-provisioning {slug}`
re-runs the pipeline for a failed tenant.

## Tests and CI

```bash
php artisan test
```

`TenantSmokeTest` seeds the demo, asserts both tenants are `ready`, that each
tenant database holds only its own `projects` rows, and that the owner can open
`http://{slug}.filament-tenancy-demo.test/admin/projects` for each.

[`.github/workflows/smoke.yml`](.github/workflows/smoke.yml) runs the same
thing on a clean runner — **installing the plugin from the real Packstub
registry** — on every push, weekly, and on demand. It exists so a new Laravel,
Filament, stancl/tenancy, or plugin release that breaks a fresh install shows
up here before it reaches you. To run it in your own fork, add a
`COMPOSER_AUTH` repository secret:

```json
{"http-basic":{"packstub.dev":{"username":"pkg_xxxxxxxxxxxxxxxx","password":"your-token-secret"}}}
```

## Going further

- **MySQL/PostgreSQL** — set `DB_CONNECTION` and give the DB user `CREATE DATABASE`; tenant databases are named `tenant{uuid}`.
- **Path identification** (`example.com/admin/teams/acme`) — `'identification' => 'path'` in `config/packstub-tenancy.php`; no code changes.
- **Custom domains**, **database pools across servers**, **resource syncing** (a `users` mirror inside each tenant DB) — see the [plugin docs](https://packstub.dev/docs/filament-tenancy).

## License

The demo app is MIT ([LICENSE.md](LICENSE.md)). `packstub/filament-tenancy`
is commercial software licensed separately by [Packstub](https://packstub.dev)
— questions to [support@packstub.dev](mailto:support@packstub.dev).
