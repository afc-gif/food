import { createPoller } from './polling';

// Mobile nav
const navToggle = document.getElementById("navToggle");
const nav = document.querySelector(".af-nav");
if (navToggle && nav) {
  navToggle.addEventListener("click", () => {
    nav.classList.toggle("af-nav-open");
  });
  nav.querySelectorAll("a").forEach((link) => {
    link.addEventListener("click", () => nav.classList.remove("af-nav-open"));
  });
}

// Year in footer (guarded for pages without the element)
const yearEl = document.getElementById("year");
if (yearEl) yearEl.textContent = new Date().getFullYear();

// Simple cart
let cart = [];
const cartCountEl = document.getElementById("cartCount");
const cartFab = document.getElementById("cartFab");
const cartOverlay = document.getElementById("cartOverlay");
const cartOverlayClose = document.getElementById("cartOverlayClose");
const cartOverlayBackdrop = document.getElementById("cartOverlayBackdrop");

if (cartFab) {
  cartFab.addEventListener("click", () => {
    openCartOverlay();
  });
}

if (cartOverlayClose) {
  cartOverlayClose.addEventListener("click", closeCartOverlay);
}

if (cartOverlayBackdrop) {
  cartOverlayBackdrop.addEventListener("click", closeCartOverlay);
}

function openCartOverlay() {
  if (!cartOverlay) return;
  cartOverlay.classList.add("af-open");
  document.body.classList.add("af-modal-open");
}

function closeCartOverlay() {
  if (!cartOverlay) return;
  cartOverlay.classList.remove("af-open");
  document.body.classList.remove("af-modal-open");
}

function setSoldOutState(itemId, isSoldOut) {
  const soldOut = isSoldOut ? "1" : "0";

  document
    .querySelectorAll(`[data-item-id="${itemId}"]`)
    .forEach((btn) => {
      btn.setAttribute("data-sold-out", soldOut);
      btn.disabled = isSoldOut;
      btn.textContent = isSoldOut ? "Sold Out" : "Add to Cart";
    });

  document
    .querySelectorAll(`[data-menu-item][data-item-id="${itemId}"]`)
    .forEach((card) => {
      card.setAttribute("data-sold-out", soldOut);
      const pill = card.querySelector("[data-soldout-pill]");
      if (pill) {
        pill.style.display = isSoldOut ? "inline-flex" : "none";
        if (isSoldOut) {
          pill.removeAttribute("hidden");
        } else {
          pill.setAttribute("hidden", "");
        }
      }
    });
}

async function syncMenuAvailability() {
  try {
    const response = await fetch("/api/menu-items?active_only=1", { cache: "no-store" });
    if (!response.ok) return;
    const items = await response.json();
    if (!Array.isArray(items)) return;
    items.forEach((item) => {
      setSoldOutState(item.id, !!item.is_sold_out);
    });
  } catch (error) {
    // Silence network errors; polling will retry.
  }
}

const menuPoller = createPoller(syncMenuAvailability, 20000);
menuPoller.start();

if (window.Echo) {
  window.Echo.channel("menu-items").listen(".menu-item.updated", (event) => {
    setSoldOutState(event.id, !!event.is_sold_out);
  });
}

function bumpCartFab() {
  if (!cartFab) return;
  cartFab.classList.remove("af-cart-fab-bump");
  // force reflow for retrigger
  void cartFab.offsetWidth;
  cartFab.classList.add("af-cart-fab-bump");
}

function updateCartCount() {
  if (!cartCountEl) return;
  const count = cart.reduce((sum, item) => sum + item.qty, 0);
  cartCountEl.textContent = count;
  bumpCartFab();
}

