<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Store Inventory | Acie Fraiche</title>
    <meta name="description" content="Manage store supplies and ingredient inventory for Acie Fraiche.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
    <link rel="icon" href="/assets/logo2.png" type="image/png">
    <style>
        /* ── Design tokens (match Kitchen / POS) ── */
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
            padding: 14px 20px;
            background: #fff;
            border-bottom: 1px solid var(--af-line);
            box-shadow: var(--af-shadow);
            position: sticky; top: 0; z-index: 100;
        }
        .brand { display: flex; align-items: center; gap: 12px; }
        .brand img { width: 44px; height: 44px; border-radius: 12px; border: 1px solid var(--af-line); padding: 5px; }
        .brand h1 { font-size: 19px; font-family: 'Playfair Display', Georgia, serif; color: var(--af-brown); letter-spacing: -0.02em; }
        .brand .sub { font-size: 12px; color: var(--af-ink-soft); margin-top: 1px; }
        .topbar-right { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

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
        .btn-danger { background: #fee2e2; color: var(--af-danger); border: 1px solid #fca5a5; }
        .btn-danger:hover { background: #fca5a5; }
        .btn-sm { padding: 6px 10px; font-size: 12px; border-radius: 8px; }
        .btn-icon { padding: 7px 9px; }

        /* ── Main layout ── */
        main { padding: 20px; max-width: 1300px; margin: 0 auto; display: grid; gap: 16px; }

        /* ── Hero banner ── */
        .hero {
            background: linear-gradient(135deg, rgba(82,55,0,0.07) 0%, rgba(204,153,51,0.06) 100%);
            border: 1px solid var(--af-line); border-radius: 18px; padding: 18px 20px;
            display: flex; align-items: center; justify-content: space-between; gap: 12px;
            box-shadow: var(--af-shadow);
        }
        .hero h2 { font-size: 20px; color: var(--af-ink); letter-spacing: -0.02em; }
        .hero p  { color: var(--af-ink-soft); font-size: 13px; margin-top: 4px; }

        /* ── Stat cards ── */
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px,1fr)); gap: 12px; }
        .stat-card {
            background: #fff; border: 1px solid var(--af-line); border-radius: var(--radius);
            padding: 16px; box-shadow: var(--af-shadow); display: flex; flex-direction: column; gap: 6px;
        }
        .stat-card .label { font-size: 12px; color: var(--af-ink-soft); text-transform: uppercase; letter-spacing: 0.05em; }
        .stat-card .value { font-size: 26px; font-weight: 700; color: var(--af-brown); letter-spacing: -0.03em; }
        .stat-card.warn  .value { color: var(--af-warn); }
        .stat-card.danger .value { color: var(--af-danger); }
        .stat-card.ok    .value { color: var(--af-success); }

        /* ── Toolbar ── */
        .toolbar {
            display: flex; gap: 10px; align-items: center; flex-wrap: wrap;
            background: #fff; border: 1px solid var(--af-line); border-radius: var(--radius);
            padding: 12px 14px; box-shadow: var(--af-shadow);
        }
        .toolbar input[type="text"] {
            flex: 1; min-width: 160px; padding: 9px 12px; border: 1px solid var(--af-line);
            border-radius: 10px; font: inherit; font-size: 13px; background: var(--af-cream);
        }
        .toolbar input[type="text"]:focus { outline: 2px solid var(--af-gold); outline-offset: 1px; }
        .cat-filter { display: flex; gap: 6px; flex-wrap: wrap; }
        .cat-pill {
            padding: 6px 12px; border-radius: 999px; font-size: 12px; font-weight: 600;
            border: 1px solid var(--af-line); background: #fff; cursor: pointer;
            transition: background 0.1s, border-color 0.1s; white-space: nowrap;
        }
        .cat-pill.active { background: var(--af-brown); color: #fff; border-color: var(--af-brown); }

        /* ── Table card ── */
        .table-card {
            background: #fff; border: 1px solid var(--af-line); border-radius: 18px;
            overflow: hidden; box-shadow: var(--af-shadow);
        }
        .table-head {
            display: flex; align-items: center; justify-content: space-between; gap: 10px;
            padding: 14px 16px; border-bottom: 1px solid var(--af-line);
        }
        .table-head h3 { font-size: 15px; }
        .divider { height: 1px; background: var(--af-line); }

        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        thead th {
            text-align: left; padding: 10px 14px; color: var(--af-ink-soft);
            font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 700;
            border-bottom: 1px solid var(--af-line); background: var(--af-cream);
        }
        tbody tr { border-bottom: 1px solid var(--af-line); transition: background 0.08s; }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: rgba(247,241,231,0.6); }
        tbody tr.expanded { background: rgba(204,153,51,0.05); }
        td { padding: 11px 14px; vertical-align: middle; }
        td.name-cell { font-weight: 700; color: var(--af-ink); }
        td.qty-cell  { font-weight: 700; font-size: 15px; }
        td.qty-cell.low    { color: var(--af-warn); }
        td.qty-cell.danger { color: var(--af-danger); }
        td.qty-cell.ok     { color: var(--af-success); }

        /* ── Status pills ── */
        .pill {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; white-space: nowrap;
        }
        .pill.in-stock  { background: #dcfce7; color: var(--af-success); border: 1px solid #bbf7d0; }
        .pill.low-stock { background: #fef3c7; color: var(--af-warn);    border: 1px solid #fcd34d; }
        .pill.out       { background: #fee2e2; color: var(--af-danger);   border: 1px solid #fca5a5; }

        /* ── Row actions ── */
        .row-actions { display: flex; gap: 6px; align-items: center; }

        /* ── Expand panel (adjust + history) ── */
        .expand-row { display: none; }
        .expand-row.open { display: table-row; }
        .expand-panel {
            padding: 14px 16px 16px;
            background: linear-gradient(135deg, rgba(247,241,231,0.8), rgba(255,253,248,0.9));
            border-top: 1px solid var(--af-line);
        }
        .expand-inner { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        @media (max-width: 760px) { .expand-inner { grid-template-columns: 1fr; } }
        .expand-section { background: #fff; border: 1px solid var(--af-line); border-radius: 12px; padding: 14px; }
        .expand-section h4 { font-size: 13px; color: var(--af-ink-soft); margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.05em; }

        /* Adjust form */
        .adjust-form { display: grid; gap: 10px; }
        .adjust-form .row { display: flex; gap: 8px; }
        .adjust-form .row .sign-btn {
            width: 40px; height: 40px; border-radius: 10px; border: 1px solid var(--af-line);
            font-size: 20px; font-weight: 700; cursor: pointer; background: #fff;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
            transition: background 0.1s;
        }
        .adjust-form .row .sign-btn.plus:hover  { background: #dcfce7; }
        .adjust-form .row .sign-btn.minus:hover { background: #fee2e2; }
        .adjust-form input[type="number"],
        .adjust-form input[type="text"] {
            flex: 1; padding: 9px 11px; border: 1px solid var(--af-line); border-radius: 10px;
            font: inherit; font-size: 13px;
        }
        .adjust-form input:focus { outline: 2px solid var(--af-gold); outline-offset: 1px; }

        /* History list */
        .history-list { display: grid; gap: 7px; max-height: 240px; overflow-y: auto; }
        .history-entry {
            display: flex; align-items: flex-start; gap: 10px;
            border: 1px solid var(--af-line); border-radius: 10px; padding: 9px 11px;
            background: var(--af-card);
        }
        .history-entry .delta { font-weight: 700; font-size: 14px; width: 54px; flex-shrink: 0; text-align: right; }
        .history-entry .delta.pos { color: var(--af-success); }
        .history-entry .delta.neg { color: var(--af-danger); }
        .history-entry .meta { font-size: 12px; color: var(--af-ink-soft); }
        .history-entry .reason { font-size: 13px; font-weight: 600; color: var(--af-ink); }

        /* ── Modal overlay ── */
        .modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(15,11,5,0.45); backdrop-filter: blur(3px);
            z-index: 200; align-items: center; justify-content: center; padding: 16px;
        }
        .modal-overlay.open { display: flex; }
        .modal {
            background: #fff; border-radius: 20px; padding: 24px; width: 100%; max-width: 460px;
            box-shadow: 0 24px 60px rgba(0,0,0,0.18); animation: slideUp 0.18s ease;
        }
        @keyframes slideUp { from { opacity:0; transform: translateY(18px); } to { opacity:1; transform: none; } }
        .modal h2 { font-size: 18px; margin-bottom: 16px; font-family: 'Playfair Display', Georgia, serif; color: var(--af-brown); }
        .modal-form { display: grid; gap: 12px; }
        .field label { display: block; font-size: 12px; color: var(--af-ink-soft); margin-bottom: 5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }
        .field input, .field select, .field textarea {
            width: 100%; padding: 10px 12px; border: 1px solid var(--af-line); border-radius: 10px;
            font: inherit; font-size: 13px; background: var(--af-cream);
        }
        .field input:focus, .field select:focus, .field textarea:focus { outline: 2px solid var(--af-gold); outline-offset: 1px; background: #fff; }
        .field textarea { resize: vertical; min-height: 70px; }
        .field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .modal-actions { display: flex; gap: 8px; margin-top: 6px; justify-content: flex-end; }
        .modal-error { color: var(--af-danger); font-size: 13px; min-height: 18px; }

        /* ── Empty / loading states ── */
        .empty-state {
            text-align: center; padding: 40px 20px; color: var(--af-ink-soft);
            font-size: 14px; border: 1px dashed var(--af-line); border-radius: var(--radius);
            margin: 16px;
        }
        .empty-state .icon { font-size: 36px; margin-bottom: 10px; }

        /* ── Toast ── */
        #toast {
            position: fixed; right: 18px; bottom: 18px;
            background: var(--af-brown); color: #fff; padding: 12px 16px;
            border-radius: 12px; box-shadow: 0 16px 36px rgba(0,0,0,0.18);
            font-weight: 600; font-size: 13px; display: none; z-index: 300;
            animation: fadeIn 0.2s ease;
        }
        #toast.error { background: var(--af-danger); }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity:1; transform:none; } }

        /* ── Misc ── */
        .muted { color: var(--af-ink-soft); }
        .fw7  { font-weight: 700; }
        @media (max-width: 700px) {
            header.topbar { position: static; }
            table thead { display: none; }
            tbody td { display: block; padding: 6px 14px; }
            tbody td::before { content: attr(data-label) ': '; color: var(--af-ink-soft); font-size: 11px; }
            tbody tr { display: block; border: 1px solid var(--af-line); border-radius: 12px; margin: 8px; }
        }
    </style>
</head>
<body>

    <!-- ────────────────── TOPBAR ────────────────── -->
    <header class="topbar">
        <div class="brand">
            <img src="/assets/logo2.png" alt="Acie Fraiche">
            <div>
                <h1>Acie Fraiche · Store Inventory</h1>
                <div class="sub">Signed in as {{ auth()->user()->name ?? 'User' }}</div>
            </div>
        </div>
        <div class="topbar-right">
            <button id="btnRefresh" class="btn btn-ghost btn-sm" type="button">⟳ Refresh</button>
            <button id="btnAddItem" class="btn btn-primary btn-sm" type="button">+ Add Item</button>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-ghost btn-sm">Logout</button>
            </form>
        </div>
    </header>

    <!-- ────────────────── MAIN ────────────────── -->
    <main>

        <!-- Hero -->
        <div class="hero">
            <div>
                <h2>Store Supplies Inventory</h2>
                <p>Track and manage all store supplies — ingredients, packaging, cleaning materials and more.</p>
            </div>
            <div id="lastUpdated" class="muted" style="font-size:12px; white-space:nowrap;"></div>
        </div>

        <!-- Stats -->
        <div class="stats">
            <div class="stat-card">
                <div class="label">Total Items</div>
                <div class="value" id="statTotal">—</div>
            </div>
            <div class="stat-card ok">
                <div class="label">In Stock</div>
                <div class="value" id="statOk">—</div>
            </div>
            <div class="stat-card warn">
                <div class="label">Low Stock</div>
                <div class="value" id="statLow">—</div>
            </div>
            <div class="stat-card danger">
                <div class="label">Out of Stock</div>
                <div class="value" id="statOut">—</div>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="toolbar">
            <input type="text" id="searchBox" placeholder="🔍  Search supplies…">
            <div class="cat-filter" id="catFilter">
                <button class="cat-pill active" data-cat="">All</button>
            </div>
        </div>

        <!-- Table -->
        <div class="table-card">
            <div class="table-head">
                <h3>Supplies</h3>
                <span class="muted" style="font-size:12px;" id="rowCount"></span>
            </div>
            <div id="tableWrap">
                <div class="empty-state"><div class="icon">📦</div>Loading inventory…</div>
            </div>
        </div>

    </main>

    <!-- ────────────────── ADD / EDIT MODAL ────────────────── -->
    <div class="modal-overlay" id="itemModal">
        <div class="modal">
            <h2 id="modalTitle">Add Supply Item</h2>
            <form class="modal-form" id="itemForm" autocomplete="off">
                <input type="hidden" id="itemId">
                <div class="field">
                    <label>Item Name *</label>
                    <input type="text" id="fieldName" placeholder="e.g. Salt" required>
                </div>
                <div class="field-row">
                    <div class="field">
                        <label>Category</label>
                        <input type="text" id="fieldCategory" placeholder="e.g. Dry Goods" list="catSuggestions">
                        <datalist id="catSuggestions"></datalist>
                    </div>
                    <div class="field">
                        <label>Unit *</label>
                        <input type="text" id="fieldUnit" placeholder="e.g. kg, L, pcs, bags" required list="unitSuggestions">
                        <datalist id="unitSuggestions">
                            <option value="kg"><option value="g"><option value="L"><option value="mL">
                            <option value="pcs"><option value="bags"><option value="boxes"><option value="cans">
                            <option value="bottles"><option value="rolls"><option value="packs">
                        </datalist>
                    </div>
                </div>
                <div class="field-row">
                    <div class="field">
                        <label>Quantity *</label>
                        <input type="number" id="fieldQty" step="0.01" min="0" placeholder="0" required>
                    </div>
                    <div class="field">
                        <label>Low-Stock Alert At</label>
                        <input type="number" id="fieldThreshold" step="0.01" min="0" placeholder="5">
                    </div>
                </div>
                <div class="field">
                    <label>Supplier Notes</label>
                    <textarea id="fieldNotes" placeholder="Optional notes about supplier, reorder info…"></textarea>
                </div>
                <div class="modal-error" id="modalError"></div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-ghost" id="btnModalCancel">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="btnModalSave">Save Item</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ────────────────── TOAST ────────────────── -->
    <div id="toast"></div>

    <script>
    (() => {
        'use strict';

        const CSRF = document.querySelector('meta[name="csrf-token"]').content;
        const API  = '/api';

        // ── State ──────────────────────────────────────────
        let allItems      = [];
        let activeCategory = '';
        let searchQuery   = '';
        let expandedId    = null;
        let editingId     = null;

        // ── Toast ──────────────────────────────────────────
        let toastTimer;
        function toast(msg, isError = false) {
            const el = document.getElementById('toast');
            el.textContent = msg;
            el.className = isError ? 'error' : '';
            el.style.display = 'block';
            clearTimeout(toastTimer);
            toastTimer = setTimeout(() => { el.style.display = 'none'; }, 3200);
        }

        // ── API helpers ────────────────────────────────────
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

        // ── Load items ─────────────────────────────────────
        async function loadItems() {
            try {
                allItems = await apiFetch('/store-items');
                renderStats();
                renderCategoryFilter();
                renderTable();
                document.getElementById('lastUpdated').textContent =
                    'Last updated ' + new Date().toLocaleTimeString();
            } catch (e) {
                document.getElementById('tableWrap').innerHTML =
                    `<div class="empty-state"><div class="icon">⚠️</div>${e.message}</div>`;
            }
        }

        // ── Stats ──────────────────────────────────────────
        function renderStats() {
            const total  = allItems.length;
            const ok     = allItems.filter(i => i.status === 'in_stock').length;
            const low    = allItems.filter(i => i.status === 'low_stock').length;
            const out    = allItems.filter(i => i.status === 'out_of_stock').length;
            document.getElementById('statTotal').textContent = total;
            document.getElementById('statOk').textContent   = ok;
            document.getElementById('statLow').textContent  = low;
            document.getElementById('statOut').textContent  = out;
        }

        // ── Category filter ────────────────────────────────
        function renderCategoryFilter() {
            const cats = [...new Set(allItems.map(i => i.category).filter(Boolean))].sort();
            const wrap = document.getElementById('catFilter');
            wrap.innerHTML = `<button class="cat-pill${activeCategory === '' ? ' active' : ''}" data-cat="">All</button>`;
            cats.forEach(c => {
                const btn = document.createElement('button');
                btn.className = 'cat-pill' + (activeCategory === c ? ' active' : '');
                btn.dataset.cat = c;
                btn.textContent = c;
                wrap.appendChild(btn);
            });
            // Also update datalist suggestions for add form
            const dl = document.getElementById('catSuggestions');
            dl.innerHTML = cats.map(c => `<option value="${esc(c)}">`).join('');
        }

        // ── Table ──────────────────────────────────────────
        function filteredItems() {
            let items = allItems;
            if (activeCategory) items = items.filter(i => i.category === activeCategory);
            if (searchQuery)     items = items.filter(i =>
                i.name.toLowerCase().includes(searchQuery) ||
                (i.category || '').toLowerCase().includes(searchQuery)
            );
            return items;
        }

        function statusPill(status) {
            const map = {
                in_stock:    ['in-stock',  '● In Stock'],
                low_stock:   ['low-stock', '⚠ Low Stock'],
                out_of_stock:['out',       '✕ Out of Stock'],
            };
            const [cls, label] = map[status] || ['', status];
            return `<span class="pill ${cls}">${label}</span>`;
        }

        function qtyClass(status) {
            return status === 'in_stock' ? 'ok' : status === 'low_stock' ? 'low' : 'danger';
        }

        function renderTable() {
            const items = filteredItems();
            const wrap = document.getElementById('tableWrap');
            document.getElementById('rowCount').textContent = `${items.length} item${items.length !== 1 ? 's' : ''}`;

            if (items.length === 0) {
                wrap.innerHTML = `<div class="empty-state"><div class="icon">📦</div>No items found.</div>`;
                return;
            }

            const rows = items.map(item => {
                const isExp = expandedId === item.id;
                return `
                <tr class="item-row${isExp ? ' expanded' : ''}" data-id="${item.id}">
                    <td data-label="Item" class="name-cell">${esc(item.name)}</td>
                    <td data-label="Category" class="muted">${esc(item.category || '—')}</td>
                    <td data-label="Quantity" class="qty-cell ${qtyClass(item.status)}">${fmtQty(item.quantity)} <span style="font-size:11px;font-weight:500;">${esc(item.unit)}</span></td>
                    <td data-label="Status">${statusPill(item.status)}</td>
                    <td data-label="Threshold" class="muted">≤ ${fmtQty(item.low_stock_threshold)} ${esc(item.unit)}</td>
                    <td data-label="Actions">
                        <div class="row-actions">
                            <button class="btn btn-ghost btn-sm btn-expand" data-id="${item.id}" title="Adjust / History">
                                ${isExp ? '▲ Close' : '▼ Adjust'}
                            </button>
                            <button class="btn btn-ghost btn-sm btn-edit" data-id="${item.id}" title="Edit item">✎</button>
                            <button class="btn btn-danger btn-sm btn-delete" data-id="${item.id}" title="Delete item">✕</button>
                        </div>
                    </td>
                </tr>
                ${isExp ? expandRow(item) : `<tr class="expand-row" data-expand="${item.id}"></tr>`}
                `;
            }).join('');

            wrap.innerHTML = `
            <table>
                <thead>
                    <tr>
                        <th>Item</th><th>Category</th><th>Quantity</th>
                        <th>Status</th><th>Alert At</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>`;
        }

        function expandRow(item) {
            return `
            <tr class="expand-row open" data-expand="${item.id}">
                <td colspan="6" style="padding:0;">
                    <div class="expand-panel">
                        <div class="expand-inner">
                            <div class="expand-section">
                                <h4>Adjust Stock</h4>
                                <div class="adjust-form" id="adjustForm-${item.id}">
                                    <div class="row">
                                        <button class="sign-btn minus" data-sign="-1" data-item="${item.id}" title="Remove">−</button>
                                        <input type="number" id="adjQty-${item.id}" step="0.01" min="0.01" placeholder="Quantity" style="flex:1;">
                                        <button class="sign-btn plus" data-sign="1" data-item="${item.id}" title="Add">+</button>
                                    </div>
                                    <input type="text" id="adjReason-${item.id}" placeholder="Reason (optional) — e.g. Restock, Spillage">
                                    <div style="display:flex;gap:8px;">
                                        <button class="btn btn-ghost btn-sm" style="flex:1;" data-action="use" data-item="${item.id}">− Record Usage</button>
                                        <button class="btn btn-primary btn-sm" style="flex:1;" data-action="restock" data-item="${item.id}">+ Restock</button>
                                    </div>
                                    <div class="modal-error" id="adjError-${item.id}"></div>
                                </div>
                                ${item.supplier_notes ? `<div style="margin-top:10px;font-size:12px;color:var(--af-ink-soft);">📋 ${esc(item.supplier_notes)}</div>` : ''}
                            </div>
                            <div class="expand-section">
                                <h4>Adjustment History</h4>
                                <div class="history-list" id="history-${item.id}">
                                    <div class="muted" style="font-size:12px;">Loading…</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>`;
        }

        // ── History ────────────────────────────────────────
        async function loadHistory(itemId) {
            const el = document.getElementById(`history-${itemId}`);
            if (!el) return;
            try {
                const entries = await apiFetch(`/store-items/${itemId}/history`);
                if (!entries.length) {
                    el.innerHTML = `<div class="muted" style="font-size:12px;">No adjustments yet.</div>`;
                    return;
                }
                el.innerHTML = entries.map(e => {
                    const delta = parseFloat(e.quantity_change);
                    const sign  = delta >= 0 ? '+' : '';
                    const cls   = delta >= 0 ? 'pos' : 'neg';
                    const when  = new Date(e.created_at).toLocaleString();
                    return `
                    <div class="history-entry">
                        <div class="delta ${cls}">${sign}${fmtQty(e.quantity_change)}</div>
                        <div>
                            <div class="reason">${esc(e.reason || 'No reason given')}</div>
                            <div class="meta">${esc(e.adjusted_by || 'Unknown')} · ${when}</div>
                        </div>
                    </div>`;
                }).join('');
            } catch (err) {
                el.innerHTML = `<div class="muted" style="font-size:12px;">Failed to load: ${esc(err.message)}</div>`;
            }
        }

        // ── Adjust stock ───────────────────────────────────
        async function doAdjust(itemId, sign) {
            const qtyInput    = document.getElementById(`adjQty-${itemId}`);
            const reasonInput = document.getElementById(`adjReason-${itemId}`);
            const errEl       = document.getElementById(`adjError-${itemId}`);
            const qty = parseFloat(qtyInput.value);
            if (!qty || qty <= 0) { errEl.textContent = 'Enter a valid quantity.'; return; }
            errEl.textContent = '';
            try {
                await apiFetch(`/store-items/${itemId}/adjust`, {
                    method: 'POST',
                    body: JSON.stringify({ quantity_change: sign * qty, reason: reasonInput.value }),
                });
                toast(sign > 0 ? '✓ Stock added' : '✓ Usage recorded');
                qtyInput.value = '';
                reasonInput.value = '';
                await loadItems();
                // Re-open expanded row
                if (expandedId === itemId) {
                    loadHistory(itemId);
                }
            } catch (e) {
                errEl.textContent = e.message;
            }
        }

        // ── Event delegation (table interactions) ──────────
        document.getElementById('tableWrap').addEventListener('click', async e => {
            const btn = e.target.closest('button');
            if (!btn) return;

            // Expand / collapse
            if (btn.classList.contains('btn-expand')) {
                const id = parseInt(btn.dataset.id);
                expandedId = expandedId === id ? null : id;
                renderTable();
                if (expandedId) loadHistory(expandedId);
                return;
            }

            // Edit
            if (btn.classList.contains('btn-edit')) {
                const item = allItems.find(i => i.id === parseInt(btn.dataset.id));
                if (item) openModal(item);
                return;
            }

            // Delete
            if (btn.classList.contains('btn-delete')) {
                const id   = parseInt(btn.dataset.id);
                const item = allItems.find(i => i.id === id);
                if (!confirm(`Delete "${item?.name}"? This cannot be undone.`)) return;
                try {
                    await apiFetch(`/store-items/${id}`, { method: 'DELETE' });
                    toast('Item deleted');
                    if (expandedId === id) expandedId = null;
                    await loadItems();
                } catch (e) { toast(e.message, true); }
                return;
            }

            // Adjust via +/- sign buttons
            if (btn.dataset.sign) {
                doAdjust(parseInt(btn.dataset.item), parseInt(btn.dataset.sign));
                return;
            }

            // Adjust via restock / usage named buttons
            if (btn.dataset.action) {
                const sign = btn.dataset.action === 'restock' ? 1 : -1;
                doAdjust(parseInt(btn.dataset.item), sign);
            }
        });

        // ── Category filter clicks ─────────────────────────
        document.getElementById('catFilter').addEventListener('click', e => {
            const btn = e.target.closest('.cat-pill');
            if (!btn) return;
            activeCategory = btn.dataset.cat;
            document.querySelectorAll('.cat-pill').forEach(b => b.classList.toggle('active', b === btn));
            renderTable();
        });

        // ── Search ─────────────────────────────────────────
        document.getElementById('searchBox').addEventListener('input', e => {
            searchQuery = e.target.value.toLowerCase().trim();
            renderTable();
        });

        // ── Modal (add / edit) ─────────────────────────────
        function openModal(item = null) {
            editingId = item ? item.id : null;
            document.getElementById('modalTitle').textContent = item ? 'Edit Item' : 'Add Supply Item';
            document.getElementById('itemId').value       = item?.id ?? '';
            document.getElementById('fieldName').value    = item?.name ?? '';
            document.getElementById('fieldCategory').value= item?.category ?? '';
            document.getElementById('fieldUnit').value    = item?.unit ?? '';
            document.getElementById('fieldQty').value     = item ? '' : '';      // qty only for new items
            document.getElementById('fieldThreshold').value = item?.low_stock_threshold ?? 5;
            document.getElementById('fieldNotes').value   = item?.supplier_notes ?? '';
            document.getElementById('modalError').textContent = '';

            // For edit: hide qty field (use adjust instead)
            const qtyField = document.getElementById('fieldQty').closest('.field-row') || document.getElementById('fieldQty').parentElement;
            if (item) {
                document.getElementById('fieldQty').parentElement.style.display = 'none';
                document.getElementById('fieldQty').removeAttribute('required');
            } else {
                document.getElementById('fieldQty').parentElement.style.display = '';
                document.getElementById('fieldQty').setAttribute('required', '');
            }

            document.getElementById('itemModal').classList.add('open');
            document.getElementById('fieldName').focus();
        }

        function closeModal() {
            document.getElementById('itemModal').classList.remove('open');
            editingId = null;
        }

        document.getElementById('btnAddItem').addEventListener('click', () => openModal());
        document.getElementById('btnModalCancel').addEventListener('click', closeModal);
        document.getElementById('itemModal').addEventListener('click', e => {
            if (e.target === document.getElementById('itemModal')) closeModal();
        });

        document.getElementById('itemForm').addEventListener('submit', async e => {
            e.preventDefault();
            const errEl = document.getElementById('modalError');
            errEl.textContent = '';
            const btn   = document.getElementById('btnModalSave');
            btn.disabled = true;

            const payload = {
                name:                document.getElementById('fieldName').value.trim(),
                category:            document.getElementById('fieldCategory').value.trim() || null,
                unit:                document.getElementById('fieldUnit').value.trim(),
                low_stock_threshold: parseFloat(document.getElementById('fieldThreshold').value) || 5,
                supplier_notes:      document.getElementById('fieldNotes').value.trim() || null,
            };

            if (!editingId) {
                payload.quantity = parseFloat(document.getElementById('fieldQty').value) || 0;
            }

            try {
                if (editingId) {
                    await apiFetch(`/store-items/${editingId}`, { method: 'PUT', body: JSON.stringify(payload) });
                    toast('✓ Item updated');
                } else {
                    await apiFetch('/store-items', { method: 'POST', body: JSON.stringify(payload) });
                    toast('✓ Item added');
                }
                closeModal();
                await loadItems();
            } catch (err) {
                errEl.textContent = err.message;
            } finally {
                btn.disabled = false;
            }
        });

        // ── Refresh button ─────────────────────────────────
        document.getElementById('btnRefresh').addEventListener('click', () => {
            loadItems();
        });

        // ── Helpers ────────────────────────────────────────
        function esc(str) {
            return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }
        function fmtQty(n) {
            const f = parseFloat(n);
            return isNaN(f) ? '0' : (f % 1 === 0 ? f.toString() : f.toFixed(2));
        }

        // ── Boot ───────────────────────────────────────────
        loadItems();
        setInterval(loadItems, 30_000); // auto-refresh every 30 s

    })();
    </script>
</body>
</html>
