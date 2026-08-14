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

        /* ── Layout ── */
        main { padding: 20px; max-width: 1300px; margin: 0 auto; display: grid; gap: 16px; }

        /* ── Hero ── */
        .hero {
            background: linear-gradient(135deg, rgba(82,55,0,0.07) 0%, rgba(204,153,51,0.06) 100%);
            border: 1px solid var(--af-line); border-radius: 18px; padding: 18px 20px;
            display: flex; align-items: center; justify-content: space-between; gap: 12px;
            box-shadow: var(--af-shadow);
        }
        .hero h2 { font-size: 20px; color: var(--af-ink); letter-spacing: -0.02em; }
        .hero p  { color: var(--af-ink-soft); font-size: 13px; margin-top: 4px; }

        /* ── Stats ── */
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

        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        thead th {
            text-align: left; padding: 10px 14px; color: var(--af-ink-soft);
            font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 700;
            border-bottom: 1px solid var(--af-line); background: var(--af-cream);
        }
        tbody tr { border-bottom: 1px solid var(--af-line); transition: background 0.08s; }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: rgba(247,241,231,0.6); }
        tbody tr.expanded { background: rgba(204,153,51,0.04); }
        td { padding: 11px 14px; vertical-align: middle; }
        td.name-cell { font-weight: 700; }
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
        .pill.cat       { background: rgba(82,55,0,0.08); color: var(--af-brown); border: 1px solid rgba(82,55,0,0.15); font-size: 11px; }

        /* ── Row actions ── */
        .row-actions { display: flex; gap: 6px; align-items: center; }

        /* ── Expand panel ── */
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
        .expand-section h4 { font-size: 11px; color: var(--af-ink-soft); margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 700; }

        /* Adjust form */
        .adjust-form { display: grid; gap: 10px; }
        .adjust-row  { display: flex; gap: 8px; }
        .sign-btn {
            width: 40px; height: 40px; border-radius: 10px; border: 1px solid var(--af-line);
            font-size: 20px; font-weight: 700; cursor: pointer; background: #fff;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
            transition: background 0.1s;
        }
        .sign-btn.plus:hover  { background: #dcfce7; }
        .sign-btn.minus:hover { background: #fee2e2; }
        .adjust-form input[type="number"],
        .adjust-form input[type="text"] {
            flex: 1; padding: 9px 11px; border: 1px solid var(--af-line); border-radius: 10px;
            font: inherit; font-size: 13px;
        }
        .adjust-form input:focus { outline: 2px solid var(--af-gold); outline-offset: 1px; }
        .adjust-actions { display: flex; gap: 8px; }

        /* History */
        .history-list { display: grid; gap: 7px; max-height: 240px; overflow-y: auto; }
        .history-entry {
            display: flex; align-items: flex-start; gap: 10px;
            border: 1px solid var(--af-line); border-radius: 10px; padding: 9px 11px;
            background: var(--af-card);
        }
        .delta { font-weight: 700; font-size: 14px; width: 56px; flex-shrink: 0; text-align: right; }
        .delta.pos { color: var(--af-success); }
        .delta.neg { color: var(--af-danger); }
        .h-reason { font-size: 13px; font-weight: 600; color: var(--af-ink); }
        .h-meta   { font-size: 12px; color: var(--af-ink-soft); margin-top: 2px; }

        /* ── Category manager card ── */
        .cat-manager {
            background: #fff; border: 1px solid var(--af-line); border-radius: 18px;
            overflow: hidden; box-shadow: var(--af-shadow);
        }
        .cat-manager-head {
            display: flex; align-items: center; justify-content: space-between;
            padding: 14px 16px; border-bottom: 1px solid var(--af-line);
            cursor: pointer; user-select: none;
        }
        .cat-manager-head h3 { font-size: 15px; }
        .cat-manager-body { padding: 14px 16px; display: none; }
        .cat-manager-body.open { display: block; }
        .cat-grid { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; }
        .cat-tag {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 6px 10px; border-radius: 999px; font-size: 12px; font-weight: 600;
            background: rgba(82,55,0,0.08); border: 1px solid rgba(82,55,0,0.15); color: var(--af-brown);
        }
        .cat-tag button { background: none; border: none; cursor: pointer; color: var(--af-danger); font-size: 13px; padding: 0; line-height: 1; }
        .cat-add-row { display: flex; gap: 8px; }
        .cat-add-row input {
            flex: 1; padding: 9px 12px; border: 1px solid var(--af-line); border-radius: 10px;
            font: inherit; font-size: 13px;
        }
        .cat-add-row input:focus { outline: 2px solid var(--af-gold); outline-offset: 1px; }
        .cat-error { color: var(--af-danger); font-size: 12px; min-height: 16px; margin-top: 4px; }

        /* ── Modal ── */
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
        .field label { display: block; font-size: 11px; color: var(--af-ink-soft); margin-bottom: 5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }
        .field input, .field select, .field textarea {
            width: 100%; padding: 10px 12px; border: 1px solid var(--af-line); border-radius: 10px;
            font: inherit; font-size: 13px; background: var(--af-cream); appearance: none;
        }
        .field select { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23523700' d='M6 8L1 3h10z'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 12px center; padding-right: 32px; }
        .field input:focus, .field select:focus, .field textarea:focus { outline: 2px solid var(--af-gold); outline-offset: 1px; background: #fff; }
        .field textarea { resize: vertical; min-height: 70px; }
        .field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .modal-actions { display: flex; gap: 8px; margin-top: 6px; justify-content: flex-end; }
        .modal-error { color: var(--af-danger); font-size: 13px; min-height: 18px; }

        /* ── Empty / toast ── */
        .empty-state {
            text-align: center; padding: 40px 20px; color: var(--af-ink-soft); font-size: 14px;
            border: 1px dashed var(--af-line); border-radius: var(--radius); margin: 16px;
        }
        .empty-state .icon { font-size: 36px; margin-bottom: 10px; }
        #toast {
            position: fixed; right: 18px; bottom: 18px;
            background: var(--af-brown); color: #fff; padding: 12px 16px;
            border-radius: 12px; box-shadow: 0 16px 36px rgba(0,0,0,0.18);
            font-weight: 600; font-size: 13px; display: none; z-index: 300;
            animation: fadeIn 0.2s ease;
        }
        #toast.error { background: var(--af-danger); }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity:1; transform:none; } }
        .muted { color: var(--af-ink-soft); }
    </style>
</head>
<body>

<!-- ── TOPBAR ── -->
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

<main>

    <!-- Hero -->
    <div class="hero">
        <div>
            <h2>Store Supplies Inventory</h2>
            <p>Track all store supplies — ingredients, packaging, cleaning materials and more.</p>
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

    <!-- Toolbar: search + category filter -->
    <div class="toolbar">
        <input type="text" id="searchBox" placeholder="🔍  Search supplies…">
        <div class="cat-filter" id="catFilter">
            <button class="cat-pill active" data-cat="">All</button>
        </div>
    </div>

    <!-- Items table -->
    <div class="table-card">
        <div class="table-head">
            <h3>Supplies</h3>
            <span class="muted" style="font-size:12px;" id="rowCount"></span>
        </div>
        <div id="tableWrap">
            <div class="empty-state"><div class="icon">📦</div>Loading inventory…</div>
        </div>
    </div>

    <!-- Category manager (collapsible) -->
    <div class="cat-manager">
        <div class="cat-manager-head" id="catManagerToggle">
            <h3>🏷️ Manage Categories</h3>
            <span class="muted" style="font-size:12px;">Click to expand</span>
        </div>
        <div class="cat-manager-body" id="catManagerBody">
            <div class="cat-grid" id="catTagGrid">
                <span class="muted" style="font-size:13px;">Loading…</span>
            </div>
            <div class="cat-add-row">
                <input type="text" id="newCatName" placeholder="New category name (e.g. Dry Goods, Cleaning…)" maxlength="100">
                <button class="btn btn-primary btn-sm" id="btnAddCat" type="button">+ Add</button>
            </div>
            <div class="cat-error" id="catError"></div>
        </div>
    </div>

</main>

<!-- ── ADD / EDIT ITEM MODAL ── -->
<div class="modal-overlay" id="itemModal">
    <div class="modal">
        <h2 id="modalTitle">Add Supply Item</h2>
        <form class="modal-form" id="itemForm" autocomplete="off">
            <input type="hidden" id="itemId">
            <div class="field">
                <label>Item Name *</label>
                <input type="text" id="fieldName" placeholder="e.g. Salt" required>
            </div>
            <div class="field">
                <label>Category</label>
                <select id="fieldCategory">
                    <option value="">— No category —</option>
                </select>
            </div>
            <div class="field-row" id="qtyRow">
                <div class="field">
                    <label>Starting Quantity *</label>
                    <input type="number" id="fieldQty" step="0.01" min="0" placeholder="0" required>
                </div>
                <div class="field">
                    <label>Unit *</label>
                    <input type="text" id="fieldUnit" placeholder="kg, L, pcs, bags…" required list="unitSuggestions">
                    <datalist id="unitSuggestions">
                        <option value="kg"><option value="g"><option value="L"><option value="mL">
                        <option value="pcs"><option value="bags"><option value="boxes"><option value="cans">
                        <option value="bottles"><option value="rolls"><option value="packs">
                    </datalist>
                </div>
            </div>
            <div class="field-row" id="editUnitRow" style="display:none;">
                <div class="field">
                    <label>Unit *</label>
                    <input type="text" id="fieldUnitEdit" placeholder="kg, L, pcs, bags…" required list="unitSuggestions">
                </div>
                <div class="field">
                    <label>Low-Stock Alert At</label>
                    <input type="number" id="fieldThreshold" step="0.01" min="0" placeholder="5">
                </div>
            </div>
            <div class="field" id="thresholdRow">
                <label>Low-Stock Alert At</label>
                <input type="number" id="fieldThresholdNew" step="0.01" min="0" placeholder="5">
            </div>
            <div class="field">
                <label>Supplier Notes</label>
                <textarea id="fieldNotes" placeholder="Optional — supplier info, reorder details…"></textarea>
            </div>
            <div class="modal-error" id="modalError"></div>
            <div class="modal-actions">
                <button type="button" class="btn btn-ghost" id="btnModalCancel">Cancel</button>
                <button type="submit" class="btn btn-primary" id="btnModalSave">Save Item</button>
            </div>
        </form>
    </div>
</div>

<div id="toast"></div>

<script>
(() => {
    'use strict';
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    const API  = '/api';

    let allItems      = [];
    let allCategories = [];
    let activeCatId   = '';   // '' = All
    let searchQuery   = '';
    let expandedId    = null;
    let editingId     = null;

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

    // ── API ───────────────────────────────────────
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

    // ── Load everything ───────────────────────────
    async function loadAll() {
        const [cats, items] = await Promise.all([
            apiFetch('/store-categories'),
            apiFetch('/store-items'),
        ]);
        allCategories = cats;
        allItems      = items;
        renderStats();
        renderCategoryFilter();
        renderTable();
        renderCategoryManager();
        populateCategoryDropdown();
        document.getElementById('lastUpdated').textContent =
            'Last updated ' + new Date().toLocaleTimeString();
    }

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
                `<div class="empty-state"><div class="icon">⚠️</div>${esc(e.message)}</div>`;
        }
    }

    // ── Stats ─────────────────────────────────────
    function renderStats() {
        document.getElementById('statTotal').textContent = allItems.length;
        document.getElementById('statOk').textContent    = allItems.filter(i => i.status === 'in_stock').length;
        document.getElementById('statLow').textContent   = allItems.filter(i => i.status === 'low_stock').length;
        document.getElementById('statOut').textContent   = allItems.filter(i => i.status === 'out_of_stock').length;
    }

    // ── Category filter pills ─────────────────────
    function renderCategoryFilter() {
        const wrap = document.getElementById('catFilter');
        wrap.innerHTML = `<button class="cat-pill${activeCatId === '' ? ' active' : ''}" data-cat="">All</button>`;
        allCategories.forEach(c => {
            const btn = document.createElement('button');
            btn.className = 'cat-pill' + (activeCatId == c.id ? ' active' : '');
            btn.dataset.cat = c.id;
            btn.textContent = c.name;
            wrap.appendChild(btn);
        });
    }

    // ── Table ─────────────────────────────────────
    function filteredItems() {
        let items = allItems;
        if (activeCatId !== '') {
            items = items.filter(i => String(i.store_category_id) === String(activeCatId));
        }
        if (searchQuery) {
            items = items.filter(i =>
                i.name.toLowerCase().includes(searchQuery) ||
                (i.category?.name || '').toLowerCase().includes(searchQuery)
            );
        }
        return items;
    }

    function statusPill(s) {
        const m = { in_stock: ['in-stock','● In Stock'], low_stock: ['low-stock','⚠ Low Stock'], out_of_stock: ['out','✕ Out of Stock'] };
        const [cls, label] = m[s] || ['',''];
        return `<span class="pill ${cls}">${label}</span>`;
    }

    function qtyClass(s) {
        return s === 'in_stock' ? 'ok' : s === 'low_stock' ? 'low' : 'danger';
    }

    function renderTable() {
        const items = filteredItems();
        const wrap = document.getElementById('tableWrap');
        document.getElementById('rowCount').textContent = `${items.length} item${items.length !== 1 ? 's' : ''}`;

        if (!items.length) {
            wrap.innerHTML = `<div class="empty-state"><div class="icon">📦</div>No items found.</div>`;
            return;
        }

        const rows = items.map(item => {
            const isExp   = expandedId === item.id;
            const catName = item.category?.name ?? '—';
            return `
            <tr class="item-row${isExp ? ' expanded' : ''}" data-id="${item.id}">
                <td data-label="Item" class="name-cell">${esc(item.name)}</td>
                <td data-label="Category"><span class="pill cat">${esc(catName)}</span></td>
                <td data-label="Qty" class="qty-cell ${qtyClass(item.status)}">${fmtQty(item.quantity)} <span style="font-size:11px;font-weight:500;">${esc(item.unit)}</span></td>
                <td data-label="Status">${statusPill(item.status)}</td>
                <td data-label="Alert At" class="muted">≤ ${fmtQty(item.low_stock_threshold)} ${esc(item.unit)}</td>
                <td data-label="Actions">
                    <div class="row-actions">
                        <button class="btn btn-ghost btn-sm btn-expand" data-id="${item.id}">${isExp ? '▲ Close' : '▼ Adjust'}</button>
                        <button class="btn btn-ghost btn-sm btn-edit" data-id="${item.id}" title="Edit">✎</button>
                        <button class="btn btn-danger btn-sm btn-delete" data-id="${item.id}" title="Delete">✕</button>
                    </div>
                </td>
            </tr>
            ${isExp ? expandRow(item) : `<tr class="expand-row" data-expand="${item.id}"></tr>`}`;
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
                            <div class="adjust-form">
                                <div class="adjust-row">
                                    <button class="sign-btn minus" data-sign="-1" data-item="${item.id}" title="Remove">−</button>
                                    <input type="number" id="adjQty-${item.id}" step="0.01" min="0.01" placeholder="Quantity" style="flex:1;">
                                    <button class="sign-btn plus"  data-sign="1"  data-item="${item.id}" title="Add">+</button>
                                </div>
                                <input type="text" id="adjReason-${item.id}" placeholder="Reason — e.g. Weekly restock, Spillage…">
                                <div class="adjust-actions">
                                    <button class="btn btn-ghost btn-sm" style="flex:1;" data-action="use"     data-item="${item.id}">− Record Usage</button>
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

    // ── History ───────────────────────────────────
    async function loadHistory(itemId) {
        const el = document.getElementById(`history-${itemId}`);
        if (!el) return;
        try {
            const entries = await apiFetch(`/store-items/${itemId}/history`);
            if (!entries.length) { el.innerHTML = `<div class="muted" style="font-size:12px;">No adjustments yet.</div>`; return; }
            el.innerHTML = entries.map(e => {
                const d = parseFloat(e.quantity_change);
                return `
                <div class="history-entry">
                    <div class="delta ${d >= 0 ? 'pos' : 'neg'}">${d >= 0 ? '+' : ''}${fmtQty(e.quantity_change)}</div>
                    <div>
                        <div class="h-reason">${esc(e.reason || 'No reason given')}</div>
                        <div class="h-meta">${esc(e.adjusted_by || 'Unknown')} · ${new Date(e.created_at).toLocaleString()}</div>
                    </div>
                </div>`;
            }).join('');
        } catch (err) {
            el.innerHTML = `<div class="muted" style="font-size:12px;">Failed: ${esc(err.message)}</div>`;
        }
    }

    // ── Adjust stock ──────────────────────────────
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
            if (expandedId === itemId) loadHistory(itemId);
        } catch (e) { errEl.textContent = e.message; }
    }

    // ── Table event delegation ─────────────────────
    document.getElementById('tableWrap').addEventListener('click', async e => {
        const btn = e.target.closest('button');
        if (!btn) return;

        if (btn.classList.contains('btn-expand')) {
            const id = parseInt(btn.dataset.id);
            expandedId = expandedId === id ? null : id;
            renderTable();
            if (expandedId) loadHistory(expandedId);
            return;
        }
        if (btn.classList.contains('btn-edit')) {
            const item = allItems.find(i => i.id === parseInt(btn.dataset.id));
            if (item) openModal(item);
            return;
        }
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
        if (btn.dataset.sign) { doAdjust(parseInt(btn.dataset.item), parseInt(btn.dataset.sign)); return; }
        if (btn.dataset.action) {
            doAdjust(parseInt(btn.dataset.item), btn.dataset.action === 'restock' ? 1 : -1);
        }
    });

    // ── Category filter clicks ─────────────────────
    document.getElementById('catFilter').addEventListener('click', e => {
        const btn = e.target.closest('.cat-pill');
        if (!btn) return;
        activeCatId = btn.dataset.cat;
        document.querySelectorAll('.cat-pill').forEach(b => b.classList.toggle('active', b === btn));
        renderTable();
    });

    // ── Search ────────────────────────────────────
    document.getElementById('searchBox').addEventListener('input', e => {
        searchQuery = e.target.value.toLowerCase().trim();
        renderTable();
    });

    // ── Category dropdown in modal ─────────────────
    function populateCategoryDropdown() {
        const sel = document.getElementById('fieldCategory');
        const cur = sel.value;
        sel.innerHTML = '<option value="">— No category —</option>';
        allCategories.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = c.name;
            if (String(c.id) === String(cur)) opt.selected = true;
            sel.appendChild(opt);
        });
    }

    // ── Category manager ──────────────────────────
    function renderCategoryManager() {
        const grid = document.getElementById('catTagGrid');
        if (!allCategories.length) {
            grid.innerHTML = `<span class="muted" style="font-size:13px;">No categories yet. Add one below.</span>`;
            return;
        }
        grid.innerHTML = allCategories.map(c => `
            <span class="cat-tag">
                ${esc(c.name)}
                <button data-cat-del="${c.id}" title="Delete category">✕</button>
            </span>`).join('');
    }

    document.getElementById('catManagerToggle').addEventListener('click', () => {
        const body = document.getElementById('catManagerBody');
        const isOpen = body.classList.toggle('open');
        document.querySelector('#catManagerToggle span.muted').textContent =
            isOpen ? 'Click to collapse' : 'Click to expand';
    });

    document.getElementById('catTagGrid').addEventListener('click', async e => {
        const btn = e.target.closest('[data-cat-del]');
        if (!btn) return;
        const id  = btn.dataset.catDel;
        const cat = allCategories.find(c => String(c.id) === String(id));
        const itemsInCat = allItems.filter(i => String(i.store_category_id) === String(id)).length;
        const warn = itemsInCat > 0 ? ` (${itemsInCat} item${itemsInCat > 1 ? 's' : ''} will become uncategorised)` : '';
        if (!confirm(`Delete category "${cat?.name}"?${warn}`)) return;
        try {
            await apiFetch(`/store-categories/${id}`, { method: 'DELETE' });
            toast('Category deleted');
            await loadAll();
        } catch (err) { toast(err.message, true); }
    });

    document.getElementById('btnAddCat').addEventListener('click', async () => {
        const input  = document.getElementById('newCatName');
        const errEl  = document.getElementById('catError');
        const name   = input.value.trim();
        if (!name) { errEl.textContent = 'Enter a category name.'; return; }
        errEl.textContent = '';
        try {
            await apiFetch('/store-categories', { method: 'POST', body: JSON.stringify({ name }) });
            toast(`✓ Category "${name}" added`);
            input.value = '';
            await loadAll();
        } catch (err) { errEl.textContent = err.message; }
    });

    document.getElementById('newCatName').addEventListener('keydown', e => {
        if (e.key === 'Enter') { e.preventDefault(); document.getElementById('btnAddCat').click(); }
    });

    // ── Item modal ────────────────────────────────
    function openModal(item = null) {
        editingId = item ? item.id : null;
        document.getElementById('modalTitle').textContent = item ? 'Edit Item' : 'Add Supply Item';
        document.getElementById('itemId').value       = item?.id ?? '';
        document.getElementById('fieldName').value    = item?.name ?? '';
        document.getElementById('fieldCategory').value= item?.store_category_id ?? '';
        document.getElementById('fieldNotes').value   = item?.supplier_notes ?? '';
        document.getElementById('modalError').textContent = '';

        if (item) {
            // Edit: show unit+threshold row, hide qty row
            document.getElementById('qtyRow').style.display       = 'none';
            document.getElementById('thresholdRow').style.display = 'none';
            document.getElementById('editUnitRow').style.display  = '';
            document.getElementById('fieldUnitEdit').value        = item.unit ?? '';
            document.getElementById('fieldThreshold').value       = item.low_stock_threshold ?? 5;
            document.getElementById('fieldQty').removeAttribute('required');
            document.getElementById('fieldUnit').removeAttribute('required');
            document.getElementById('fieldUnitEdit').setAttribute('required', '');
        } else {
            // New: show qty+unit row
            document.getElementById('qtyRow').style.display       = '';
            document.getElementById('thresholdRow').style.display = '';
            document.getElementById('editUnitRow').style.display  = 'none';
            document.getElementById('fieldQty').setAttribute('required', '');
            document.getElementById('fieldUnit').setAttribute('required', '');
            document.getElementById('fieldUnitEdit').removeAttribute('required');
            document.getElementById('fieldQty').value             = '';
            document.getElementById('fieldUnit').value            = '';
            document.getElementById('fieldThresholdNew').value    = 5;
        }

        populateCategoryDropdown();
        if (item) document.getElementById('fieldCategory').value = item.store_category_id ?? '';

        document.getElementById('itemModal').classList.add('open');
        setTimeout(() => document.getElementById('fieldName').focus(), 80);
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
        const errEl  = document.getElementById('modalError');
        const saveBtn = document.getElementById('btnModalSave');
        errEl.textContent = '';
        saveBtn.disabled  = true;

        const isEdit  = !!editingId;
        const catVal  = document.getElementById('fieldCategory').value;
        const payload = {
            name:                document.getElementById('fieldName').value.trim(),
            store_category_id:   catVal ? parseInt(catVal) : null,
            low_stock_threshold: parseFloat(isEdit
                ? document.getElementById('fieldThreshold').value
                : document.getElementById('fieldThresholdNew').value) || 5,
            supplier_notes:      document.getElementById('fieldNotes').value.trim() || null,
        };

        if (isEdit) {
            payload.unit = document.getElementById('fieldUnitEdit').value.trim();
        } else {
            payload.unit     = document.getElementById('fieldUnit').value.trim();
            payload.quantity = parseFloat(document.getElementById('fieldQty').value) || 0;
        }

        try {
            if (isEdit) {
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
            saveBtn.disabled = false;
        }
    });

    // ── Refresh ───────────────────────────────────
    document.getElementById('btnRefresh').addEventListener('click', loadAll);

    // ── Helpers ───────────────────────────────────
    function esc(s) {
        return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function fmtQty(n) {
        const f = parseFloat(n);
        return isNaN(f) ? '0' : (f % 1 === 0 ? f.toString() : f.toFixed(2));
    }

    // ── Boot ──────────────────────────────────────
    loadAll().catch(err => {
        document.getElementById('tableWrap').innerHTML =
            `<div class="empty-state"><div class="icon">⚠️</div>${esc(err.message)}</div>`;
    });
    setInterval(loadItems, 30_000);

})();
</script>
</body>
</html>
