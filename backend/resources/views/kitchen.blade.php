<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Kitchen | Acie Fraiche</title>
    <link rel="icon" href="/assets/logo.png" type="image/png">
    @vite(['resources/js/kitchen.js'])
    <style>
        :root {
            --af-gold: #cc9933;
            --af-brown: #523700;
            --af-ink: #1b1206;
            --af-cream: #f7f1e7;
            --af-card: #fbf7f0;
            --af-line: rgba(82, 55, 0, 0.15);
            --af-success: #0f5132;
            --af-warn: #b45309;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Manrope", system-ui, -apple-system, sans-serif;
            background: radial-gradient(circle at 15% 10%, rgba(204, 153, 51, 0.08), transparent 32%),
                        radial-gradient(circle at 80% 15%, rgba(82, 55, 0, 0.06), transparent 28%),
                        var(--af-cream);
            color: var(--af-ink);
        }
        header {
            display:flex; justify-content:space-between; align-items:center;
            padding:14px 18px; background:linear-gradient(120deg, #f4e3c6 0%, #fefbf5 100%);
            border-bottom:1px solid var(--af-line);
        }
        header .brand { display:flex; align-items:center; gap:10px; }
        header img { width:44px; height:44px; border-radius:12px; background:#fff; padding:6px; border:1px solid var(--af-line); }
        header h1 { margin:0; font-size:18px; color:var(--af-brown); }
        .muted { color: rgba(0,0,0,0.65); }
        main { padding:18px; max-width:1100px; margin:0 auto 24px; }
        .card { background:var(--af-card); border:1px solid var(--af-line); border-radius:16px; padding:16px; box-shadow:0 14px 32px rgba(0,0,0,0.06); }
        .pill { border:1px solid var(--af-line); border-radius:999px; padding:8px 12px; font-size:12px; display:inline-flex; align-items:center; gap:6px; background:#fff; }
        .pill.success { background:rgba(15,81,50,0.1); color:var(--af-success); border-color:rgba(15,81,50,0.25); }
        .pill.warn { background:rgba(180,83,9,0.08); color:var(--af-warn); border-color:rgba(180,83,9,0.18); }
        .pill.tone-success { background:rgba(15,81,50,0.1); color:var(--af-success); border-color:rgba(15,81,50,0.25); }
        .pill.tone-active { background:rgba(82,55,0,0.08); color:var(--af-brown); border-color:rgba(82,55,0,0.28); }
        .pill.tone-neutral { background:#fff; color:rgba(0,0,0,0.7); }
        .pill.tone-warn { background:rgba(180,83,9,0.08); color:var(--af-warn); border-color:rgba(180,83,9,0.18); }
        .pill.tone-note { background:#fff7e6; color:#7a4a00; border-color:rgba(180,83,9,0.24); }
        .pill.tone-muted { background:#f2f2f2; color:rgba(0,0,0,0.55); border-color:rgba(0,0,0,0.08); }
        .stat-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:12px; margin:14px 0 6px; }
        .stat { border:1px dashed var(--af-line); border-radius:12px; padding:12px; background:#fff; display:flex; flex-direction:column; gap:4px; }
        .stat .small { font-size:13px; color: rgba(0,0,0,0.6); }
        .stat .highlight { color:var(--af-brown); font-weight:700; font-size:18px; }
        .orders { display:grid; gap:12px; }
        .order { border:1px solid var(--af-line); border-radius:14px; padding:12px; background:#fff; box-shadow:0 10px 20px rgba(0,0,0,0.04); }
        .order-header { display:flex; justify-content:space-between; align-items:center; gap:8px; flex-wrap:wrap; }
        .badge { padding:6px 8px; border-radius:10px; font-size:12px; background: rgba(255,165,0,0.14); color:#7a4a00; }
        .order-meta { display:flex; gap:10px; flex-wrap:wrap; align-items:center; font-size:12px; color:rgba(0,0,0,0.65); }
        .items { list-style:none; padding:0; margin:8px 0 0; display:grid; gap:6px; }
        .items li { display:flex; justify-content:space-between; }
        .small { font-size:13px; color: rgba(0,0,0,0.65); }
        .highlight { color:var(--af-brown); font-weight:700; }
        .empty { border:1px dashed var(--af-line); border-radius:12px; padding:16px; text-align:center; color:rgba(0,0,0,0.6); background:#fff; }
        .toast { position:fixed; right:18px; bottom:18px; background:#0f0b05; color:#fff; padding:12px 14px; border-radius:12px; box-shadow:0 18px 36px rgba(0,0,0,0.16); display:none; }
        .controls { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
        button.brand-btn { border:1px solid var(--af-brown); background:var(--af-brown); color:#fff; border-radius:10px; padding:10px 12px; cursor:pointer; font-weight:600; }
        button.brand-btn.ghost { background:#fff; color:var(--af-brown); }
        .kitchen-actions { margin:10px 0; }
        .kitchen-actions .brand-btn { padding:8px 10px; font-size:13px; }
    </style>
</head>
<body>
    <header>
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
        <div class="card">
            <div class="board-header" style="padding:0 0 10px 0;">
                <div>
                    <h2 style="margin-top:0; margin-bottom:6px;">Kitchen board</h2>
                    <p class="muted" style="margin:0;">Live feed from POS and online orders.</p>
                </div>
                <div id="kitchenConnection" class="pill">Connecting…</div>
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
