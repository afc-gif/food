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
    const money = (value) => '₦' + Number(value ?? 0).toLocaleString();
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
        const normalized = (Array.isArray(data) ? data : data.data || []).map(normalizeOrder).filter((o) => o.kitchen_status !== 'pending');
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

    let orders = (window.initialOrders ?? []).map(normalizeOrder).filter((o) => o.kitchen_status !== 'pending');
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
        if (order.kitchen_status === 'pending') {
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
        if (!orders.length) {
            ordersEl.innerHTML = '';
            emptyEl.style.display = 'block';
            return;
        }

        emptyEl.style.display = 'none';
        ordersEl.innerHTML = orders
            .map((order) => {
                const items = order.items
                    .map((item) => `<li><span>${escapeHtml(item.name)}</span><span class="small">x${item.quantity} • ${money(item.total ?? item.unit_price ?? 0)}</span></li>`)
                    .join('');
                const customer = order.customer_name || order.customer_phone
                    ? `${escapeHtml(order.customer_name || 'Guest')} ${order.customer_phone ? ' · ' + escapeHtml(order.customer_phone) : ''}`
                    : 'Walk-in';
                const statusPill = renderStatus(order.kitchen_status);
                const etaPill = renderEta(order);
                const notePill = order.kitchen_note ? `<span class="pill tone-note">${escapeHtml(order.kitchen_note)}</span>` : '';
                const isFresh = Date.now() - new Date(order.created_at).getTime() < 3 * 60 * 1000;
                const channelPill = `<span class="pill tone-neutral">${escapeHtml(order.channel ?? 'pos')}</span>`;
                const etaBroadcast = order.kitchen_eta_minutes || order.kitchen_eta_at
                    ? `<span class="pill tone-success">ETA sent to POS/Admin</span>`
                    : `<span class="pill tone-muted">ETA pending</span>`;

                return `
                    <div class="order" data-order-id="${order.id}">
                        ${isFresh ? `<span style="position:absolute; top:10px; right:10px;" class="pill tone-active">New</span>` : ''}
                        <div class="order-header">
                            <div class="order-title">
                                <span style="font-size:17px;">${escapeHtml(order.code ?? 'New order')}</span>
                                ${channelPill}
                                <span class="pill warn" data-elapsed="${order.created_at}">${elapsed(order.created_at)}</span>
                                <span class="pill tone-neutral">${formatTime(order.created_at)}</span>
                            </div>
                            <div class="order-channel">
                                <span class="badge">${escapeHtml(order.status ?? 'pending')}</span>
                                <span class="pill tone-neutral">${money(order.total)}</span>
                            </div>
                        </div>
                        <div class="order-meta-row">
                            ${statusPill}
                            ${etaPill}
                            ${etaBroadcast}
                            ${notePill}
                        </div>
                        <div class="order-customer">Customer · ${customer}</div>
                        <div class="controls kitchen-actions" data-order="${order.id}">
                            <button class="brand-btn ghost" data-action="status" data-status="prepping" data-order="${order.id}">Start</button>
                            <button class="brand-btn ghost" data-action="eta" data-eta="10" data-order="${order.id}">ETA 10m</button>
                            <button class="brand-btn ghost" data-action="eta" data-eta="15" data-order="${order.id}">ETA 15m</button>
                            <button class="brand-btn ghost" data-action="eta" data-eta="20" data-order="${order.id}">ETA 20m</button>
                            <button class="brand-btn" data-action="status" data-status="ready" data-order="${order.id}">Ready</button>
                            <button class="brand-btn ghost" data-action="status" data-status="served" data-order="${order.id}">Served</button>
                        </div>
                        <ul class="items">${items}</ul>
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
    }

    function renderStatus(status) {
        const meta = kitchenStatuses[status] ?? { label: status || 'Pending', tone: 'neutral' };
        return `<span class="pill tone-${meta.tone}">${meta.label}</span>`;
    }

    function renderEta(order) {
        if (!order.kitchen_eta_minutes && !order.kitchen_eta_at) {
            return `<span class="pill tone-neutral">ETA not set</span>`;
        }
        const etaText = order.kitchen_eta_minutes
            ? `${order.kitchen_eta_minutes}m`
            : '';
        const atText = order.kitchen_eta_at ? ` · ${formatTime(order.kitchen_eta_at)}` : '';
        return `<span class="pill tone-active">ETA ${etaText}${atText}</span>`;
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
                showToast(`Order ${updated.code ?? orderId} → ${kitchenStatuses[targetStatus]?.label ?? targetStatus}`);
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
                showToast(`ETA set to ${eta}m`);
            }
        } catch (error) {
            console.error(error);
            alert(error?.message || 'Could not update this order.');
        }
    });

    function updateStats() {
        statCountEl.textContent = orders.length;
        statTotalEl.textContent = money(orders.reduce((sum, order) => sum + (order.total ?? 0), 0));
        statLastEl.textContent = orders[0] ? formatTime(orders[0].created_at) : '—';
    }

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
            connectionEl.style.background = 'rgba(82,55,0,0.08)';
            connectionEl.style.borderColor = 'rgba(82,55,0,0.28)';
            connectionEl.style.color = '#523700';
            return;
        }
        connectionEl.style.background = ok ? 'rgba(0,128,0,0.08)' : 'rgba(255,165,0,0.12)';
        connectionEl.style.borderColor = ok ? 'rgba(0,128,0,0.35)' : 'rgba(255,165,0,0.35)';
        connectionEl.style.color = ok ? '#0f5132' : '#7a4a00';
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
            new Notification(title, { body, icon: '/assets/logo.png' });
        }
    }

    // Refresh elapsed timers every 30s
    setInterval(() => {
        ordersEl.querySelectorAll('[data-elapsed]').forEach((pill) => {
            pill.textContent = elapsed(pill.getAttribute('data-elapsed'));
        });
    }, 30000);
}
