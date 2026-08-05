<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Acie Fraiche Admin</title>
    <link rel="icon" href="/assets/logo2.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700&family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <style>
        :root {
            --af-gold: #cc9933;
            --af-brown: #523700;
            --af-ink: #0f0b05;
            --af-cream: #f7f1e7;
            --af-card: #fbf7f0;
            --af-line: rgba(82, 55, 0, 0.14);
            --af-shadow: 0 18px 48px rgba(0, 0, 0, 0.12);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Manrope', system-ui, -apple-system, sans-serif;
            color: var(--af-ink);
            background:
                radial-gradient(circle at 14% 18%, rgba(204,153,51,0.12), transparent 28%),
                radial-gradient(circle at 88% 12%, rgba(82,55,0,0.09), transparent 22%),
                var(--af-cream);
            display: grid;
            grid-template-columns: 260px 1fr;
            min-height: 100vh;
            transition: grid-template-columns 0.2s ease;
        }
        body.collapsed { grid-template-columns: 72px 1fr; }
        .sidebar {
            position: sticky;
            top: 0;
            height: 100vh;
            padding: 18px;
            border-right: 1px solid var(--af-line);
            background: rgba(255,255,255,0.94);
            backdrop-filter: blur(10px);
            box-shadow: 6px 0 30px rgba(0,0,0,0.05);
            display: grid;
            align-content: start;
            gap: 12px;
            transition: width 0.2s ease, transform 0.2s ease, padding 0.2s ease;
        }
        .sidebar.collapsed { width: 72px; padding: 12px; }
        .brand {
            display: flex; align-items: center; gap: 12px; padding: 10px; border-radius: 12px;
            border: 1px solid var(--af-line); background: #fff; box-shadow: 0 10px 24px rgba(0,0,0,0.06);
            transition: opacity 0.2s ease;
        }
        .sidebar.collapsed .brand { opacity: 0; pointer-events: none; height: 0; padding: 0; margin: 0; }
        .brand img { width: 48px; height: 48px; border-radius: 12px; object-fit: contain; }
        .brand-title { margin: 0; font-family: 'Playfair Display', Georgia, serif; font-size: 20px; }
        .muted { color: rgba(0,0,0,0.64); font-size: 14px; margin: 2px 0 0; }
        .hamburger {
            display: block;
            position: fixed;
            top: 16px;
            left: 16px;
            background: #fff;
            border: 1px solid var(--af-line);
            border-radius: 12px;
            padding: 10px 12px;
            box-shadow: var(--af-shadow);
            cursor: pointer;
            z-index: 30;
        }
        nav { display: grid; gap: 6px; margin-top: 8px; }
        .nav-btn {
            border: 1px solid var(--af-line);
            background: #fff;
            color: var(--af-brown);
            padding: 11px 12px;
            border-radius: 12px;
            font-weight: 700;
            text-align: left;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: background 0.2s ease, color 0.2s ease;
        }
        .nav-btn.active { background: var(--af-brown); color: #fff; box-shadow: var(--af-shadow); }
        .nav-label { white-space: nowrap; }
        .sidebar.collapsed .nav-label { display: none; }
        main { padding: 22px; display: grid; gap: 18px; }
        .hero {
            background: linear-gradient(135deg, rgba(204,153,51,0.2), rgba(255,255,255,0.9));
            border: 1px solid var(--af-line);
            border-radius: 18px;
            padding: 18px;
            box-shadow: var(--af-shadow);
        }
        .hero-top { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
        .actions { display: flex; gap: 10px; flex-wrap: wrap; }
        button { border: none; border-radius: 12px; padding: 10px 14px; font-weight: 700; cursor: pointer; transition: transform 0.1s ease; }
        button:active { transform: translateY(1px); }
        .btn-primary { background: var(--af-brown); color: #fff; }
        .btn-ghost { background: #fff; color: var(--af-brown); border: 1px solid var(--af-line); }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-top: 12px; }
        .stat { background: #fff; border: 1px solid var(--af-line); border-radius: 14px; padding: 14px; box-shadow: 0 8px 24px rgba(0,0,0,0.05); }
        .stat h3 { margin: 0; font-size: 26px; }
        .stat span { color: rgba(0,0,0,0.6); font-size: 13px; }
        .panels { display: grid; gap: 16px; }
        .panel { display: none; }
        .panel.active { display: block; }
        .card { background: var(--af-card); border: 1px solid var(--af-line); border-radius: 16px; padding: 16px; box-shadow: var(--af-shadow); }
        .card h2 { margin: 0 0 8px; font-size: 18px; font-family: 'Playfair Display', Georgia, serif; }
        form { display: grid; gap: 8px; margin-top: 10px; }
        label { font-size: 13px; color: rgba(0,0,0,0.6); }
        input, textarea, select {
            width: 100%; padding: 10px 12px;
            border: 1px solid var(--af-line); border-radius: 12px;
            font: inherit; background: #fff;
        }
        .list { display: grid; gap: 8px; max-height: 380px; overflow: auto; }
        .item { border: 1px solid var(--af-line); border-radius: 14px; padding: 12px; background: #fff; display: flex; justify-content: space-between; align-items: center; gap: 10px; }
        .item h4 { margin: 0 0 4px; font-size: 15px; }
        .pill { display: inline-flex; align-items: center; gap: 6px; padding: 5px 10px; border-radius: 999px; border: 1px solid var(--af-line); font-size: 12px; color: rgba(0,0,0,0.65); }
        .price { font-weight: 700; color: var(--af-brown); }
        .row { display: flex; gap: 8px; flex-wrap: wrap; }
        .thumb { width: 64px; height: 64px; object-fit: cover; border-radius: 12px; border: 1px solid var(--af-line); background: #fff; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; background: #fff; border: 1px solid var(--af-line); border-radius: 14px; overflow: hidden; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid var(--af-line); }
        th { color: rgba(0,0,0,0.6); font-weight: 700; background: #fdf8ef; }
        tr:last-child td { border-bottom: none; }
        .grid-2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 14px; }
        .frame-wrap { border: 1px solid var(--af-line); border-radius: 14px; overflow: hidden; box-shadow: var(--af-shadow); background: #fff; }
        iframe { width: 100%; height: 70vh; border: none; }
        /* Admin menu cards */
        .menu-card { border: 1px solid var(--af-line); border-radius: 16px; padding: 12px; background: #fff; box-shadow: 0 10px 24px rgba(0,0,0,0.04); display: grid; gap: 8px; }
        .menu-card-head { display:flex; justify-content:space-between; gap:8px; align-items:flex-start; flex-wrap:wrap; }
        .menu-card-title { margin:0; font-size:16px; }
        .menu-tags { display:flex; gap:6px; flex-wrap:wrap; }
        .menu-pill { border: 1px solid var(--af-line); border-radius: 999px; padding: 4px 10px; font-size:12px; color: rgba(0,0,0,0.7); background:#fff; }
        .menu-pill.sold { border-color:#fca5a5; color:#b91c1c; background:#fef2f2; }
        .menu-pill.active { border-color:#bbf7d0; color:#166534; background:#f0fdf4; }
        .menu-meta { font-size:13px; color: rgba(0,0,0,0.7); }
        .menu-actions { display:flex; gap:6px; flex-wrap:wrap; }
        @media (max-width: 960px) {
            body { grid-template-columns: 1fr; }
            .sidebar { position: fixed; left: 0; top: 0; width: 260px; transform: translateX(-110%); z-index: 20; }
            .sidebar.open { transform: translateX(0); }
            .sidebar.collapsed { width: 260px; padding: 18px; }
        }
    </style>
</head>
    <body>
    <button class="hamburger" id="toggleSidebar">☰</button>
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <img src="/assets/logo2.png" alt="Acie Fraiche Logo">
            <div>
                <p class="brand-title">Acie Fraiche</p>
                <p class="muted">Admin & POS</p>
            </div>
        </div>
        <nav>
            <button class="nav-btn active" data-tab="overview"><span class="nav-label">Overview</span></button>
            <button class="nav-btn" data-tab="categories"><span class="nav-label">Categories</span></button>
            <button class="nav-btn" data-tab="menu"><span class="nav-label">Menu</span></button>
            <button class="nav-btn" data-tab="users"><span class="nav-label">Users</span></button>
            <button class="nav-btn" data-tab="orders"><span class="nav-label">Orders</span></button>
            <button class="nav-btn" data-tab="pos"><span class="nav-label">POS</span></button>
            <button class="nav-btn" data-tab="health"><span class="nav-label">Health</span></button>
            <button class="nav-btn" data-tab="site"><span class="nav-label">Public Site</span></button>
        </nav>
        <div style="margin-top:12px;">
            <div class="muted" style="margin-bottom:8px;">
                Signed in as {{ auth()->user()->name ?? 'User' }}
            </div>
            <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                @csrf
                <button type="submit" class="nav-btn" style="width:100%; justify-content:center;">
                    <span class="nav-label">Logout</span>
                </button>
            </form>
        </div>
    </aside>

    <main>
        <div class="hero">
            <div class="hero-top">
                <div>
                    <p class="muted" style="margin:0 0 4px;">Dashboard</p>
                    <h1 style="margin:0; font-family:'Playfair Display', Georgia, serif;">Today at a glance</h1>
                </div>
                <div class="actions">
                    <button class="btn-ghost" onclick="switchTab('pos')">Open POS</button>
                    <button class="btn-primary" onclick="switchTab('menu')">Add Menu Item</button>
                </div>
            </div>
            <div class="stats">
                <div class="stat"><h3 id="statCategories">0</h3><span>Categories</span></div>
                <div class="stat"><h3 id="statItems">0</h3><span>Menu items</span></div>
                <div class="stat"><h3 id="statOrders">0</h3><span>Orders today</span></div>
                <div class="stat"><h3 id="statRevenue">₦0</h3><span>Revenue today</span></div>
            </div>
        </div>

        <div class="panels">
            <section class="panel active" data-section="overview">
                <div class="card">
                    <h2>Overview</h2>
                    <p class="muted">Quick links to start.</p>
                    <div class="row" style="gap:10px; flex-wrap:wrap;">
                        <button class="btn-primary" onclick="switchTab('menu')">Manage Menu</button>
                        <button class="btn-ghost" onclick="switchTab('categories')">Manage Categories</button>
                        <button class="btn-ghost" onclick="switchTab('orders')">View Orders</button>
                        <button class="btn-ghost" onclick="switchTab('pos')">Open POS</button>
                    </div>
                    <div style="margin-top:14px; border:1px solid var(--af-line); border-radius:12px; padding:12px; background:#fff;">
                        <div style="display:flex; justify-content:space-between; gap:10px; align-items:flex-start; flex-wrap:wrap;">
                            <div>
                                <h3 style="margin:0 0 4px;">Website ordering</h3>
                                <p class="muted" id="orderAvailabilityDetail" style="margin:0;">Checking order availability...</p>
                            </div>
                            <span class="pill" id="orderAvailabilityBadge">Checking</span>
                        </div>
                        <div class="row" style="gap:8px; flex-wrap:wrap; margin-top:10px;">
                            <button class="btn-primary" data-order-mode="auto">Automatic schedule</button>
                            <button class="btn-ghost" data-order-mode="force_open">Force open</button>
                            <button class="btn-ghost" data-order-mode="force_closed">Force closed</button>
                        </div>
                        <form id="orderScheduleForm" style="display:grid; gap:10px; margin-top:12px;">
                            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:10px;">
                                <label style="display:grid; gap:5px;">
                                    <span class="muted" style="font-size:12px;">Mon-Sat opens</span>
                                    <input id="weekdayOpen" type="time" required style="padding:9px 10px; border-radius:10px; border:1px solid var(--af-line);" />
                                </label>
                                <label style="display:grid; gap:5px;">
                                    <span class="muted" style="font-size:12px;">Mon-Sat closes</span>
                                    <input id="weekdayClose" type="time" required style="padding:9px 10px; border-radius:10px; border:1px solid var(--af-line);" />
                                </label>
                                <label style="display:grid; gap:5px;">
                                    <span class="muted" style="font-size:12px;">Sunday opens</span>
                                    <input id="sundayOpen" type="time" required style="padding:9px 10px; border-radius:10px; border:1px solid var(--af-line);" />
                                </label>
                                <label style="display:grid; gap:5px;">
                                    <span class="muted" style="font-size:12px;">Sunday closes</span>
                                    <input id="sundayClose" type="time" required style="padding:9px 10px; border-radius:10px; border:1px solid var(--af-line);" />
                                </label>
                            </div>
                            <div class="row" style="gap:8px; flex-wrap:wrap;">
                                <button class="btn-primary" type="submit">Save schedule</button>
                                <small class="muted">Defaults are Mon-Sat 8am - 10pm and Sunday 12noon - 10pm.</small>
                            </div>
                        </form>
                    </div>
                </div>
            </section>

            <section class="panel" data-section="categories">
                <div class="card">
                    <h2>Categories</h2>
                    <p class="muted">Create and manage categories.</p>
                    <div class="grid-2">
                        <div class="list" id="categoryList"></div>
                        <form id="categoryForm">
                            <input type="hidden" name="category_id" id="categoryEditId" />
                            <label>Name</label>
                            <input name="name" placeholder="e.g. Mains" required />
                            <label>Description</label>
                            <input name="description" placeholder="Optional" />
                            <label>Image</label>
                            <input name="image" type="file" accept="image/*" />
                            <div class="row">
                                <button class="btn-primary" type="submit" id="categorySubmitBtn">Add Category</button>
                                <button class="btn-ghost" type="button" id="categoryCancelEditBtn" style="display:none;">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </section>

            <section class="panel" data-section="menu">
                <div class="card">
                    <h2>Menu Items</h2>
                    <p class="muted">Add items and toggle sold-out.</p>
                    <div class="grid-2">
                        <div class="list" id="menuList"></div>
                        <form id="menuForm">
                            <input type="hidden" name="menu_item_id" id="menuEditId" />
                            <label>Name</label>
                            <input name="name" placeholder="Item name" required />
                            <label>Description</label>
                            <textarea name="description" rows="2" placeholder="Optional"></textarea>
                            <label>Price (NGN)</label>
                            <input name="price" type="number" step="0.01" min="0" required />
                            <label>Category</label>
                            <select name="category_id" id="menuCategorySelect">
                                <option value="">No category</option>
                            </select>
                            <label>Image</label>
                            <input name="image" type="file" accept="image/*" />
                            <div class="row">
                                <button class="btn-primary" type="submit" id="menuSubmitBtn">Add Menu Item</button>
                                <button class="btn-ghost" type="button" id="menuCancelEditBtn" style="display:none;">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </section>

            <section class="panel" data-section="users">
                <div class="card">
                    <h2>Users</h2>
                    <p class="muted">Approve accounts and assign roles.</p>
                    <div class="list" id="usersList"></div>
                </div>
            </section>

            <section class="panel" data-section="orders">
                <div class="card">
                    <div style="display:flex; justify-content:space-between; gap:10px; align-items:flex-start; flex-wrap:wrap;">
                        <div>
                            <h2 style="margin:0 0 4px;">Orders</h2>
                            <p class="muted" style="margin:0;">Today and recent sales. Seller is recorded per order.</p>
                        </div>
                        <div class="row" style="gap:8px; align-items:center; flex-wrap:wrap;">
                            <select id="ordersExportRange" style="padding:8px 10px; border-radius:10px; border:1px solid var(--af-line);">
                                <option value="weekly">Last 7 days</option>
                                <option value="monthly" selected>Last 30 days</option>
                            </select>
                            <button class="btn-ghost" id="ordersExportBtn" style="white-space:nowrap;">Download CSV</button>
                            <button class="btn-ghost" id="purgeOrdersBtn" style="white-space:nowrap;">Delete test orders</button>
                        </div>
                    </div>

                    <div style="display:grid; gap:12px; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); margin-top:12px;">
                        <div style="border:1px solid var(--af-line); border-radius:12px; padding:12px; background:#fff;">
                            <div class="muted" style="font-size:12px; margin-bottom:6px;">Revenue (last 7 days)</div>
                            <div id="ordersChart" >
                                <div id="ordersChartBars" style="display:flex; align-items:flex-end; gap:8px; height:120px;"></div>
                                <div id="ordersChartLabels" style="display:flex; gap:8px; justify-content:space-between; font-size:11px; color:rgba(0,0,0,0.6); margin-top:6px;"></div>
                            </div>
                        </div>
                        <div style="border:1px solid var(--af-line); border-radius:12px; padding:12px; background:#fff;">
                            <div class="muted" style="font-size:12px;">Snapshot</div>
                            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap:10px; margin-top:8px;">
                                <div class="stat" style="margin:0; box-shadow:none; border-color:var(--af-line);">
                                    <h3 id="statOrders">0</h3><span>Orders today</span>
                                </div>
                                <div class="stat" style="margin:0; box-shadow:none; border-color:var(--af-line);">
                                    <h3 id="statRevenue">₦0</h3><span>Revenue today</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="frame-wrap" style="margin-top:12px; border-color:var(--af-line); box-shadow:none;">
                        <div style="overflow:auto; max-height:420px;">
                            <table id="ordersTable" style="margin:0;">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Seller</th>
                                        <th>Status</th>
                                        <th>Total</th>
                                        <th>Channel</th>
                                        <th>Kitchen</th>
                                        <th>ETA</th>
                                        <th>Action</th>
                                        <th>When</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            <section class="panel" data-section="pos">
                <div class="card">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:10px; flex-wrap:wrap;">
                        <div>
                            <h2 style="margin:0 0 4px;">POS</h2>
                            <p class="muted" style="margin:0;">Scan, add, and print quickly with consistent layout.</p>
                        </div>
                        <div style="border:1px solid var(--af-line); border-radius:12px; padding:10px 12px; background:#fff; min-width:180px; text-align:right;">
                            <div class="muted" style="font-size:12px;">Cart total</div>
                            <div style="font-weight:800; color:var(--af-brown); font-size:18px;" id="posCartTotal">₦0</div>
                        </div>
                    </div>

                    <div style="display:grid; gap:12px; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); margin-top:12px;">
                        <div style="border:1px solid var(--af-line); border-radius:14px; padding:12px; background:#fff;">
                            <label style="display:block; font-size:13px; color:rgba(0,0,0,0.7); margin-bottom:6px;">Scan / Enter barcode</label>
                            <input id="posBarcodeInput" placeholder="Focus here and scan" style="width:100%; padding:12px; border-radius:12px; border:1px solid var(--af-line); font-size:16px;" />
                            <small class="muted" id="posScanStatus" style="display:block; margin-top:6px;">Ready to scan.</small>
                            <div id="posLookupResult" class="item" style="display:none; flex-direction:column; align-items:flex-start; margin-top:10px;"></div>
                            <div id="posSavedCustomers" style="margin-top:10px; display:flex; gap:6px; flex-wrap:wrap;"></div>
                        </div>
                        <div style="border:1px solid var(--af-line); border-radius:14px; padding:12px; background:#fff;">
                            <div style="display:flex; justify-content:space-between; align-items:center; gap:8px;">
                                <h3 style="margin:0;">POS Cart</h3>
                                <span class="pill">Auto-print</span>
                            </div>
                            <div id="posCartList" class="list" style="margin-top:8px;"></div>
                            <div style="margin-top:10px; border-top:1px dashed var(--af-line); padding-top:10px; display:grid; gap:6px;">
                                <div style="display:flex; justify-content:space-between;"><span class="muted">Subtotal</span><strong id="posSubtotal">₦0</strong></div>
                                <div style="display:flex; gap:8px; align-items:center; justify-content:space-between;">
                                    <span class="muted">Discount</span>
                                    <input id="posDiscount" type="number" min="0" step="1" value="0" style="width:120px; padding:8px; border-radius:10px; border:1px solid var(--af-line);" />
                                </div>
                                <div style="display:flex; gap:8px; align-items:center; justify-content:space-between;">
                                    <span class="muted">Tax / Fee</span>
                                    <input id="posTax" type="number" min="0" step="1" value="0" style="width:120px; padding:8px; border-radius:10px; border:1px solid var(--af-line);" />
                                </div>
                                <div style="display:flex; justify-content:space-between; align-items:center; font-weight:700;">
                                    <span>Total</span><span id="posGrandTotal">₦0</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div style="display:grid; gap:12px; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-top:12px;">
                        <div style="display:grid; gap:6px;">
                            <label style="font-size:13px; color:rgba(0,0,0,0.7);">Customer name (optional)</label>
                            <input id="posCustomerName" placeholder="Walk-in" style="padding:10px; border-radius:12px; border:1px solid var(--af-line);" />
                        </div>
                        <div style="display:grid; gap:6px;">
                            <label style="font-size:13px; color:rgba(0,0,0,0.7);">Customer phone</label>
                            <input id="posCustomerPhone" placeholder="080..." style="padding:10px; border-radius:12px; border:1px solid var(--af-line);" />
                        </div>
                        <div style="display:grid; gap:6px;">
                            <label style="font-size:13px; color:rgba(0,0,0,0.7);">Payment method</label>
                            <select id="posPaymentMethod" style="padding:10px; border-radius:12px; border:1px solid var(--af-line);">
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="transfer">Transfer</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div style="display:grid; gap:6px;">
                            <label style="font-size:13px; color:rgba(0,0,0,0.7);">Kitchen handoff</label>
                            <label class="pill" style="padding:8px 10px; display:flex; align-items:center; gap:8px; border-radius:12px; border:1px solid var(--af-line); background:#fff;">
                                <input id="posSendKitchen" type="checkbox" checked style="width:16px; height:16px; accent-color: var(--af-brown);">
                                <span class="muted" style="color:var(--af-brown);">Send to kitchen immediately</span>
                            </label>
                            <small class="muted">Uncheck if you need to confirm on WhatsApp before cooking.</small>
                        </div>
                    </div>

                    <div style="margin-top:10px; display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                        <button class="btn-primary" id="posCheckoutBtn">Complete Sale & Print Receipt</button>
                        <small class="muted">Saves order with seller info and prints a receipt.</small>
                        <button class="btn-ghost" id="posParkBtn">Park ticket</button>
                    </div>

                    <div style="margin-top:12px; border:1px solid var(--af-line); border-radius:12px; padding:10px; background:#fff;">
                        <div style="display:flex; justify-content:space-between; align-items:center; gap:8px;">
                            <strong>Parked tickets</strong>
                            <small class="muted">Hold & resume orders</small>
                        </div>
                        <div id="posParkedList" class="list" style="margin-top:8px;"></div>
                    </div>
                </div>
            </section>

            <section class="panel" data-section="health">
                <div class="card">
                    <h2>API Health</h2>
                    <p class="muted">Live check of the backend.</p>
                    <p id="healthDetail" class="muted">Checking...</p>
                </div>
            </section>

            <section class="panel" data-section="site">
                <div class="card">
                    <h2>Public Site Preview</h2>
                    <p class="muted">This loads the current landing page so you can keep visuals aligned.</p>
                    <div class="frame-wrap">
                        <iframe src="/live.html" title="Public site preview"></iframe>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <script>
    (function () {
        try {
        if (!window.fetch) {
            alert('Your browser is too old to run the admin. Please use a modern browser.');
            return;
        }

        const sidebar = document.getElementById('sidebar');
        const toggleSidebar = document.getElementById('toggleSidebar');
        const navBtns = Array.from(document.querySelectorAll('.nav-btn'));
        const panels = Array.from(document.querySelectorAll('.panel'));

        const categoryList = document.getElementById('categoryList');
        const categoryForm = document.getElementById('categoryForm');
        const categoryEditId = document.getElementById('categoryEditId');
        const categorySubmitBtn = document.getElementById('categorySubmitBtn');
        const categoryCancelEditBtn = document.getElementById('categoryCancelEditBtn');
        const menuList = document.getElementById('menuList');
        const menuForm = document.getElementById('menuForm');
        const menuEditId = document.getElementById('menuEditId');
        const menuSubmitBtn = document.getElementById('menuSubmitBtn');
        const menuCancelEditBtn = document.getElementById('menuCancelEditBtn');
        const menuCategorySelect = document.getElementById('menuCategorySelect');
        const usersList = document.getElementById('usersList');
        const ordersTableBody = document.querySelector('#ordersTable tbody');
        const ordersChartBars = document.getElementById('ordersChartBars');
        const ordersChartLabels = document.getElementById('ordersChartLabels');
        const purgeOrdersBtn = document.getElementById('purgeOrdersBtn');
        const ordersExportBtn = document.getElementById('ordersExportBtn');
        const ordersExportRange = document.getElementById('ordersExportRange');
        const statCategories = document.getElementById('statCategories');
        const statItems = document.getElementById('statItems');
        const statOrders = document.getElementById('statOrders');
        const statRevenue = document.getElementById('statRevenue');
        const healthDetail = document.getElementById('healthDetail');
        const roles = ['admin', 'staff', 'pos', 'kitchen', 'desk'];
        const posBarcodeInput = document.getElementById('posBarcodeInput');
        const posLookupResult = document.getElementById('posLookupResult');
        const posScanStatus = document.getElementById('posScanStatus');
        const posCartList = document.getElementById('posCartList');
        const posCartTotal = document.getElementById('posCartTotal');
        const posCustomerName = document.getElementById('posCustomerName');
        const posCustomerPhone = document.getElementById('posCustomerPhone');
        const posPaymentMethod = document.getElementById('posPaymentMethod');
        const posCheckoutBtn = document.getElementById('posCheckoutBtn');
        const posDiscount = document.getElementById('posDiscount');
        const posTax = document.getElementById('posTax');
        const posSubtotal = document.getElementById('posSubtotal');
        const posGrandTotal = document.getElementById('posGrandTotal');
        const posSendKitchen = document.getElementById('posSendKitchen');
        const posParkBtn = document.getElementById('posParkBtn');
        const posParkedList = document.getElementById('posParkedList');
        const posSavedCustomers = document.getElementById('posSavedCustomers');
        const barcodeCache = {};
        const kitchenStatusMeta = {
            pending: { label: 'Awaiting send', color: '#b45309', bg: 'rgba(180,83,9,0.08)' },
            queued: { label: 'Queued', color: '#523700', bg: 'rgba(82,55,0,0.08)' },
            prepping: { label: 'In progress', color: '#0f5132', bg: 'rgba(15,81,50,0.1)' },
            ready: { label: 'Ready', color: '#0f5132', bg: 'rgba(15,81,50,0.1)' },
            served: { label: 'Served', color: 'rgba(0,0,0,0.6)', bg: '#f2f2f2' },
        };
        let menuCacheReady = false;
        let lastSummary = null;
        let posCart = [];
        let lastLookup = null;
        let isInteracting = false;
        let interactionTimeout;
        let posLookupInFlight = false;
        let scanDebounce = null;
        let ordersCache = [];
        let categoriesCache = [];
        let menuItemsCache = [];

        const createPoller = (task, intervalMs, options = {}) => {
            const { immediate = true, runWhileHidden = false, onError = null } = options;
            let timer = null;
            let running = false;

            const shouldRun = () => {
                if (runWhileHidden) return true;
                if (document.visibilityState === 'hidden') return false;
                return true;
            };

            const tick = async () => {
                if (running || !shouldRun()) return;
                running = true;
                try {
                    await task();
                } catch (err) {
                    if (onError) {
                        onError(err);
                    } else {
                        console.warn('Poller task failed', err);
                    }
                } finally {
                    running = false;
                }
            };

            const start = () => {
                if (timer) return;
                if (immediate) tick();
                timer = setInterval(tick, intervalMs);
            };

            const stop = () => {
                if (timer) {
                    clearInterval(timer);
                    timer = null;
                }
            };

            document.addEventListener('visibilitychange', () => {
                if (timer && shouldRun()) tick();
            });

            return { start, stop, isRunning: () => !!timer };
        };

        const markInteracting = () => {
            isInteracting = true;
            clearTimeout(interactionTimeout);
            interactionTimeout = setTimeout(() => { isInteracting = false; }, 2000);
        };

        const loadSavedCustomers = () => {
            try {
                const data = JSON.parse(localStorage.getItem('pos_saved_customers') || '[]');
                return Array.isArray(data) ? data.slice(0, 6) : [];
            } catch { return []; }
        };

        const persistSavedCustomers = (list) => {
            localStorage.setItem('pos_saved_customers', JSON.stringify(list.slice(0, 6)));
        };

        const loadParkedTickets = () => {
            try {
                const data = JSON.parse(localStorage.getItem('pos_parked_tickets') || '[]');
                return Array.isArray(data) ? data : [];
            } catch { return []; }
        };

        const persistParkedTickets = (list) => {
            localStorage.setItem('pos_parked_tickets', JSON.stringify(list));
        };

        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : null;

        const escapeAttr = (value = '') => String(value)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');

        const apiFetch = (url, options = {}) => {
            const headers = {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                ...(options.headers || {}),
            };
            return fetch(url, {
                credentials: 'same-origin',
                cache: options.cache ?? 'no-store',
                ...options,
                headers,
            });
        };

        const setBusy = (btn, busy) => {
            if (!btn) return;
            btn.disabled = busy;
            if (busy) {
                btn.dataset.originalText = btn.dataset.originalText || btn.textContent;
                btn.textContent = 'Working...';
            } else if (btn.dataset.originalText) {
                btn.textContent = btn.dataset.originalText;
            }
        };

        const toast = (message, tone = 'ok') => {
            const el = document.createElement('div');
            el.textContent = message;
            el.style.position = 'fixed';
            el.style.right = '16px';
            el.style.bottom = '16px';
            el.style.zIndex = '9999';
            el.style.padding = '12px 14px';
            el.style.borderRadius = '10px';
            el.style.fontWeight = '700';
            el.style.boxShadow = '0 16px 38px rgba(0,0,0,0.18)';
            el.style.background = tone === 'error' ? '#fee2e2' : '#0f0b05';
            el.style.color = tone === 'error' ? '#991b1b' : '#fff';
            document.body.appendChild(el);
            setTimeout(() => el.remove(), 2600);
        };

        const safeRequest = async (url, options = {}) => {
            const res = await apiFetch(url, options);
            if (!res.ok) {
                let message = `Request failed (${res.status})`;
                try {
                    const data = await res.clone().json();
                    if (data && data.message) message = data.message;
                } catch (err) {
                    const text = await res.text().catch(() => '');
                    if (text) message = text;
                }
                throw new Error(message);
            }
            return res;
        };

        const runAction = async (btn, fn) => {
            setBusy(btn, true);
            try {
                await fn();
            } catch (e) {
                toast(e.message || 'Could not complete that action.', 'error');
                console.error(e);
            } finally {
                setBusy(btn, false);
            }
        };

        if (toggleSidebar) {
            toggleSidebar.addEventListener('click', () => {
                const isMobile = window.innerWidth <= 960;
                if (isMobile) {
                    sidebar.classList.toggle('open');
                } else {
                    document.body.classList.toggle('collapsed');
                    sidebar.classList.toggle('collapsed');
                }
                toggleSidebar.setAttribute('aria-expanded', sidebar.classList.contains('open') ? 'true' : 'false');
            });
        }

        function switchTab(tab) {
            navBtns.forEach(b => b.classList.toggle('active', b.dataset.tab === tab));
            panels.forEach(p => p.classList.toggle('active', p.dataset.section === tab));
            if (window.innerWidth <= 960) {
                sidebar.classList.remove('open');
                toggleSidebar.setAttribute('aria-expanded', 'false');
            }
        }
        navBtns.forEach(btn => btn.addEventListener('click', () => switchTab(btn.dataset.tab)));

        async function checkHealth() {
            try {
                const res = await apiFetch('/api/health');
                if (!res.ok) throw new Error('bad status');
                healthDetail.textContent = 'API responding normally.';
            } catch (e) {
                healthDetail.textContent = 'API not reachable. Check server.';
            }
        }

        function renderCategories(categories) {
            categoriesCache = categories;
            categoryList.innerHTML = categories.map(cat => `
                <div class="item">
                    <div>
                        ${cat.image_url ? `<img class="thumb" src="${escapeAttr(cat.image_url)}" alt="${escapeAttr(cat.name)}">` : ''}
                        <h4>${escapeAttr(cat.name)}</h4>
                        <small class="muted">${escapeAttr(cat.description || '')}</small>
                    </div>
                    <span class="pill" style="border-color:${cat.is_active ? '#bbf7d0' : '#fca5a5'};color:${cat.is_active ? '#166534' : '#b91c1c'}">
                        ${cat.is_active ? 'Active' : 'Inactive'}
                    </span>
                    <div class="row" style="gap:6px;">
                        <button class="btn-ghost" onclick="editCategory(${cat.id})">Edit</button>
                        <button class="btn-ghost" onclick="deleteCategory(${cat.id}, this)">Delete</button>
                    </div>
                </div>
            `).join('');

            menuCategorySelect.innerHTML = `<option value="">No category</option>` + categories.map(cat => `<option value="${cat.id}">${escapeAttr(cat.name)}</option>`).join('');
            statCategories.textContent = categories.length;
        }

        function resetCategoryForm() {
            categoryForm.reset();
            categoryEditId.value = '';
            categorySubmitBtn.textContent = 'Add Category';
            categoryCancelEditBtn.style.display = 'none';
        }

        window.editCategory = (id) => {
            const category = categoriesCache.find(cat => Number(cat.id) === Number(id));
            if (!category) {
                toast('Category not found. Refresh and try again.', 'error');
                return;
            }

            categoryEditId.value = category.id;
            categoryForm.elements.name.value = category.name || '';
            categoryForm.elements.description.value = category.description || '';
            categoryForm.elements.image.value = '';
            categorySubmitBtn.textContent = 'Save Category';
            categoryCancelEditBtn.style.display = '';
            categoryForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
            categoryForm.elements.name.focus();
        };

        function renderMenu(items) {
            menuItemsCache = items;
            const cards = items.map(item => {
                const safeBarcode = escapeAttr(item.barcode || '');
                const safeName = escapeAttr(item.name || '');
                if (item.barcode) {
                    barcodeCache[item.barcode] = item;
                }
                return `
                    <div class="menu-card">
                        <div class="menu-card-head">
                            <div style="display:flex; gap:10px; align-items:center;">
                                ${item.image_url ? `<img class="thumb" src="${escapeAttr(item.image_url)}" alt="${safeName}">` : ''}
                                <div>
                                    <p class="menu-card-title">${safeName}</p>
                                    <div class="menu-tags">
                                        <span class="menu-pill">₦${Number(item.price).toLocaleString()}</span>
                                    <span class="menu-pill">${item.category && item.category.name ? escapeAttr(item.category.name) : 'Uncategorized'}</span>
                                        <span class="menu-pill ${item.is_sold_out ? 'sold' : 'active'}">${item.is_sold_out ? 'Sold Out' : 'Available'}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="menu-tags">
                                <span class="menu-pill">Barcode: ${safeBarcode || 'Not set'}</span>
                                <button class="btn-ghost" ${item.barcode ? '' : 'disabled'} data-action="copy" data-barcode="${safeBarcode}">Copy</button>
                                <button class="btn-ghost" ${item.barcode ? '' : 'disabled'} data-action="download" data-barcode="${safeBarcode}" data-name="${safeName}">Download</button>
                                <button class="btn-ghost" onclick="regenBarcode(${item.id}, this)">Regenerate</button>
                            </div>
                        </div>
                        <p class="menu-meta">${escapeAttr(item.description || 'No description yet.')}</p>
                        <div class="menu-actions">
                            <button class="btn-ghost" onclick="toggleSoldOut(${item.id}, this)">${item.is_sold_out ? 'Mark Available' : 'Mark Sold Out'}</button>
                            <button class="btn-ghost" onclick="editMenuItem(${item.id})">Edit</button>
                            <button class="btn-ghost" onclick="deleteMenuItem(${item.id}, this)">Delete</button>
                        </div>
                    </div>
                `;
            });
            menuList.innerHTML = cards.join('');
            statItems.textContent = items.length;
        }

        function resetMenuForm() {
            menuForm.reset();
            menuEditId.value = '';
            menuSubmitBtn.textContent = 'Add Menu Item';
            menuCancelEditBtn.style.display = 'none';
        }

        window.editMenuItem = (id) => {
            const item = menuItemsCache.find(menuItem => Number(menuItem.id) === Number(id));
            if (!item) {
                toast('Menu item not found. Refresh and try again.', 'error');
                return;
            }

            menuEditId.value = item.id;
            menuForm.elements.name.value = item.name || '';
            menuForm.elements.description.value = item.description || '';
            menuForm.elements.price.value = item.price ?? '';
            menuForm.elements.category_id.value = item.category_id || '';
            menuForm.elements.image.value = '';
            menuSubmitBtn.textContent = 'Save Menu Item';
            menuCancelEditBtn.style.display = '';
            menuForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
            menuForm.elements.name.focus();
        };

        function renderKitchenStatus(status) {
            const meta = kitchenStatusMeta[status] ?? { label: status || 'pending', color: '#523700', bg: 'rgba(82,55,0,0.12)' };
            return `<span class="pill" style="background:${meta.bg}; color:${meta.color}; border-color:${meta.bg};">${meta.label}</span>`;
        }

        function renderEta(order) {
            if (!order.kitchen_eta_minutes && !order.kitchen_eta_at) return '<span class="muted">—</span>';
            const eta = order.kitchen_eta_minutes ? `${order.kitchen_eta_minutes}m` : '';
            const at = order.kitchen_eta_at ? ` · ${new Date(order.kitchen_eta_at).toLocaleTimeString()}` : '';
            return `<span class="pill" style="background:#fff; border-color:var(--af-line);">${eta}${at}</span>`;
        }

        if (menuList) {
            menuList.addEventListener('click', (e) => {
                const btn = e.target.closest('button[data-action]');
                if (!btn) return;
                const { action, barcode = '', name = '' } = btn.dataset;
                if (action === 'copy') {
                    copyBarcode(barcode);
                }
                if (action === 'download') {
                    printBarcode(barcode, name);
                }
            });
        }

        function renderOrders(orders) {
            ordersCache = orders;
            let revenue = 0;
            ordersTableBody.innerHTML = orders.map(o => {
                revenue += Number(o.total || 0);
                const kitchenBadge = renderKitchenStatus(o.kitchen_status);
                const etaText = renderEta(o);
                return `
                    <tr>
                        <td>${o.code}</td>
                        <td>${o.creator && o.creator.name ? o.creator.name : '—'}</td>
                        <td>${o.status}</td>
                        <td>₦${Number(o.total).toLocaleString()}</td>
                        <td>${o.channel}</td>
                        <td>${kitchenBadge}</td>
                        <td>${etaText}</td>
                        <td>
                            <div class="row" style="gap:6px; flex-wrap:wrap;">
                                ${o.kitchen_status === 'pending' ? `<button class="btn-ghost" onclick="sendOrderToKitchen(${o.id}, this)">Send</button>` : ''}
                                <button class="btn-ghost" onclick="setKitchenEta(${o.id}, 15, this)">ETA 15m</button>
                                <button class="btn-ghost" onclick="setKitchenStatus(${o.id}, 'ready', this)">Ready</button>
                                <button class="btn-ghost" onclick="shareOrderWhatsapp(${o.id})">WhatsApp</button>
                                ${o.status === 'pending' ? `<button class="btn-ghost" onclick="deletePendingOrder(${o.id}, this)">Delete</button>` : ''}
                            </div>
                        </td>
                        <td>${new Date(o.created_at).toLocaleString()}</td>
                    </tr>
                `;
            }).join('');
        }

        function upsertOrderCache(order) {
            if (!order) return;
            ordersCache = [order, ...ordersCache.filter(o => o.id !== order.id)];
            renderOrders(ordersCache);
        }

        window.sendOrderToKitchen = async (id, btn) => {
            const note = prompt('Add kitchen note (optional)', '') || null;
            await runAction(btn, async () => {
                const res = await safeRequest(`/api/orders/${id}/send-to-kitchen`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ note }),
                });
                const updated = await res.json();
                upsertOrderCache(updated);
            });
        };

        window.setKitchenEta = async (id, minutes, btn) => {
            const order = ordersCache.find(o => o.id === id);
            await runAction(btn, async () => {
                const res = await safeRequest(`/api/orders/${id}/kitchen-status`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        kitchen_status: order?.kitchen_status || 'queued',
                        eta_minutes: minutes,
                        note: order?.kitchen_note || null,
                    }),
                });
                const updated = await res.json();
                upsertOrderCache(updated);
            });
        };

        window.setKitchenStatus = async (id, status, btn) => {
            const order = ordersCache.find(o => o.id === id);
            await runAction(btn, async () => {
                const res = await safeRequest(`/api/orders/${id}/kitchen-status`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        kitchen_status: status,
                        eta_minutes: order?.kitchen_eta_minutes || null,
                        note: order?.kitchen_note || null,
                    }),
                });
                const updated = await res.json();
                upsertOrderCache(updated);
            });
        };

        window.deletePendingOrder = async (id, btn) => {
            const order = ordersCache.find(o => o.id === id);
            const label = order?.code ? `order ${order.code}` : 'this pending order';
            if (!confirm(`Delete ${label}? This can only be done before approval and cannot be undone.`)) {
                return;
            }

            const originalText = btn?.textContent;
            if (btn) btn.textContent = 'Deleting...';
            await runAction(btn, async () => {
                await safeRequest(`/api/orders/${id}`, { method: 'DELETE' });
                ordersCache = ordersCache.filter(o => o.id !== id);
                renderOrders(ordersCache);
                await loadOrderSummary();
                toast(`${order?.code || 'Order'} deleted.`);
            });
            if (btn && originalText) btn.textContent = originalText;
        };

        window.shareOrderWhatsapp = (id) => {
            const order = ordersCache.find(o => o.id === id);
            if (!order) {
                alert('Order not found yet.');
                return;
            }
            const items = (order.items || []).map(item => `• ${item.quantity}x ${item.name}`).join('\n');
            const kitchenMeta = kitchenStatusMeta[order.kitchen_status] || {};
            const lines = [
                `Order ${order.code} (${order.channel})`,
                order.customer_name || order.customer_phone ? `Customer: ${order.customer_name || 'Walk-in'}${order.customer_phone ? ' · ' + order.customer_phone : ''}` : '',
                `Total: ₦${Number(order.total || 0).toLocaleString()} (${order.status})`,
                `Kitchen: ${kitchenMeta.label || order.kitchen_status || 'pending'}${order.kitchen_eta_minutes ? ` · ETA ${order.kitchen_eta_minutes}m` : ''}`,
                order.kitchen_note ? `Note: ${order.kitchen_note}` : '',
                items ? 'Items:\n' + items : '',
            ].filter(Boolean).join('\n');
            const url = `https://wa.me/?text=${encodeURIComponent(lines)}`;
            const win = window.open(url, '_blank');
            if (!win) {
                navigator.clipboard?.writeText(lines).then(() => alert('Copied to clipboard. Paste in WhatsApp.'));
            }
        };

        function renderSummary(summary) {
            if (summary) {
                lastSummary = summary;
                statOrders.textContent = summary.today_orders ?? 0;
                statRevenue.textContent = '₦' + Number(summary.today_revenue || 0).toLocaleString();
                renderChart(summary.series || []);
            }
        }

        function renderChart(series) {
            if (!ordersChartBars || !ordersChartLabels) return;
            if (!Array.isArray(series) || !series.length) {
                ordersChartBars.innerHTML = '<div class="muted">No data</div>';
                ordersChartLabels.innerHTML = '';
                return;
            }
            const maxRevenue = Math.max(...series.map(s => Number(s.revenue || 0)), 1);
            ordersChartBars.innerHTML = series.map(s => {
                const height = Math.max(4, (Number(s.revenue || 0) / maxRevenue) * 100);
                return `<div title="₦${Number(s.revenue || 0).toLocaleString()}" style="flex:1; min-width:10px; background:var(--af-brown); height:${height}%; border-radius:6px 6px 2px 2px;"></div>`;
            }).join('');
            ordersChartLabels.innerHTML = series.map(s => {
                const label = s.day ? s.day.slice(5) : '';
                return `<span style="flex:1; text-align:center;">${label}</span>`;
            }).join('');
        }

        async function loadCategories() {
            try {
                const res = await safeRequest('/api/categories');
                const data = await res.json();
                renderCategories(data);
            } catch (e) {
                console.error(e);
                categoryList.innerHTML = '<div class="muted">Could not load categories.</div>';
            }
        }

        async function loadMenu() {
            try {
                const res = await safeRequest('/api/menu-items');
                const data = await res.json();
                // Refresh barcode cache for instant lookups
                Object.keys(barcodeCache).forEach(key => delete barcodeCache[key]);
                data.forEach(item => {
                    if (item.barcode) {
                        barcodeCache[item.barcode] = item;
                    }
                });
                menuCacheReady = true;
                renderMenu(data);
            } catch (e) {
                console.error(e);
                menuList.innerHTML = '<div class="muted">Could not load menu items.</div>';
            }
        }

        async function loadOrders() {
            try {
                const res = await safeRequest('/api/orders');
                const payload = await res.json();
                const data = payload.data || payload; // paginate or flat
                renderOrders(data);
                if (!lastSummary) {
                    const fallbackRev = data.reduce((sum, o) => sum + Number(o.total || 0), 0);
                    statOrders.textContent = data.length;
                    statRevenue.textContent = '₦' + fallbackRev.toLocaleString();
                }
            } catch (e) {
                console.error(e);
                ordersTableBody.innerHTML = '<tr><td colspan="6">Could not load orders.</td></tr>';
            }
        }

        async function loadOrderSummary() {
            try {
                const res = await safeRequest('/api/orders/summary');
                const data = await res.json();
                renderSummary(data);
            } catch (e) {
                console.error('Could not load summary', e);
            }
        }

        async function loadUsers() {
            if (!usersList) return;
            try {
                const res = await safeRequest('/api/users');
                const data = await res.json();
                renderUsers(data);
            } catch (e) {
                console.error(e);
                usersList.innerHTML = '<div class="muted">Could not load users.</div>';
            }
        }

        function renderUsers(users) {
            if (!usersList) return;
            usersList.innerHTML = users.map(u => `
                <div class="item" style="align-items:flex-start;">
                    <div>
                        <h4>${u.name}</h4>
                        <div class="muted">${u.email}</div>
                        <div class="row" style="gap:6px;margin-top:6px;">
                            <span class="pill" style="border-color:${u.is_active ? '#bbf7d0' : '#fca5a5'};color:${u.is_active ? '#166534' : '#b91c1c'}">
                                ${u.is_active ? 'Active' : 'Pending'}
                            </span>
                            <span class="pill">Role: ${u.role || 'staff'}</span>
                        </div>
                    </div>
                    <div class="row" style="gap:6px;">
                        <select onchange="updateUserRole(${u.id}, this.value)" value="${u.role || 'staff'}">
                            ${roles.map(r => `<option value="${r}" ${r === (u.role || 'staff') ? 'selected' : ''}>${r}</option>`).join('')}
                        </select>
                        <button class="btn-ghost" onclick="toggleUserActive(${u.id}, ${u.is_active ? 'false' : 'true'})">
                            ${u.is_active ? 'Deactivate' : 'Approve'}
                        </button>
                        <button class="btn-ghost" onclick="deleteUser(${u.id})">Delete</button>
                    </div>
                </div>
            `).join('');
        }

        function setPosStatus(message, tone = 'muted') {
            if (!posScanStatus) return;
            posScanStatus.textContent = message;
            posScanStatus.style.color = tone === 'error' ? '#b91c1c' : 'rgba(0,0,0,0.6)';
        }

        function renderPosCart() {
            if (!posCartList || !posCartTotal) return;
            if (!posCart.length) {
                posCartList.innerHTML = '<div class="item">Cart is empty.</div>';
                posCartTotal.textContent = '₦0';
                if (posSubtotal) posSubtotal.textContent = '₦0';
                if (posGrandTotal) posGrandTotal.textContent = '₦0';
                return;
            }

            const total = computePosTotal();
            posCartList.innerHTML = posCart.map((item, index) => {
                const line = item.price * item.qty;
                return `
                    <div class="item" style="align-items:center;">
                        <div>
                            <h4>${item.name}</h4>
                            <div class="row" style="gap:6px;">
                                <span class="pill">Barcode: ${item.barcode || 'n/a'}</span>
                                <span class="pill">₦${Number(item.price).toLocaleString()} × ${item.qty}</span>
                            </div>
                        </div>
                        <div class="row" style="gap:6px;">
                            <button class="btn-ghost" onclick="updatePosQty(${index}, 'dec')">-</button>
                            <button class="btn-ghost" onclick="updatePosQty(${index}, 'inc')">+</button>
                            <button class="btn-ghost" onclick="updatePosQty(${index}, 'remove')">Remove</button>
                        </div>
                    </div>
                `;
            }).join('');

            posCartTotal.textContent = '₦' + total.toLocaleString();
            renderTotals();
        }

        function addToPosCart(item) {
            const existing = posCart.find((i) => i.id === item.id);
            if (existing) {
                existing.qty += 1;
            } else {
                posCart.push({
                    id: item.id,
                    name: item.name,
                    price: Number(item.price) || 0,
                    barcode: item.barcode,
                    qty: 1,
                });
            }
            renderPosCart();
        }

        function computePosTotal() {
            return posCart.reduce((sum, item) => sum + (item.price * item.qty), 0);
        }

        function computeGrandTotal() {
            const subtotal = computePosTotal();
            const discount = Number(posDiscount ? posDiscount.value : 0) || 0;
            const tax = Number(posTax ? posTax.value : 0) || 0;
            return Math.max(0, subtotal - discount + tax);
        }

        function renderTotals() {
            const subtotal = computePosTotal();
            const grand = computeGrandTotal();
            if (posSubtotal) posSubtotal.textContent = '₦' + subtotal.toLocaleString();
            if (posGrandTotal) posGrandTotal.textContent = '₦' + grand.toLocaleString();
            if (posCartTotal) posCartTotal.textContent = '₦' + grand.toLocaleString();
        }

        window.updatePosQty = (index, action) => {
            const item = posCart[index];
            if (!item) return;
            if (action === 'inc') item.qty += 1;
            if (action === 'dec') item.qty = Math.max(1, item.qty - 1);
            if (action === 'remove') posCart.splice(index, 1);
            renderPosCart();
        };

        function showLookupResult(item) {
            if (!posLookupResult) return;
            lastLookup = item;
            posLookupResult.style.display = 'flex';
            posLookupResult.innerHTML = `
                <div style="display:flex; flex-direction:column; gap:6px;">
                    <h4 style="margin:0;">${item.name}</h4>
                    <div class="row" style="gap:6px; flex-wrap:wrap;">
                        <span class="pill">Price: ₦${Number(item.price).toLocaleString()}</span>
                        <span class="pill">Barcode: ${item.barcode}</span>
                        <span class="pill">${item.category && item.category.name ? item.category.name : 'Uncategorized'}</span>
                    </div>
                    <button class="btn-primary" data-add-pos-item>Add to cart</button>
                </div>
            `;
        }

        function showLookupError(message) {
            if (!posLookupResult) return;
            lastLookup = null;
            posLookupResult.style.display = 'flex';
            posLookupResult.innerHTML = `<div class="muted">${message}</div>`;
        }

        async function lookupBarcode(barcode, { addToCartOnSuccess = false } = {}) {
            if (!barcode) return;

            // Instant local cache hit (kept fresh by loadMenu)
            if (barcodeCache[barcode]) {
                const item = barcodeCache[barcode];
                if (item.is_sold_out || item.stock === 0) {
                    const msg = 'Item found but currently marked sold out.';
                    showLookupError(msg);
                    setPosStatus(msg, 'error');
                    return;
                }
                showLookupResult(item);
                if (addToCartOnSuccess) {
                    addToPosCart(item);
                    setPosStatus(`Added ${item.name}. Ready for next scan.`);
                    if (posBarcodeInput) {
                        posBarcodeInput.value = '';
                        posBarcodeInput.focus();
                    }
                } else {
                    setPosStatus('Found. Price pulled live; add to cart.');
                }
                return;
            }

            if (posLookupInFlight) return;
            posLookupInFlight = true;
            setPosStatus('Looking up barcode...');
            try {
                const res = await apiFetch(`/api/menu-items/lookup?barcode=${encodeURIComponent(barcode)}`);
                if (!res.ok) {
                    const msg = res.status === 404
                        ? 'No item found for this barcode.'
                        : res.status === 409
                            ? 'Item found but currently marked sold out.'
                            : 'Could not look up this barcode.';
                    showLookupError(msg);
                    setPosStatus(msg, 'error');
                    return;
                }
                const item = await res.json();
                if (item.barcode) barcodeCache[item.barcode] = item;
                showLookupResult(item);
                if (addToCartOnSuccess) {
                    addToPosCart(item);
                    setPosStatus(`Added ${item.name}. Ready for next scan.`);
                    if (posBarcodeInput) {
                        posBarcodeInput.value = '';
                        posBarcodeInput.focus();
                    }
                } else {
                    setPosStatus('Found. Price pulled live; add to cart.');
                }
            } catch (e) {
                showLookupError('Lookup failed. Check connection.');
                setPosStatus('Lookup failed.', 'error');
            } finally {
                posLookupInFlight = false;
                if (posBarcodeInput) posBarcodeInput.select();
            }
        }

        categoryForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = new FormData(categoryForm);
            const editingId = categoryEditId.value;
            form.delete('category_id');
            if (form.get('image') && form.get('image').size === 0) {
                form.delete('image');
            }
            const editingCategory = categoriesCache.find(cat => Number(cat.id) === Number(editingId));
            form.set('is_active', editingId ? (editingCategory && !editingCategory.is_active ? '0' : '1') : '1');
            let saved = false;
            await runAction(categorySubmitBtn, async () => {
                const url = editingId ? `/api/categories/${editingId}` : '/api/categories';
                if (editingId) form.set('_method', 'PUT');
                const res = await safeRequest(url, { method: 'POST', body: form });
                if (res.ok) toast(editingId ? 'Category updated' : 'Category added');
                saved = true;
                await Promise.all([loadCategories(), loadMenu()]);
            });
            if (saved) resetCategoryForm();
        });

        categoryCancelEditBtn.addEventListener('click', resetCategoryForm);

        menuForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = new FormData(menuForm);
            const editingId = menuEditId.value;
            form.delete('menu_item_id');
            if (form.get('image') && form.get('image').size === 0) {
                form.delete('image');
            }
            let saved = false;
            await runAction(menuSubmitBtn, async () => {
                const url = editingId ? `/api/menu-items/${editingId}` : '/api/menu-items';
                if (editingId) form.set('_method', 'PUT');
                const res = await safeRequest(url, { method: 'POST', body: form });
                if (res.ok) toast(editingId ? 'Menu item updated' : 'Menu item added');
                saved = true;
                await Promise.all([loadMenu(), loadCategories()]);
            });
            if (saved) resetMenuForm();
        });

        menuCancelEditBtn.addEventListener('click', resetMenuForm);

        if (posLookupResult) posLookupResult.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-add-pos-item]');
            if (!btn || !lastLookup) return;
            addToPosCart(lastLookup);
        });

        if (posBarcodeInput) posBarcodeInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                const code = e.target.value.trim();
                if (!code) return;
                lookupBarcode(code, { addToCartOnSuccess: true });
            }
        });

        if (posBarcodeInput) posBarcodeInput.addEventListener('input', (e) => {
            const code = e.target.value.trim();
            clearTimeout(scanDebounce);
            if (!code) {
                setPosStatus('Ready to scan.');
                return;
            }
            scanDebounce = setTimeout(() => lookupBarcode(code, { addToCartOnSuccess: true }), 50);
        });

        if (posCheckoutBtn) posCheckoutBtn.addEventListener('click', async () => {
            if (!posCart.length) {
                alert('Cart is empty. Scan an item first.');
                return;
            }
            const payload = {
                channel: 'pos',
                customer_name: posCustomerName ? posCustomerName.value : null,
                customer_phone: posCustomerPhone ? posCustomerPhone.value : null,
                items: posCart.map(item => ({
                    menu_item_id: item.id,
                    quantity: item.qty,
                    price: item.price,
                })),
                payment: {
                    amount: computeGrandTotal(),
                    method: posPaymentMethod ? posPaymentMethod.value : 'cash',
                    reference: `POS-${Date.now()}`,
                },
                discount: Number(posDiscount ? posDiscount.value : 0) || 0,
                tax: Number(posTax ? posTax.value : 0) || 0,
                send_to_kitchen: posSendKitchen ? posSendKitchen.checked : true,
            };

            await runAction(posCheckoutBtn, async () => {
                const res = await safeRequest('/api/orders', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                });
                const order = await res.json();
                alert(`Sale recorded. Order code: ${order.code || 'pending'}.`);
                openPosReceipt(order);
                posCart = [];
                renderPosCart();
                saveRecentCustomer(payload.customer_name, payload.customer_phone);
                if (posBarcodeInput) {
                    posBarcodeInput.value = '';
                    posBarcodeInput.focus();
                }
                await Promise.all([loadOrders(), loadOrderSummary()]);
            });
        });

        if (posDiscount) posDiscount.addEventListener('input', renderTotals);
        if (posTax) posTax.addEventListener('input', renderTotals);

        const saveRecentCustomer = (name, phone) => {
            if (!name && !phone) return;
            const list = loadSavedCustomers();
            const existingIndex = list.findIndex(c => c.name === name && c.phone === phone);
            if (existingIndex >= 0) list.splice(existingIndex, 1);
            list.unshift({ name: name || 'Walk-in', phone: phone || '' });
            persistSavedCustomers(list);
            renderSavedCustomers();
        };

        const renderSavedCustomers = () => {
            if (!posSavedCustomers) return;
            const list = loadSavedCustomers();
            if (!list.length) {
                posSavedCustomers.innerHTML = '';
                return;
            }
            posSavedCustomers.innerHTML = list.map(c => `
                <button class="btn-ghost" data-fill-name="${c.name || ''}" data-fill-phone="${c.phone || ''}" style="font-size:12px; padding:6px 10px; border-radius:999px;">
                    ${c.name || 'Walk-in'}${c.phone ? ' · ' + c.phone : ''}
                </button>
            `).join('');
        };

        if (posSavedCustomers) posSavedCustomers.addEventListener('click', (e) => {
            const btn = e.target.closest('button[data-fill-name]');
            if (!btn) return;
            if (posCustomerName) posCustomerName.value = btn.getAttribute('data-fill-name') || '';
            if (posCustomerPhone) posCustomerPhone.value = btn.getAttribute('data-fill-phone') || '';
        });

        const renderParkedTickets = () => {
            if (!posParkedList) return;
            const list = loadParkedTickets();
            if (!list.length) {
                posParkedList.innerHTML = '<div class="item">No parked tickets.</div>';
                return;
            }
            posParkedList.innerHTML = list.map((t, idx) => `
                <div class="item" style="align-items:center;">
                    <div>
                        <strong>${t.name || 'Walk-in'}</strong>
                        <div class="muted" style="font-size:12px;">${new Date(t.created_at).toLocaleTimeString()}</div>
                        <div class="muted" style="font-size:12px;">Items: ${t.cart.length}</div>
                    </div>
                    <div class="row" style="gap:6px;">
                        <button class="btn-ghost" data-resume="${idx}">Resume</button>
                        <button class="btn-ghost" data-drop="${idx}">Delete</button>
                    </div>
                </div>
            `).join('');
        };

        const parkCurrentTicket = () => {
            if (!posCart.length) {
                alert('Nothing to park.');
                return;
            }
            const list = loadParkedTickets();
            list.unshift({
                created_at: Date.now(),
                cart: posCart.map(i => ({ ...i })),
                name: posCustomerName ? posCustomerName.value : '',
                phone: posCustomerPhone ? posCustomerPhone.value : '',
                method: posPaymentMethod ? posPaymentMethod.value : 'cash',
                discount: Number(posDiscount ? posDiscount.value : 0) || 0,
                tax: Number(posTax ? posTax.value : 0) || 0,
            });
            persistParkedTickets(list.slice(0, 10));
            posCart = [];
            renderPosCart();
            if (posBarcodeInput) posBarcodeInput.value = '';
            renderParkedTickets();
        };

        if (posParkBtn) posParkBtn.addEventListener('click', parkCurrentTicket);

        if (posParkedList) posParkedList.addEventListener('click', (e) => {
            const resume = e.target.closest('[data-resume]');
            const drop = e.target.closest('[data-drop]');
            const list = loadParkedTickets();
            if (resume) {
                const idx = Number(resume.getAttribute('data-resume'));
                const ticket = list[idx];
                if (ticket) {
                    posCart = ticket.cart || [];
                    if (posCustomerName) posCustomerName.value = ticket.name || '';
                    if (posCustomerPhone) posCustomerPhone.value = ticket.phone || '';
                    if (posPaymentMethod) posPaymentMethod.value = ticket.method || 'cash';
                    if (posDiscount) posDiscount.value = ticket.discount || 0;
                    if (posTax) posTax.value = ticket.tax || 0;
                    renderPosCart();
                    renderTotals();
                    if (posBarcodeInput) posBarcodeInput.focus();
                }
            }
            if (drop) {
                const idx = Number(drop.getAttribute('data-drop'));
                if (!Number.isNaN(idx)) {
                    list.splice(idx, 1);
                    persistParkedTickets(list);
                    renderParkedTickets();
                }
            }
        });

        if (purgeOrdersBtn) purgeOrdersBtn.addEventListener('click', async () => {
            if (!confirm('Delete all orders? This cannot be undone.')) return;
            await runAction(purgeOrdersBtn, async () => {
                await safeRequest('/api/orders/purge', { method: 'POST' });
                await Promise.all([loadOrders(), loadOrderSummary()]);
            });
        });

        if (ordersExportBtn) ordersExportBtn.addEventListener('click', async () => {
            const range = ordersExportRange ? ordersExportRange.value : 'monthly';
            const url = `/api/orders/export?range=${encodeURIComponent(range)}`;
            try {
                const res = await apiFetch(url);
                if (!res.ok) throw new Error('Export failed');
                const blob = await res.blob();
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = `orders-${range}-${Date.now()}.csv`;
                document.body.appendChild(link);
                link.click();
                link.remove();
                toast('CSV downloaded');
            } catch (err) {
                toast('Could not download CSV', 'error');
                console.error(err);
            }
        });

        function openPosReceipt(order) {
            try {
                const receiptWindow = window.open('', 'pos-receipt');
                if (!receiptWindow) return;
                const itemsHtml = (order.items || []).map(item => `
                    <tr>
                        <td>${item.name}</td>
                        <td style="text-align:center;">${item.quantity}</td>
                        <td style="text-align:right;">₦${Number(item.unit_price || item.price || 0).toLocaleString()}</td>
                        <td style="text-align:right;">₦${Number(item.total || item.unit_price * item.quantity || 0).toLocaleString()}</td>
                    </tr>
                `).join('');
                receiptWindow.document.write(`
                    <html>
                        <head><title>Receipt ${order.code || ''}</title></head>
                        <body style="font-family: Arial, sans-serif; padding:16px;">
                            <h2 style="margin:0 0 8px;">Acie Fraiche Cafe</h2>
                            <div style="margin-bottom:10px;">Order Code: <strong>${order.code || ''}</strong></div>
                            <table style="width:100%; border-collapse: collapse;">
                                <thead>
                                    <tr>
                                        <th style="text-align:left; border-bottom:1px solid #ddd;">Item</th>
                                        <th style="text-align:center; border-bottom:1px solid #ddd;">Qty</th>
                                        <th style="text-align:right; border-bottom:1px solid #ddd;">Price</th>
                                        <th style="text-align:right; border-bottom:1px solid #ddd;">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${itemsHtml}
                                    <tr>
                                        <td colspan="3" style="text-align:right; border-top:1px solid #ddd;">Total</td>
                                        <td style="text-align:right; border-top:1px solid #ddd;">₦${Number(order.total || 0).toLocaleString()}</td>
                                    </tr>
                                </tbody>
                            </table>
                            <p style="margin-top:12px;">Sold by: {{ auth()->user()->name ?? 'POS user' }}</p>
                            <script>window.onload = function(){ window.print(); };<\/script>
                        </body>
                    </html>
                `);
                receiptWindow.document.close();
            } catch (e) {
                console.error('Could not open receipt', e);
            }
        }

        window.toggleSoldOut = async (id, btn) => {
            await runAction(btn, async () => {
                await safeRequest(`/api/menu-items/${id}/toggle-sold-out`, { method: 'POST' });
                await loadMenu();
            });
        };

        window.deleteCategory = async (id, btn) => {
            if (!confirm('Delete this category? Items will remain uncategorized.')) return;
            await runAction(btn, async () => {
                await safeRequest(`/api/categories/${id}`, { method: 'DELETE' });
                await Promise.all([loadCategories(), loadMenu()]);
            });
        };

        window.deleteMenuItem = async (id, btn) => {
            if (!confirm('Delete this menu item?')) return;
            await runAction(btn, async () => {
                await safeRequest(`/api/menu-items/${id}`, { method: 'DELETE' });
                await loadMenu();
            });
        };

        window.copyBarcode = async (code) => {
            if (!code) {
                alert('This item does not have a barcode yet.');
                return;
            }
            const text = String(code);
            try {
                if (navigator && navigator.clipboard && navigator.clipboard.writeText) {
                    await navigator.clipboard.writeText(text);
                    alert('Barcode copied to clipboard.');
                    return;
                }
                throw new Error('Clipboard API unavailable');
            } catch (err) {
                try {
                    const input = document.createElement('input');
                    input.value = text;
                    document.body.appendChild(input);
                    input.select();
                    document.execCommand('copy');
                    input.remove();
                    alert('Barcode copied to clipboard.');
                } catch (e) {
                    alert(`Barcode: ${text}`);
                }
            }
        };

        let barcodeLibPromise = null;
        const ensureBarcodeLib = () => {
            if (typeof JsBarcode !== 'undefined') return Promise.resolve();
            if (!barcodeLibPromise) {
                barcodeLibPromise = new Promise((resolve, reject) => {
                    const script = document.createElement('script');
                    script.src = 'https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js';
                    script.onload = () => resolve();
                    script.onerror = () => reject(new Error('Could not load barcode library.'));
                    document.head.appendChild(script);
                });
            }
            return barcodeLibPromise;
        };

        window.printBarcode = async (code, name = '') => {
            if (!code) {
                alert('This item does not have a barcode yet.');
                return;
            }
            try {
                await ensureBarcodeLib();
            } catch (e) {
                alert('Barcode generator not loaded. Please check your connection and retry.');
                return;
            }
            try {
                const canvas = document.createElement('canvas');
                const safeCode = String(code);
                const safeName = String(name || 'Menu Item');
                JsBarcode(canvas, safeCode, {
                    format: 'code128',
                    width: 2,
                    height: 80,
                    displayValue: true,
                    fontSize: 14,
                    margin: 10,
                });
                const link = document.createElement('a');
                const slug = safeName.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '') || 'item';
                link.download = `${slug}-${safeCode}.png`;
                link.href = canvas.toDataURL('image/png');
                document.body.appendChild(link);
                link.click();
                link.remove();
            } catch (e) {
                console.error(e);
                alert('Could not generate barcode image. Please try again.');
            }
        };

        window.regenBarcode = async (id, btn) => {
            if (!confirm('Regenerate barcode? Printed labels with the old code will stop working.')) return;
            await runAction(btn, async () => {
                await safeRequest(`/api/menu-items/${id}/regenerate-barcode`, { method: 'POST' });
                await loadMenu();
            });
        };

        window.updateUserRole = async (id, role) => {
            await runAction(null, async () => {
                await safeRequest(`/api/users/${id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ role }),
                });
                await loadUsers();
            });
        };

        window.toggleUserActive = async (id, active) => {
            await runAction(null, async () => {
                await safeRequest(`/api/users/${id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ is_active: active }),
                });
                await loadUsers();
            });
        };

        window.deleteUser = async (id) => {
            if (!confirm('Delete this user account?')) return;
            await runAction(null, async () => {
                await safeRequest(`/api/users/${id}`, { method: 'DELETE' });
                await loadUsers();
            });
        };

        function renderOrderAvailability(availability) {
            const detail = document.getElementById('orderAvailabilityDetail');
            const badge = document.getElementById('orderAvailabilityBadge');
            const modeButtons = document.querySelectorAll('[data-order-mode]');
            const weekdayOpen = document.getElementById('weekdayOpen');
            const weekdayClose = document.getElementById('weekdayClose');
            const sundayOpen = document.getElementById('sundayOpen');
            const sundayClose = document.getElementById('sundayClose');
            if (!detail || !badge) return;

            const modeLabel = {
                auto: 'Automatic',
                force_open: 'Forced open',
                force_closed: 'Forced closed',
            }[availability.mode] || 'Automatic';

            detail.textContent = `${availability.message || 'Ordering status unavailable'} Mode: ${modeLabel}.`;
            badge.textContent = availability.is_open ? 'Open' : 'Closed';
            badge.style.borderColor = availability.is_open ? '#bbf7d0' : '#fca5a5';
            badge.style.color = availability.is_open ? '#166534' : '#b91c1c';
            badge.style.background = availability.is_open ? '#f0fdf4' : '#fef2f2';

            modeButtons.forEach((btn) => {
                const active = btn.getAttribute('data-order-mode') === availability.mode;
                btn.className = active ? 'btn-primary' : 'btn-ghost';
            });

            if (availability.schedule) {
                if (weekdayOpen) weekdayOpen.value = availability.schedule.weekday?.open || '08:00';
                if (weekdayClose) weekdayClose.value = availability.schedule.weekday?.close || '22:00';
                if (sundayOpen) sundayOpen.value = availability.schedule.sunday?.open || '12:00';
                if (sundayClose) sundayClose.value = availability.schedule.sunday?.close || '22:00';
            }
        }

        async function loadOrderAvailability() {
            try {
                const res = await safeRequest('/api/order-availability');
                renderOrderAvailability(await res.json());
            } catch (e) {
                const detail = document.getElementById('orderAvailabilityDetail');
                if (detail) detail.textContent = 'Could not load order availability.';
                console.error(e);
            }
        }

        document.querySelectorAll('[data-order-mode]').forEach((btn) => {
            btn.addEventListener('click', async () => {
                const mode = btn.getAttribute('data-order-mode');
                await runAction(btn, async () => {
                    const res = await safeRequest('/api/order-availability', {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ mode }),
                    });
                    renderOrderAvailability(await res.json());
                    toast('Website ordering updated');
                });
            });
        });

        const orderScheduleForm = document.getElementById('orderScheduleForm');
        if (orderScheduleForm) {
            orderScheduleForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const submitBtn = orderScheduleForm.querySelector('button[type="submit"]');
                const schedule = {
                    weekday: {
                        open: document.getElementById('weekdayOpen')?.value || '08:00',
                        close: document.getElementById('weekdayClose')?.value || '22:00',
                    },
                    sunday: {
                        open: document.getElementById('sundayOpen')?.value || '12:00',
                        close: document.getElementById('sundayClose')?.value || '22:00',
                    },
                };

                await runAction(submitBtn, async () => {
                    const res = await safeRequest('/api/order-availability', {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ schedule }),
                    });
                    renderOrderAvailability(await res.json());
                    toast('Automatic schedule saved');
                });
            });
        }

        async function init() {
            await checkHealth();
            renderPosCart();
            if (posBarcodeInput) posBarcodeInput.focus();
            await Promise.all([loadCategories(), loadMenu(), loadOrders(), loadOrderSummary(), loadUsers(), loadOrderAvailability()]);
            renderSavedCustomers();
            renderParkedTickets();

            // Live refresh
            const refresh = async () => {
                if (isInteracting) return;
                await Promise.all([loadCategories(), loadMenu(), loadOrders(), loadOrderSummary(), loadUsers(), loadOrderAvailability()]);
            };
            const refreshPoller = createPoller(refresh, 5000, {
                onError: (err) => console.warn('Admin refresh failed', err),
            });
            refreshPoller.start();

            // Pause refresh while typing or focusing inputs
            document.addEventListener('focusin', markInteracting);
            document.addEventListener('input', markInteracting);
            document.addEventListener('mousedown', markInteracting);
        }

        init();
        } catch (err) {
            console.error('Admin UI failed to init', err);
            alert('The admin interface failed to load. Please refresh or use a modern browser.');
        }
    })();
    </script>
</body>
</html>
