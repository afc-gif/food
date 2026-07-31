<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Staff Desk | Acie Fraiche</title>
    <link rel="icon" href="/assets/logo2.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700&family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #0f0b05;
            --line: rgba(15, 11, 5, 0.12);
            --card: #fffdf8;
            --accent: #523700;
            --accent-soft: rgba(82, 55, 0, 0.1);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Manrope', system-ui, -apple-system, sans-serif;
            background:
                radial-gradient(circle at 12% 18%, rgba(204,153,51,0.1), transparent 26%),
                radial-gradient(circle at 82% 10%, rgba(82,55,0,0.08), transparent 22%),
                #f7f1e7;
            color: var(--ink);
        }
        header {
            display:flex; align-items:center; justify-content:space-between;
            padding:16px 20px;
            background:#fff;
            border-bottom:1px solid var(--line);
            box-shadow:0 12px 24px rgba(0,0,0,0.05);
            position:sticky; top:0; z-index:10;
        }
        header h1 { margin:0; font-size:20px; font-family:'Playfair Display', Georgia, serif; }
        header .muted { margin:2px 0 0; color:rgba(0,0,0,0.6); font-size:13px; }
        main { padding:20px; display:grid; gap:16px; max-width:1200px; margin:0 auto 32px; }
        .card {
            background:var(--card);
            border:1px solid var(--line);
            border-radius:16px;
            padding:16px;
            box-shadow:0 14px 30px rgba(0,0,0,0.06);
        }
        .section-head { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; }
        .pill { display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:999px; border:1px solid var(--line); font-size:12px; }
        .pill.warn { background:#fef3c7; border-color:#fcd34d; color:#92400e; font-weight:700; }
        .pill.success { background:#ecfdf3; border-color:#bbf7d0; color:#166534; font-weight:700; }
        .pill.neutral { background:#fff; color:rgba(0,0,0,0.7); }
        .pill.note { background:#fff7ed; border-color:#fed7aa; color:#9a3412; }
        table { width:100%; border-collapse:collapse; }
        th, td { padding:10px; text-align:left; border-bottom:1px solid var(--line); font-size:14px; vertical-align:top; }
        th { background:#fff; color:rgba(0,0,0,0.6); font-weight:700; }
        tr:last-child td { border-bottom:none; }
        .actions { display:flex; gap:8px; flex-wrap:wrap; }
        button {
            border:1px solid var(--line);
            background:#fff;
            color:var(--accent);
            border-radius:12px;
            padding:9px 12px;
            font-weight:700;
            cursor:pointer;
            transition: transform 0.08s ease;
        }
        button:active { transform: translateY(1px); }
        button.primary { background:var(--accent); color:#fff; }
        button.danger { background:#fff1f2; border-color:#fecdd3; color:#b91c1c; }
        .muted { color:rgba(0,0,0,0.6); }
        .grid { display:grid; gap:14px; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); }
        .stat { border:1px dashed var(--line); border-radius:14px; padding:12px; background:#fff; box-shadow:0 8px 20px rgba(0,0,0,0.04); }
        .stat h3 { margin:0; font-size:22px; }
    </style>
</head>
<body>
    <header>
        <div style="display:flex; align-items:center; gap:12px;">
            <img src="/assets/logo2.png" alt="Acie Fraiche" style="width:46px; height:46px; border-radius:14px; border:1px solid var(--line); background:#fff; padding:6px;">
            <div>
                <h1>Staff Desk</h1>
                <p class="muted">Confirm orders, push to kitchen, update customers.</p>
            </div>
        </div>
        <div style="display:flex; align-items:center; gap:10px;">
            <div class="pill neutral" id="connection">Connecting…</div>
            <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                @csrf
                <button type="submit" style="padding:8px 14px; font-size:13px;">Logout</button>
            </form>
        </div>
    </header>

    <main>
        <div class="grid">
            <div class="stat">
                <div class="muted">Pending approvals</div>
                <h3 id="statPending">0</h3>
            </div>
            <div class="stat">
                <div class="muted">Approved & sent</div>
                <h3 id="statApproved">0</h3>
            </div>
        </div>

        <div class="card">
            <div class="section-head">
                <div>
                    <h2 style="margin:0;">Pending orders</h2>
                    <p class="muted" style="margin:4px 0 0;">Verify payment with the customer, then approve to send to kitchen.</p>
                </div>
                <span class="pill warn">Needs approval</span>
            </div>
            <div style="overflow:auto; margin-top:10px;">
                <table>
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Created</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="pendingBody">
                        <tr><td colspan="5" class="muted">Loading…</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="section-head">
                <div>
                    <h2 style="margin:0;">Approved / in kitchen</h2>
                    <p class="muted" style="margin:4px 0 0;">Track ETAs set by kitchen and message customers.</p>
                </div>
                <span class="pill success">Payment confirmed</span>
            </div>
            <div style="overflow:auto; margin-top:10px;">
                <table>
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Status</th>
                            <th>ETA</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="approvedBody">
                        <tr><td colspan="5" class="muted">Loading…</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const pendingBody = document.getElementById('pendingBody');
        const approvedBody = document.getElementById('approvedBody');
        const statPending = document.getElementById('statPending');
        const statApproved = document.getElementById('statApproved');
        const connectionEl = document.getElementById('connection');

        const money = (value) => '₦' + Number(value || 0).toLocaleString();
        const formatTime = (date) => new Date(date).toLocaleString(undefined, { hour: '2-digit', minute: '2-digit' });

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
                    if (data?.message) message = data.message;
                } catch (e) {
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
                return document.visibilityState !== 'hidden';
            };

            const tick = async () => {
                if (running || !shouldRun()) return;
                running = true;
                try { await task(); }
                catch (err) { onError ? onError(err) : console.warn('Poller failed', err); }
                finally { running = false; }
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

        let ordersCache = [];

        const summarize = () => {
            const relevant = ordersCache.filter(o => (o.channel || '').toLowerCase() !== 'pos');
            const pending = relevant.filter(o => (o.status || 'pending') === 'pending');
            const approved = relevant.filter(o => (o.status || '') !== 'pending');
            statPending.textContent = pending.length;
            statApproved.textContent = approved.length;
            renderPending(pending);
            renderApproved(approved);
        };

        const renderPending = (list) => {
            if (!list.length) {
                pendingBody.innerHTML = `<tr><td colspan="5" class="muted">No pending orders.</td></tr>`;
                return;
            }
            pendingBody.innerHTML = list.map(order => {
                const items = (order.items || []).map(i => `${i.quantity}× ${i.name}`).join(', ');
                const customer = order.customer_name || order.customer_phone
                    ? `${order.customer_name || 'Guest'}${order.customer_phone ? ' · ' + order.customer_phone : ''}`
                    : 'Walk-in';
                return `
                    <tr>
                        <td><strong>${order.code || 'Order'}</strong><div class="muted">${(order.channel || 'web').toUpperCase()}</div></td>
                        <td>${customer}<div class="muted">${money(order.total)}</div></td>
                        <td>${items || '<span class="muted">No items</span>'}</td>
                        <td>${formatTime(order.created_at)}</td>
                        <td>
                            <div class="actions">
                                <button class="primary" onclick="approveOrder(${order.id}, this)">Approve &amp; send</button>
                                <button class="danger" onclick="deletePendingOrder(${order.id}, this)">Delete</button>
                                <button onclick="shareOrderWhatsapp(${order.id})">WhatsApp</button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        };

        const renderApproved = (list) => {
            if (!list.length) {
                approvedBody.innerHTML = `<tr><td colspan="5" class="muted">No approved orders yet.</td></tr>`;
                return;
            }
            approvedBody.innerHTML = list.map(order => {
                const customer = order.customer_name || order.customer_phone
                    ? `${order.customer_name || 'Guest'}${order.customer_phone ? ' · ' + order.customer_phone : ''}`
                    : 'Walk-in';
                const status = order.kitchen_status || 'queued';
                const eta = order.kitchen_eta_minutes ? `${order.kitchen_eta_minutes}m` : (order.kitchen_eta_at ? new Date(order.kitchen_eta_at).toLocaleTimeString() : '—');
                const etaPill = order.kitchen_eta_minutes || order.kitchen_eta_at
                    ? `<span class="pill success">ETA ${eta}</span>`
                    : `<span class="pill warn">ETA missing</span>`;
                const notePill = order.kitchen_note ? `<span class="pill note">${order.kitchen_note}</span>` : '';
                return `
                    <tr>
                        <td><strong>${order.code || 'Order'}</strong><div class="muted">${(order.channel || 'web').toUpperCase()}</div></td>
                        <td>${customer}<div class="muted">${money(order.total)}</div></td>
                        <td><span class="pill neutral">${status}</span> ${notePill}</td>
                        <td>${etaPill}</td>
                        <td>
                            <div class="actions">
                                <button onclick="shareOrderWhatsapp(${order.id})">WhatsApp</button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        };

        const upsertOrder = (order) => {
            if (!order) return;
            ordersCache = [order, ...ordersCache.filter(o => o.id !== order.id)];
            summarize();
        };

        async function loadOrders() {
            try {
                const res = await safeRequest('/api/orders?all=1');
                const data = await res.json();
                ordersCache = Array.isArray(data) ? data : (data.data || []);
                summarize();
                setConnection(true);
            } catch (e) {
                console.error(e);
                setConnection(false, e.message);
            }
        }

        window.approveOrder = async (id, btn) => {
            const note = prompt('Kitchen note (optional, visible to kitchen):', '') || null;
            const text = btn?.textContent;
            if (btn) { btn.disabled = true; btn.textContent = 'Sending...'; }
            try {
                const res = await safeRequest(`/api/orders/${id}/approve`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ note }),
                });
                const order = await res.json();
                upsertOrder(order);
                // Redirect to print receipt page
                const printWindow = window.open(`/print/${order.id}`, 'print_receipt', 'width=600,height=800');
                if (printWindow) {
                    printWindow.focus();
                } else {
                    alert('Approved and sent to kitchen.\n\nPlease enable pop-ups to print receipt.');
                }
            } catch (e) {
                alert(e.message || 'Could not approve this order.');
            } finally {
                if (btn) { btn.disabled = false; btn.textContent = text || 'Approve'; }
            }
        };

        window.deletePendingOrder = async (id, btn) => {
            const order = ordersCache.find(o => o.id === id);
            const label = order?.code ? `order ${order.code}` : 'this pending order';
            if (!confirm(`Delete ${label}? This can only be done before approval and cannot be undone.`)) {
                return;
            }

            const text = btn?.textContent;
            if (btn) { btn.disabled = true; btn.textContent = 'Deleting...'; }
            try {
                await safeRequest(`/api/orders/${id}`, { method: 'DELETE' });
                ordersCache = ordersCache.filter(o => o.id !== id);
                summarize();
                showToast(`${order?.code || 'Order'} deleted.`);
            } catch (e) {
                alert(e.message || 'Could not delete this order.');
            } finally {
                if (btn) { btn.disabled = false; btn.textContent = text || 'Delete'; }
            }
        };

        window.shareOrderWhatsapp = (id) => {
            const order = ordersCache.find(o => o.id === id);
            if (!order) {
                alert('Order not found yet.');
                return;
            }
            const items = (order.items || []).map(i => `• ${i.quantity}x ${i.name}`).join('\n');
            const eta = order.kitchen_eta_minutes ? `ETA: ${order.kitchen_eta_minutes}m` : (order.kitchen_eta_at ? `ETA at ${new Date(order.kitchen_eta_at).toLocaleTimeString()}` : 'ETA: not set');
            const lines = [
                `Order ${order.code} (${order.channel || 'web'})`,
                order.customer_name || order.customer_phone ? `Customer: ${order.customer_name || 'Walk-in'}${order.customer_phone ? ' · ' + order.customer_phone : ''}` : '',
                `Total: ${money(order.total)}`,
                `Kitchen: ${order.kitchen_status || 'queued'}`,
                eta,
                order.kitchen_note ? `Note: ${order.kitchen_note}` : '',
                items ? 'Items:\n' + items : '',
            ].filter(Boolean).join('\n');
            const url = `https://wa.me/?text=${encodeURIComponent(lines)}`;
            const win = window.open(url, '_blank');
            if (!win) {
                navigator.clipboard?.writeText(lines).then(() => alert('Copied to clipboard. Paste into WhatsApp.'));
            }
        };

        const setConnection = (ok, message = '') => {
            if (!connectionEl) return;
            connectionEl.textContent = ok ? 'Live via polling' : (message || 'Reconnecting…');
            connectionEl.style.background = ok ? '#ecfdf3' : '#fef3c7';
            connectionEl.style.borderColor = ok ? '#bbf7d0' : '#fcd34d';
            connectionEl.style.color = ok ? '#166534' : '#92400e';
        };

        const showToast = (message, duration = 4000) => {
            const toast = document.createElement('div');
            toast.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: #166534;
                color: white;
                padding: 12px 16px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 100;
                font-size: 14px;
                font-weight: 600;
                animation: slideInUp 0.3s ease;
            `;
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(() => {
                toast.style.animation = 'slideOutDown 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, duration);
        };

        const addAnimationStyles = () => {
            const style = document.createElement('style');
            style.textContent = `
                @keyframes slideInUp {
                    from { transform: translateY(100px); opacity: 0; }
                    to { transform: translateY(0); opacity: 1; }
                }
                @keyframes slideOutDown {
                    from { transform: translateY(0); opacity: 1; }
                    to { transform: translateY(100px); opacity: 0; }
                }
            `;
            document.head.appendChild(style);
        };
        addAnimationStyles();

        const poller = createPoller(loadOrders, 5000, {
            onError: (err) => setConnection(false, err.message || 'Reconnecting…'),
        });
        poller.start();

        // Listen for ETA assignments
        const etaHandler = (event) => {
            const data = event.detail;
            if (!data) return;
            const order = ordersCache.find(o => o.id === data.id);
            if (order) {
                const eta = data.kitchen_eta_minutes ? `${data.kitchen_eta_minutes}m` : 'ETA set';
                const customer = data.customer_name || data.customer_phone || 'Walk-in';
                showToast(`Order ${data.code} (${customer}): ${eta}`);
                if (Notification?.permission === 'granted') {
                    new Notification(`ETA Updated - ${data.code}`, {
                        body: `Order for ${customer}: ${eta}`,
                        tag: `order-${data.id}`,
                        badge: '/assets/logo2.png',
                    });
                }
            }
        };
        window.addEventListener('order:eta-assigned', etaHandler);
    </script>
</body>
</html>
