<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Kitchen | Acie Fraiche</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700&family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
    <link rel="icon" href="/assets/logo2.png" type="image/png">
    @vite(['resources/js/kitchen.js'])
    <style>
        :root {
            --af-ink: #0f0b05;
            --af-ink-soft: rgba(15, 11, 5, 0.68);
            --af-line: rgba(15, 11, 5, 0.12);
            --af-card: #fffdf8;
            --af-accent: #523700;
            --af-bg: #f7f1e7;
            --af-success: #166534;
            --af-warn: #b45309;
            --af-shadow: 0 12px 28px rgba(0,0,0,0.06);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Manrope', system-ui, -apple-system, sans-serif;
            background:
                radial-gradient(circle at 12% 18%, rgba(204,153,51,0.08), transparent 26%),
                radial-gradient(circle at 82% 10%, rgba(82,55,0,0.06), transparent 22%),
                var(--af-bg);
            color: var(--af-ink);
            min-height: 100vh;
        }
        header.topbar {
            display:flex; justify-content:space-between; align-items:center;
            padding:16px 20px;
            background:#fff;
            border-bottom:1px solid var(--af-line);
            box-shadow: var(--af-shadow);
            position: sticky;
            top:0;
            z-index: 10;
        }
        header .brand { display:flex; align-items:center; gap:12px; }
        header img { width:46px; height:46px; border-radius:14px; background:#fff; padding:6px; border:1px solid var(--af-line); }
        header h1 { margin:0; font-size:20px; color:var(--af-accent); letter-spacing:-0.02em; font-family:'Playfair Display', Georgia, serif; }
        .muted { color: var(--af-ink-soft); font-weight:500; }
        main { padding:22px; max-width:1200px; margin:0 auto 32px; display:grid; gap:14px; }
        .layout { display:block; }
        .card { background:var(--af-card); border:1px solid var(--af-line); border-radius:16px; padding:16px; box-shadow: var(--af-shadow); }
        .pill { border:1px solid var(--af-line); border-radius:999px; padding:7px 10px; font-size:12px; display:inline-flex; align-items:center; gap:6px; background:#fff; white-space:nowrap; }
        .pill.success, .pill.tone-success { background:#ecfdf3; border-color:#bbf7d0; color:var(--af-success); font-weight:700; }
        .pill.warn, .pill.tone-warn { background:#fef3c7; border-color:#fcd34d; color:#92400e; font-weight:700; }
        .pill.tone-active { background:rgba(82,55,0,0.1); color:var(--af-accent); border-color:rgba(82,55,0,0.2); font-weight:700; }
        .pill.tone-neutral { background:#fff; color:rgba(0,0,0,0.7); }
        .pill.tone-note { background:#fff7ed; color:#9a3412; border-color:#fed7aa; }
        .pill.tone-muted { background:#f5f5f5; color:rgba(0,0,0,0.55); border-color:rgba(0,0,0,0.08); }
        .stat-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:12px; margin:6px 0 4px; }
        .stat { border:1px dashed var(--af-line); border-radius:14px; padding:14px; background:#fff; display:flex; flex-direction:column; gap:6px; box-shadow:0 8px 18px rgba(0,0,0,0.04); }
        .stat .small { font-size:13px; color: var(--af-ink-soft); letter-spacing:0.01em; }
        .stat .highlight { color:var(--af-accent); font-weight:700; font-size:20px; }
        .orders { display:grid; gap:14px; grid-template-columns: minmax(0, 1fr); }
        .order {
            border:1px solid var(--af-line);
            border-radius:16px;
            padding:14px;
            background:#fff;
            box-shadow:0 14px 30px rgba(0,0,0,0.06);
            display:grid;
            gap:10px;
            position:relative;
            transition: transform 0.1s ease, box-shadow 0.1s ease;
        }
        .order:hover { transform: translateY(-2px); box-shadow:0 16px 34px rgba(0,0,0,0.08); }
        .order-header { display:flex; justify-content:space-between; align-items:flex-start; gap:10px; flex-wrap:wrap; }
        .order-title { display:flex; align-items:center; gap:10px; font-weight:700; color:var(--af-ink); letter-spacing:-0.01em; }
        .badge { padding:7px 9px; border-radius:10px; font-size:12px; background: rgba(204,153,51,0.14); color:#7a4a00; font-weight:600; }
        .order-meta-row { display:flex; gap:8px; flex-wrap:wrap; align-items:center; font-size:12px; color:var(--af-ink-soft); }
        .items { list-style:none; padding:0; margin:6px 0 0; display:grid; gap:6px; }
        .items li { display:flex; justify-content:space-between; font-weight:600; color:var(--af-ink); }
        .small { font-size:13px; color: var(--af-ink-soft); }
        .highlight { color:var(--af-accent); font-weight:700; }
        .empty { border:1px dashed var(--af-line); border-radius:14px; padding:18px; text-align:center; color:var(--af-ink-soft); background:#fff; box-shadow:0 10px 22px rgba(0,0,0,0.05); }
        .toast { position:fixed; right:18px; bottom:18px; background:var(--af-accent); color:#fff; padding:12px 14px; border-radius:12px; box-shadow:0 18px 36px rgba(0,0,0,0.16); display:none; }
        .controls { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
        button.brand-btn {
            border:1px solid var(--af-accent);
            background:var(--af-accent);
            color:#fff;
            border-radius:12px;
            padding:10px 13px;
            cursor:pointer;
            font-weight:700;
            letter-spacing:-0.01em;
            box-shadow:0 12px 20px rgba(82,55,0,0.16);
            transition: transform 0.08s ease, box-shadow 0.08s ease, background 0.1s ease;
        }
        button.brand-btn:hover { transform: translateY(-1px); box-shadow:0 16px 26px rgba(82,55,0,0.2); }
        button.brand-btn.ghost { background:#fff; color:var(--af-accent); box-shadow:none; }
        .kitchen-actions { margin:4px 0 2px; display:flex; flex-wrap:wrap; gap:8px; }
        .kitchen-actions .brand-btn { padding:9px 11px; font-size:13px; }
        .section-heading { display:flex; justify-content:space-between; align-items:center; gap:10px; margin-bottom:8px; }
        .hero { background:#fff; border-radius:16px; padding:14px; display:flex; align-items:center; justify-content:space-between; gap:12px; border:1px solid var(--af-line); box-shadow: var(--af-shadow); }
        .hero h2 { margin:0; font-size:20px; color:var(--af-ink); letter-spacing:-0.02em; }
        .hero p { margin:6px 0 0; color:var(--af-ink-soft); }
        .divider { height:1px; background:var(--af-line); margin:12px 0; }
        .order-footer { display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap; align-items:center; }
        .order-channel { display:flex; gap:8px; align-items:center; }
        .order-customer { display:flex; align-items:center; gap:8px; color:var(--af-ink-soft); font-weight:600; }
        .soft-card { background:#fff; border:1px dashed var(--af-line); border-radius:14px; padding:12px; }
        .panel-stack { display:grid; gap:10px; }
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
            <img src="/assets/logo2.png" alt="Acie Fraiche">
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
                <h2>Board Overview</h2>
                <p>Live feed from POS and online orders. Keep one clean column and send clear ETAs back to the floor.</p>
            </div>
            <div style="display:flex; gap:8px; align-items:center;">
                <div id="kitchenConnection" class="pill" style="font-weight:700;">Connecting…</div>
            </div>
        </div>

        <div class="card" style="margin-top:4px;">
            <div class="section-heading">
                <h3 style="margin:0;">Board metrics</h3>
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
                <div class="small">Statuses and ETA updates broadcast instantly to POS and admin.</div>
            </div>
        </div>

        <div class="card">
            <div class="section-heading">
                <div>
                    <h2 style="margin:0 0 4px;">Live Tickets</h2>
                    <p class="muted" style="margin:0;">Full-width, responsive list of the newest orders.</p>
                </div>
                <div class="pill tone-neutral">Stream: live feed</div>
            </div>
            <div class="divider"></div>
            <div id="kitchenOrders" class="orders"></div>
            <div id="kitchenEmpty" class="empty" style="display:none;">No orders yet. They will appear here in real time.</div>
        </div>
    </main>
    <div id="kitchenToast" class="toast"></div>
    <script>
        window.initialOrders = @json($initialOrders ?? []);
    </script>
</body>
</html>