function flyToCart(sourceEl) {
  if (!cartFab) return;
  const targetRect = cartFab.getBoundingClientRect();
  const sourceImg =
    sourceEl.closest("article")?.querySelector("img") || sourceEl;
  const sourceRect = sourceImg.getBoundingClientRect();

  const dot = document.createElement("span");
  dot.className = "af-fly-item";
  dot.style.left = `${sourceRect.left + sourceRect.width / 2}px`;
  dot.style.top = `${sourceRect.top + sourceRect.height / 2}px`;
  document.body.appendChild(dot);

  const deltaX =
    targetRect.left +
    targetRect.width / 2 -
    (sourceRect.left + sourceRect.width / 2);
  const deltaY =
    targetRect.top +
    targetRect.height / 2 -
    (sourceRect.top + sourceRect.height / 2);

  if (dot.animate) {
    const animation = dot.animate(
      [
        { transform: "translate(0, 0) scale(1)", opacity: 0.95 },
        { transform: `translate(${deltaX}px, ${deltaY}px) scale(0.35)`, opacity: 0 }
      ],
      { duration: 600, easing: "ease-in-out" }
    );
    animation.onfinish = () => dot.remove();
  } else {
    dot.remove();
  }
}

function renderCart() {
  const contexts = [
    {
      list: document.getElementById("cartList"),
      totalEl: document.getElementById("cartTotal")
    },
    {
      list: document.getElementById("cartListOverlay"),
      totalEl: document.getElementById("cartTotalOverlay")
    }
  ];

  let total = 0;
  cart.forEach((item) => {
    total += item.price * item.qty;
  });

  contexts.forEach(({ list, totalEl }) => {
    if (!list || !totalEl) return;
    list.innerHTML = "";

    cart.forEach((item, index) => {
      const li = document.createElement("li");
      li.className = "af-cart-item";

      li.innerHTML = `
        <div class="af-cart-item-info">
          <span class="af-cart-item-name">${item.name}</span>
          <span class="af-cart-item-meta">₦${item.price.toLocaleString()} × ${item.qty}</span>
        </div>
        <div class="af-cart-actions">
          <button class="af-qty-btn" data-action="dec" data-index="${index}">-</button>
          <button class="af-qty-btn" data-action="inc" data-index="${index}">+</button>
          <button class="af-qty-btn" data-action="remove" data-index="${index}">×</button>
        </div>
      `;
      list.appendChild(li);
    });

    totalEl.textContent = "₦" + total.toLocaleString();
  });

  updateCartCount();
}

