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
const featuredGrid = document.getElementById("featuredGrid");
const menuGrid = document.getElementById("menuGrid");
const menuFilters = document.getElementById("menuFilters");

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

  document.querySelectorAll(`[data-item-id="${itemId}"]`).forEach((btn) => {
    btn.setAttribute("data-sold-out", soldOut);
    btn.disabled = isSoldOut;
    btn.textContent = isSoldOut ? "Sold Out" : "Add to Cart";
  });

  document
    .querySelectorAll(`[data-menu-item][data-item-id="${itemId}"]`)
    .forEach((card) => {
      card.setAttribute("data-sold-out", soldOut);
      const pill = card.querySelector("[data-soldout-pill]");
      if (pill) pill.style.display = isSoldOut ? "inline-flex" : "none";
    });
}

async function syncMenuAvailability() {
  try {
    const res = await fetch("/api/menu-items?active_only=1", { cache: "no-store" });
    if (!res.ok) return;
    const items = await res.json();
    if (!Array.isArray(items)) return;
    items.forEach((item) => setSoldOutState(item.id, !!item.is_sold_out));
  } catch (error) {
    // network errors are ignored; next poll will retry
  }
}

// Initialize Echo for real-time updates
function initializeEcho() {
  if (!window.Pusher) {
    // Load Pusher library dynamically
    const script = document.createElement('script');
    script.src = 'https://js.pusher.com/8.2.0/pusher.min.js';
    script.onload = () => {
      loadEcho();
    };
    document.head.appendChild(script);
  } else {
    loadEcho();
  }
}

function loadEcho() {
  if (window.Echo) {
    window.Echo.channel("menu-items").listen(".menu-item.updated", (event) => {
      upsertMenuItem(event);
    });
  }
}

