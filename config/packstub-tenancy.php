<?php

use Database\Seeders\TenantSeeder;
use Packstub\Tenancy\Filament\Billing\NullBillingProvider;
use Packstub\Tenancy\Filament\Pages\EditPackstubTenantProfile;
use Packstub\Tenancy\Filament\Pages\OnboardTenant;
use Packstub\Tenancy\Models\Tenant;

return [
    'tenant_model' => Tenant::class,

    /*
    | Whether the package's own migrations run automatically (zero-config default).
    |
    | Set to false when you need to adapt the schema to your app — e.g. extra
    | columns on tenants, different cascade rules, or an integer tenant key.
    | Then publish the migrations and edit your copies:
    |
    |   php artisan vendor:publish --tag=packstub-tenancy-migrations
    |
    | Never leave this true after publishing: both copies would register as
    | pending migrations and `migrate` would try to create each table twice.
    | Note: with auto-run disabled you own the schema — future plugin updates
    | that change it will ship upgrade notes instead of applying automatically.
    */
    'run_migrations' => true,

    /*
    | How tenant DATA is separated — the master switch several keys below
    | follow.
    |
    | - 'dedicated' (default): one database per tenant, provisioned on signup
    |   by the queued CreateDatabase/MigrateDatabase pipeline. Isolation is the
    |   connection switch itself; tenant tables carry no tenant_id column.
    |   With the database pool enabled, pool members are database SERVERS and
    |   each new tenant's database is created on one of them.
    |
    | - 'shared': tenants share pre-provisioned databases and are isolated by
    |   a tenant_id relationship scope (scope_resources_to_tenant defaults to
    |   true). No per-tenant database is created — tenants are ready almost
    |   instantly. Rows live in the central database by default; with the
    |   database pool enabled, pool members are shared SHARD databases (each an
    |   ordinary connection whose `database` already exists and is migrated)
    |   and new tenants are load-balanced across them.
    |
    | Per-tenant override for hybrid fleets: create a tenant with an
    | `isolation_mode` attribute ('dedicated' | 'shared') to give it the other
    | model — e.g. a private database for an enterprise/data-residency tenant
    | inside a shared app. See docs/database-strategies.md.
    */
    'database_strategy' => 'dedicated',

    'central_domain' => env('TENANCY_CENTRAL_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),

    /*
    | How a request is matched to a tenant. NO stancl middleware to wire in
    | either mode — the plugin identifies tenants itself.
    | - 'subdomain': full-host domain routing (`{tenant:route_host}`): the
    |                request host is matched against VERIFIED domain rows,
    |                covering `{slug}.central_domain` subdomains and (when
    |                enabled) custom domains.
    | - 'path':      panel is wired with `->tenantRoutePrefix(route_prefix)`;
    |                tenants live at /{panel}/{route_prefix}/{slug}.
    */
    'identification' => 'subdomain',

    'route_prefix' => 'teams',

    /*
    | Seeder run inside each freshly provisioned tenant database.
    |
    | Tenant seeding is OPT-IN. Leave this null to skip the seed step entirely
    | (the safe default): Stancl's global `tenancy.seeder_parameters` points at
    | the CENTRAL Database\Seeders\DatabaseSeeder, which references central-only
    | tables/models and would fail — or worse, silently misbehave — against a
    | fresh tenant DB. Set this to a tenant-safe seeder class to enable seeding;
    | the plugin runs it with --force so queued (non-interactive) workers don't
    | prompt in production.
    */
    'seeder' => TenantSeeder::class,

    /*
    | The relationship name on tenant-aware resources that points to the tenant.
    | Leave null to use Filament's default (camelCased basename of the tenant model).
    | Mostly relevant when resources are scoped to the tenant (shared strategy).
    */
    'ownership_relationship' => null,

    /*
    | Whether Filament should automatically scope resources to the current
    | tenant through the ownership relationship (whereBelongsTo($tenant)).
    |
    | Default null = follow database_strategy: false under 'dedicated', where
    | Stancl's DatabaseTenancyBootstrapper already isolates each tenant on its
    | own connection and tenant-DB models have no tenant relationship to scope
    | by; true under 'shared', where the tenant_id relationship scope IS the
    | isolation. Set an explicit bool to override either way.
    */
    'scope_resources_to_tenant' => null,

    'menu' => [
        'enabled' => true,
        'switcher_enabled' => true,
        'searchable' => true,
        // Array of \Filament\Actions\Action | \Filament\Navigation\MenuItem | Closure
        // forwarded verbatim to Panel::tenantMenuItems().
        'items' => [],
    ],

    'onboarding' => [
        'enabled' => true,
        'page' => OnboardTenant::class,
    ],

    'profile' => [
        'enabled' => false,
        'page' => EditPackstubTenantProfile::class,
    ],

    'billing' => [
        'enabled' => false,
        'provider' => NullBillingProvider::class,
        'route_slug' => 'billing',
        'required' => false,
    ],

    'middleware' => [
        // Extra persistent middleware appended after EnsureTenantIsReady.
        'extra' => [],
    ],

    /*
    | Horizontal scaling: load-balance new tenant databases across multiple
    | database servers.
    |
    | Each entry in `connections` is the name of an ordinary connection from
    | config/database.php that points at a tenant database server — its host,
    | port, admin credentials (CREATE DATABASE privilege), and an existing
    | maintenance database on that server. When a tenant is created, the plugin
    | picks a member using `strategy` and persists it as the tenant's
    | `tenancy_db_connection`; Stancl then provisions, migrates, connects, and
    | deletes that tenant on its own server — no further routing needed.
    |
    | Strategies:
    |   - 'least-tenants' (default): member with the fewest tenants; self-heals
    |     after deletions and after a new server joins the pool.
    |   - 'round-robin': rotate by total pooled-tenant count.
    |   - 'weighted': fewest tenants per weight unit — give bigger servers a
    |     larger `weights` entry (missing weights count as 1).
    |
    | To scale out: add a connection in config/database.php, append its name
    | here, deploy (workers too — provisioning runs on the queue). Tenants
    | with an explicit `tenancy_db_connection` (e.g. data-residency pins) are
    | never reassigned.
    |
    | Under database_strategy 'shared' the pool means something different:
    | each member is a pre-provisioned shared SHARD database (its `database`
    | key must name an existing, migrated database), and new shared tenants
    | are load-balanced across shards instead of getting their own database.
    | See docs/database-strategies.md.
    */
    'database_pool' => [
        'enabled' => false,
        'connections' => [],
        'strategy' => 'least-tenants',
        'weights' => [],
    ],

    /*
    | Shown on the provisioning page when a tenant's setup fails, so users
    | land on YOUR support channel. Any URL (https://…, mailto:…).
    */
    'support_url' => null,

    /*
    | In subdomain mode the plugin's global IdentifyTenantHost middleware sets
    | the session cookie domain per request host: `.central-domain` on the
    | central domain and tenant subdomains (one shared session), host-only on
    | verified custom domains. That removes the SESSION_DOMAIN env wiring from
    | installs. Set to false to manage `session.domain` yourself.
    */
    'manage_session_cookie' => true,

    /*
    | Custom domains per tenant (subdomain mode only).
    |
    | When enabled, tenants can attach their own domains (app.acme-corp.com).
    | Each domain must pass DNS TXT verification before it identifies the
    | tenant. Authentication on custom domains uses a central-login handoff:
    | a single-use, short-TTL code minted on the central domain and exchanged
    | on the custom domain for a host-only session cookie.
    |
    |   - interstitial: false (default) establishes the session silently;
    |     true shows a "Continue as …" confirmation on the custom domain.
    |   - handoff_ttl: seconds a handoff code stays redeemable (hard max 120).
    |   - handoff_path: path of the landing/exchange endpoint on tenant hosts.
    |   - verification_prefix: DNS TXT record name prefix; the record
    |     `{prefix}.{domain}` must contain the token shown in the Domains UI.
    */
    'custom_domains' => [
        'enabled' => false,
        'interstitial' => false,
        'handoff_ttl' => 60,
        'handoff_path' => 'auth/handoff',
        'verification_prefix' => '_packstub-verify',
    ],

    /*
    | Cross-database resource syncing (powered by Stancl\Tenancy\ResourceSyncing).
    |
    | When enabled, models marked with the SyncsToTenants and IsTenantResource
    | traits keep their `synced_attributes` mirrored between the central DB and
    | every attached tenant DB.
    |
    | The plugin equivalents (preferred) are:
    |   ->syncResources([CentralUser::class => TenantUser::class])
    |   ->queueResourceSync()
    |   ->cleanupOrphanedResourceMappings()
    */
    'resource_syncing' => [
        'enabled' => false,
        'pairs' => [],
        'queue' => false,
        // false | true (default tenant_resources pivot) | ['table' => 'tenant_id_column', ...]
        'cleanup' => false,
    ],
];
