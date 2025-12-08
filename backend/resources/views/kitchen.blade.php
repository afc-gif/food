<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Kitchen | Acie Fraiche</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="/assets/logo.png" type="image/png">
    @vite(['resources/js/kitchen.js'])
    <style>
        :root {
            --af-gold: #cc9933;
            --af-brown: #523700;
            --af-ink: #1b1206;
            --af-cream: #f7f1e7;
            --af-card: #fdf9f0;
            --af-line: rgba(82, 55, 0, 0.16);
            --af-success: #0f5132;
            --af-warn: #b45309;
            --af-ink-soft: rgba(27, 18, 6, 0.78);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Sora", system-ui, -apple-system, sans-serif;
            background:
                radial-gradient(circle at 16% 14%, rgba(204, 153, 51, 0.16), transparent 24%),
                radial-gradient(circle at 80% 12%, rgba(82, 55, 0, 0.12), transparent 22%),
                radial-gradient(180deg, rgba(255,255,255,0.45), rgba(247,241,231,0.95));
            color: var(--af-ink);
            min-height: 100vh;
        }
        header.topbar {
            display:flex; justify-content:space-between; align-items:center;
            padding:16px 22px;
            background:linear-gradient(120deg, rgba(204,153,51,0.18) 0%, rgba(255,255,255,0.7) 100%);
            border-bottom:1px solid rgba(82, 55, 0, 0.08);
            box-shadow:0 14px 28px rgba(0,0,0,0.05);
            position: sticky;
            top:0;
            z-index: 10;
        }
        header .brand { display:flex; align-items:center; gap:12px; }
        header img { width:46px; height:46px; border-radius:14px; background:#fff; padding:6px; border:1px solid var(--af-line); }
        header h1 { margin:0; font-size:20px; color:var(--af-brown); letter-spacing:-0.02em; }
        .muted { color: var(--af-ink-soft); font-weight:500; }
        main { padding:24px; max-width:1360px; margin:0 auto 32px; display:grid; gap:16px; }
        .layout { display:grid; grid-template-columns: minmax(0, 1.4fr) minmax(320px, 0.8fr); gap:16px; align-items:start; }
        .card { background:var(--af-card); border:1px solid var(--af-line); border-radius:18px; padding:18px; box-shadow:0 20px 40px rgba(0,0,0,0.08); }
        .pill { border:1px solid var(--af-line); border-radius:999px; padding:8px 12px; font-size:12px; display:inline-flex; align-items:center; gap:6px; background:#fff; white-space:nowrap; }
        .pill.success { background:rgba(15,81,50,0.1); color:var(--af-success); border-color:rgba(15,81,50,0.25); }
        .pill.warn { background:rgba(180,83,9,0.08); color:var(--af-warn); border-color:rgba(180,83,9,0.18); }
        .pill.tone-success { background:rgba(15,81,50,0.1); color:var(--af-success); border-color:rgba(15,81,50,0.25); }
        .pill.tone-active { background:rgba(82,55,0,0.08); color:var(--af-brown); border-color:rgba(82,55,0,0.28); }
        .pill.tone-neutral { background:#fff; color:rgba(0,0,0,0.7); }
        .pill.tone-warn { background:rgba(180,83,9,0.08); color:var(--af-warn); border-color:rgba(180,83,9,0.18); }
        .pill.tone-note { background:#fff7e6; color:#7a4a00; border-color:rgba(180,83,9,0.24); }
        .pill.tone-muted { background:#f2f2f2; color:rgba(0,0,0,0.55); border-color:rgba(0,0,0,0.08); }
        .stat-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:12px; margin:10px 0 4px; }
        .stat { border:1px dashed var(--af-line); border-radius:14px; padding:14px; background:#fff; display:flex; flex-direction:column; gap:6px; box-shadow:0 8px 20px rgba(0,0,0,0.04); }
        .stat .small { font-size:13px; color: var(--af-ink-soft); letter-spacing:0.01em; }
        .stat .highlight { color:var(--af-brown); font-weight:700; font-size:20px; }
        .orders { display:grid; gap:14px; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); }
        .order {
            border:1px solid var(--af-line);
            border-radius:18px;
            padding:16px;
            background:#fff;
            box-shadow:0 18px 36px rgba(0,0,0,0.06);
            display:grid;
            gap:12px;
            position:relative;
            transition: transform 0.12s ease, box-shadow 0.12s ease;
        }
        .order:hover { transform: translateY(-3px); box-shadow:0 22px 42px rgba(0,0,0,0.08); }
        .order-header { display:flex; justify-content:space-between; align-items:flex-start; gap:10px; flex-wrap:wrap; }
        .order-title { display:flex; align-items:center; gap:10px; font-weight:700; color:var(--af-ink); letter-spacing:-0.01em; }
        .badge { padding:7px 9px; border-radius:10px; font-size:12px; background: rgba(204,153,51,0.12); color:#7a4a00; font-weight:600; }
        .order-meta-row { display:flex; gap:8px; flex-wrap:wrap; align-items:center; font-size:12px; color:var(--af-ink-soft); }
        .items { list-style:none; padding:0; margin:6px 0 0; display:grid; gap:6px; }
        .items li { display:flex; justify-content:space-between; font-weight:500; color:var(--af-ink); }
        .small { font-size:13px; color: var(--af-ink-soft); }
        .highlight { color:var(--af-brown); font-weight:700; }
        .empty { border:1px dashed var(--af-line); border-radius:14px; padding:20px; text-align:center; color:var(--af-ink-soft); background:#fff; box-shadow:0 10px 24px rgba(0,0,0,0.05); }
        .toast { position:fixed; right:18px; bottom:18px; background:#0f0b05; color:#fff; padding:12px 14px; border-radius:12px; box-shadow:0 18px 36px rgba(0,0,0,0.16); display:none; }
        .controls { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
        button.brand-btn {
            border:1px solid var(--af-brown);
            background:var(--af-brown);
            color:#fff;
            border-radius:12px;
            padding:10px 13px;
            cursor:pointer;
            font-weight:700;
            letter-spacing:-0.01em;
            box-shadow:0 12px 20px rgba(82,55,0,0.2);
            transition: transform 0.08s ease, box-shadow 0.08s ease, background 0.1s ease;
        }
        button.brand-btn:hover { transform: translateY(-1px); box-shadow:0 16px 26px rgba(82,55,0,0.25); }
        button.brand-btn.ghost { background:#fff; color:var(--af-brown); box-shadow:none; }
        .kitchen-actions { margin:4px 0 2px; display:flex; flex-wrap:wrap; gap:8px; }
        .kitchen-actions .brand-btn { padding:9px 11px; font-size:13px; }
        .section-heading { display:flex; justify-content:space-between; align-items:center; gap:10px; margin-bottom:10px; }
        .hero { background: linear-gradient(135deg, rgba(204,153,51,0.16), rgba(255,255,255,0.9)); border-radius:20px; padding:18px; display:flex; align-items:center; justify-content:space-between; gap:14px; border:1px solid rgba(204,153,51,0.25); box-shadow:0 18px 36px rgba(0,0,0,0.07); }
        .hero h2 { margin:0; font-size:22px; color:var(--af-ink); letter-spacing:-0.02em; }
        .hero p { margin:6px 0 0; color:var(--af-ink-soft); }
        .divider { height:1px; background:var(--af-line); margin:12px 0; }
        .order-footer { display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap; align-items:center; }
        .order-channel { display:flex; gap:8px; align-items:center; }
        .order-customer { display:flex; align-items:center; gap:8px; color:var(--af-ink-soft); font-weight:600; }
        .soft-card { background:#fff; border:1px dashed var(--af-line); border-radius:14px; padding:12px; }
        .panel-stack { display:grid; gap:12px; }
        @media (max-width: 980px) {
            header.topbar { position:static; border-radius:0; }
            .layout { grid-template-columns: 1fr; }
            .orders { grid-template-columns: 1fr; }
            main { padding:16px; }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="brand">
            <img src="/assets/logo.png" alt="Acie Fraiche">
            <div>
                <h1>Acie Fraiche · Kitchen</h1>
                <div class="muted">Signed in as {{ auth()->user()->name ?? 'Kitchen User' }}</div>
            </div>
        </div>
        <div class="controls">
            <button id="toggleSound" class="brand-btn ghost" type="button">Sound: Off</button>
            <button id="toggleNotify" class="brand-btn ghost" type="button">Browser Alerts: Off</button>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="brand-btn ghost">Logout</button>
            </form>
        </div>
    </header>
    <main>
        <div class="hero">
            <div>
                <h2>Kitchen Control Room</h2>
                <p>Clean stream of tickets from POS and online orders. Prioritize, set ETA, and keep the line moving.</p>
            </div>
            <div style="display:flex; gap:8px; align-items:center;">
                <div id="kitchenConnection" class="pill" style="font-weight:700;">Connecting…</div>
            </div>
        </div>

        <div class="layout">
            <div class="card">
                <div class="section-heading">
                    <div>
                        <h2 style="margin:0 0 4px;">Live Tickets</h2>
                        <p class="muted" style="margin:0;">Clean, stacked orders ready for action.</p>
                    </div>
                    <div class="pill tone-neutral">Stream: live feed</div>
                </div>
                <div class="divider"></div>
                <div id="kitchenOrders" class="orders"></div>
                <div id="kitchenEmpty" class="empty" style="display:none;">No orders yet. They will appear here in real time.</div>
            </div>

            <div class="panel-stack">
                <div class="card">
                    <div class="section-heading">
                        <h3 style="margin:0;">Board Overview</h3>
                        <span class="pill tone-active">Kitchen radar</span>
                    </div>
                    <div class="stat-grid">
                        <div class="stat">
                            <div class="small">Orders today</div>
                            <div class="highlight" id="kitchenStatCount">0</div>
                        </div>
                        <div class="stat">
                            <div class="small">Last order</div>
                            <div class="highlight" id="kitchenStatLast">—</div>
                        </div>
                        <div class="stat">
                            <div class="small">Total value</div>
                            <div class="highlight" id="kitchenStatTotal">₦0</div>
                        </div>
                    </div>
                    <div class="soft-card" style="margin-top:10px;">
                        <div class="small">Keep tickets flowing — statuses and ETA updates broadcast instantly to staff.</div>
                    </div>
                </div>

                <div class="card">
                    <div class="section-heading">
                        <h3 style="margin:0;">Quick actions</h3>
                        <span class="pill tone-neutral">Safety tools</span>
                    </div>
                    <p class="muted" style="margin:0 0 12px;">If websockets drop, these keep the board fresh.</p>
                    <div class="kitchen-actions">
                        <button class="brand-btn ghost" type="button" onclick="location.reload()">Refresh board</button>
                        <button class="brand-btn ghost" type="button" onclick="document.getElementById('kitchenOrders')?.scrollIntoView({behavior:'smooth'})">Jump to orders</button>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <div id="kitchenToast" class="toast"></div>
    <script>
        window.initialOrders = @json($initialOrders ?? []);
    </script>
</body>
</html>
