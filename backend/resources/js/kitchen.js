import './bootstrap';
import { createPoller } from './polling';

const ordersEl = document.getElementById('kitchenOrders');
const emptyEl = document.getElementById('kitchenEmpty');
const connectionEl = document.getElementById('kitchenConnection');
const statCountEl = document.getElementById('kitchenStatCount');
const statLastEl = document.getElementById('kitchenStatLast');
const statTotalEl = document.getElementById('kitchenStatTotal');
const toastEl = document.getElementById('kitchenToast');
const soundBtn = document.getElementById('toggleSound');
const notifyBtn = document.getElementById('toggleNotify');
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
const seenOrders = new Set();

let soundEnabled = localStorage.getItem('kitchenSound') === '1';
let notifyEnabled = localStorage.getItem('kitchenNotify') === '1';
const chime = new Audio('data:audio/wav;base64,UklGRjQAAABXQVZFZm10IBAAAAABAAEAQB8AAIA+AAACABAAZGF0YQAAAAA=');

if (ordersEl) {
    const escapeHtml = (value = '') =>
        String(value).replace(/[&<>"']/g, (char) =>
            ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[char] ?? char)
        );
            const kitchenStatuses = {
                pending: { label: 'Hold', tone: 'warn' },
                queued: { label: 'Sent to kitchen', tone: 'neutral' },
                prepping: { label: 'In progress', tone: 'active' },
                ready: { label: 'Ready', tone: 'success' },
                served: { label: 'Served', tone: 'muted' },
    };

    const apiFetch = (url, options = {}) => {
        const headers = {
            Accept: 'application/json',
            ...(options.headers || {}),
        };
        if (!('Content-Type' in headers) && options.body && !(options.body instanceof FormData)) {
            headers['Content-Type'] = 'application/json';
        }
        if (csrfToken) {
            headers['X-CSRF-TOKEN'] = csrfToken;
        }
        return fetch(url, {
            credentials: 'same-origin',
            cache: options.cache ?? 'no-store',
            ...options,
            headers,
        });
    };

    const updateKitchen = async (orderId, payload) => {
        const res = await apiFetch(`/api/orders/${orderId}/kitchen-status`, {
            method: 'POST',
            body: JSON.stringify(payload),
        });
        if (!res.ok) {
            const text = await res.text();
            throw new Error(text || 'Could not update kitchen state');
        }
        return res.json();
    };

    const pollOrders = async () => {
        const res = await apiFetch('/api/orders?all=1', { cache: 'no-store' });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();
        const normalized = (Array.isArray(data) ? data : data.data || [])
            .map(normalizeOrder);
        const nextOrders = normalized.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));

        // Detect new orders during polling and notify once
        nextOrders.forEach((order) => {
            if (!seenOrders.has(order.id)) {
                seenOrders.add(order.id);
                notifyNewOrder(order);
            }
        });

        orders = nextOrders;
        renderOrders();
        updateStats();
        setConnection('Live via polling', true, 'polling');
    };

    const ordersPoller = createPoller(pollOrders, 3000, {
        onError: (err) => {
            console.warn('Polling failed', err);
            setConnection('Reconnecting…', false, 'polling');
        },
    });

    let orders = (window.initialOrders ?? [])
        .map(normalizeOrder);
    orders.forEach(o => seenOrders.add(o.id));

    renderOrders();
    updateStats();
    setConnection('Starting polling…', true, 'polling');
    ordersPoller.start();

    if (soundBtn) {
        const setSoundLabel = () => soundBtn.textContent = `Sound: ${soundEnabled ? 'On' : 'Off'}`;
        setSoundLabel();
        soundBtn.addEventListener('click', () => {
            soundEnabled = !soundEnabled;
            localStorage.setItem('kitchenSound', soundEnabled ? '1' : '0');
            setSoundLabel();
        });
    }

    if (notifyBtn) {
        const setNotifyLabel = () => notifyBtn.textContent = `Browser Alerts: ${notifyEnabled ? 'On' : 'Off'}`;
        setNotifyLabel();
        notifyBtn.addEventListener('click', async () => {
            if (!notifyEnabled && Notification?.permission === 'default') {
                await Notification.requestPermission();
            }
            notifyEnabled = Notification?.permission === 'granted';
            localStorage.setItem('kitchenNotify', notifyEnabled ? '1' : '0');
            setNotifyLabel();
        });
    }

    function normalizeOrder(order) {
        return {
            ...order,
            total: Number(order.total ?? 0),
            created_at: order.created_at ?? order.createdAt ?? new Date().toISOString(),
            customer_name: order.customer_name ?? order.customerName ?? '',
            customer_phone: order.customer_phone ?? order.customerPhone ?? '',
            kitchen_status: order.kitchen_status ?? order.kitchenStatus ?? 'pending',
            kitchen_eta_minutes: order.kitchen_eta_minutes ?? order.kitchenEtaMinutes ?? null,
            kitchen_eta_at: order.kitchen_eta_at ?? order.kitchenEtaAt ?? null,
            kitchen_note: order.kitchen_note ?? order.kitchenNote ?? null,
            kitchen_sent_at: order.kitchen_sent_at ?? order.kitchenSentAt ?? null,
            items: (order.items ?? []).map((item) => ({
                ...item,
                quantity: Number(item.quantity ?? 0),
                total: Number(item.total ?? item.unit_price ?? 0),
                name: item.name ?? '',
            })),
        };
    }

    function upsertOrder(order) {
        // Remove orders not on the board (pending or already served)
        if (order.kitchen_status === 'pending' || order.kitchen_status === 'served') {
            orders = orders.filter((existing) => existing.id !== order.id);
            renderOrders();
            updateStats();
            return;
        }
        orders = [
            order,
            ...orders.filter((existing) => existing.id !== order.id),
        ].sort((a, b) => new Date(b.created_at) - new Date(a.created_at));

        renderOrders();
        updateStats();
    }

    function renderOrders() {
        const visible = orders.filter((o) => o.kitchen_status !== 'pending' && o.kitchen_status !== 'served');
        if (!visible.length) {
            ordersEl.innerHTML = '';
            emptyEl.style.display = 'block';
            updateStats();
            return;
        }

        emptyEl.style.display = 'none';
        
        // Save current input values before re-render
        const savedInputs = {};
        document.querySelectorAll('[data-custom-eta]').forEach(input => {
            const orderId = input.getAttribute('data-order');
            if (input.value) {
                savedInputs[orderId] = input.value;
            }
        });
        
        ordersEl.innerHTML = visible
            .map((order) => {
                const items = order.items
                    .map((item) => `<li><span>${escapeHtml(item.name)}</span><span class="small">x${item.quantity}</span></li>`)
                    .join('');
                const customer = order.customer_name || order.customer_phone
                    ? `${escapeHtml(order.customer_name || 'Guest')} ${order.customer_phone ? ' · ' + escapeHtml(order.customer_phone) : ''}`
                    : 'Walk-in';
                const statusPill = renderStatus(order.kitchen_status);
                const etaPill = renderEta(order);
                const notePill = order.kitchen_note ? `<span class="pill tone-note">${escapeHtml(order.kitchen_note)}</span>` : '';
                const isFresh = Date.now() - new Date(order.created_at).getTime() < 3 * 60 * 1000;
                const channelPill = `<span class="pill tone-neutral">${escapeHtml(order.channel ?? 'pos')}</span>`;
                const isReady = order.kitchen_status === 'ready';
                const isPrepping = order.kitchen_status === 'prepping';
                
                // Restore saved input value if it exists
                const savedValue = savedInputs[order.id] ? ` value="${escapeHtml(savedInputs[order.id])}"` : '';

                return `
                    <div class="order ${isFresh ? 'live' : ''}" data-order-id="${order.id}">
                        ${isFresh ? `<span style="position:absolute; top:10px; right:10px;" class="pill tone-live">New</span>` : ''}
                        <div class="order-header">
                            <div class="order-title">
                                <span style="font-size:17px;">${escapeHtml(order.code ?? 'Order')}</span>
                                ${channelPill}
                                <span class="pill warn" data-elapsed="${order.created_at}">${elapsed(order.created_at)}</span>
                                <span class="pill tone-neutral">${formatTime(order.created_at)}</span>
                            </div>
                            <div class="order-channel">
                                <span class="badge">${escapeHtml(order.status ?? 'pending')}</span>
                            </div>
                        </div>
                        <div class="order-meta-row">
                            ${statusPill}
                            ${etaPill}
                            ${notePill}
                        </div>
                        <div class="order-customer">${customer}</div>
                        <ul class="items">${items}</ul>
                        <div class="controls kitchen-actions" data-order="${order.id}">
                            <button class="brand-btn ghost" data-action="status" data-status="prepping" data-order="${order.id}" ${isPrepping ? 'disabled' : ''}>Start</button>
                            <button class="brand-btn ghost" data-action="eta" data-eta="10" data-order="${order.id}">10m</button>
                            <button class="brand-btn ghost" data-action="eta" data-eta="15" data-order="${order.id}">15m</button>
                            <button class="brand-btn ghost" data-action="eta" data-eta="20" data-order="${order.id}">20m</button>
                            <div class="eta-input-group">
                                <input type="number" data-custom-eta data-order="${order.id}" placeholder="mins" min="1" max="180"${savedValue}>
                                <button type="button" class="brand-btn ghost" data-action="custom-eta" data-order="${order.id}">Set</button>
                            </div>
                            <button class="brand-btn" data-action="status" data-status="ready" data-order="${order.id}" ${isReady ? 'disabled' : ''}>Ready</button>
                            <button class="brand-btn ghost" data-action="status" data-status="served" data-order="${order.id}">Served</button>
                        </div>
                        <div class="order-footer">
                            <div class="order-channel">
                                <span class="pill tone-neutral">Ticket #${order.id}</span>
                                <span class="pill tone-muted">Since ${elapsed(order.created_at)}</span>
                            </div>
                        </div>
                    </div>
                `;
            })
            .join('');

        updateStats();
    }

    function updateStats() {
        const total = orders.length;
        const pending = orders.filter(o => o.kitchen_status === 'prepping' || o.kitchen_status === 'queued').length;
        const etas = orders
            .filter(o => o.kitchen_eta_minutes)
            .map(o => o.kitchen_eta_minutes)
            .sort((a, b) => a - b);
        const avgEta = etas.length ? Math.round(etas.reduce((a, b) => a + b) / etas.length) : '—';

        statCountEl.textContent = total;
        document.getElementById('kitchenStatPending').textContent = pending;
        document.getElementById('kitchenStatETA').textContent = avgEta === '—' ? '—' : `${avgEta}m`;
    }    function renderStatus(status) {
        const meta = kitchenStatuses[status] ?? { label: status || 'Pending', tone: 'neutral' };
        return `<span class="pill tone-${meta.tone}">${meta.label}</span>`;
    }

    function renderEta(order) {
        if (!order.kitchen_eta_minutes && !order.kitchen_eta_at) {
            return `<span class="pill tone-warn" style="font-weight:700;">ETA missing · tap a quick ETA</span>`;
        }
        const etaText = order.kitchen_eta_minutes ? `${order.kitchen_eta_minutes}m` : '';
        const atText = order.kitchen_eta_at ? ` · ${formatTime(order.kitchen_eta_at)}` : '';
        return `<span class="pill tone-active" style="font-weight:700;">ETA ${etaText}${atText}</span>`;
    }

    ordersEl.addEventListener('click', async (event) => {
        const btn = event.target.closest('[data-action]');
        if (!btn) return;
        const orderId = Number(btn.getAttribute('data-order'));
        if (!orderId) return;

        const current = orders.find((o) => o.id === orderId);
        const currentStatus = current?.kitchen_status ?? 'queued';

        try {
            if (btn.dataset.action === 'status') {
                const targetStatus = btn.getAttribute('data-status');
                const updated = await updateKitchen(orderId, {
                    kitchen_status: targetStatus,
                    eta_minutes: current?.kitchen_eta_minutes ?? null,
                    note: current?.kitchen_note ?? null,
                });
                upsertOrder(normalizeOrder(updated));
                showToast(`Order ${updated.code ?? orderId} -> ${kitchenStatuses[targetStatus]?.label ?? targetStatus}`);
            }

            if (btn.dataset.action === 'eta') {
                const eta = Number(btn.getAttribute('data-eta'));
                if (Number.isNaN(eta)) return;
                const updated = await updateKitchen(orderId, {
                    kitchen_status: currentStatus,
                    eta_minutes: eta,
                    note: current?.kitchen_note ?? null,
                });
                upsertOrder(normalizeOrder(updated));
                showToast(`ETA set to ${eta}m for order ${updated.code ?? orderId}`);
                // Notify staff and POS about ETA assignment
                window.dispatchEvent(new CustomEvent('order:eta-assigned', { detail: updated }));
            }

            if (btn.dataset.action === 'custom-eta') {
                const input = event.target.closest('.kitchen-actions').querySelector(`[data-custom-eta][data-order="${orderId}"]`);
                const eta = Number(input?.value);
                if (Number.isNaN(eta) || eta < 1) {
                    alert('Please enter a valid number of minutes (1-180)');
                    return;
                }
                const updated = await updateKitchen(orderId, {
                    kitchen_status: currentStatus,
                    eta_minutes: eta,
                    note: current?.kitchen_note ?? null,
                });
                upsertOrder(normalizeOrder(updated));
                input.value = '';
                showToast(`ETA set to ${eta}m for order ${updated.code ?? orderId}`);
                // Notify staff and POS about ETA assignment
                window.dispatchEvent(new CustomEvent('order:eta-assigned', { detail: updated }));
            }
        } catch (error) {
            console.error(error);
            alert(error?.message || 'Could not update this order.');
        }
    });

    function formatTime(timestamp) {
        const date = new Date(timestamp);
        if (Number.isNaN(date.getTime())) {
            return 'Just now';
        }
        return date.toLocaleString(undefined, {
            hour: '2-digit',
            minute: '2-digit',
            day: '2-digit',
            month: 'short',
        });
    }

    function elapsed(timestamp) {
        const date = new Date(timestamp);
        if (Number.isNaN(date.getTime())) return 'Just now';
        const diff = Math.max(0, Date.now() - date.getTime());
        const mins = Math.floor(diff / 60000);
        if (mins < 1) return 'Just now';
        if (mins < 60) return `${mins}m ago`;
        const hrs = Math.floor(mins / 60);
        return `${hrs}h ${mins % 60}m`;
    }

    function setConnection(label, ok, mode = 'live') {
        if (!connectionEl) return;
        connectionEl.textContent = label;
        const isPolling = mode === 'polling';
        if (isPolling) {
            connectionEl.style.background = ok ? '#ecfdf3' : '#fef3c7';
            connectionEl.style.borderColor = ok ? '#bbf7d0' : '#fcd34d';
            connectionEl.style.color = ok ? '#166534' : '#92400e';
            return;
        }
        connectionEl.style.background = ok ? '#ecfdf3' : '#fef3c7';
        connectionEl.style.borderColor = ok ? '#bbf7d0' : '#fcd34d';
        connectionEl.style.color = ok ? '#166534' : '#92400e';
    }

    function showToast(message) {
        if (!toastEl) return;
        toastEl.textContent = message;
        toastEl.style.display = 'block';
        setTimeout(() => {
            toastEl.style.display = 'none';
        }, 2600);
    }

    function notifyNewOrder(event) {
        if (soundEnabled && chime?.play) {
            chime.currentTime = 0;
            chime.play().catch(() => {});
        }
        if (notifyEnabled && Notification?.permission === 'granted') {
            const title = event.code ? `New order ${event.code}` : 'New order received';
            const body = (event.items || []).map(i => `${i.quantity}× ${i.name}`).join(', ') || 'New ticket in the kitchen';
            new Notification(title, { body, icon: '/assets/logo2.png' });
        }
    }

    // Refresh elapsed timers every 30s
    setInterval(() => {
        ordersEl.querySelectorAll('[data-elapsed]').forEach((pill) => {
            pill.textContent = elapsed(pill.getAttribute('data-elapsed'));
        });
    }, 30000);
}