function promptSideChoice(name, sidesRaw, callback) {
  let sides = [];
  if (typeof sidesRaw === 'string') {
    const txt = document.createElement('textarea');
    txt.innerHTML = sidesRaw;
    const decoded = txt.value;
    try {
      sides = JSON.parse(decoded);
    } catch (e) {
      sides = decoded.split(',').map(s => s.trim().replace(/^["'\[\]]+|["'\[\]]+$/g, '')).filter(Boolean);
    }
  } else if (Array.isArray(sidesRaw)) {
    sides = sidesRaw;
  }

  if (!Array.isArray(sides) || !sides.length) {
    sides = ['Rice', 'Yam', 'Plantain'];
  }

  const backdrop = document.createElement('div');
  backdrop.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:99999;display:flex;align-items:center;justify-content:center;padding:16px;box-sizing:border-box;backdrop-filter:blur(3px);';

  const card = document.createElement('div');
  card.style.cssText = 'background:#ffffff;border-radius:20px;padding:28px 24px;max-width:400px;width:100%;box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);font-family:inherit;text-align:left;box-sizing:border-box;';

  card.innerHTML = `
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px;">
      <div>
        <h3 style="margin:0;font-size:20px;font-weight:800;color:#111827;">Select Side Choice</h3>
        <p style="margin:4px 0 0;font-size:14px;color:#6b7280;">For <strong>${name}</strong> (Included at no extra charge)</p>
      </div>
      <button type="button" class="af-side-cancel-btn" style="background:#f3f4f6;border:none;border-radius:50%;width:32px;height:32px;font-size:18px;color:#4b5563;cursor:pointer;display:flex;align-items:center;justify-content:center;">&times;</button>
    </div>
    
    <div style="margin:20px 0 24px;">
      <label for="afSideSelectInput" style="display:block;font-size:13px;font-weight:700;color:#374151;margin-bottom:8px;text-transform:uppercase;letter-spacing:0.5px;">Choose your side dish:</label>
      <select id="afSideSelectInput" style="width:100%;padding:14px 16px;border-radius:12px;border:2px solid #d1d5db;background:#fff;font-size:16px;font-weight:600;color:#111827;outline:none;cursor:pointer;box-sizing:border-box;">
        <option value="" disabled selected>-- Select a Side Option --</option>
        ${sides.map(s => `<option value="${s}">${s} (Included)</option>`).join('')}
      </select>
    </div>

    <div style="display:flex;gap:12px;">
      <button type="button" class="af-side-cancel-btn" style="flex:1;padding:12px;border-radius:12px;border:1px solid #d1d5db;background:#fff;font-size:15px;font-weight:600;color:#4b5563;cursor:pointer;">Cancel</button>
      <button type="button" class="af-side-confirm-btn" style="flex:2;padding:12px;border-radius:12px;border:none;background:#f97316;font-size:15px;font-weight:700;color:#fff;cursor:pointer;opacity:0.5;pointer-events:none;" disabled>Add to Order</button>
    </div>
  `;

  backdrop.appendChild(card);
  document.body.appendChild(backdrop);

  const selectEl = card.querySelector('#afSideSelectInput');
  const confirmBtn = card.querySelector('.af-side-confirm-btn');

  selectEl.addEventListener('change', () => {
    if (selectEl.value) {
      confirmBtn.style.opacity = '1';
      confirmBtn.style.pointerEvents = 'auto';
      confirmBtn.disabled = false;
    }
  });

  confirmBtn.addEventListener('click', () => {
    const chosenSide = selectEl.value;
    if (chosenSide) {
      document.body.removeChild(backdrop);
      callback(chosenSide);
    }
  });

  card.querySelectorAll('.af-side-cancel-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.body.removeChild(backdrop);
    });
  });
}

function addToCart(item) {
  if (!item?.id) {
    alert("Missing menu item ID; please refresh and try again.");
    return;
  }
  const displayName = item.sideChoice ? `${item.name} (${item.sideChoice})` : item.name;
  const existing = cart.find((i) => i.id === item.id && (i.sideChoice || '') === (item.sideChoice || ''));
  if (existing) {
    existing.qty += 1;
  } else {
    cart.push({
      id: item.id,
      name: displayName,
      rawName: item.name,
      price: item.price || 0,
      qty: 1,
      sideChoice: item.sideChoice || null
    });
  }
  renderCart();
}

// Attach to "Add to Cart" buttons via global event delegation
document.addEventListener("click", (e) => {
  const btn = e.target.closest("[data-item]");
  if (!btn) return;

  const name = btn.getAttribute("data-item") || btn.getAttribute("data-item-name");
  const id = parseInt(btn.getAttribute("data-item-id"), 10);
  const soldOut = btn.getAttribute("data-sold-out") === "1" || btn.disabled;
  if (soldOut || !id) return;

  const priceEl = btn.closest("article")?.querySelector(".af-price");
  const priceAttr = btn.getAttribute("data-item-price");
  const parsedPrice = priceAttr
    ? parseFloat(priceAttr)
    : priceEl
      ? parseInt(priceEl.textContent.replace(/[^\d]/g, ""), 10)
      : 0;
  const price = Number.isFinite(parsedPrice) ? parsedPrice : 0;
  let sidesRaw = btn.getAttribute("data-sides") || btn.closest("article")?.getAttribute("data-sides");
  const lowerName = (name || '').toLowerCase();
  if (!sidesRaw && (lowerName.includes('catfish') || (lowerName.includes('pepper') && lowerName.includes('soup')))) {
    sidesRaw = '["Rice","Yam","Plantain"]';
  }

  if (sidesRaw) {
    promptSideChoice(name, sidesRaw, (chosenSide) => {
      if (chosenSide) {
        addToCart({ id, name, price, sideChoice: chosenSide });
        flyToCart(btn);
      }
    });
  } else {
    addToCart({ id, name, price });
    flyToCart(btn);
  }
});