// Try to initialize Echo if available
if (window.Echo) {
  window.Echo.channel("menu-items").listen(".menu-item.updated", (event) => {
    upsertMenuItem(event);
  });
} else {
  // If Echo is not available, we'll rely on polling
  console.log('Echo not available, using polling for menu updates');
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

function getCartTotal() {
  return cart.reduce((sum, item) => sum + item.price * item.qty, 0);
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

  const total = getCartTotal();

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

function addToCart(item) {
  if (!item?.id) {
    alert("Missing menu item ID; please refresh and try again.");
    return;
  }
  const name = item.name;
  const existing = cart.find((i) => i.name === name);
  if (existing) {
    existing.qty += 1;
  } else {
    cart.push({ id: item.id, name, price: item.price || 0, qty: 1 });
  }
  renderCart();
}

function bindAddToCartButtons() {
  document.querySelectorAll("[data-item]").forEach((btn) => {
    if (btn.dataset.bound === "1") return;
    btn.dataset.bound = "1";
    btn.addEventListener("click", () => {
      const name = btn.getAttribute("data-item");
      const id = parseInt(btn.getAttribute("data-item-id"), 10);
      const soldOut = btn.getAttribute("data-sold-out") === "1";
      if (soldOut) {
        alert("Sorry, this item is sold out.");
        return;
      }
      const priceEl = btn.closest("article")?.querySelector(".af-price");
      const priceAttr = btn.getAttribute("data-item-price");
      const price = priceAttr
        ? parseFloat(priceAttr)
        : priceEl
          ? parseInt(priceEl.textContent.replace(/[^\d]/g, ""), 10)
          : 0;
      addToCart({ id, name, price: isNaN(price) ? 0 : price });
      flyToCart(btn);
    });
  });
}

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

// Dynamic menu loading
const slugify = (text) =>
  (text || "menu")
    .toString()
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "") || "menu";

let activeFilter = "all";

function bindFilterButtons() {
  if (!menuFilters) return;
  menuFilters.addEventListener("click", (e) => {
    const chipBtn = e.target.closest(".af-chip");
    if (!chipBtn) return;
    const raw = chipBtn.getAttribute("data-filter") || "all";
    activeFilter = slugify(raw);
    menuFilters
      .querySelectorAll(".af-chip")
      .forEach((c) => c.classList.remove("af-chip-active"));
    chipBtn.classList.add("af-chip-active");
    applyFilter();
  });

  const initial = menuFilters.querySelector(".af-chip-active") || menuFilters.querySelector(".af-chip");
  if (initial) {
    activeFilter = slugify(initial.getAttribute("data-filter") || "all");
    applyFilter();
  }
}

function renderFilters(categories) {
  if (!menuFilters) return;
  const chips = [
    { slug: "all", name: "All", active: true },
    ...categories.map((c) => ({
      slug: slugify(c.name),
      name: c.name,
      active: false
    }))
  ];

  menuFilters.innerHTML = chips
    .map(
      (chip) => `
        <button class="af-chip ${chip.active ? "af-chip-active" : ""}" data-filter="${chip.slug}">
          ${chip.name}
        </button>
      `
    )
    .join("");

  bindFilterButtons();
}

function renderFeatured(items) {
  if (!featuredGrid) return;
  if (!items.length) {
    featuredGrid.innerHTML =
      '<p style="grid-column:1/-1;text-align:center;">Featured items coming soon.</p>';
    return;
  }

  const topThree = items.slice(0, 3);
  featuredGrid.innerHTML = topThree
    .map((item) => {
      const catName = item.category?.name || "Signature";
      return `
        <article
          class="af-card"
          data-menu-item
          data-item-id="${item.id}"
          data-sold-out="${item.is_sold_out ? "1" : "0"}"
          data-category="${slugify(catName)}"
        >
          ${item.image_url ? `<img src="${item.image_url}" alt="${item.name}" class="af-card-img" />` : ""}
          <div class="af-card-body">
            <div class="af-card-top">
              <h3>${item.name}</h3>
              <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                <span class="af-tag">${catName}</span>
                <span
                  class="af-pill"
                  data-soldout-pill
                  style="background:#fef2f2;color:#b91c1c;border-color:#fecdd3;${item.is_sold_out ? "" : "display:none;"}"
                >Sold Out</span>
              </div>
            </div>
            <p>${item.description || "Fresh from our kitchen."}</p>
            <div class="af-card-footer">
              <span class="af-price">₦${Number(item.price).toLocaleString()}</span>
              <button
                class="af-btn af-btn-sm af-btn-primary"
                data-item="${item.name}"
                data-item-id="${item.id}"
                data-item-price="${item.price}"
                data-sold-out="${item.is_sold_out ? "1" : "0"}"
                ${item.is_sold_out ? "disabled" : ""}
              >
                ${item.is_sold_out ? "Sold Out" : "Add to Cart"}
              </button>
            </div>
          </div>
        </article>
      `;
    })
    .join("");

  bindAddToCartButtons();
}

const hasSSRMenuItems = !!(menuGrid && menuGrid.querySelector("[data-menu-item]"));
const hasSSRFeatured = !!(featuredGrid && featuredGrid.querySelector("[data-menu-item]"));
const hasSSRFilters = !!(menuFilters && menuFilters.querySelectorAll(".af-chip").length > 1);


function renderMenu(items) {
  if (!menuGrid) return;
  if (!items.length) {
    menuGrid.innerHTML =
      '<p style="grid-column:1/-1;text-align:center;">Menu is coming soon. Please check back.</p>';
    return;
  }
  
  if (!hasSSRMenuItems) {
    menuGrid.innerHTML = items
      .map((item) => {
        const catName = item.category?.name || "Menu";
        const catSlug = slugify(catName);
        return `
          <article
            class="af-menu-item"
            data-menu-item
            data-item-id="${item.id}"
            data-sold-out="${item.is_sold_out ? "1" : "0"}"
            data-category="${catSlug}"
          >
            <div class="af-menu-head">
              <h3>${item.name}</h3>
              <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                <span class="af-pill">${catName}</span>
                <span
                  class="af-pill"
                  data-soldout-pill
                  style="background:#fef2f2;color:#b91c1c;border-color:#fecdd3;${item.is_sold_out ? "" : "display:none;"}"
                >Sold Out</span>
              </div>
            </div>
            <p>${item.description || "Freshly prepared from our kitchen."}</p>
            <div class="af-menu-footer">
              <span class="af-price">₦${Number(item.price).toLocaleString()}</span>
              <button
                class="af-btn af-btn-sm af-btn-outline"
                data-item="${item.name}"
                data-item-id="${item.id}"
                data-item-price="${item.price}"
                data-sold-out="${item.is_sold_out ? "1" : "0"}"
                ${item.is_sold_out ? "disabled" : ""}
              >
                ${item.is_sold_out ? "Sold Out" : "Add to Cart"}
              </button>
            </div>
          </article>
        `;
      })
      .join("");

    bindAddToCartButtons();
    applyFilter(); // Now this function exists!
  } else {
    // Only update sold-out state (no render)
    items.forEach((item) => {
      setSoldOutState(item.id, !!item.is_sold_out);
    });
  }
}

function applyFilter() {
  if (!menuGrid) return;
  menuGrid.querySelectorAll(".af-menu-item").forEach((item) => {
    const category = item.getAttribute("data-category") || "all";
    const normalized = slugify(category);
    item.style.display =
      activeFilter === "all" || normalized === activeFilter ? "" : "none";
  });
}

async function loadMenuData() {
  if (!menuGrid && !featuredGrid && !menuFilters) return;
  try {
    const [itemsRes, categoriesRes] = await Promise.all([
      fetch("/api/menu-items?active_only=1", { cache: "no-store" }),
      fetch("/api/categories?active_only=1", { cache: "no-store" })
    ]);

    if (!itemsRes.ok || !categoriesRes.ok) {
      throw new Error("Could not load menu data.");
    }

    const [items, categories] = await Promise.all([
      itemsRes.json(),
      categoriesRes.json()
    ]);

    const safeItems = Array.isArray(items) ? items : [];
    const safeCategories = Array.isArray(categories) ? categories : [];

    if (safeCategories.length && menuFilters) {
      if (!hasSSRFilters) {
        renderFilters(safeCategories);
      } else {
        safeCategories.forEach((c) => ensureCategoryChip(c.name));
      }
    }

    if (safeItems.length) {
      if (!hasSSRMenuItems) {
    renderMenu(safeItems);
} else {
    // Update sold-out status only; no full re-render
    safeItems.forEach((item) => {
        setSoldOutState(item.id, !!item.is_sold_out);
    });
}

      if (!hasSSRFeatured) {
        renderFeatured(safeItems);
      }
    }
  } catch (err) {
    if (featuredGrid && !hasSSRFeatured) {
      featuredGrid.innerHTML =
        '<p style="grid-column:1/-1;text-align:center;">Unable to load menu right now.</p>';
    }
    if (menuGrid && !hasSSRMenuItems) {
      menuGrid.innerHTML =
        '<p style="grid-column:1/-1;text-align:center;">Unable to load menu right now.</p>';
    }
    console.error(err);
  }
}

function ensureCategoryChip(catName) {
  if (!menuFilters || !catName) return;
  const slug = slugify(catName);
  const existing = menuFilters.querySelector(`[data-filter="${slug}"]`);
  if (existing) return;
  const btn = document.createElement("button");
  btn.className = "af-chip";
  btn.setAttribute("data-filter", slug);
  btn.textContent = catName;
  menuFilters.appendChild(btn);
  bindFilterButtons();
}

function createMenuCard(item) {
  const catName = item.category?.name || "Menu";
  const catSlug = slugify(catName);
  const soldOut = item.is_sold_out ? "1" : "0";
  const card = document.createElement("article");
  card.className = "af-menu-item";
  card.setAttribute("data-menu-item", "");
  card.setAttribute("data-item-id", item.id);
  card.setAttribute("data-sold-out", soldOut);
  card.setAttribute("data-category", catSlug);
  card.innerHTML = `
    <div class="af-menu-head">
      <h3>${item.name}</h3>
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
        <span class="af-pill">${catName}</span>
        <span
          class="af-pill"
          data-soldout-pill
          style="background:#fef2f2;color:#b91c1c;border-color:#fecdd3;${item.is_sold_out ? "" : "display:none;"}"
        >Sold Out</span>
      </div>
    </div>
    <p>${item.description || "Freshly prepared from our kitchen."}</p>
    <div class="af-menu-footer">
      <span class="af-price">₦${Number(item.price).toLocaleString()}</span>
      <button
        class="af-btn af-btn-sm af-btn-outline"
        data-item="${item.name}"
        data-item-id="${item.id}"
        data-item-price="${item.price}"
        data-sold-out="${soldOut}"
        ${item.is_sold_out ? "disabled" : ""}
      >
        ${item.is_sold_out ? "Sold Out" : "Add to Cart"}
      </button>
    </div>
  `;
  return card;
}

function upsertMenuItem(item) {
  if (!item || item.is_active === false) {
    return;
  }
  const existing = document.querySelector(`[data-menu-item][data-item-id="${item.id}"]`);
  if (existing) {
    existing.querySelector(".af-menu-head h3").textContent = item.name;
    const priceEl = existing.querySelector(".af-price");
    if (priceEl) priceEl.textContent = `₦${Number(item.price).toLocaleString()}`;
    existing.setAttribute("data-sold-out", item.is_sold_out ? "1" : "0");
    const pill = existing.querySelector("[data-soldout-pill]");
    if (pill) pill.style.display = item.is_sold_out ? "inline-flex" : "none";
    const btn = existing.querySelector("[data-item]");
    if (btn) {
      btn.setAttribute("data-sold-out", item.is_sold_out ? "1" : "0");
      btn.disabled = !!item.is_sold_out;
      btn.textContent = item.is_sold_out ? "Sold Out" : "Add to Cart";
      btn.setAttribute("data-item-price", item.price ?? 0);
    }
  } else if (menuGrid) {
    const card = createMenuCard(item);
    menuGrid.appendChild(card);
    ensureCategoryChip(item.category?.name);
    bindAddToCartButtons();
    applyFilter();
  }
  setSoldOutState(item.id, !!item.is_sold_out);
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

  cart.forEach((item) => {
    message += `- ${item.name} (₦${item.price.toLocaleString()} × ${item.qty})%0A`;
  });

  message += `%0ATotal: ₦${getCartTotal().toLocaleString()}%0A`;
  message += `%0AOrder Source: Website`;

  const whatsappNumber = "2347015862018";
  const url = `https://wa.me/${whatsappNumber}?text=${message}`;
  window.open(url, "_blank");
}

// Kick off bindings and load menu data (avoid double-render if server already rendered content)
const hasSSRMenuItems = !!(menuGrid && menuGrid.querySelector("[data-menu-item]"));
const hasSSRFeatured = !!(featuredGrid && featuredGrid.querySelector("[data-menu-item]"));
const hasSSRFilters = !!(menuFilters && menuFilters.querySelectorAll(".af-chip").length > 1);

// Bind whatever the server already rendered for instant UX
bindAddToCartButtons();
bindFilterButtons();
applyFilter();

// Hydrate from the API (merge-only; we never clear SSR content to avoid flicker)
loadMenuData();

syncMenuAvailability();
// Reduced polling interval from 10s to 30s to prevent flickering
// setInterval(syncMenuAvailability, 30000);

// Attach checkout handlers for all buttons
document.querySelectorAll("[data-whatsapp-btn]").forEach((btn) => {
  btn.addEventListener("click", () => {
    const formId = btn.getAttribute("data-form");
    const form = formId ? document.getElementById(formId) : null;
    handleWhatsApp(form);
  });
});
