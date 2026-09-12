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
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
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
            --radius:     16px;
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
            padding: 14px 24px; background: #fff;
            border-bottom: 1px solid var(--af-line);
            box-shadow: var(--af-shadow);
            position: sticky; top: 0; z-index: 100;
        }
        .brand { display: flex; align-items: center; gap: 12px; }
        .brand img { width: 44px; height: 44px; border-radius: 12px; border: 1px solid var(--af-line); padding: 4px; object-fit: contain; }
        .brand h1 { font-size: 19px; font-family: 'Playfair Display', Georgia, serif; color: var(--af-brown); letter-spacing: -0.02em; }
        .brand .sub { font-size: 12px; color: var(--af-ink-soft); margin-top: 1px; }
        .topbar-right { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

        /* ── Store Lock Switch ── */
        .store-lock-btn {
            border: none; border-radius: 999px; padding: 8px 18px; font-weight: 700;
            font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;
            transition: all 0.2s ease;
        }
        .store-lock-btn.open   { background: #dcfce7; color: var(--af-success); border: 1px solid #bbf7d0; }
        .store-lock-btn.locked { background: #fee2e2; color: var(--af-danger); border: 1px solid #fca5a5; }
        .store-lock-btn:hover  { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.08); }

        /* ── Buttons ── */
        .btn {
            border: none; border-radius: 10px; padding: 9px 14px; font-weight: 700;
            cursor: pointer; font-family: inherit; font-size: 13px;
            transition: transform 0.08s ease, box-shadow 0.08s ease;
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
        }
        .btn:active { transform: translateY(1px); }
        .btn-primary { background: var(--af-brown); color: #fff; box-shadow: 0 8px 18px rgba(82,55,0,0.18); }
        .btn-primary:hover { box-shadow: 0 12px 24px rgba(82,55,0,0.26); transform: translateY(-1px); }
        .btn-danger  { background: #dc2626; color: #fff; box-shadow: 0 8px 18px rgba(220,38,38,0.2); }
        .btn-danger:hover  { background: #b91c1c; transform: translateY(-1px); }
        .btn-success { background: #16a34a; color: #fff; box-shadow: 0 8px 18px rgba(22,163,74,0.2); }
        .btn-success:hover { background: #15803d; transform: translateY(-1px); }
        .btn-ghost   { background: #fff; color: var(--af-ink); border: 1px solid var(--af-line); }
        .btn-ghost:hover   { background: var(--af-cream); }
        .btn-sm { padding: 6px 12px; font-size: 12px; border-radius: 8px; }

        /* ── Main Layout ── */
        main { padding: 20px; max-width: 1280px; margin: 0 auto; display: grid; gap: 20px; }

        /* ── Hero ── */
        .hero {
            background: linear-gradient(135deg, rgba(82,55,0,0.07) 0%, rgba(204,153,51,0.06) 100%);
            border: 1px solid var(--af-line); border-radius: 20px; padding: 20px 24px;
            display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;
            box-shadow: var(--af-shadow);
        }
        .hero h2 { font-size: 22px; color: var(--af-ink); letter-spacing: -0.02em; }
        .hero p  { color: var(--af-ink-soft); font-size: 13px; margin-top: 3px; }

        /* ── Stats Strip ── */
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px,1fr)); gap: 14px; }
        .stat-card {
            background: #fff; border: 1px solid var(--af-line); border-radius: var(--radius);
            padding: 18px; box-shadow: var(--af-shadow); display: flex; flex-direction: column; gap: 4px;
        }
        .stat-card .label { font-size: 11px; color: var(--af-ink-soft); text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; }
        .stat-card .value { font-size: 28px; font-weight: 800; color: var(--af-brown); letter-spacing: -0.03em; }
        .stat-card.warn   .value { color: var(--af-warn); }
        .stat-card.ok     .value { color: var(--af-success); }
        .stat-card.danger .value { color: var(--af-danger); }

        /* ── Nav Tabs ── */
        .nav-tabs {
            display: flex; gap: 10px; border-bottom: 2px solid var(--af-line); padding-bottom: 2px;
        }
        .tab-btn {
            background: transparent; border: none; border-bottom: 3px solid transparent;
            padding: 10px 18px; font-weight: 700; font-size: 14px; color: var(--af-ink-soft);
            cursor: pointer; transition: all 0.15s ease; border-radius: 8px 8px 0 0;
        }
        .tab-btn.active { color: var(--af-brown); border-bottom-color: var(--af-brown); background: rgba(82,55,0,0.04); }

        /* ── Cards / Panels ── */
        .panel-card {
            background: #fff; border: 1px solid var(--af-line); border-radius: 20px;
            overflow: hidden; box-shadow: var(--af-shadow);
        }
        .panel-head {
            display: flex; align-items: center; justify-content: space-between; gap: 14px;
            padding: 16px 22px; border-bottom: 1px solid var(--af-line); background: #fff;
            flex-wrap: wrap;
        }
        .panel-head h3 { font-size: 17px; font-weight: 800; }

        /* ── Category Chips Filter ── */
        .category-filter {
            display: flex; gap: 8px; flex-wrap: wrap; padding: 14px 22px;
            background: var(--af-cream); border-bottom: 1px solid var(--af-line);
        }
        .chip {
            background: #fff; border: 1px solid var(--af-line); border-radius: 999px;
            padding: 6px 14px; font-size: 12px; font-weight: 700; color: var(--af-ink-soft);
            cursor: pointer; transition: all 0.15s ease;
        }
        .chip.active { background: var(--af-brown); color: #fff; border-color: var(--af-brown); }

        /* ── Dish Card Grid ── */
        .dish-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 18px; padding: 22px; background: #faf8f5;
        }
        .dish-card {
            background: #fff; border: 1px solid var(--af-line); border-radius: 16px;
            overflow: hidden; box-shadow: 0 4px 14px rgba(0,0,0,0.04);
            display: flex; flex-direction: column; transition: transform 0.15s ease, box-shadow 0.15s ease;
            position: relative;
        }
        .dish-card:hover { transform: translateY(-3px); box-shadow: 0 10px 24px rgba(0,0,0,0.08); }
        .dish-card.is-sold-out { opacity: 0.88; border-color: #fca5a5; }

        .dish-img-wrap {
            height: 160px; width: 100%; background: #f1e9dd; position: relative; overflow: hidden;
        }
        .dish-img-wrap img {
            width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease;
        }
        .dish-card:hover .dish-img-wrap img { transform: scale(1.05); }
        .dish-img-fallback {
            width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;
            font-size: 48px; background: linear-gradient(135deg, #f7f1e7 0%, #e6dcd0 100%);
        }

        .dish-status-overlay {
            position: absolute; top: 10px; right: 10px;
            padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 800;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15); text-transform: uppercase; letter-spacing: 0.04em;
        }
        .dish-status-overlay.in-stock { background: #166534; color: #fff; }
        .dish-status-overlay.sold-out { background: #dc2626; color: #fff; }

        .dish-body { padding: 14px; flex: 1; display: flex; flex-direction: column; gap: 8px; }
        .dish-category { font-size: 11px; font-weight: 700; color: var(--af-gold); text-transform: uppercase; letter-spacing: 0.05em; }
        .dish-title { font-size: 15px; font-weight: 800; color: var(--af-ink); line-height: 1.3; }
        .dish-price { font-size: 17px; font-weight: 800; color: var(--af-brown); margin-top: auto; }

        .dish-actions { padding: 0 14px 14px 14px; }
        .dish-actions .btn { width: 100%; }

        /* ── Orders Cards / Table Layout ── */
        .orders-feed { display: grid; gap: 16px; padding: 20px; }
        .order-card {
            background: #fff; border: 1px solid var(--af-line); border-radius: 16px;
            padding: 18px; box-shadow: 0 4px 14px rgba(0,0,0,0.03);
            display: flex; flex-direction: column; gap: 14px; transition: border-color 0.15s;
        }
        .order-card:hover { border-color: var(--af-gold); }
        .order-head {
            display: flex; align-items: center; justify-content: space-between; gap: 10px;
            border-bottom: 1px solid var(--af-line); padding-bottom: 12px; flex-wrap: wrap;
        }
        .order-code { font-size: 16px; font-weight: 800; color: var(--af-brown); }
        .order-customer { font-size: 13px; font-weight: 700; }
        .order-meta { font-size: 12px; color: var(--af-ink-soft); }

        /* ── Ordered Items Text List ── */
        .order-items-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 8px;
        }
        .order-item-row {
            display: flex; align-items: center; justify-content: space-between; gap: 8px;
            background: var(--af-cream); padding: 8px 12px; border-radius: 10px;
            border: 1px solid rgba(15,11,5,0.07); font-size: 13px;
        }
        .order-item-qty-badge {
            background: var(--af-brown); color: #fff; padding: 2px 7px;
            border-radius: 6px; font-weight: 800; font-size: 11px; flex-shrink: 0;
        }
        .order-item-name {
            font-weight: 700; flex: 1; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--af-ink);
        }
        .order-item-price { font-weight: 800; color: var(--af-brown); flex-shrink: 0; font-size: 12px; }

        .order-foot {
            display: flex; align-items: center; justify-content: space-between; gap: 10px;
            border-top: 1px solid var(--af-line); padding-top: 12px; flex-wrap: wrap;
        }
        .order-total-price { font-size: 18px; font-weight: 800; color: var(--af-brown); }

        /* ── Pills & Badges ── */
        .pill {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; white-space: nowrap;
        }
        .pill.in-stock  { background: #dcfce7; color: var(--af-success); border: 1px solid #bbf7d0; }
        .pill.sold-out  { background: #fee2e2; color: var(--af-danger);   border: 1px solid #fca5a5; }
        .pill.pending   { background: #fef3c7; color: var(--af-warn);     border: 1px solid #fcd34d; }
        .pill.paid      { background: #dbeafe; color: #1d4ed8;             border: 1px solid #93c5fd; }
        .pill.completed { background: #dcfce7; color: var(--af-success);  border: 1px solid #bbf7d0; }
        .pill.cancelled { background: #fee2e2; color: var(--af-danger);   border: 1px solid #fca5a5; }
        .pill.cat       { background: rgba(82,55,0,0.08); color: var(--af-brown); border: 1px solid rgba(82,55,0,0.15); font-size: 11px; }
        .order-card.is-cancelled { opacity: 0.6; border-color: #fca5a5; background: #fff8f8; }
        .order-card.is-completed { border-color: #bbf7d0; background: #f0fdf4; }

        /* ── Search Input ── */
        .search-input {
            padding: 9px 16px; border: 1px solid var(--af-line); border-radius: 12px;
            font: inherit; font-size: 13px; background: var(--af-cream); outline: none;
            width: 100%; max-width: 320px;
        }
        .search-input:focus { outline: 2px solid var(--af-gold); background: #fff; }

        /* ── Empty State ── */
        .empty-state {
            text-align: center; padding: 48px 20px; color: var(--af-ink-soft); font-size: 14px;
        }
        .empty-state .icon { font-size: 42px; margin-bottom: 10px; }

        .expenses-grid {
            padding: 20px; display: grid; gap: 20px; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        }

        #toast {
            position: fixed; right: 20px; bottom: 20px;
            background: var(--af-brown); color: #fff; padding: 12px 18px;
            border-radius: 14px; box-shadow: 0 16px 36px rgba(0,0,0,0.22);
            font-weight: 700; font-size: 13px; display: none; z-index: 300;
        }
        #toast.error { background: var(--af-danger); }
        .muted { color: var(--af-ink-soft); }

        /* ── Tablet & iPad Responsive Optimization ── */
        @media (max-width: 1024px) {
            header.topbar {
                padding: 10px 16px; gap: 8px;
            }
            .brand img {
                width: 36px; height: 36px; border-radius: 8px; padding: 2px;
            }
            .brand h1 {
                font-size: 16px;
            }
            .brand .sub {
                font-size: 11px;
            }
            .store-lock-btn {
                padding: 6px 14px; font-size: 12px;
            }
            .btn {
                padding: 7px 12px; font-size: 12px;
            }
            .btn-sm {
                padding: 5px 10px; font-size: 11px;
            }

            main {
                padding: 12px; gap: 12px; max-width: 100%;
            }

            .hero {
                padding: 12px 16px; border-radius: 14px; gap: 8px;
            }
            .hero h2 {
                font-size: 17px;
            }
            .hero p {
                font-size: 12px; margin-top: 1px;
            }

            .stats {
                grid-template-columns: repeat(4, 1fr); gap: 10px;
            }
            .stat-card {
                padding: 10px 12px; border-radius: 12px; gap: 2px;
            }
            .stat-card .label {
                font-size: 10px;
            }
            .stat-card .value {
                font-size: 20px;
            }

            .nav-tabs {
                gap: 6px;
            }
            .tab-btn {
                padding: 8px 14px; font-size: 13px; border-radius: 8px 8px 0 0;
            }

            .panel-card {
                border-radius: 14px;
            }
            .panel-head {
                padding: 10px 14px; gap: 8px;
            }
            .panel-head h3 {
                font-size: 15px;
            }

            .category-filter {
                padding: 10px 14px; gap: 6px;
            }
            .chip {
                padding: 5px 12px; font-size: 11px;
            }

            .dish-grid {
                grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
                gap: 10px; padding: 12px;
            }
            .dish-card {
                border-radius: 12px;
            }
            .dish-img-wrap {
                height: 100px;
            }
            .dish-body {
                padding: 10px; gap: 4px;
            }
            .dish-title {
                font-size: 13px;
            }
            .dish-price {
                font-size: 14px;
            }
            .dish-actions {
                padding: 0 10px 10px 10px;
            }
            .dish-actions .btn {
                padding: 6px 10px; font-size: 11px;
            }

            .orders-feed {
                grid-template-columns: repeat(2, 1fr); gap: 12px; padding: 12px;
            }
            .order-card {
                padding: 12px; border-radius: 12px; gap: 8px;
            }
            .order-head {
                padding-bottom: 8px;
            }
            .order-code {
                font-size: 14px;
            }
            .order-customer {
                font-size: 12px;
            }
            .order-total-price {
                font-size: 15px;
            }
            .order-items-grid {
                grid-template-columns: 1fr; gap: 4px;
            }
            .order-item-row {
                padding: 5px 8px; font-size: 12px;
            }
            .order-foot {
                padding-top: 8px;
            }
            .expenses-grid {
                padding: 12px; gap: 12px; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }
            .search-input {
                padding: 7px 12px; font-size: 12px; max-width: 220px;
            }
        }

        @media (max-width: 680px) {
            .stats {
                grid-template-columns: repeat(2, 1fr);
            }
            .orders-feed {
                grid-template-columns: 1fr;
            }
            .dish-grid {
                grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            }
        }
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
            <p>Track today's live orders with item details, update dish availability with full photos, and control website ordering access.</p>
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
            <div class="label">Today's Expenses</div>
            <div class="value" id="statTodayExpenses">₦0</div>
        </div>
        <div class="stat-card" id="statStoreCard">
            <div class="label">Website Ordering</div>
            <div class="value" id="statStoreStatus" style="font-size:18px;">Open</div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="nav-tabs">
        <button class="tab-btn active" data-tab="orders">📋 Today's Orders (<span id="tabOrderCount">0</span>)</button>
        <button class="tab-btn" data-tab="dishes">🍲 Dish Availability & Photos</button>
        <button class="tab-btn" data-tab="expenses">💸 Today's Expenses (<span id="tabExpenseTotal">₦0</span>)</button>
    </div>

    <!-- Tab 1: Today's Orders -->
    <div class="panel-card" id="tabContentOrders">
        <div class="panel-head">
            <div>
                <h3>Orders Placed Today ({{ date('M d, Y') }})</h3>
                <span class="muted" style="font-size:12px;">Live feed showing all ordered items and quantities</span>
            </div>
            <span class="pill cat" id="ordersCountBadge">0 Orders</span>
        </div>
        <div id="ordersFeedWrap" class="orders-feed">
            <div class="empty-state"><div class="icon">📋</div>Loading today's orders…</div>
        </div>
    </div>

    <!-- Tab 2: Sold Out Dishes Control -->
    <div class="panel-card" id="tabContentDishes" style="display:none;">
        <div class="panel-head">
            <div>
                <h3>Menu Item Availability & Dish Photos</h3>
                <span class="muted" style="font-size:12px;">1-click toggle to mark dishes in-stock or sold-out</span>
            </div>
            <input type="text" id="dishSearch" class="search-input" placeholder="🔍 Search dishes by name…">
        </div>
        <div class="category-filter" id="categoryChipsWrap">
            <button class="chip active" data-cat="all">All Dishes</button>
        </div>
        <div id="dishesGridWrap" class="dish-grid">
            <div class="empty-state"><div class="icon">🍲</div>Loading menu items…</div>
        </div>
    </div>

    <!-- Tab 3: Today's Expenses -->
    <div class="panel-card" id="tabContentExpenses" style="display:none;">
        <div class="panel-head">
            <div>
                <h3>Daily Expenses ({{ date('M d, Y') }})</h3>
                <span class="muted" style="font-size:12px;">Log and view all expenses incurred today</span>
            </div>
            <span class="pill cat" id="todayExpensesPill">Total Today: ₦0</span>
        </div>

        <div class="expenses-grid">
            <!-- Add Expense Form -->
            <div style="background:var(--af-cream); padding:18px; border-radius:14px; border:1px solid var(--af-line);">
                <h4 style="margin:0 0 12px 0; color:var(--af-brown);">+ Record New Expense</h4>
                <form id="managerExpenseForm" style="display:grid; gap:12px;">
                    <div>
                        <label style="font-size:12px; font-weight:700; display:block; margin-bottom:4px;">Title / Description *</label>
                        <input type="text" name="title" required placeholder="e.g. Bought 2 bags of ice, Gas refill" style="width:100%; padding:9px 12px; border-radius:10px; border:1px solid var(--af-line); font:inherit; font-size:13px; box-sizing:border-box;" />
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:8px;">
                        <div>
                            <label style="font-size:11px; font-weight:700; display:block; margin-bottom:4px;">Qty</label>
                            <input type="number" id="mgrQty" name="quantity" step="any" min="0" placeholder="e.g. 5" style="width:100%; padding:8px 10px; border-radius:10px; border:1px solid var(--af-line); font:inherit; font-size:13px; box-sizing:border-box;" />
                        </div>
                        <div>
                            <label style="font-size:11px; font-weight:700; display:block; margin-bottom:4px;">Unit</label>
                            <select id="mgrUnit" name="unit" style="width:100%; padding:8px 6px; border-radius:10px; border:1px solid var(--af-line); font:inherit; font-size:12px; box-sizing:border-box;">
                                <option value="">— Select —</option>
                                <option value="kg">kg (Kilograms)</option>
                                <option value="pcs">pieces (pcs)</option>
                                <option value="cups">cups</option>
                                <option value="bags">bags</option>
                                <option value="litres">litres (L)</option>
                                <option value="crates">crates</option>
                                <option value="cartons">cartons</option>
                                <option value="packs">packs</option>
                                <option value="bottles">bottles</option>
                                <option value="units">units</option>
                            </select>
                        </div>
                        <div>
                            <label style="font-size:11px; font-weight:700; display:block; margin-bottom:4px;">Price/Unit (₦)</label>
                            <input type="number" id="mgrUnitPrice" name="price_per_unit" step="any" min="0" placeholder="e.g. 1000" style="width:100%; padding:8px 10px; border-radius:10px; border:1px solid var(--af-line); font:inherit; font-size:13px; box-sizing:border-box;" />
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                        <div>
                            <label style="font-size:12px; font-weight:700; display:block; margin-bottom:4px;">Total Amount (₦) *</label>
                            <input type="number" id="mgrTotalAmount" name="amount" step="0.01" min="0.01" required placeholder="5000" style="width:100%; padding:9px 12px; border-radius:10px; border:1px solid var(--af-line); font:inherit; font-size:13px; font-weight:700; color:var(--af-brown); background:#fff; box-sizing:border-box;" />
                        </div>
                        <div>
                            <label style="font-size:12px; font-weight:700; display:block; margin-bottom:4px;">Category</label>
                            <select name="category" style="width:100%; padding:9px 12px; border-radius:10px; border:1px solid var(--af-line); font:inherit; font-size:13px; box-sizing:border-box;">
                                <option value="Supplies">Supplies & Ingredients</option>
                                <option value="Utilities">Utilities & Power</option>
                                <option value="Logistics">Logistics & Transport</option>
                                <option value="Staff">Staff / Welfare</option>
                                <option value="General" selected>General</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label style="font-size:12px; font-weight:700; display:block; margin-bottom:4px;">Buyer / Person Who Purchased</label>
                        <input type="text" name="buyer_name" placeholder="e.g. Amaka, Emeka, Market runner" style="width:100%; padding:9px 12px; border-radius:10px; border:1px solid var(--af-line); font:inherit; font-size:13px; box-sizing:border-box;" />
                    </div>
                    <div>
                        <label style="font-size:12px; font-weight:700; display:block; margin-bottom:4px;">Note (Optional)</label>
                        <input type="text" name="note" placeholder="Receipt or additional details" style="width:100%; padding:9px 12px; border-radius:10px; border:1px solid var(--af-line); font:inherit; font-size:13px; box-sizing:border-box;" />
                    </div>
                    <button class="btn btn-primary" type="submit" style="width:100%; margin-top:4px;">Save Expense Record</button>
                </form>
            </div>

            <!-- Today's Expenses List -->
            <div>
                <h4 style="margin:0 0 12px 0; color:var(--af-brown);">Today's Logged Expenses</h4>
                <div id="managerExpensesWrap" style="display:grid; gap:10px;">
                    <div class="empty-state"><div class="icon">💸</div>No expenses logged today yet.</div>
                </div>
            </div>
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
    let selectedCat  = 'all';

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
            renderOrdersFeed();
            renderCategoryChips();
            renderDishesGrid();
            await loadTodayExpenses();

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
        const elOrders = document.getElementById('statTodayOrders');
        const elRevenue = document.getElementById('statTodayRevenue');
        const elSoldOut = document.getElementById('statSoldOutCount');
        const elTabCount = document.getElementById('tabOrderCount');
        const elBadge = document.getElementById('ordersCountBadge');

        if (elOrders) elOrders.textContent = s.today_orders ?? 0;
        if (elRevenue) elRevenue.textContent = '₦' + Number(s.today_revenue || 0).toLocaleString();
        if (elSoldOut) elSoldOut.textContent = s.sold_out_count ?? 0;
        if (elTabCount) elTabCount.textContent = s.today_orders ?? 0;
        if (elBadge) elBadge.textContent = (s.today_orders ?? 0) + ' Orders';
    }

    // ── Today's Orders Feed (Clean, Responsive & Text-Only) ──
    function renderOrdersFeed() {
        const wrap = document.getElementById('ordersFeedWrap');
        if (!todayOrders.length) {
            wrap.innerHTML = `<div class="empty-state"><div class="icon">📋</div>No orders placed today yet.</div>`;
            return;
        }

        // Stats: cancelled excluded
        const activeOrders = todayOrders.filter(o => o.status !== 'cancelled');
        document.getElementById('statTodayOrders').textContent = activeOrders.length;

        const statusLabel = {
            pending:   '🟡 Pending',
            paid:      '🔵 Paid',
            completed: '✅ Completed',
            cancelled: '❌ Cancelled',
        };

        const cardsHtml = todayOrders.map(o => {
            const time = new Date(o.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            const isCancelled = o.status === 'cancelled';
            const isCompleted = o.status === 'completed' || ['ready', 'served'].includes(o.kitchen_status);
            const cardClass = isCancelled ? 'order-card is-cancelled' : isCompleted ? 'order-card is-completed' : 'order-card';

            const itemsHtml = (o.items || []).map(i => {
                const qty = i.quantity || 1;
                const price = Number(i.unit_price || i.price || 0);
                const total = Number(i.total || (qty * price));
                return `
                <div class="order-item-row">
                    <span class="order-item-qty-badge">${qty}x</span>
                    <span class="order-item-name">${esc(i.name || i.item_name || 'Dish')}</span>
                    <span class="order-item-price">₦${total.toLocaleString()}</span>
                </div>`;
            }).join('');

            // Action buttons — completed orders are locked and cannot be cancelled by managers
            const actions = [];
            if (!isCompleted && !isCancelled) {
                actions.push(`<button class="btn btn-success btn-sm btn-order-action" data-id="${o.id}" data-status="completed">✅ Mark Complete</button>`);
                actions.push(`<button class="btn btn-danger btn-sm btn-order-action" data-id="${o.id}" data-status="cancelled">❌ Cancel</button>`);
            }
            if (isCancelled) {
                actions.push(`<button class="btn btn-ghost btn-sm btn-order-action" data-id="${o.id}" data-status="pending">↩ Restore</button>`);
            }
            actions.push(`<button class="btn btn-ghost btn-sm btn-delete-order" data-delete-id="${o.id}" style="color:var(--af-danger);">🗑 Delete</button>`);

            const statusPillClass = isCompleted ? 'completed' : (isCancelled ? 'cancelled' : esc(o.status));
            const statusPillText  = isCompleted ? '✅ Completed' : (statusLabel[o.status] || esc(o.status));

            return `
            <div class="${cardClass}">
                <div class="order-head">
                    <div>
                        <span class="order-code">#${esc(o.code)}</span>
                        <span class="pill cat" style="margin-left:8px;">${esc(o.channel || 'web')} · ${esc(o.service || 'Takeout')}</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                        <span class="order-customer">👤 ${esc(o.customer_name || 'Guest')}</span>
                        ${o.customer_phone ? `<span class="order-meta">(${esc(o.customer_phone)})</span>` : ''}
                        <span class="order-meta">⏰ ${time}</span>
                    </div>
                </div>

                <div class="order-items-grid">
                    ${itemsHtml || '<div class="muted" style="font-size:12px; padding:6px 0;">No item details</div>'}
                </div>

                <div class="order-foot">
                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                        <span class="pill ${statusPillClass}">${statusPillText}</span>
                        ${actions.join('')}
                    </div>
                    <div class="order-total-price">
                        ${isCancelled ? `<span style="text-decoration:line-through; color:var(--af-ink-soft); font-size:14px;">₦${Number(o.total||0).toLocaleString()}</span> <span class="muted" style="font-size:12px;">Cancelled</span>` : `<span class="muted" style="font-size:13px; font-weight:500;">Total:</span> ₦${Number(o.total||0).toLocaleString()}`}
                    </div>
                </div>
            </div>`;
        }).join('');

        wrap.innerHTML = cardsHtml;
    }

    // ── Update Order Status ───────────────────────
    async function updateOrderStatus(orderId, status) {
        try {
            await apiFetch(`/orders/${orderId}/status`, {
                method: 'POST',
                body: JSON.stringify({ status }),
            });
            toast(status === 'completed' ? '✅ Order marked as completed!' : status === 'cancelled' ? '❌ Order cancelled' : '↩ Order restored');
            await loadAll();
        } catch (err) {
            toast(err.message, true);
        }
    }

    async function deleteManagerOrder(orderId) {
        if (!confirm('Are you sure you want to delete this order? This action cannot be undone.')) {
            return;
        }
        try {
            await apiFetch(`/orders/${orderId}`, { method: 'DELETE' });
            toast('🗑 Order deleted');
            await loadAll();
        } catch (err) {
            toast(err.message || 'Failed to delete order', true);
        }
    }

    // ── Category Chips ────────────────────────────
    function renderCategoryChips() {
        const wrap = document.getElementById('categoryChipsWrap');
        const categories = Array.from(new Set(menuItems.map(i => i.category?.name).filter(Boolean)));

        const chipsHtml = [
            `<button class="chip ${selectedCat === 'all' ? 'active' : ''}" data-cat="all">All Dishes (${menuItems.length})</button>`,
            ...categories.map(cat => {
                const count = menuItems.filter(i => i.category?.name === cat).length;
                return `<button class="chip ${selectedCat === cat ? 'active' : ''}" data-cat="${esc(cat)}">${esc(cat)} (${count})</button>`;
            })
        ].join('');

        wrap.innerHTML = chipsHtml;
    }

    // ── Sold Out Dishes Cards Grid (With Pictures) ──
    function renderDishesGrid() {
        const wrap = document.getElementById('dishesGridWrap');
        const filter = (document.getElementById('dishSearch')?.value || '').toLowerCase().trim();

        const filtered = menuItems.filter(i => {
            const matchesCat = selectedCat === 'all' || (i.category?.name === selectedCat);
            const matchesSearch = i.name.toLowerCase().includes(filter) ||
                (i.category?.name || '').toLowerCase().includes(filter);
            return matchesCat && matchesSearch;
        });

        if (!filtered.length) {
            wrap.innerHTML = `<div class="empty-state" style="grid-column:1/-1;"><div class="icon">🍲</div>No dishes found.</div>`;
            return;
        }

        const cardsHtml = filtered.map(i => {
            const isSoldOut = i.is_sold_out || i.stock === 0;
            const imgHtml = i.image_url
                ? `<img src="${esc(i.image_url)}" alt="${esc(i.name)}" onerror="this.outerHTML='<div class=\\'dish-img-fallback\\'>🍲</div>'">`
                : `<div class="dish-img-fallback">🍲</div>`;

            return `
            <div class="dish-card ${isSoldOut ? 'is-sold-out' : ''}">
                <div class="dish-img-wrap">
                    ${imgHtml}
                    <div class="dish-status-overlay ${isSoldOut ? 'sold-out' : 'in-stock'}">
                        ${isSoldOut ? '🔴 SOLD OUT' : '🟢 IN STOCK'}
                    </div>
                </div>
                <div class="dish-body">
                    <div class="dish-category">${esc(i.category?.name || 'Menu')}</div>
                    <div class="dish-title">${esc(i.name)}</div>
                    <div class="dish-price">₦${Number(i.price || 0).toLocaleString()}</div>
                </div>
                <div class="dish-actions">
                    <button class="btn ${isSoldOut ? 'btn-success' : 'btn-danger'} btn-toggle-sold" data-id="${i.id}">
                        ${isSoldOut ? '🟢 Mark as In Stock' : '🔴 Mark as Sold Out'}
                    </button>
                </div>
            </div>`;
        }).join('');

        wrap.innerHTML = cardsHtml;
    }

    // ── Today's Expenses ─────────────────────────
    let todayExpenses = [];

    async function loadTodayExpenses() {
        try {
            const data = await apiFetch('/expenses?today_only=1');
            todayExpenses = data.expenses || [];
            const total = data.total || 0;

            document.getElementById('statTodayExpenses').textContent = '₦' + Number(total).toLocaleString();
            document.getElementById('tabExpenseTotal').textContent = '₦' + Number(total).toLocaleString();
            document.getElementById('todayExpensesPill').textContent = 'Total Today: ₦' + Number(total).toLocaleString();

            renderManagerExpenses();
        } catch (err) {
            console.error('Failed to load today expenses', err);
        }
    }

    function renderManagerExpenses() {
        const wrap = document.getElementById('managerExpensesWrap');
        if (!todayExpenses.length) {
            wrap.innerHTML = `<div class="empty-state"><div class="icon">💸</div>No expenses logged today yet.</div>`;
            return;
        }

        wrap.innerHTML = todayExpenses.map(e => {
            const hasQty = e.quantity && Number(e.quantity) > 0;
            const hasUnitPrice = e.price_per_unit && Number(e.price_per_unit) > 0;
            let qtyBreakdown = '';
            if (hasQty) {
                const qtyVal = Number(e.quantity);
                const unitStr = e.unit ? ` ${esc(e.unit)}` : '';
                const priceStr = hasUnitPrice ? ` @ ₦${Number(e.price_per_unit).toLocaleString()}${e.unit ? '/' + esc(e.unit) : ''}` : '';
                qtyBreakdown = `${qtyVal}${unitStr}${priceStr}`;
            }

            return `
            <div style="background:#fff; border:1px solid var(--af-line); border-radius:12px; padding:12px 16px; display:flex; justify-content:space-between; align-items:center; gap:10px;">
                <div>
                    <div style="font-weight:800; font-size:14px; color:var(--af-ink);">${esc(e.title)}</div>
                    ${qtyBreakdown ? `<div style="font-size:12px; font-weight:700; color:var(--af-brown); margin-top:2px;">📦 ${qtyBreakdown}</div>` : ''}
                    <div style="font-size:12px; color:var(--af-ink-soft); margin-top:3px; display:flex; flex-wrap:wrap; gap:4px; align-items:center;">
                        <span class="pill cat" style="font-size:10px; padding:1px 6px;">${esc(e.category)}</span>
                        ${e.buyer_name ? `<span style="font-weight:700; color:var(--af-ink);">· 🛒 ${esc(e.buyer_name)}</span>` : ''}
                        ${e.note ? `<span>· ${esc(e.note)}</span>` : ''}
                        <span style="opacity:0.6;">· ${esc(e.logged_by || 'Manager')}</span>
                    </div>
                </div>
                <div style="text-align:right; flex-shrink:0;">
                    <div style="font-weight:800; font-size:15px; color:var(--af-danger);">₦${Number(e.amount).toLocaleString()}</div>
                    <button class="btn btn-ghost btn-sm" style="color:var(--af-danger); padding:2px 6px; font-size:11px;" onclick="deleteManagerExpense(${e.id})">Delete</button>
                </div>
            </div>
            `;
        }).join('');
    }

    async function deleteManagerExpense(id) {
        if (!confirm('Are you sure you want to delete this expense record?')) return;
        try {
            await apiFetch(`/expenses/${id}`, { method: 'DELETE' });
            toast('Expense record deleted');
            await loadTodayExpenses();
        } catch (err) {
            toast(err.message, true);
        }
    }
    window.deleteManagerExpense = deleteManagerExpense;

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
            document.getElementById('tabContentExpenses').style.display = tab === 'expenses' ? 'block' : 'none';
            if (tab === 'expenses') loadTodayExpenses();
        });
    });

    // Auto-sum quantity * price_per_unit
    const mgrQtyEl = document.getElementById('mgrQty');
    const mgrUnitPriceEl = document.getElementById('mgrUnitPrice');
    const mgrTotalAmountEl = document.getElementById('mgrTotalAmount');

    function calcMgrTotal() {
        const qty = parseFloat(mgrQtyEl?.value || 0);
        const price = parseFloat(mgrUnitPriceEl?.value || 0);
        if (qty > 0 && price > 0) {
            mgrTotalAmountEl.value = (qty * price).toFixed(2);
        }
    }

    if (mgrQtyEl) mgrQtyEl.addEventListener('input', calcMgrTotal);
    if (mgrUnitPriceEl) mgrUnitPriceEl.addEventListener('input', calcMgrTotal);

    // Form submission for manager expenses
    document.getElementById('managerExpenseForm').addEventListener('submit', async e => {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const payload = Object.fromEntries(formData.entries());

        try {
            await apiFetch('/expenses', {
                method: 'POST',
                body: JSON.stringify(payload),
            });
            toast('✓ Expense recorded successfully');
            form.reset();
            await loadTodayExpenses();
        } catch (err) {
            toast(err.message, true);
        }
    });

    // Category Chips Filter
    document.getElementById('categoryChipsWrap').addEventListener('click', e => {
        const chip = e.target.closest('.chip');
        if (chip) {
            selectedCat = chip.dataset.cat;
            renderCategoryChips();
            renderDishesGrid();
        }
    });

    // Search filter for dishes
    document.getElementById('dishSearch').addEventListener('input', renderDishesGrid);

    // Card click delegation for Sold Out toggle
    document.getElementById('dishesGridWrap').addEventListener('click', e => {
        const btn = e.target.closest('.btn-toggle-sold');
        if (btn) toggleSoldOut(parseInt(btn.dataset.id));
    });

    // Orders feed click delegation for status action buttons
    document.getElementById('ordersFeedWrap').addEventListener('click', e => {
        const btn = e.target.closest('.btn-order-action');
        if (btn) {
            updateOrderStatus(parseInt(btn.dataset.id), btn.dataset.status);
            return;
        }
        const delBtn = e.target.closest('.btn-delete-order');
        if (delBtn) {
            deleteManagerOrder(parseInt(delBtn.dataset.deleteId));
        }
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