// Cart quantity buttons
["cartList", "cartListOverlay"].forEach((listId) => {
  const listEl = document.getElementById(listId);
  if (!listEl) return;
  listEl.addEventListener("click", (e) => {
    const btn = e.target.closest(".af-qty-btn");
    if (!btn) return;

    const index = parseInt(btn.getAttribute("data-index"), 10);
    const action = btn.getAttribute("data-action");
    const item = cart[index];
    if (!item) return;

    if (action === "inc") item.qty += 1;
    if (action === "dec") item.qty = Math.max(1, item.qty - 1);
    if (action === "remove") cart.splice(index, 1);

    renderCart();
  });
});

// Initialize displayed count
updateCartCount();

// Menu filters (delegated for SSR + dynamic chips)
const menuFilters = document.getElementById("menuFilters");
const menuGrid = document.getElementById("menuGrid");
let activeFilter = "all";
const slugify = (text = "") =>
  text
    .toString()
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "") || "menu";

function applyFilter(filter) {
  if (!menuGrid) return;
  const target = slugify(filter || activeFilter || "all");
  menuGrid.querySelectorAll(".af-menu-item").forEach((item) => {
    const category = slugify(item.getAttribute("data-category") || "all");
    item.style.display =
      target === "all" || category === target ? "" : "none";
  });
}

if (menuFilters && menuGrid) {
  menuFilters.addEventListener("click", (e) => {
    const chip = e.target.closest(".af-chip");
    if (!chip) return;
    activeFilter = slugify(chip.getAttribute("data-filter") || "all");
    menuFilters
      .querySelectorAll(".af-chip")
      .forEach((c) => c.classList.remove("af-chip-active"));
    chip.classList.add("af-chip-active");
    applyFilter(activeFilter);
  });
  const initial = menuFilters.querySelector(".af-chip-active") || menuFilters.querySelector(".af-chip");
  if (initial) {
    activeFilter = slugify(initial.getAttribute("data-filter") || "all");
  }
  applyFilter(activeFilter);
}

// Checkout buttons (WhatsApp only for now)

function handleWhatsApp(form) {
  if (!cart.length) {
    alert("Your cart is empty.");
    return;
  }
  if (!form) {
    alert("Please fill your details first.");
    return;
  }

  const formData = new FormData(form);

  const name = formData.get("name");
  const phone = formData.get("phone");
  const service = formData.get("service");
  const time = formData.get("time");
  const note = formData.get("note");

  let message = `New Order - Acie Fraiche Cafe%0A%0A`;
  message += `Name: ${name}%0A`;
  message += `Phone: ${phone}%0A`;
  message += `Service: ${service}%0A`;
  message += `Time: ${time}%0A`;
  if (note) message += `Note: ${note}%0A`;
  message += `%0AItems:%0A`;

  let total = 0;
  cart.forEach((item) => {
    total += item.price * item.qty;
    message += `- ${item.name} (₦${item.price.toLocaleString()} × ${
      item.qty
    })%0A`;
  });

  message += `%0ATotal: ₦${total.toLocaleString()}%0A`;
  message += `%0AOrder Source: Website`;

  const whatsappNumber = "2348143190700";
  const url = `https://wa.me/${whatsappNumber}?text=${message}`;
  window.open(url, "_blank");
}

// Attach checkout handlers for all buttons
document.querySelectorAll("[data-whatsapp-btn]").forEach((btn) => {
  btn.addEventListener("click", () => {
    const formId = btn.getAttribute("data-form");
    const form = formId ? document.getElementById(formId) : null;
    handleWhatsApp(form);
  });
});
