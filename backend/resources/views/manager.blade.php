<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Manager Panel | Acie Fraiche Cafe</title>
    <meta name="description" content="Acie Fraiche Cafe Manager Panel — Track today's orders, lock website ordering, and manage sold out dishes.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="/assets/logo2.png" type="image/png">
    <style>
        :root {
            --af-gold:    #cc9933;
            --af-brown:   #523700;
            --af-ink:     #0f0b05;
            --af-ink-soft: rgba(15,11,5,0.62);
            --af-line:    rgba(15,11,5,0.11);
            --af-card:    #fffdf8;
            --af-cream:   #f7f1e7;
            --af-success: #166534;
            --af-warn:    #b45309;
            --af-danger:  #991b1b;
            --af-shadow:  0 12px 28px rgba(0,0,0,0.06);
            --radius:     14px;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Manrope', system-ui, sans-serif;
            background:
                radial-gradient(circle at 10% 15%, rgba(204,153,51,0.09), transparent 28%),
                radial-gradient(circle at 85% 8%,  rgba(82,55,0,0.07),  transparent 24%),
                var(--af-cream);
            color: var(--af-ink);
            min-height: 100vh;
        }

        /* ── Topbar ── */
        header.topbar {
            display: flex; align-items: center; justify-content: space-between; gap: 12px;
            padding: 14px 20px; background: #fff;
            border-bottom: 1px solid var(--af-line);
            box-shadow: var(--af-shadow);
            position: sticky; top: 0; z-index: 100;
        }
        .brand { display: flex; align-items: center; gap: 12px; }
        .brand img { width: 44px; height: 44px; border-radius: 12px; border: 1px solid var(--af-line); padding: 4px; }
        .brand h1 { font-size: 19px; font-family: 'Playfair Display', Georgia, serif; color: var(--af-brown); letter-spacing: -0.02em; }
        .brand .sub { font-size: 12px; color: var(--af-ink-soft); margin-top: 1px; }
        .topbar-right { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

        /* ── Store Lock Switch ── */
        .store-lock-btn {
            border: none; border-radius: 999px; padding: 7px 16px; font-weight: 700;
            font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;
            transition: all 0.2s ease;
        }
        .store-lock-btn.open  { background: #dcfce7; color: var(--af-success); border: 1px solid #bbf7d0; }
        .store-lock-btn.locked { background: #fee2e2; color: var(--af-danger); border: 1px solid #fca5a5; }
        .store-lock-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.08); }

        /* ── Buttons ── */
        .btn {
            border: none; border-radius: 10px; padding: 9px 14px; font-weight: 700;
            cursor: pointer; font-family: inherit; font-size: 13px;
            transition: transform 0.08s ease, box-shadow 0.08s ease;
            display: inline-flex; align-items: center; gap: 6px;
        }
        .btn:active { transform: translateY(1px); }
        .btn-primary { background: var(--af-brown); color: #fff; box-shadow: 0 8px 18px rgba(82,55,0,0.18); }
        .btn-primary:hover { box-shadow: 0 12px 24px rgba(82,55,0,0.26); transform: translateY(-1px); }
        .btn-ghost  { background: #fff; color: var(--af-ink); border: 1px solid var(--af-line); }
        .btn-ghost:hover  { background: var(--af-cream); }
        .btn-sm { padding: 6px 11px; font-size: 12px; border-radius: 8px; }

        /* ── Main Layout ── */
        main { padding: 20px; max-width: 1240px; margin: 0 auto; display: grid; gap: 18px; }

        /* ── Hero ── */
        .hero {
            background: linear-gradient(135deg, rgba(82,55,0,0.07) 0%, rgba(204,153,51,0.06) 100%);
            border: 1px solid var(--af-line); border-radius: 18px; padding: 18px 20px;
            display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;
            box-shadow: var(--af-shadow);
        }
        .hero h2 { font-size: 20px; color: var(--af-ink); letter-spacing: -0.02em; }
        .hero p  { color: var(--af-ink-soft); font-size: 13px; margin-top: 3px; }

        /* ── Stats Strip ── */
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px,1fr)); gap: 12px; }
        .stat-card {
            background: #fff; border: 1px solid var(--af-line); border-radius: var(--radius);
            padding: 16px; box-shadow: var(--af-shadow); display: flex; flex-direction: column; gap: 4px;
        }
        .stat-card .label { font-size: 11px; color: var(--af-ink-soft); text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; }
        .stat-card .value { font-size: 26px; font-weight: 800; color: var(--af-brown); letter-spacing: -0.03em; }
        .stat-card.warn  .value { color: var(--af-warn); }
        .stat-card.ok    .value { color: var(--af-success); }
        .stat-card.danger .value { color: var(--af-danger); }

        /* ── Nav Tabs ── */
        .nav-tabs {
            display: flex; gap: 8px; border-bottom: 1px solid var(--af-line); padding-bottom: 2px;
        }
        .tab-btn {
            background: transparent; border: none; border-bottom: 3px solid transparent;
            padding: 9px 16px; font-weight: 700; font-size: 14px; color: var(--af-ink-soft);
            cursor: pointer; transition: all 0.15s ease;
        }
        .tab-btn.active { color: var(--af-brown); border-bottom-color: var(--af-brown); }

        /* ── Cards / Panels ── */
        .panel-card {
            background: #fff; border: 1px solid var(--af-line); border-radius: 18px;
            overflow: hidden; box-shadow: var(--af-shadow);
        }
        .panel-head {
            display: flex; align-items: center; justify-content: space-between; gap: 10px;
            padding: 14px 18px; border-bottom: 1px solid var(--af-line); background: #fff;
            flex-wrap: wrap;
        }
        .panel-head h3 { font-size: 16px; }

        /* ── Tables ── */
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        thead th {
            text-align: left; padding: 11px 16px; color: var(--af-ink-soft);
            font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 700;
            border-bottom: 1px solid var(--af-line); background: var(--af-cream);
        }
        tbody tr { border-bottom: 1px solid var(--af-line); transition: background 0.08s; }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: rgba(247,241,231,0.5); }
        td { padding: 12px 16px; vertical-align: middle; }

        /* ── Pills & Badges ── */
        .pill {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; white-space: nowrap;
        }
        .pill.in-stock  { background: #dcfce7; color: var(--af-success); border: 1px solid #bbf7d0; }
        .pill.sold-out  { background: #fee2e2; color: var(--af-danger);   border: 1px solid #fca5a5; }
        .pill.pending   { background: #fef3c7; color: var(--af-warn);     border: 1px solid #fcd34d; }
        .pill.completed { background: #dcfce7; color: var(--af-success);  border: 1px solid #bbf7d0; }
        .pill.cat       { background: rgba(82,55,0,0.08); color: var(--af-brown); border: 1px solid rgba(82,55,0,0.15); font-size: 11px; }

        /* ── Search Input ── */
        .search-input {
            padding: 8px 14px; border: 1px solid var(--af-line); border-radius: 10px;
            font: inherit; font-size: 13px; background: var(--af-cream); outline: none;
            width: 100%; max-width: 280px;
        }
        .search-input:focus { outline: 2px solid var(--af-gold); background: #fff; }

        /* ── Empty State ── */
        .empty-state {
            text-align: center; padding: 40px 20px; color: var(--af-ink-soft); font-size: 14px;
        }
        .empty-state .icon { font-size: 36px; margin-bottom: 8px; }

        #toast {
            position: fixed; right: 18px; bottom: 18px;
            background: var(--af-brown); color: #fff; padding: 12px 16px;
            border-radius: 12px; box-shadow: 0 16px 36px rgba(0,0,0,0.18);
            font-weight: 600; font-size: 13px; display: none; z-index: 300;
        }
        #toast.error { background: var(--af-danger); }
        .muted { color: var(--af-ink-soft); }
    </style>
</head>
<body>

<!-- ── TOPBAR ── -->
<header class="topbar">
    <div class="brand">
        <img src="/assets/logo2.png" alt="Acie Fraiche Cafe">
        <div>
            <h1>Acie Fraiche Cafe · Manager Panel</h1>
            <div class="sub">Signed in as {{ auth()->user()->name ?? 'Manager' }}</div>
        </div>
    </div>
    <div class="topbar-right">
        <!-- Site Ordering Lock Toggle -->
        <button id="storeLockBtn" class="store-lock-btn open" type="button">
            <span id="storeLockDot">🟢</span> <span id="storeLockText">Website Open</span>
        </button>
        <button id="btnRefresh" class="btn btn-ghost btn-sm" type="button">⟳ Refresh</button>
        <form method="POST" action="{{ route('logout') }}" style="margin:0;">
            @csrf
            <button type="submit" class="btn btn-ghost btn-sm">Logout</button>
        </form>
    </div>
</header>

<main>

    <!-- Hero -->
    <div class="hero">
        <div>
            <h2>Today's Manager Dashboard</h2>
            <p>Track today's live orders, update dish availability (sold-out status), and control website ordering availability.</p>
        </div>
        <div id="lastUpdated" class="muted" style="font-size:12px; white-space:nowrap;"></div>
    </div>

    <!-- Today's Stats Bar -->
    <div class="stats">
        <div class="stat-card ok">
            <div class="label">Today's Orders</div>
            <div class="value" id="statTodayOrders">—</div>
        </div>
        <div class="stat-card ok">
            <div class="label">Today's Revenue</div>
            <div class="value" id="statTodayRevenue">₦0</div>
        </div>
        <div class="stat-card warn">
            <div class="label">Sold Out Dishes</div>
            <div class="value" id="statSoldOutCount">—</div>
        </div>
        <div class="stat-card" id="statStoreCard">
            <div class="label">Website Ordering</div>
            <div class="value" id="statStoreStatus" style="font-size:18px;">Open</div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="nav-tabs">
        <button class="tab-btn active" data-tab="orders">📋 Today's Orders (<span id="tabOrderCount">0</span>)</button>
        <button class="tab-btn" data-tab="dishes">🍲 Dish Availability (Sold Out Toggle)</button>
    </div>

    <!-- Tab 1: Today's Orders -->
    <div class="panel-card" id="tabContentOrders">
        <div class="panel-head">
            <h3>Orders for Today ({{ date('M d, Y') }})</h3>
            <span class="muted" style="font-size:12px;">Only orders placed today are shown</span>
        </div>
        <div id="ordersTableWrap">
            <div class="empty-state"><div class="icon">📋</div>Loading today's orders…</div>
        </div>
    </div>

    <!-- Tab 2: Sold Out Dishes Control -->
    <div class="panel-card" id="tabContentDishes" style="display:none;">
        <div class="panel-head">
            <h3>Menu Item Availability</h3>
            <input type="text" id="dishSearch" class="search-input" placeholder="🔍 Search dishes…">
        </div>
        <div id="dishesTableWrap">
            <div class="empty-state"><div class="icon">🍲</div>Loading menu items…</div>
        </div>
    </div>

</main>

<div id="toast"></div>

<script>
(() => {
    'use strict';
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    const API  = '/api';

    let todayOrders  = [];
    let menuItems    = [];
    let isStoreOpen  = true;

    // ── Toast ─────────────────────────────────────
    let toastTimer;
    function toast(msg, isError = false) {
        const el = document.getElementById('toast');
        el.textContent = msg;
        el.className = isError ? 'error' : '';
        el.style.display = 'block';
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => { el.style.display = 'none'; }, 3200);
    }

    // ── API Helper ────────────────────────────────
    async function apiFetch(url, opts = {}) {
        const res = await fetch(API + url, {
            headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            ...opts,
        });
        if (!res.ok) {
            const body = await res.json().catch(() => ({}));
            throw new Error(body.message || `HTTP ${res.status}`);
        }
        return res.status === 204 ? null : res.json();
    }

    // ── Load All Data ─────────────────────────────
    async function loadAll() {
        try {
            const [summary, orders, items] = await Promise.all([
                apiFetch('/manager/summary'),
                apiFetch('/manager/orders'),
                apiFetch('/menu-items'),
            ]);

            isStoreOpen = !!summary.is_open;
            todayOrders = orders || [];
            menuItems   = items || [];

            renderStoreLockBtn(isStoreOpen);
            renderStats(summary);
            renderOrdersTable();
            renderDishesTable();

            document.getElementById('lastUpdated').textContent =
                'Last updated ' + new Date().toLocaleTimeString();
        } catch (err) {
            toast(err.message, true);
        }
    }

    // ── Render Store Lock Button ──────────────────
    function renderStoreLockBtn(isOpen) {
        const btn  = document.getElementById('storeLockBtn');
        const dot  = document.getElementById('storeLockDot');
        const txt  = document.getElementById('storeLockText');
        const stat = document.getElementById('statStoreStatus');

        if (isOpen) {
            btn.className = 'store-lock-btn open';
            dot.textContent = '🟢';
            txt.textContent = 'Website Open (Lock)';
            stat.textContent = '🟢 Open';
            stat.style.color = 'var(--af-success)';
        } else {
            btn.className = 'store-lock-btn locked';
            dot.textContent = '🔴';
            txt.textContent = 'Website Locked (Unlock)';
            stat.textContent = '🔴 Locked';
            stat.style.color = 'var(--af-danger)';
        }
    }

    // ── Toggle Website Lock Status ────────────────
    async function toggleStoreLock() {
        const nextState = !isStoreOpen;
        try {
            await apiFetch('/order-availability', {
                method: 'PUT',
                body: JSON.stringify({
                    mode: nextState ? 'force_open' : 'force_closed',
                }),
            });
            isStoreOpen = nextState;
            renderStoreLockBtn(isStoreOpen);
            toast(isStoreOpen ? '🟢 Website ordering opened!' : '🔴 Website ordering locked!');
        } catch (err) {
            toast(err.message, true);
        }
    }

    // ── Render Stats ──────────────────────────────
    function renderStats(s) {
        document.getElementById('statTodayOrders').textContent  = s.today_orders ?? 0;
        document.getElementById('statTodayRevenue').textContent = '₦' + Number(s.today_revenue || 0).toLocaleString();
        document.getElementById('statSoldOutCount').textContent  = s.sold_out_count ?? 0;
        document.getElementById('tabOrderCount').textContent    = s.today_orders ?? 0;
    }

    // ── Today's Orders Table ──────────────────────
    function renderOrdersTable() {
        const wrap = document.getElementById('ordersTableWrap');
        if (!todayOrders.length) {
            wrap.innerHTML = `<div class="empty-state"><div class="icon">📋</div>No orders placed today yet.</div>`;
            return;
        }

        const rows = todayOrders.map(o => {
            const time = new Date(o.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            const itemNames = (o.items || []).map(i => `${i.quantity}x ${esc(i.item_name || i.menu_item?.name || 'Item')}`).join(', ');
            const statusClass = o.status === 'completed' ? 'completed' : 'pending';

            return `
            <tr>
                <td style="font-weight:700;">#${esc(o.code)}</td>
                <td>${esc(o.customer_name || 'Guest')}<br><small class="muted">${esc(o.customer_phone || '')}</small></td>
                <td style="max-width:280px;">${itemNames || '—'}</td>
                <td><span class="pill cat">${esc(o.channel || 'web')} · ${esc(o.service || 'Takeout')}</span></td>
                <td><span class="pill ${statusClass}">${esc(o.status)}</span></td>
                <td style="font-weight:700; color:var(--af-brown);">₦${Number(o.total || 0).toLocaleString()}</td>
                <td class="muted">${time}</td>
            </tr>`;
        }).join('');

        wrap.innerHTML = `
        <table>
            <thead>
                <tr>
                    <th>Code</th><th>Customer</th><th>Items</th><th>Channel</th>
                    <th>Status</th><th>Total</th><th>Time</th>
                </tr>
            </thead>
            <tbody>${rows}</tbody>
        </table>`;
    }

    // ── Sold Out Dishes Table ─────────────────────
    function renderDishesTable() {
        const wrap = document.getElementById('dishesTableWrap');
        const filter = (document.getElementById('dishSearch')?.value || '').toLowerCase().trim();

        const filtered = menuItems.filter(i =>
            i.name.toLowerCase().includes(filter) ||
            (i.category?.name || '').toLowerCase().includes(filter)
        );

        if (!filtered.length) {
            wrap.innerHTML = `<div class="empty-state"><div class="icon">🍲</div>No dishes found.</div>`;
            return;
        }

        const rows = filtered.map(i => {
            const isSoldOut = i.is_sold_out || i.stock === 0;
            return `
            <tr>
                <td style="font-weight:700;">${esc(i.name)}</td>
                <td><span class="pill cat">${esc(i.category?.name || 'Menu')}</span></td>
                <td style="font-weight:700; color:var(--af-brown);">₦${Number(i.price || 0).toLocaleString()}</td>
                <td><span class="pill ${isSoldOut ? 'sold-out' : 'in-stock'}">${isSoldOut ? '🔴 Sold Out' : '🟢 In Stock'}</span></td>
                <td>
                    <button class="btn ${isSoldOut ? 'btn-primary' : 'btn-ghost'} btn-sm btn-toggle-sold" data-id="${i.id}">
                        ${isSoldOut ? 'Mark In Stock' : 'Mark Sold Out'}
                    </button>
                </td>
            </tr>`;
        }).join('');

        wrap.innerHTML = `
        <table>
            <thead>
                <tr>
                    <th>Dish Name</th><th>Category</th><th>Price</th>
                    <th>Status</th><th>Action</th>
                </tr>
            </thead>
            <tbody>${rows}</tbody>
        </table>`;
    }

    // ── Toggle Sold Out Status ────────────────────
    async function toggleSoldOut(itemId) {
        try {
            await apiFetch(`/menu-items/${itemId}/toggle-sold-out`, { method: 'POST' });
            toast('✓ Dish availability updated');
            await loadAll();
        } catch (err) {
            toast(err.message, true);
        }
    }

    // ── Event Listeners ───────────────────────────
    document.getElementById('storeLockBtn').addEventListener('click', toggleStoreLock);
    document.getElementById('btnRefresh').addEventListener('click', loadAll);

    // Tab switching
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const tab = btn.dataset.tab;
            document.getElementById('tabContentOrders').style.display = tab === 'orders' ? 'block' : 'none';
            document.getElementById('tabContentDishes').style.display = tab === 'dishes' ? 'block' : 'none';
        });
    });

    // Search filter for dishes
    document.getElementById('dishSearch').addEventListener('input', renderDishesTable);

    // Table click delegation for Sold Out toggle
    document.getElementById('dishesTableWrap').addEventListener('click', e => {
        const btn = e.target.closest('.btn-toggle-sold');
        if (btn) toggleSoldOut(parseInt(btn.dataset.id));
    });

    // Helpers
    function esc(s) {
        return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // Boot & Auto-refresh every 15s
    loadAll();
    setInterval(loadAll, 15_000);

})();
</script>
</body>
</html>
