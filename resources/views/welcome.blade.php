@php
    $central = config('packstub-tenancy.central_domain');
    $scheme = str(config('app.url'))->before('://')->toString() ?: 'http';
    $port = parse_url((string) config('app.url'), PHP_URL_PORT);
    $host = $port ? "{$central}:{$port}" : $central;
    $centralUrl = "{$scheme}://{$host}";
    $tenantUrl = fn (string $slug) => "{$scheme}://{$slug}.{$host}/admin";
    $prefill = filter_var(env('DEMO_LOGIN_PREFILL', false), FILTER_VALIDATE_BOOL);
    $resets = filter_var(env('DEMO_RESET_SCHEDULE', false), FILTER_VALIDATE_BOOL);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} — database-per-tenant Filament, live</title>
    <meta name="description" content="A live Filament 5 app: one login, a workspace per tenant on its own subdomain, every tenant on its own database — powered by Packstub Tenancy and stancl/tenancy v4.">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700|geist-mono:400,500" rel="stylesheet">
    <style>
        :root {
            --paper: #fcfcfb; --surface: #fff; --ink: #17191e; --muted: #5d6167; --faint: #8b9096;
            --hairline: #e7e8e5; --edge: #dddeda; --accent: oklch(0.55 0.15 167); --accent-deep: oklch(0.42 0.128 167);
            --accent-tint: oklch(0.55 0.15 167 / 0.1);
            --sans: "Instrument Sans", ui-sans-serif, system-ui, sans-serif; --mono: "Geist Mono", ui-monospace, SFMono-Regular, Menlo, monospace;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: var(--paper); color: var(--ink); font-family: var(--sans); font-size: 16px; line-height: 1.6; -webkit-font-smoothing: antialiased; }
        a { color: inherit; }
        code { font-family: var(--mono); font-size: 0.85em; background: #eef0ec; padding: 0.1em 0.4em; border-radius: 4px; }
        .wrap { max-width: 1040px; margin: 0 auto; padding: 0 24px; }
        header { border-bottom: 1px solid var(--hairline); background: var(--surface); }
        header .wrap { display: flex; align-items: center; justify-content: space-between; height: 64px; gap: 16px; }
        .brand { font-weight: 700; letter-spacing: -0.02em; text-decoration: none; font-size: 18px; }
        .brand span { color: var(--accent); }
        nav { display: flex; gap: 20px; font-size: 14px; }
        nav a { color: var(--muted); text-decoration: none; }
        nav a:hover { color: var(--ink); }
        .eyebrow { font-family: var(--mono); font-size: 11px; letter-spacing: 0.1em; text-transform: uppercase; color: var(--accent-deep); }
        .hero { padding: 72px 0 40px; }
        h1 { font-size: clamp(32px, 5vw, 48px); line-height: 1.1; letter-spacing: -0.03em; margin: 14px 0 18px; max-width: 760px; }
        .lead { font-size: 18px; color: var(--muted); max-width: 640px; }
        .cta { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 28px; align-items: center; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 20px; border-radius: 10px; font-weight: 600; font-size: 14px; text-decoration: none; border: 1px solid var(--ink); transition: background .15s, border-color .15s; }
        .btn-primary { background: var(--ink); color: #fff; }
        .btn-primary:hover { background: #2a2d34; border-color: #2a2d34; }
        .btn-secondary { background: var(--surface); border-color: var(--edge); }
        .btn-secondary:hover { border-color: var(--ink); }
        .creds { font-family: var(--mono); font-size: 12.5px; color: var(--muted); }
        .creds b { color: var(--ink); font-weight: 500; }
        .grid { display: grid; gap: 14px; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); margin-top: 40px; }
        .card { background: var(--surface); border: 1px solid var(--hairline); border-radius: 16px; padding: 22px; box-shadow: 0 1px 2px rgba(23,25,30,.04); display: flex; flex-direction: column; gap: 8px; }
        .card .eyebrow { color: var(--faint); }
        .card h3 { font-size: 17px; letter-spacing: -0.01em; }
        .card p { color: var(--muted); font-size: 14.5px; flex: 1; }
        .card a.link { font-family: var(--mono); font-size: 12.5px; text-decoration: none; color: var(--accent-deep); word-break: break-all; }
        .card a.link:hover { text-decoration: underline; }
        .card.db { border-left: 3px solid var(--accent); }
        .section { padding: 56px 0 0; }
        h2 { font-size: 24px; letter-spacing: -0.02em; margin-bottom: 8px; }
        .sub { color: var(--muted); max-width: 640px; }
        .steps { counter-reset: s; margin-top: 24px; border: 1px solid var(--hairline); border-radius: 16px; background: var(--surface); overflow: hidden; }
        .step { display: grid; grid-template-columns: 28px 1fr; gap: 14px; align-items: start; padding: 18px 22px; border-top: 1px solid var(--hairline); font-size: 15px; }
        .step:first-child { border-top: 0; }
        .step::before { counter-increment: s; content: counter(s); font-family: var(--mono); font-size: 12px; color: var(--accent-deep); background: var(--accent-tint); width: 28px; height: 28px; border-radius: 50%; display: grid; place-items: center; }
        .step b { font-weight: 600; }
        .step span { color: var(--muted); }
        pre { background: var(--ink); color: #edeef0; font-family: var(--mono); font-size: 13px; line-height: 1.75; padding: 18px 22px; border-radius: 16px; overflow-x: auto; margin-top: 20px; }
        pre .c { color: #8b9096; }
        footer.wrap { margin-top: 72px; border-top: 1px solid var(--hairline); padding: 28px 0 40px; font-size: 13.5px; color: var(--muted); display: flex; flex-wrap: wrap; gap: 8px 24px; justify-content: space-between; }
        footer a { color: var(--muted); }
        .note { margin-top: 14px; font-size: 13.5px; color: var(--faint); }
        @media (max-width: 640px) { nav { display: none; } .hero { padding-top: 48px; } }
    </style>
</head>
<body>
<header>
    <div class="wrap">
        <a class="brand" href="/">Packstub Tenancy <span>demo</span></a>
        <nav>
            <a href="https://packstub.dev/docs/filament-tenancy">Docs</a>
            <a href="https://github.com/packstub/filament-tenancy-demo">Source</a>
            <a href="{{ $centralUrl }}/central">Operator panel</a>
            <a href="https://packstub.dev/tenancy">Get the plugin</a>
        </nav>
    </div>
</header>

<main class="wrap">
    <section class="hero">
        <p class="eyebrow">Laravel 13 · Filament 5 · stancl/tenancy v4</p>
        <h1>One login. A workspace per tenant. Every tenant on its own database.</h1>
        <p class="lead">A stock Filament panel with <a href="https://packstub.dev/tenancy">Packstub Tenancy</a> plugged in. Sign in once, switch between two provisioned tenants, and create a third through the onboarding wizard — it gets its own database in a few seconds.</p>
        <div class="cta">
            <a class="btn btn-primary" href="/admin">Open the demo →</a>
            <a class="btn btn-secondary" href="https://github.com/packstub/filament-tenancy-demo">Read the source</a>
            @if ($prefill)
                <span class="creds">Login is pre-filled · <b>demo@example.com</b> / <b>packstub-tenancy-demo</b></span>
            @endif
        </div>
        @if ($resets)
            <p class="note">Shared playground — everything resets every hour, so create, rename, and delete freely.</p>
        @endif
    </section>

    <div class="grid">
        <div class="card">
            <p class="eyebrow">Central</p>
            <h3>Sign in &amp; switch</h3>
            <p>Login lives on the central domain only. The sidebar switcher moves you between workspaces; <code>/admin/new</code> opens the wizard.</p>
            <a class="link" href="/admin">{{ $host }}/admin</a>
        </div>
        <div class="card db">
            <p class="eyebrow">Tenant · DB 1</p>
            <h3>Acme Inc.</h3>
            <p>Its own subdomain, its own database. The Projects resource shows only Acme's rows — no <code>tenant_id</code> anywhere.</p>
            <a class="link" href="{{ $tenantUrl('acme') }}">acme.{{ $host }}/admin</a>
        </div>
        <div class="card db">
            <p class="eyebrow">Tenant · DB 2</p>
            <h3>Globex Corp.</h3>
            <p>Same code, same panel, a different database. Compare the Projects lists side by side.</p>
            <a class="link" href="{{ $tenantUrl('globex') }}">globex.{{ $host }}/admin</a>
        </div>
        <div class="card">
            <p class="eyebrow">Operator</p>
            <h3>Back-office panel</h3>
            <p>A second panel without the plugin, pinned to the central host: all tenants, their status and domains, retry provisioning, delete a tenant.</p>
            <a class="link" href="{{ $centralUrl }}/central">{{ $host }}/central</a>
        </div>
    </div>

    <section class="section">
        <h2>Try the whole flow</h2>
        <p class="sub">Three minutes, nothing to install.</p>
        <div class="steps">
            <div class="step"><div><b>Open the demo and sign in.</b> <span>You land in Acme's panel at <code>acme.{{ $host }}</code>.</span></div></div>
            <div class="step"><div><b>Add a project, then switch to Globex.</b> <span>It isn't there — each tenant reads and writes its own database.</span></div></div>
            <div class="step"><div><b>Create a third organization.</b> <span>Switcher → <em>Create organization</em>. The queue creates a database, migrates and seeds it, then redirects you in.</span></div></div>
            <div class="step"><div><b>Rename it, change its slug, delete it.</b> <span>Profile inside the panel; deletion (drops the database) from the operator panel.</span></div></div>
        </div>
    </section>

    <section class="section">
        <h2>All the tenancy wiring, in one line</h2>
        <p class="sub">No stancl middleware in the panel, no tenant scopes in the models.</p>
<pre><span class="c">// app/Providers/Filament/AdminPanelProvider.php</span>
return $panel
    ->id('admin')
    ->path('admin')
    ->login()
    ->plugin(TenancyPlugin::make());</pre>
        <p class="note">The rest of the repo is <code>laravel new</code> + <code>filament:install --panels</code>. Read all of it before you buy: <a href="https://github.com/packstub/filament-tenancy-demo">github.com/packstub/filament-tenancy-demo</a>.</p>
    </section>
</main>

<footer class="wrap">
    <span>Built with <a href="https://packstub.dev/tenancy">Packstub Tenancy</a> · <a href="https://packstub.dev/docs/filament-tenancy">Documentation</a></span>
    <span><a href="https://packstub.dev/customer">Customer portal</a> · <a href="mailto:support@packstub.dev">support@packstub.dev</a></span>
</footer>
</body>
</html>
