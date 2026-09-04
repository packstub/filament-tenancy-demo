# Packstub Tenancy Demo

The smallest complete app showing **database-per-tenant multi-tenancy in Filament**: Laravel 13 + Filament 5 + stancl/tenancy v4, wired by the [Packstub Tenancy](https://packstub.dev/tenancy) plugin.

One login on the central domain, a workspace per tenant on its own subdomain, every tenant on its **own database** — no `tenant_id` columns, no global scopes.

[![Launch the hosted demo](https://img.shields.io/badge/Launch-hosted%20demo-f59e0b?style=for-the-badge&logo=laravel&logoColor=white)](https://tenancy-demo.packstub.dev)
[![Deploy on Laravel Cloud](https://img.shields.io/badge/Deploy-Laravel%20Cloud-0a0a0a?style=for-the-badge&logo=laravel&logoColor=white)](#deploy-on-laravel-cloud)
[![smoke](https://github.com/packstub/filament-tenancy-demo/actions/workflows/smoke.yml/badge.svg)](https://github.com/packstub/filament-tenancy-demo/actions/workflows/smoke.yml)

> [!NOTE]
> This is a reference, not a framework — deliberately small so every file is about one thing. The plugin itself is commercial and **not** in this repo; it installs from the Packstub registry with the token you get on purchase. All of this code is readable before buying.

## Run it locally

You need PHP 8.4+, Composer, a Packstub access token (`pkg_…`, from your [dashboard](https://packstub.dev/dashboard)), and wildcard local DNS — [Laravel Herd](https://herd.laravel.com) or Valet handle `*.test` automatically.

```bash
git clone https://github.com/packstub/filament-tenancy-demo.git && cd filament-tenancy-demo
composer config --auth http-basic.packstub.dev pkg_xxxxxxxxxxxxxxxx your-token-secret   # writes git-ignored auth.json
composer setup                        # install, migrate, seed two tenants on their own SQLite DBs
herd link filament-tenancy-demo       # → http://filament-tenancy-demo.test
composer run dev                      # server + queue worker + vite (provisioning needs the worker)
```

Open <http://filament-tenancy-demo.test> — the landing page links everything below.

> [!TIP]
> Not using Herd? Add `127.0.0.1 filament-tenancy-demo.test acme.filament-tenancy-demo.test globex.filament-tenancy-demo.test` to `/etc/hosts`, set `APP_URL=http://filament-tenancy-demo.test:8000`, and `php artisan serve`. Every tenant you create later needs its own hosts line — which is why wildcard DNS is the recommended setup.

## What to click

Two accounts, both with password `packstub-tenancy-demo` (while `DEMO_LOGIN_PREFILL=true` the first is pre-filled and the login page has a **Sign in as viewer** button):

| Login | Acme | Globex |
|---|---|---|
| `demo@example.com` | owner | owner |
| `viewer@example.com` | owner | member — Projects is read-only, no inviting |

Roles are **per tenant**: they live on the central `tenant_user` pivot (`role` column), and `ProjectPolicy` + the Members page read it through `User::isOwnerOf($tenant)`.

| URL | What it is |
|---|---|
| `filament-tenancy-demo.test/admin` | Central: login, tenant switcher, onboarding wizard (`/admin/new`) |
| `acme.filament-tenancy-demo.test/admin` | Tenant **Acme Inc.** — its own SQLite database |
| `globex.filament-tenancy-demo.test/admin` | Tenant **Globex Corp.** — a different database |
| `…/admin/projects` | An ordinary Filament resource, isolated per tenant |
| `…/admin/profile` | Rename the workspace, change its slug and avatar |
| `…/admin/members` | Who's in this workspace; owners invite by email, change roles, remove members |
| `filament-tenancy-demo.test/central` | **Operator panel** (no tenancy): all tenants, retry provisioning, delete a tenant (drops its DB) |

1. Sign in — you land in Acme's panel.
2. Add a project, switch to Globex. It isn't there: each tenant reads and writes its own database.
3. **Create organization** in the switcher. The queue creates a database, migrates and seeds it, and redirects you in.

## Where tenancy lives

Everything tenancy-specific is in these files; the rest is a stock `laravel new` + `filament:install --panels`.

| File | Role |
|---|---|
| [`app/Providers/Filament/AdminPanelProvider.php`](app/Providers/Filament/AdminPanelProvider.php) | `->plugin(TenancyPlugin::make())` — the one line that wires tenant model, subdomain routing, switcher, onboarding, provisioning page. No stancl middleware. |
| [`app/Providers/Filament/CentralPanelProvider.php`](app/Providers/Filament/CentralPanelProvider.php), [`app/Filament/Central/`](app/Filament/Central) | A second panel **without** the plugin, pinned to the central domain — the pattern for any operator/back-office panel. |
| [`app/Models/User.php`](app/Models/User.php) | `CentralConnection` + `HasPackstubTenants`: users, sessions, and auth stay in the **central** database. |
| [`config/packstub-tenancy.php`](config/packstub-tenancy.php) | Plugin config. Only change from the default: `'seeder' => TenantSeeder::class`. |
| [`config/tenancy.php`](config/tenancy.php) | stancl config as published by the installer — tenant model set, `DatabaseSessionBootstrapper` disabled. |
| [`database/migrations/tenant/`](database/migrations/tenant) | Runs against **each tenant database**. Holds `projects` only — never `users`/`sessions`. |
| [`database/seeders/TenantSeeder.php`](database/seeders/TenantSeeder.php) | Tenant-safe seeder the provisioning pipeline runs inside every new tenant DB. |
| [`database/seeders/DatabaseSeeder.php`](database/seeders/DatabaseSeeder.php) | Demo data: the owner + two tenants, created through `TenantOnboarder` exactly like the wizard. |
| [`app/Models/Project.php`](app/Models/Project.php), [`app/Filament/Tenant/Resources/Projects/`](app/Filament/Tenant/Resources/Projects) | A plain model and resource. Nothing tenant-aware in them — the connection is. |
| [`app/Filament/Pages/Auth/Login.php`](app/Filament/Pages/Auth/Login.php) | Stock login page that pre-fills the demo account. Not tenancy-related — delete it in your app. |
| [`tests/TenantTestCase.php`](tests/TenantTestCase.php), [`tests/Feature/TenantSmokeTest.php`](tests/Feature/TenantSmokeTest.php) | Tenant-aware test harness (sync queue, SQLite tenant DBs in a temp dir) and the end-to-end smoke test. |

How provisioning works, what lives in which database, and why there's no stancl middleware: [How it works](https://packstub.dev/docs/filament-tenancy/how-it-works) in the plugin docs.

## Tests and CI

```bash
php artisan test
```

`TenantSmokeTest` seeds the demo, asserts both tenants are `ready`, that each tenant database holds only its own `projects`, and that the owner can open each tenant's panel.

[`.github/workflows/smoke.yml`](.github/workflows/smoke.yml) runs the same on a clean runner — installing the plugin from the real Packstub registry — on every push, weekly, and on demand, so a new Laravel, Filament, or plugin release that breaks a fresh install shows up here first. To run it in a fork, add a `COMPOSER_AUTH` repository secret:

```json
{"http-basic":{"packstub.dev":{"username":"pkg_xxxxxxxxxxxxxxxx","password":"your-token-secret"}}}
```

## Going further

- **MySQL / PostgreSQL** — set `DB_CONNECTION` and give the DB user `CREATE DATABASE`; tenant databases are named `tenant{uuid}`.
- **Path identification** (`example.com/admin/teams/acme`) — `'identification' => 'path'` in `config/packstub-tenancy.php`; no code changes.
- **Custom domains, database pools, resource syncing** — see the [plugin docs](https://packstub.dev/docs/filament-tenancy).

## Deploy on Laravel Cloud

The hosted demo runs on [Laravel Cloud](https://cloud.laravel.com) (Starter plan, scales to zero, resets daily). Cloud creates apps from a connected repository, so deploying your own copy is a short dashboard walk-through:

1. **Fork this repo**, then *New application → existing repository*.
2. **Database** — *Add database → Laravel Serverless Postgres* (SQLite isn't available on Cloud's ephemeral filesystem). Every tenant gets its own database inside that cluster; the injected `DB_*` variables serve as the central connection and the template for tenant databases.
3. **Queue** — *Add compute → Managed queue* (Flex, 256 MiB, 0–1 workers). Provisioning runs here.
4. **Environment variables** — on top of the injected `APP_KEY`, `DB_*`, `QUEUE_CONNECTION=cloud`:
   ```ini
   APP_NAME="Packstub Tenancy Demo"
   APP_URL=https://tenancy-demo.example.com
   DEMO_LOGIN_PREFILL=true
   # DEMO_RESET_SCHEDULE=true   # public demo only: wipes ALL tenants daily (needs the scheduler)
   PACKSTUB_USER=pkg_xxxxxxxxxxxxxxxx
   PACKSTUB_SECRET=your-token-secret
   ```
   Cloud injects `SESSION_DRIVER=cookie` and `CACHE_STORE=database` by default — change
   `CACHE_STORE` to `file` so ordinary page views never wake Serverless Postgres, which is
   billed per awake hour. The file cache is per-instance and wiped on deploy — fine for a demo
   that re-seeds anyway.
5. **Commands** — *Settings → Deployments* ([Cloud docs on private packages](https://laravel.com/cloud/docs/environments#private-composer-packages)):
   ```shell
   # Build
   composer config http-basic.packstub.dev "$PACKSTUB_USER" "$PACKSTUB_SECRET"
   composer install --no-dev --optimize-autoloader
   php artisan optimize

   # Deploy
   php artisan migrate --force && php artisan db:seed --force
   ```
   The seeder is idempotent — every deploy re-asserts the demo account and both tenants.
6. **Domain** — *Network → Add domain*: **wildcard** on, **no redirect**. Add every record Cloud shows (the `_cf-custom-hostname` TXT, the permanent `_acme-challenge` CNAME that renews the wildcard certificate, and the origin CNAMEs for root and `*.`) as **DNS-only** — Cloud fronts the app with its own edge, so proxying breaks certificate issuance.

Running elsewhere? Any host with wildcard TLS, a database user allowed to `CREATE DATABASE`, and a queue worker will do — see the [production guide](https://packstub.dev/docs/filament-tenancy/production).

## License

The demo app is MIT ([LICENSE.md](LICENSE.md)). `packstub/filament-tenancy` is commercial software licensed separately by [Packstub](https://packstub.dev) — questions to [support@packstub.dev](mailto:support@packstub.dev).
