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
            --paper: #fafaf8; --surface: #fff; --ink: #16181d; --muted: #5d6167; --faint: #8b9096;
            --hairline: #e8e9e5; --edge: #dcded9;
            --accent: oklch(0.55 0.15 167); --accent-deep: oklch(0.42 0.128 167);
            --accent-tint: oklch(0.55 0.15 167 / 0.09); --accent-glow: oklch(0.72 0.14 167 / 0.22);
            --sans: "Instrument Sans", ui-sans-serif, system-ui, sans-serif;
            --mono: "Geist Mono", ui-monospace, SFMono-Regular, Menlo, monospace;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body { background: var(--paper); color: var(--ink); font-family: var(--sans); font-size: 16px; line-height: 1.6; -webkit-font-smoothing: antialiased; overflow-x: hidden; }
        a { color: inherit; }
        code { font-family: var(--mono); font-size: 0.85em; background: #eef0ec; padding: 0.1em 0.4em; border-radius: 4px; }
        .wrap { max-width: 1080px; margin: 0 auto; padding: 0 24px; }

        /* ---------- reveal-on-scroll ---------- */
        [data-reveal] { opacity: 0; transform: translateY(18px); transition: opacity .7s cubic-bezier(.2,.6,.2,1), transform .7s cubic-bezier(.2,.6,.2,1); transition-delay: var(--d, 0s); }
        [data-reveal].in { opacity: 1; transform: none; }
        @media (prefers-reduced-motion: reduce) {
            [data-reveal] { opacity: 1; transform: none; transition: none; }
            *, *::before, *::after { animation-duration: .01ms !important; animation-iteration-count: 1 !important; }
        }

        /* ---------- header ---------- */
        header { position: sticky; top: 0; z-index: 10; background: color-mix(in srgb, var(--paper) 80%, transparent); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border-bottom: 1px solid var(--hairline); }
        header .wrap { display: flex; align-items: center; justify-content: space-between; height: 60px; gap: 16px; }
        .brand { font-weight: 700; letter-spacing: -0.02em; text-decoration: none; font-size: 17px; display: flex; align-items: center; gap: 9px; }
        .brand .dot { width: 9px; height: 9px; border-radius: 50%; background: var(--accent); box-shadow: 0 0 0 4px var(--accent-tint); animation: breathe 3.2s ease-in-out infinite; }
        @keyframes breathe { 0%,100% { box-shadow: 0 0 0 3px var(--accent-tint); } 50% { box-shadow: 0 0 0 7px var(--accent-tint); } }
        .brand span { color: var(--accent-deep); }
        nav { display: flex; gap: 4px; font-size: 14px; align-items: center; }
        nav a { color: var(--muted); text-decoration: none; padding: 7px 11px; border-radius: 8px; transition: color .15s, background .15s; }
        nav a:hover { color: var(--ink); background: #eef0ec; }
        nav a.pill { color: #fff; background: var(--ink); margin-left: 6px; }
        nav a.pill:hover { background: #2a2d34; }

        /* ---------- hero ---------- */
        .hero { position: relative; padding: 84px 0 64px; }
        .hero::before { content: ""; position: absolute; inset: 0 -40vw 0; background:
            radial-gradient(46rem 26rem at 78% 4%, var(--accent-glow), transparent 60%),
            radial-gradient(34rem 22rem at 12% 110%, oklch(0.72 0.14 167 / 0.12), transparent 60%);
            pointer-events: none; }
        .hero::after { content: ""; position: absolute; inset: 0; background-image: radial-gradient(var(--edge) 1px, transparent 1px); background-size: 26px 26px; mask-image: radial-gradient(42rem 30rem at 70% 0%, #000 0%, transparent 70%); opacity: .5; pointer-events: none; }
        .hero .wrap { position: relative; display: grid; grid-template-columns: minmax(0, 1.15fr) minmax(0, .85fr); gap: 56px; align-items: center; }
        .eyebrow { font-family: var(--mono); font-size: 11px; letter-spacing: 0.12em; text-transform: uppercase; color: var(--accent-deep); }
        .chip { display: inline-flex; align-items: center; gap: 8px; border: 1px solid var(--edge); background: var(--surface); border-radius: 999px; padding: 6px 14px; }
        h1 { font-size: clamp(34px, 4.6vw, 52px); line-height: 1.06; letter-spacing: -0.035em; margin: 20px 0 18px; }
        h1 em { font-style: normal; color: var(--accent-deep); position: relative; white-space: nowrap; }
        h1 em::after { content: ""; position: absolute; left: 0; right: 0; bottom: 0.04em; height: 0.14em; background: var(--accent); opacity: .28; border-radius: 3px; transform: scaleX(0); transform-origin: left; animation: underline .9s cubic-bezier(.2,.6,.2,1) .6s forwards; }
        @keyframes underline { to { transform: scaleX(1); } }
        .lead { font-size: 18px; color: var(--muted); max-width: 560px; }
        .cta { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 30px; align-items: center; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 13px 22px; border-radius: 12px; font-weight: 600; font-size: 14.5px; text-decoration: none; border: 1px solid var(--ink); transition: transform .18s cubic-bezier(.2,.6,.2,1), box-shadow .18s, background .15s, border-color .15s; }
        .btn:active { transform: scale(.98); }
        .btn-primary { background: var(--ink); color: #fff; box-shadow: 0 1px 2px rgba(22,24,29,.2); }
        .btn-primary:hover { background: #2a2d34; border-color: #2a2d34; transform: translateY(-1px); box-shadow: 0 8px 20px -8px rgba(22,24,29,.45); }
        .btn-primary .arr { transition: transform .18s; }
        .btn-primary:hover .arr { transform: translateX(3px); }
        .btn-secondary { background: var(--surface); border-color: var(--edge); }
        .btn-secondary:hover { border-color: var(--ink); transform: translateY(-1px); }
        .creds { font-family: var(--mono); font-size: 12.5px; color: var(--muted); background: var(--surface); border: 1px dashed var(--edge); border-radius: 10px; padding: 8px 12px; }
        .creds b { color: var(--accent-deep); font-weight: 500; }
        .note { margin-top: 16px; font-size: 13.5px; color: var(--faint); display: flex; align-items: center; gap: 8px; }
        .note::before { content: "↻"; color: var(--accent-deep); }

        /* ---------- hero diagram ---------- */
        .diagram { display: block; width: 100%; max-width: 420px; margin: 0 auto; overflow: visible; }
        .diagram .box { fill: var(--surface); stroke: var(--edge); stroke-width: 1.2; }
        .diagram .box-top { filter: drop-shadow(0 6px 14px rgba(22,24,29,.08)); }
        .diagram .flow { fill: none; stroke: var(--accent); stroke-width: 1.6; stroke-linecap: round; stroke-dasharray: 3 7; opacity: .8; animation: flow 1.6s linear infinite; }
        @keyframes flow { to { stroke-dashoffset: -10; } }
        .diagram .t { font-family: var(--sans); font-size: 12.5px; font-weight: 600; fill: var(--ink); letter-spacing: -0.01em; }
        .diagram .m { font-family: var(--mono); font-size: 8.5px; fill: var(--faint); letter-spacing: .02em; }
        .diagram .db { font-family: var(--mono); font-size: 8.5px; fill: var(--accent-deep); }
        .diagram .cyl { fill: var(--accent-tint); stroke: var(--accent); stroke-width: 1.1; }
        .diagram .pulse { fill: var(--accent); animation: pulse 2.4s ease-in-out infinite; transform-origin: center; transform-box: fill-box; }
        @keyframes pulse { 0%,100% { opacity: .5; transform: scale(1); } 50% { opacity: 1; transform: scale(1.5); } }
        .diagram .ghost { stroke-dasharray: 4 4; animation: ghostin 5s ease-in-out infinite; }
        .diagram .ghost-label { animation: ghostin 5s ease-in-out infinite; }
        @keyframes ghostin { 0%, 30% { opacity: .35; } 55%, 80% { opacity: 1; } 100% { opacity: .35; } }
        .float { animation: float 7s ease-in-out infinite; }
        @keyframes float { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }

        /* ---------- cards ---------- */
        .grid { display: grid; gap: 16px; grid-template-columns: repeat(auto-fit, minmax(235px, 1fr)); margin-top: 8px; }
        .card { position: relative; background: var(--surface); border: 1px solid var(--hairline); border-radius: 18px; padding: 24px; box-shadow: 0 1px 2px rgba(23,25,30,.04); display: flex; flex-direction: column; gap: 9px; text-decoration: none; overflow: hidden; transition: transform .25s cubic-bezier(.2,.6,.2,1), box-shadow .25s, border-color .25s; }
        .card::before { content: ""; position: absolute; top: 0; left: 24px; right: 24px; height: 2px; border-radius: 2px; background: linear-gradient(90deg, var(--accent), transparent); opacity: 0; transition: opacity .25s; }
        .card:hover { transform: translateY(-4px); border-color: var(--edge); box-shadow: 0 16px 32px -16px rgba(23,25,30,.16); }
        .card:hover::before { opacity: 1; }
        .card .eyebrow { color: var(--faint); display: flex; align-items: center; gap: 7px; }
        .card.db .eyebrow { color: var(--accent-deep); }
        .card.db .eyebrow::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: var(--accent); }
        .card h3 { font-size: 17px; letter-spacing: -0.01em; }
        .card p:not(.eyebrow) { color: var(--muted); font-size: 14.5px; flex: 1; }
        .card .link { font-family: var(--mono); font-size: 12.5px; color: var(--accent-deep); overflow-wrap: anywhere; }
        .card:hover .link { text-decoration: underline; }

        /* ---------- sections ---------- */
        .section { padding: 84px 0 0; }
        h2 { font-size: clamp(24px, 3vw, 30px); letter-spacing: -0.025em; margin: 14px 0 10px; }
        .sub { color: var(--muted); max-width: 620px; }

        /* ---------- steps ---------- */
        .steps { margin-top: 36px; position: relative; }
        .steps::before { content: ""; position: absolute; left: 17px; top: 22px; bottom: 22px; width: 2px; background: linear-gradient(var(--edge) 60%, transparent); }
        .step { position: relative; display: grid; grid-template-columns: 36px 1fr; gap: 18px; align-items: start; padding: 14px 0; font-size: 15.5px; }
        .step .n { position: relative; z-index: 1; font-family: var(--mono); font-size: 12px; color: var(--accent-deep); background: var(--paper); border: 1.5px solid var(--accent); width: 36px; height: 36px; border-radius: 50%; display: grid; place-items: center; box-shadow: 0 0 0 5px var(--paper); }
        .step b { font-weight: 600; }
        .step span { color: var(--muted); }
        .step div { padding-top: 5px; }

        /* ---------- code ---------- */
        .window { margin-top: 28px; border-radius: 16px; background: #101216; box-shadow: 0 24px 48px -24px rgba(16,18,22,.5), 0 0 0 1px rgba(255,255,255,.04) inset; overflow: hidden; }
        .window .bar { display: flex; align-items: center; gap: 6px; padding: 12px 18px; border-bottom: 1px solid rgba(255,255,255,.06); }
        .window .bar i { width: 10px; height: 10px; border-radius: 50%; background: #2c3038; }
        .window .bar em { margin-left: 10px; font-family: var(--mono); font-style: normal; font-size: 11.5px; color: #6b717a; }
        pre { color: #e6e8ea; font-family: var(--mono); font-size: 13px; line-height: 1.9; padding: 20px 24px; overflow-x: auto; }
        pre .c { color: #6b717a; }
        pre .hl { color: oklch(0.8 0.14 167); }

        footer.wrap { margin-top: 96px; border-top: 1px solid var(--hairline); padding: 30px 0 44px; font-size: 13.5px; color: var(--muted); display: flex; flex-wrap: wrap; gap: 8px 24px; justify-content: space-between; }
        footer a { color: var(--muted); }
        footer a:hover { color: var(--ink); }

        @media (max-width: 860px) {
            .hero { padding: 56px 0 40px; }
            .hero .wrap { grid-template-columns: 1fr; gap: 44px; }
            .diagram { max-width: 360px; }
        }
        @media (max-width: 640px) { nav a:not(.pill) { display: none; } }
    </style>
</head>
<body>
<header>
    <div class="wrap">
        <a class="brand" href="/"><span class="dot"></span>Packstub Tenancy <span>demo</span></a>
        <nav>
            <a href="https://packstub.dev/docs/filament-tenancy">Docs</a>
            <a href="https://github.com/packstub/filament-tenancy-demo">Source</a>
            <a href="{{ $centralUrl }}/central">Operator panel</a>
            <a class="pill" href="https://packstub.dev/tenancy">Get the plugin</a>
        </nav>
    </div>
</header>

<main>
    <section class="hero">
        <div class="wrap">
            <div>
                <p class="eyebrow chip" data-reveal>Laravel 13 · Filament 5 · stancl/tenancy v4</p>
                <h1 data-reveal style="--d:.08s">One login. A workspace per tenant. Every tenant on <em>its own database</em>.</h1>
                <p class="lead" data-reveal style="--d:.16s">A stock Filament panel with <a href="https://packstub.dev/tenancy">Packstub Tenancy</a> plugged in. Sign in once, switch between two provisioned tenants, and create a third through the onboarding wizard — it gets its own database in a few seconds.</p>
                <div class="cta" data-reveal style="--d:.24s">
                    <a class="btn btn-primary" href="/admin">Open the demo <span class="arr">→</span></a>
                    <a class="btn btn-secondary" href="https://github.com/packstub/filament-tenancy-demo">Read the source</a>
                </div>
                @if ($prefill)
                    <div class="cta" data-reveal style="--d:.3s">
                        <span class="creds">Login is pre-filled · <b>demo@example.com</b> / <b>packstub-tenancy-demo</b> · or <b>Sign in as viewer</b></span>
                    </div>
                @endif
                @if ($resets)
                    <p class="note" data-reveal style="--d:.34s">Shared playground — everything resets once a day, so create, rename, and delete freely.</p>
                @endif
            </div>
            <div data-reveal style="--d:.2s">
                <svg class="diagram float" viewBox="0 0 340 292" role="img" aria-label="One central login fanning out to isolated tenant databases">
                    <rect class="box box-top" x="85" y="8" width="170" height="52" rx="12"/>
                    <circle class="pulse" cx="104" cy="34" r="3.5"/>
                    <text class="t" x="118" y="30">Sign in once</text>
                    <text class="m" x="118" y="46">central domain · /admin</text>

                    <path class="flow" d="M170 60 C 170 100, 58 96, 58 136"/>
                    <path class="flow" style="animation-delay:.4s" d="M170 60 L 170 136"/>
                    <path class="flow ghost" style="animation-delay:.8s" d="M170 60 C 170 100, 282 96, 282 136"/>

                    <g>
                        <rect class="box" x="10" y="136" width="96" height="118" rx="12"/>
                        <text class="t" x="24" y="163">Acme</text>
                        <text class="m" x="24" y="178">acme.*</text>
                        <path class="cyl" d="M24 200 a17 6 0 0 1 34 0 v26 a17 6 0 0 1 -34 0 z"/>
                        <ellipse class="cyl" cx="41" cy="200" rx="17" ry="6"/>
                        <text class="db" x="24" y="246">DB 1</text>
                    </g>
                    <g>
                        <rect class="box" x="122" y="136" width="96" height="118" rx="12"/>
                        <text class="t" x="136" y="163">Globex</text>
                        <text class="m" x="136" y="178">globex.*</text>
                        <path class="cyl" d="M136 200 a17 6 0 0 1 34 0 v26 a17 6 0 0 1 -34 0 z"/>
                        <ellipse class="cyl" cx="153" cy="200" rx="17" ry="6"/>
                        <text class="db" x="136" y="246">DB 2</text>
                    </g>
                    <g>
                        <rect class="box ghost" x="234" y="136" width="96" height="118" rx="12"/>
                        <g class="ghost-label">
                            <text class="t" x="248" y="163">Yours</text>
                            <text class="m" x="248" y="178">created live</text>
                            <path class="cyl" d="M248 200 a17 6 0 0 1 34 0 v26 a17 6 0 0 1 -34 0 z"/>
                            <ellipse class="cyl" cx="265" cy="200" rx="17" ry="6"/>
                            <text class="db" x="248" y="246">DB 3 · new</text>
                        </g>
                    </g>

                    <text class="m" x="170" y="284" text-anchor="middle">one deployment · isolated databases</text>
                </svg>
            </div>
        </div>
    </section>

    <section class="wrap">
        <div class="grid">
            <a class="card" href="/admin" data-reveal>
                <p class="eyebrow">Central</p>
                <h3>Sign in &amp; switch</h3>
                <p>Login lives on the central domain only. The sidebar switcher moves you between workspaces; <code>/admin/new</code> opens the wizard.</p>
                <span class="link">{{ $host }}/admin</span>
            </a>
            <a class="card db" href="{{ $tenantUrl('acme') }}" data-reveal style="--d:.08s">
                <p class="eyebrow">Tenant · DB 1</p>
                <h3>Acme Inc.</h3>
                <p>Its own subdomain, its own database. The Projects resource shows only Acme's rows — no <code>tenant_id</code> anywhere.</p>
                <span class="link">acme.{{ $host }}/admin</span>
            </a>
            <a class="card db" href="{{ $tenantUrl('globex') }}" data-reveal style="--d:.16s">
                <p class="eyebrow">Tenant · DB 2</p>
                <h3>Globex Corp.</h3>
                <p>Same code, same panel, a different database. Compare the Projects lists side by side.</p>
                <span class="link">globex.{{ $host }}/admin</span>
            </a>
            <a class="card" href="{{ $centralUrl }}/central" data-reveal style="--d:.24s">
                <p class="eyebrow">Operator</p>
                <h3>Back-office panel</h3>
                <p>A second panel without the plugin, pinned to the central host: all tenants, their status and domains, retry provisioning, delete a tenant.</p>
                <span class="link">{{ $host }}/central</span>
            </a>
        </div>
    </section>

    <section class="section wrap">
        <p class="eyebrow" data-reveal>Guided tour</p>
        <h2 data-reveal style="--d:.06s">Try the whole flow</h2>
        <p class="sub" data-reveal style="--d:.12s">Three minutes, nothing to install.</p>
        <div class="steps">
            <div class="step" data-reveal><span class="n">1</span><div><b>Open the demo and sign in.</b> <span>You land in Acme's panel at <code>acme.{{ $host }}</code>.</span></div></div>
            <div class="step" data-reveal style="--d:.08s"><span class="n">2</span><div><b>Add a project, then switch to Globex.</b> <span>It isn't there — each tenant reads and writes its own database.</span></div></div>
            <div class="step" data-reveal style="--d:.16s"><span class="n">3</span><div><b>Create a third organization.</b> <span>Switcher → <em>Create organization</em>. The queue creates a database, migrates and seeds it, then redirects you in.</span></div></div>
            <div class="step" data-reveal style="--d:.24s"><span class="n">4</span><div><b>Rename it, change its slug, delete it.</b> <span>Profile inside the panel; deletion (drops the database) from the operator panel.</span></div></div>
        </div>
    </section>

    <section class="section wrap">
        <p class="eyebrow" data-reveal>Integration</p>
        <h2 data-reveal style="--d:.06s">All the tenancy wiring, in one line</h2>
        <p class="sub" data-reveal style="--d:.12s">No stancl middleware in the panel, no tenant scopes in the models.</p>
        <div class="window" data-reveal style="--d:.18s">
            <div class="bar"><i></i><i></i><i></i><em>app/Providers/Filament/AdminPanelProvider.php</em></div>
<pre><span class="c">// The whole integration:</span>
return $panel
    ->id('admin')
    ->path('admin')
    ->login()
    -><span class="hl">plugin(TenancyPlugin::make())</span>;</pre>
        </div>
        <p class="note" data-reveal style="--d:.24s">The rest of the repo is <code>laravel new</code> + <code>filament:install --panels</code>. Read all of it before you buy: <a href="https://github.com/packstub/filament-tenancy-demo">github.com/packstub/filament-tenancy-demo</a>.</p>
    </section>
</main>

<footer class="wrap">
    <span>Built with <a href="https://packstub.dev/tenancy">Packstub Tenancy</a> · <a href="https://packstub.dev/docs/filament-tenancy">Documentation</a></span>
    <span><a href="https://packstub.dev/customer">Customer portal</a> · <a href="mailto:support@packstub.dev">support@packstub.dev</a></span>
</footer>

<script>
    (() => {
        const els = document.querySelectorAll('[data-reveal]');
        if (!('IntersectionObserver' in window) || matchMedia('(prefers-reduced-motion: reduce)').matches) {
            els.forEach(el => el.classList.add('in'));
            return;
        }
        const io = new IntersectionObserver(entries => {
            for (const e of entries) if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
        }, { rootMargin: '0px 0px -8% 0px', threshold: 0.1 });
        els.forEach(el => io.observe(el));
    })();
</script>
</body>
</html>
