<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>POS | Acie Fraiche</title>
    <link rel="icon" href="/assets/logo2.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700&family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
    <style>
        :root {
            --af-gold: #cc9933;
            --af-brown: #523700;
            --af-ink: #0f0b05;
            --af-cream: #f7f1e7;
            --af-line: rgba(15, 11, 5, 0.12);
            --af-shadow: 0 12px 30px rgba(0,0,0,0.06);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Manrope', system-ui, -apple-system, sans-serif;
            color: var(--af-ink);
            background:
                radial-gradient(circle at 14% 18%, rgba(204,153,51,0.1), transparent 28%),
                radial-gradient(circle at 88% 12%, rgba(82,55,0,0.08), transparent 22%),
                var(--af-cream);
        }
        header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 14px 18px; background: #fff; border-bottom: 1px solid var(--af-line);
            position: sticky; top:0; z-index: 10;
            box-shadow: 0 10px 24px rgba(0,0,0,0.05);
        }
        main { padding: 18px; display: grid; gap: 14px; max-width: 1200px; margin: 0 auto; }
        .card { background:#fff; border:1px solid var(--af-line); border-radius:16px; padding:16px; box-shadow:var(--af-shadow); }
        h1 { margin:0; font-size:22px; font-family:'Playfair Display', Georgia, serif; }
        .brand-title { font-family:'Playfair Display', Georgia, serif; font-size:18px; margin:0; }
        .brand-tag { color: rgba(0,0,0,0.6); font-size:13px; margin:2px 0 0; }
        label { font-size:13px; color:rgba(0,0,0,0.6); display:block; margin-bottom:6px; }
        input, select {
            width:100%; padding:12px; border-radius:12px; border:1px solid var(--af-line);
            font: inherit; background:#fff;
        }
        button {
            border:none; border-radius:12px; padding:12px 14px; font-weight:700; cursor:pointer;
            transition: transform 0.1s ease;
        }
        button:active { transform: translateY(1px); }
        .btn-primary { background: var(--af-ink); color:#fff; }
        .btn-ghost { background:#fff; color:var(--af-ink); border:1px solid var(--af-line); }
        .grid { display:grid; gap:12px; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); }
        .muted { color: rgba(0,0,0,0.6); font-size:13px; }
        .status { margin-top:6px; }
        .list { display:grid; gap:10px; max-height:360px; overflow:auto; }
        .item { border:1px solid var(--af-line); border-radius:14px; padding:12px; background:#fff; display:flex; justify-content:space-between; gap:10px; align-items:center; }
        .pill { border:1px solid var(--af-line); border-radius:999px; padding:6px 10px; font-size:12px; color:rgba(0,0,0,0.7); display:inline-flex; gap:6px; align-items:center; }
        table { width:100%; border-collapse: collapse; }
        th, td { padding:6px 4px; text-align:left; font-size:13px; }
        th { border-bottom:1px solid var(--af-line); }
    </style>
</head>
<body>
    <header>
        <div style="display:flex; align-items:center; gap:12px;">
            <img src="/assets/logo2.png" alt="AFC" style="width:42px; height:42px; border-radius:12px; object-fit:contain;">
            <div>
                <p class="brand-title">POS · Acie Fraiche</p>
                <p class="brand-tag">Signed in as {{ auth()->user()->name ?? 'POS User' }}</p>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-ghost">Logout</button>
        </form>
    </header>

    <main>
        <div class="card">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap; margin-bottom:12px;">
                <div>
                    <h1 style="margin-bottom:4px;">Quick POS</h1>
                    <p class="muted" style="margin:0;">Scan, add, and print in under a second.</p>
                </div>
                <div style="border:1px solid var(--af-line); border-radius:12px; padding:10px 12px; background:#fff; min-width:180px; text-align:right;">
                    <div class="muted" style="font-size:12px;">Cart total</div>
                    <div style="font-weight:800; color:var(--af-brown); font-size:20px;" id="posCartTotal">₦0</div>
                </div>
            </div>

            <div class="grid">
                <div style="border:1px solid var(--af-line); border-radius:14px; padding:12px; background:#fff;">
                    <label>Scan / Enter barcode</label>
                    <input id="posBarcodeInput" placeholder="Focus here and scan">
                    <div id="posScanStatus" class="muted status">Ready to scan.</div>
                    <div id="posSuggestions" style="margin-top:6px;"></div>
                    <div id="posLookupResult" style="margin-top:10px;"></div>
                    <div id="posKitchenFeed" style="margin-top:10px; display:grid; gap:6px;"></div>
                    <div id="posSavedCustomers" style="margin-top:10px; display:flex; gap:6px; flex-wrap:wrap;"></div>
                </div>
                <div style="border:1px solid var(--af-line); border-radius:14px; padding:12px; background:#fff;">
                    <div style="display:flex; justify-content:space-between; align-items:center; gap:8px;">
                        <h2 style="margin:0; font-size:18px;">Cart</h2>
                        <span class="pill">Fast print</span>
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

            <div class="grid" style="margin-top:12px;">
                <div style="border:1px solid var(--af-line); border-radius:14px; padding:12px; background:#fff;">
                    <label>Customer name (optional)</label>
                    <input id="posCustomerName" placeholder="Walk-in">
                </div>
                <div style="border:1px solid var(--af-line); border-radius:14px; padding:12px; background:#fff;">
                    <label>Customer phone</label>
                    <input id="posCustomerPhone" placeholder="080...">
                </div>
                <div style="border:1px solid var(--af-line); border-radius:14px; padding:12px; background:#fff;">
                    <label>Payment method</label>
                    <select id="posPaymentMethod">
                        <option value="cash">Cash</option>
                        <option value="card">Card</option>
                        <option value="transfer">Transfer</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div style="border:1px solid var(--af-line); border-radius:14px; padding:12px; background:#fff;">
                    <label>Kitchen handoff</label>
                    <label class="pill" style="margin-top:6px; display:flex; gap:8px; align-items:center; border-radius:12px; padding:8px 10px;">
                        <input id="posSendKitchen" type="checkbox" checked style="width:16px; height:16px; accent-color: var(--af-ink);">
                        <span class="muted" style="color:var(--af-ink);">Send to kitchen immediately</span>
                    </label>
                    <div class="muted" style="margin-top:6px;">Uncheck if you need to confirm on WhatsApp first.</div>
                </div>
            </div>

            <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                <button class="btn-primary" id="posCheckoutBtn">Complete Sale & Print Receipt</button>
                <div class="muted">Saves order with seller info and prints a receipt.</div>
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
    </main>

    <script>
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : null;

        const posBarcodeInput = document.getElementById('posBarcodeInput');
        const posLookupResult = document.getElementById('posLookupResult');
        const posScanStatus = document.getElementById('posScanStatus');
        const posSuggestions = document.getElementById('posSuggestions');
        const posCartList = document.getElementById('posCartList');
        const posCartTotal = document.getElementById('posCartTotal');
        const posSubtotal = document.getElementById('posSubtotal');
        const posGrandTotal = document.getElementById('posGrandTotal');
        const posCustomerName = document.getElementById('posCustomerName');
        const posCustomerPhone = document.getElementById('posCustomerPhone');
        const posPaymentMethod = document.getElementById('posPaymentMethod');
        const posCheckoutBtn = document.getElementById('posCheckoutBtn');
        const posDiscount = document.getElementById('posDiscount');
        const posTax = document.getElementById('posTax');
        const posParkBtn = document.getElementById('posParkBtn');
        const posParkedList = document.getElementById('posParkedList');
        const posSavedCustomers = document.getElementById('posSavedCustomers');
        const posSendKitchen = document.getElementById('posSendKitchen');
        const posKitchenFeed = document.getElementById('posKitchenFeed');
        const barcodeCache = {};
        let menuCacheReady = false;
        let menuCache = [];
        let menuCachePromise = null;

        let posCart = [];
        let lastLookup = null;
        let scanDebounce = null;
        let lookupInFlight = false;
        let menuPoller = null;
        let ordersPoller = null;
        let posOrders = [];

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
                    if (onError) onError(err);
                    else console.warn('Poller task failed', err);
                } finally {
                    running = false;
                }
            };

            const start = () => {
                if (timer) return;
                if (immediate) tick();
                timer = setInterval(tick, intervalMs);
            };
            document.addEventListener('visibilitychange', () => {
                if (timer && shouldRun()) tick();
            });
            return { start };
        };

        function setPosStatus(message, tone = 'muted') {
            if (!posScanStatus) return;
            posScanStatus.textContent = message;
            posScanStatus.style.color = tone === 'error' ? '#b91c1c' : 'rgba(0,0,0,0.7)';
        }

        const renderPosOrders = () => {
            if (!posKitchenFeed) return;
            const active = posOrders
                .filter(o => (o.kitchen_status || 'queued') !== 'served')
                .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
                .slice(0, 5);
            if (!active.length) {
                posKitchenFeed.innerHTML = '<div class="muted" style="font-size:12px;">No kitchen updates yet.</div>';
                return;
            }
            posKitchenFeed.innerHTML = active.map(o => {
                const eta = o.kitchen_eta_minutes
                    ? `${o.kitchen_eta_minutes}m`
                    : (o.kitchen_eta_at ? new Date(o.kitchen_eta_at).toLocaleTimeString() : 'ETA pending');
                return `
                    <div style="border:1px solid var(--af-line); border-radius:10px; padding:8px; background:#fff;">
                        <div style="display:flex; justify-content:space-between; gap:6px; align-items:center;">
                            <strong>${o.code || 'Order'}</strong>
                            <span class="pill">${o.kitchen_status || 'queued'}</span>
                        </div>
                        <div class="muted" style="font-size:12px;">ETA: ${eta}</div>
                    </div>
                `;
            }).join('');
        };

        const loadPosOrders = async () => {
            try {
                const res = await apiFetch('/api/orders?all=1');
                if (!res.ok) return;
                const data = await res.json();
                posOrders = Array.isArray(data) ? data : (data.data || []);
                renderPosOrders();
            } catch (e) {
                console.warn('Could not load orders', e);
            }
        };

        const renderSuggestions = (items) => {
            if (!posSuggestions) return;
            if (!items.length) {
                posSuggestions.innerHTML = '';
                return;
            }
            posSuggestions.innerHTML = items.map(item => `
                <button class="btn-ghost" data-suggest-id="${item.id}" style="display:block; width:100%; text-align:left; padding:8px 10px; margin-top:4px;">
                    ${item.name} <span class="muted">(${item.barcode || 'no barcode'})</span>
                </button>
            `).join('');
        };

        const loadMenuCacheFromStorage = () => {
            try {
                const cached = JSON.parse(localStorage.getItem('pos_menu_cache') || '[]');
                if (Array.isArray(cached) && cached.length) {
                    menuCache = cached;
                    menuCacheReady = true;
                    Object.keys(barcodeCache).forEach(k => delete barcodeCache[k]);
                    cached.forEach(item => {
                        if (item.barcode) barcodeCache[item.barcode] = item;
                    });
                }
            } catch { /* ignore */ }
        };

        const findNameMatches = (term) => {
            if (!term || term.length < 2) return [];
            const t = term.toLowerCase();
            const source = menuCache.length ? menuCache : Object.values(barcodeCache);
            return source.filter(item =>
                (item.name || '').toLowerCase().includes(t) && item.is_sold_out !== true
            ).slice(0, 5);
        };

        const ensureMenuCache = async () => {
            if (menuCacheReady) return menuCache;
            if (!menuCachePromise) {
                menuCachePromise = prefetchMenuCache().catch(() => []);
            }
            return menuCachePromise;
        };

        if (posSuggestions) {
            posSuggestions.addEventListener('click', (e) => {
                const btn = e.target.closest('[data-suggest-id]');
                if (!btn) return;
                const id = Number(btn.getAttribute('data-suggest-id'));
                const item = menuCache.find(i => i.id === id);
                if (!item) return;
                renderSuggestions([]);
                showLookupResult(item);
                addToPosCart(item);
                setPosStatus(`Added ${item.name}. Ready for next scan.`);
                if (posBarcodeInput) {
                    posBarcodeInput.value = '';
                    posBarcodeInput.focus();
                }
            });
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
                    <div class="item">
                        <div>
                            <strong>${item.name}</strong>
                            <div style="display:flex; gap:6px; flex-wrap:wrap; margin-top:6px;">
                                <span class="pill">Barcode: ${item.barcode || 'n/a'}</span>
                                <span class="pill">₦${Number(item.price).toLocaleString()} × ${item.qty}</span>
                                <span class="pill">Line: ₦${line.toLocaleString()}</span>
                            </div>
                        </div>
                        <div style="display:flex; gap:6px;">
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
            posLookupResult.innerHTML = `
                <div class="item" style="flex-direction:column; align-items:flex-start;">
                    <strong>${item.name}</strong>
                    <div style="display:flex; gap:6px; flex-wrap:wrap; margin-top:6px;">
                        <span class="pill">Price: ₦${Number(item.price).toLocaleString()}</span>
                        <span class="pill">Barcode: ${item.barcode}</span>
                        <span class="pill">${item.category && item.category.name ? item.category.name : 'Uncategorized'}</span>
                    </div>
                </div>
            `;
        }

        function showLookupError(message) {
            if (!posLookupResult) return;
            lastLookup = null;
            posLookupResult.innerHTML = `<div class="muted">${message}</div>`;
        }

        async function prefetchMenuCache() {
            try {
                const res = await safeRequest('/api/menu-items');
                const data = await res.json();
                menuCache = Array.isArray(data) ? data : [];
                Object.keys(barcodeCache).forEach(k => delete barcodeCache[k]);
                menuCache.forEach(item => {
                    if (item.barcode) barcodeCache[item.barcode] = item;
                });
                localStorage.setItem('pos_menu_cache', JSON.stringify(menuCache.slice(0, 200)));
                menuCacheReady = true;
                menuCachePromise = null;
            } catch (e) {
                console.warn('Menu prefetch failed; will fall back to live lookup.', e);
            }
        }

        async function lookupBarcode(barcode, { addToCartOnSuccess = false } = {}) {
            if (!barcode) return;

            const isName = /^[a-zA-Z\s]+$/.test(barcode);

            // Manual name search if input is alphabetic
            if (isName) {
                await ensureMenuCache();
                const matches = findNameMatches(barcode);
                renderSuggestions(matches);
                if (!matches.length) {
                    showLookupError('No item matches that name.');
                    setPosStatus('No match found.', 'error');
                    return;
                }
                const item = matches[0];
                showLookupResult(item);
                if (addToCartOnSuccess) {
                    addToPosCart(item);
                    setPosStatus(`Added ${item.name}. Ready for next scan.`);
                    if (posBarcodeInput) {
                        posBarcodeInput.value = '';
                        posSuggestions.innerHTML = '';
                        posBarcodeInput.focus();
                    }
                } else {
                    setPosStatus('Found. Add to cart or scan next.');
                }
                return;
            }

            if (barcodeCache[barcode]) {
                const item = barcodeCache[barcode];
                if (item.is_sold_out) {
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

            if (lookupInFlight) return;
            lookupInFlight = true;
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
                lookupInFlight = false;
                if (posBarcodeInput) posBarcodeInput.select();
            }
        }

        if (posBarcodeInput) posBarcodeInput.addEventListener('keydown', async (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                const code = e.target.value.trim();
                if (!code) return;
                await lookupBarcode(code, { addToCartOnSuccess: true });
            }
        });

        if (posBarcodeInput) posBarcodeInput.addEventListener('input', async (e) => {
            const code = e.target.value.trim();
            clearTimeout(scanDebounce);
            if (!code) {
                setPosStatus('Ready to scan.');
                renderSuggestions([]);
                return;
            }
            const isName = /^[a-zA-Z\s]+$/.test(code);
            if (isName) {
                await ensureMenuCache();
                const matches = findNameMatches(code);
                renderSuggestions(matches);
                setPosStatus(matches.length ? 'Select an item or press Enter to add.' : 'No match found.');
                return;
            }
            scanDebounce = setTimeout(() => lookupBarcode(code, { addToCartOnSuccess: true }), 10);
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

            const resetBtn = () => {
                if (!posCheckoutBtn) return;
                posCheckoutBtn.disabled = false;
                posCheckoutBtn.textContent = 'Complete Sale & Print Receipt';
            };

            try {
                if (posCheckoutBtn) {
                    posCheckoutBtn.disabled = true;
                    posCheckoutBtn.textContent = 'Saving...';
                }
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
            } catch (e) {
                alert(e.message || 'Could not save sale.');
            } finally {
                resetBtn();
            }
        });

        if (posDiscount) posDiscount.addEventListener('input', renderTotals);
        if (posTax) posTax.addEventListener('input', renderTotals);

        const loadSavedCustomers = () => {
            try {
                const data = JSON.parse(localStorage.getItem('pos_saved_customers') || '[]');
                return Array.isArray(data) ? data.slice(0, 6) : [];
            } catch { return []; }
        };
        const persistSavedCustomers = (list) => {
            localStorage.setItem('pos_saved_customers', JSON.stringify(list.slice(0, 6)));
        };
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

        const loadParkedTickets = () => {
            try {
                const data = JSON.parse(localStorage.getItem('pos_parked_tickets') || '[]');
                return Array.isArray(data) ? data : [];
            } catch { return []; }
        };
        const persistParkedTickets = (list) => {
            localStorage.setItem('pos_parked_tickets', JSON.stringify(list.slice(0, 10)));
        };
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
                    <div style="display:flex; gap:6px;">
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
            persistParkedTickets(list);
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

        renderPosCart();
        renderSavedCustomers();
        renderParkedTickets();
        loadMenuCacheFromStorage();
        if (posBarcodeInput) posBarcodeInput.focus();
        prefetchMenuCache().catch(() => {});
        menuPoller = createPoller(prefetchMenuCache, 20000, {
            onError: (err) => console.warn('Menu refresh failed', err),
        });
        menuPoller.start();
        ordersPoller = createPoller(loadPosOrders, 5000, {
            onError: (err) => console.warn('Orders refresh failed', err),
        });
        ordersPoller.start();

        if (posSuggestions) {
            posSuggestions.addEventListener('click', (e) => {
                const btn = e.target.closest('[data-suggest-id]');
                if (!btn) return;
                const id = Number(btn.getAttribute('data-suggest-id'));
                const item = menuCache.find(i => i.id === id);
                if (!item) return;
                addToPosCart(item);
                renderSuggestions([]);
                if (posBarcodeInput) {
                    posBarcodeInput.value = '';
                    posBarcodeInput.focus();
                }
                setPosStatus(`Added ${item.name}. Ready for next scan.`);
            });
        }
    </script>
</body>
</html>
