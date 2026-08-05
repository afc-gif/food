function createPoller(task, intervalMs, options = {}) {
  const { immediate = true, runWhileHidden = false, onError = null } = options;
  let timer = null;
  let running = false;

  const shouldRun = () => {
    if (runWhileHidden) return true;
    if (typeof document === "undefined") return true;
    return document.visibilityState !== "hidden";
  };

  const tick = async () => {
    if (running || !shouldRun()) return;
    running = true;
    try {
      await task();
    } catch (error) {
      if (onError) {
        onError(error);
      } else {
        console.warn("Poller task failed", error);
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

  if (typeof document !== "undefined") {
    document.addEventListener("visibilitychange", () => {
      if (timer && shouldRun()) tick();
    });
  }

  return { start, stop, isRunning: () => !!timer };
}

const slugify = (text) =>
  (text || "menu")
    .toString()
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "") || "menu";

const formatMoney = (value) => `₦${Number(value || 0).toLocaleString()}`;

const ensureErrorBanner = () => {
  let bar = document.getElementById("afErrorBanner");
  if (!bar) {
    bar = document.createElement("div");
    bar.id = "afErrorBanner";
    bar.style.position = "fixed";
    bar.style.top = "0";
    bar.style.left = "0";
    bar.style.right = "0";
    bar.style.zIndex = "9999";
    bar.style.padding = "12px 16px";
    bar.style.background = "#b91c1c";
    bar.style.color = "#fff";
    bar.style.fontSize = "14px";
    bar.style.fontFamily = "system-ui, -apple-system, sans-serif";
    bar.style.boxShadow = "0 8px 24px rgba(0,0,0,0.15)";
    bar.style.display = "none";
    bar.style.cursor = "pointer";
    bar.title = "Click to dismiss";
    bar.addEventListener("click", () => {
      bar.style.display = "none";
    });
    document.body.appendChild(bar);
  }
  return bar;
};

const showErrorBanner = (message, detail = null) => {
  const bar = ensureErrorBanner();
  bar.textContent = message + (detail ? ` — ${detail}` : "");
  bar.style.display = "block";
};

const hideErrorBanner = () => {
  const bar = document.getElementById("afErrorBanner");
  if (bar) bar.style.display = "none";
};

document.addEventListener("DOMContentLoaded", () => {
  const dom = {
    navToggle: document.getElementById("navToggle"),
    nav: document.querySelector(".af-nav"),
    year: document.getElementById("year"),
    cartCount: document.getElementById("cartCount"),
    cartFab: document.getElementById("cartFab"),
    cartOverlay: document.getElementById("cartOverlay"),
    cartOverlayClose: document.getElementById("cartOverlayClose"),
    cartOverlayBackdrop: document.getElementById("cartOverlayBackdrop"),
    orderPromptBtn: document.getElementById("orderPromptBtn"),
    featuredGrid: document.getElementById("featuredGrid"),
    menuGrid: document.getElementById("menuGrid"),
    menuFilters: document.getElementById("menuFilters")
  };

  const state = {
    cart: [],
    activeFilter: "all",
    checkout: {
      inProgress: false,
      inFlightSignature: null,
      inFlightPromise: null
    },
    orderAvailability: {
      is_open: true,
      message: "",
      mode: "auto"
    },
    hasSSRMenuItems: !!(dom.menuGrid && dom.menuGrid.querySelector("[data-menu-item]")),
    hasSSRFeatured: !!(dom.featuredGrid && dom.featuredGrid.querySelector("[data-menu-item]")),
    hasSSRFilters: !!(dom.menuFilters && dom.menuFilters.querySelectorAll(".af-chip").length > 1)
  };

  const ensureClosedNotice = () => {
    let notice = document.getElementById("afClosedNotice");
    if (!notice) {
      notice = document.createElement("div");
      notice.id = "afClosedNotice";
      notice.className = "af-closed-notice";
      notice.setAttribute("role", "status");
      notice.setAttribute("aria-live", "polite");
      notice.hidden = true;
      notice.innerHTML = `
        <div class="af-closed-track">
          <span data-closed-message></span>
          <span data-closed-message aria-hidden="true"></span>
        </div>
      `;
      const header = document.querySelector(".af-header");
      if (header) {
        header.insertAdjacentElement("afterend", notice);
      } else {
        document.body.prepend(notice);
      }
    }
    return notice;
  };

  const setClosedNotice = (availability) => {
    const notice = ensureClosedNotice();
    const closed = availability && availability.is_open === false;
    notice.hidden = !closed;
    notice.classList.toggle("af-closed-notice-visible", closed);
    notice.querySelectorAll("[data-closed-message]").forEach((el) => {
      el.textContent = availability?.message || "We are currently closed and not accepting orders.";
    });
  };

  const formatScheduleTime = (value) => {
    if (!value || !/^\d{2}:\d{2}$/.test(value)) return "";
    const [hourRaw, minuteRaw] = value.split(":").map((part) => parseInt(part, 10));
    const suffix = hourRaw >= 12 ? "pm" : "am";
    const hour = hourRaw % 12 || 12;
    if (hourRaw === 12 && minuteRaw === 0) return "12noon";
    return `${hour}${minuteRaw ? `:${String(minuteRaw).padStart(2, "0")}` : ""}${suffix}`;
  };

  const updateBusinessHoursText = (schedule) => {
    if (!schedule?.weekday || !schedule?.sunday) return;
    const weekdayText = `Mon-Sat ${formatScheduleTime(schedule.weekday.open)} - ${formatScheduleTime(schedule.weekday.close)}`;
    const weekdayContactText = `Mon. - Sat.: ${formatScheduleTime(schedule.weekday.open)} - ${formatScheduleTime(schedule.weekday.close)}`;
    const sundayText = `Sun ${formatScheduleTime(schedule.sunday.open)} - ${formatScheduleTime(schedule.sunday.close)}`;
    const sundayContactText = `Sun.: ${formatScheduleTime(schedule.sunday.open)} - ${formatScheduleTime(schedule.sunday.close)}`;

    document.querySelectorAll("[data-business-hours-summary]").forEach((el) => {
      el.textContent = weekdayText;
    });
    document.querySelectorAll("[data-business-hours-weekday]").forEach((el) => {
      el.textContent = weekdayContactText;
    });
    document.querySelectorAll("[data-business-hours-sunday]").forEach((el) => {
      el.textContent = el.textContent.includes("Sun.:") ? sundayContactText : sundayText;
    });
  };

  const applyOrderAvailability = () => {
    const closed = state.orderAvailability.is_open === false;
    setClosedNotice(state.orderAvailability);
    updateBusinessHoursText(state.orderAvailability.schedule);

    document.querySelectorAll("[data-item]").forEach((btn) => {
      const soldOut = btn.getAttribute("data-sold-out") === "1";
      btn.disabled = closed || soldOut;
      btn.textContent = closed ? "Closed" : soldOut ? "Sold Out" : "Add to Cart";
    });

    document.querySelectorAll("[data-whatsapp-btn]").forEach((btn) => {
      btn.disabled = closed || state.checkout.inProgress;
      btn.textContent = closed
        ? "Ordering Closed"
        : state.checkout.inProgress
          ? "Opening WhatsApp..."
          : "Complete Order via WhatsApp";
    });
  };

  const syncOrderAvailability = async () => {
    try {
      const res = await fetch("/api/order-availability", { cache: "no-store" });
      if (!res.ok) return;
      state.orderAvailability = await res.json();
      applyOrderAvailability();
    } catch (error) {
      console.warn("Order availability check failed", error);
    }
  };

  const setYear = () => {
    if (dom.year) dom.year.textContent = new Date().getFullYear();
  };

  const initNav = () => {
    if (!dom.navToggle || !dom.nav) return;
    dom.navToggle.addEventListener("click", () => {
      dom.nav.classList.toggle("af-nav-open");
    });
    dom.nav.querySelectorAll("a").forEach((link) => {
      link.addEventListener("click", () => dom.nav.classList.remove("af-nav-open"));
    });
  };

  const openCartOverlay = () => {
    if (!dom.cartOverlay) return;
    dom.cartOverlay.classList.add("af-open");
    document.body.classList.add("af-modal-open");
  };

  const closeCartOverlay = () => {
    if (!dom.cartOverlay) return;
    dom.cartOverlay.classList.remove("af-open");
    document.body.classList.remove("af-modal-open");
  };

  const initCartOverlay = () => {
    if (dom.cartFab) dom.cartFab.addEventListener("click", openCartOverlay);
    if (dom.orderPromptBtn) dom.orderPromptBtn.addEventListener("click", openCartOverlay);
    if (dom.cartOverlayClose) dom.cartOverlayClose.addEventListener("click", closeCartOverlay);
    if (dom.cartOverlayBackdrop) dom.cartOverlayBackdrop.addEventListener("click", closeCartOverlay);
  };

  const bumpCartFab = () => {
    if (!dom.cartFab) return;
    dom.cartFab.classList.remove("af-cart-fab-bump");
    void dom.cartFab.offsetWidth; // force reflow to retrigger animation
    dom.cartFab.classList.add("af-cart-fab-bump");
  };

  const updateCartCount = () => {
    if (!dom.cartCount) return;
    const count = state.cart.reduce((sum, item) => sum + item.qty, 0);
    dom.cartCount.textContent = count;
    bumpCartFab();
  };

  const getCartTotal = () => state.cart.reduce((sum, item) => sum + item.price * item.qty, 0);

  const flyToCart = (sourceEl) => {
    if (!dom.cartFab || !sourceEl) return;
    const targetRect = dom.cartFab.getBoundingClientRect();
    const sourceImg = sourceEl.closest("article")?.querySelector("img") || sourceEl;
    const sourceRect = sourceImg.getBoundingClientRect();

    const dot = document.createElement("span");
    dot.className = "af-fly-item";
    dot.style.left = `${sourceRect.left + sourceRect.width / 2}px`;
    dot.style.top = `${sourceRect.top + sourceRect.height / 2}px`;
    document.body.appendChild(dot);

    const deltaX = targetRect.left + targetRect.width / 2 - (sourceRect.left + sourceRect.width / 2);
    const deltaY = targetRect.top + targetRect.height / 2 - (sourceRect.top + sourceRect.height / 2);

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
  };

  const setSoldOutState = (itemId, isSoldOut) => {
    const soldOut = isSoldOut ? "1" : "0";
    document.querySelectorAll(`[data-item-id="${itemId}"]`).forEach((btn) => {
      btn.setAttribute("data-sold-out", soldOut);
      btn.disabled = isSoldOut;
      btn.textContent = isSoldOut ? "Sold Out" : "Add to Cart";
    });

    document.querySelectorAll(`[data-menu-item][data-item-id="${itemId}"]`).forEach((card) => {
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

    applyOrderAvailability();
  };

  const renderCart = () => {
    const contexts = [
      { list: document.getElementById("cartList"), totalEl: document.getElementById("cartTotal") },
      { list: document.getElementById("cartListOverlay"), totalEl: document.getElementById("cartTotalOverlay") }
    ];

    const total = getCartTotal();

    contexts.forEach(({ list, totalEl }) => {
      if (!list || !totalEl) return;
      list.innerHTML = "";

      state.cart.forEach((item, index) => {
        const li = document.createElement("li");
        li.className = "af-cart-item";
        li.innerHTML = `
          <div class="af-cart-item-info">
            <span class="af-cart-item-name">${item.name}</span>
            <span class="af-cart-item-meta">${formatMoney(item.price)} × ${item.qty}</span>
          </div>
          <div class="af-cart-actions">
            <button class="af-qty-btn" data-action="dec" data-index="${index}">-</button>
            <button class="af-qty-btn" data-action="inc" data-index="${index}">+</button>
            <button class="af-qty-btn" data-action="remove" data-index="${index}">×</button>
          </div>
        `;
        list.appendChild(li);
      });

      totalEl.textContent = formatMoney(total);
    });

    updateCartCount();
  };

  const addToCart = (item) => {
    if (state.orderAvailability.is_open === false) {
      alert(state.orderAvailability.message || "We are currently closed and not accepting orders.");
      return;
    }
    if (!item?.id) {
      alert("Missing menu item ID; please refresh and try again.");
      return;
    }
    const existing = state.cart.find((i) => i.id === item.id);
    if (existing) {
      existing.qty += 1;
    } else {
      state.cart.push({ id: item.id, name: item.name, price: item.price || 0, qty: 1 });
    }
    renderCart();
  };

  const bindAddToCartButtons = () => {
    document.querySelectorAll("[data-item]").forEach((btn) => {
      if (btn.dataset.bound === "1") return;
      btn.dataset.bound = "1";
      btn.addEventListener("click", () => {
        const name = btn.getAttribute("data-item");
        const id = parseInt(btn.getAttribute("data-item-id"), 10);
        const soldOut = btn.getAttribute("data-sold-out") === "1";
        if (state.orderAvailability.is_open === false) {
          alert(state.orderAvailability.message || "We are currently closed and not accepting orders.");
          return;
        }
        if (soldOut) {
          alert("Sorry, this item is sold out.");
          return;
        }
        const priceEl = btn.closest("article")?.querySelector(".af-price");
        const priceAttr = btn.getAttribute("data-item-price");
        const parsedPrice = priceAttr
          ? parseFloat(priceAttr)
          : priceEl
            ? parseInt(priceEl.textContent.replace(/[^\d]/g, ""), 10)
            : 0;
        const price = Number.isFinite(parsedPrice) ? parsedPrice : 0;
        addToCart({ id, name, price });
        flyToCart(btn);
      });
    });
  };

  const bindCartQuantityButtons = () => {
    ["cartList", "cartListOverlay"].forEach((listId) => {
      const listEl = document.getElementById(listId);
      if (!listEl) return;
      listEl.addEventListener("click", (e) => {
        const btn = e.target.closest(".af-qty-btn");
        if (!btn) return;
        const index = parseInt(btn.getAttribute("data-index"), 10);
        const action = btn.getAttribute("data-action");
        const item = state.cart[index];
        if (!item) return;

        if (action === "inc") item.qty += 1;
        if (action === "dec") item.qty = Math.max(1, item.qty - 1);
        if (action === "remove") state.cart.splice(index, 1);
        renderCart();
      });
    });
  };

  const applyFilter = () => {
    if (!dom.menuGrid) return;
    dom.menuGrid.querySelectorAll(".af-menu-item").forEach((item) => {
      const category = slugify(item.getAttribute("data-category") || "all");
      item.style.display = state.activeFilter === "all" || category === state.activeFilter ? "" : "none";
    });
  };

  const bindFilterButtons = () => {
    if (!dom.menuFilters) return;
    dom.menuFilters.addEventListener("click", (e) => {
      const chipBtn = e.target.closest(".af-chip");
      if (!chipBtn) return;
      state.activeFilter = slugify(chipBtn.getAttribute("data-filter") || "all");
      dom.menuFilters.querySelectorAll(".af-chip").forEach((chip) => chip.classList.remove("af-chip-active"));
      chipBtn.classList.add("af-chip-active");
      applyFilter();
    });

    // Initialize active filter from the DOM (for SSR pages)
    const initial = dom.menuFilters.querySelector(".af-chip-active") || dom.menuFilters.querySelector(".af-chip");
    if (initial) {
      state.activeFilter = slugify(initial.getAttribute("data-filter") || "all");
    }
  };

  const ensureCategoryChip = (catName) => {
    if (!dom.menuFilters || !catName) return;
    const slug = slugify(catName);
    const existing = dom.menuFilters.querySelector(`[data-filter="${slug}"]`);
    if (existing) return;
    const btn = document.createElement("button");
    btn.className = "af-chip";
    btn.setAttribute("data-filter", slug);
    btn.textContent = catName;
    dom.menuFilters.appendChild(btn);
    bindFilterButtons();
  };

  const renderFilters = (categories) => {
    if (!dom.menuFilters) return;
    const chips = [
      { slug: "all", name: "All", active: true },
      ...categories.map((c) => ({
        slug: slugify(c.name),
        name: c.name,
        active: false
      }))
    ];

    dom.menuFilters.innerHTML = chips
      .map(
        (chip) => `
        <button class="af-chip ${chip.active ? "af-chip-active" : ""}" data-filter="${chip.slug}">
          ${chip.name}
        </button>
      `
      )
      .join("");

    bindFilterButtons();
  };

  const resolveImageUrl = (item) => {
    const raw =
      item?.image_url ||
      item?.image ||
      item?.photo_url ||
      (item?.media && item.media[0]?.url) ||
      "";
    if (!raw) return "";
    if (/^https?:\/\//i.test(raw) || raw.startsWith("data:")) return raw;
    if (raw.startsWith("/")) return raw;
    // assume it is a storage-relative path
    return `/storage/${raw}`;
  };

  const normalizeItem = (item) => {
    if (!item) return { valid: false, reason: "empty item" };
    const id = item?.id ?? item?.menu_item_id ?? null;
    const name = item?.name ?? item?.title ?? "";
    const rawPrice = Number(item?.price);
    const price = Number.isFinite(rawPrice) ? rawPrice : null;
    const categoryName = item?.category?.name ?? item?.category_name ?? "Menu";
    const description = item?.description ?? "";
    const imageUrl = resolveImageUrl(item);
    const isValid = !!id && !!name && price !== null;
    return {
      ...item,
      id,
      name,
      description,
      price,
      categoryName,
      categorySlug: slugify(categoryName),
      is_sold_out: !!item?.is_sold_out,
      imageUrl,
      valid: isValid
    };
  };

  const renderFeatured = (items) => {
    if (!dom.featuredGrid) return;
    const normalized = items.map(normalizeItem).filter((i) => i.valid);
    const skipped = items.length - normalized.length;
    if (skipped > 0) {
      console.warn("Skipped invalid featured items", { skipped });
    }
    if (!normalized.length) {
      dom.featuredGrid.innerHTML =
        '<p style="grid-column:1/-1;text-align:center;">Featured items coming soon.</p>';
      return;
    }

    const topThree = normalized.slice(0, 3);
    dom.featuredGrid.innerHTML = topThree
      .map((item) => {
        return `
          <article
            class="af-card"
            data-menu-item
            data-item-id="${item.id}"
            data-sold-out="${item.is_sold_out ? "1" : "0"}"
            data-category="${item.categorySlug}"
          >
            ${item.imageUrl ? `<img src="${item.imageUrl}" alt="${item.name}" class="af-card-img" loading="lazy" decoding="async" />` : ""}
            <div class="af-card-body">
              <div class="af-card-top">
                <h3>${item.name}</h3>
                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                  <span class="af-tag">${item.categoryName}</span>
                  <span
                    class="af-pill"
                    data-soldout-pill
                    style="background:#fef2f2;color:#b91c1c;border-color:#fecdd3;${item.is_sold_out ? "" : "display:none;"}"
                  >Sold Out</span>
                </div>
              </div>
              <p>${item.description}</p>
              <div class="af-card-footer">
                <span class="af-price">${formatMoney(item.price)}</span>
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
    applyOrderAvailability();
  };

  const createMenuCard = (rawItem) => {
    const item = normalizeItem(rawItem);
    const soldOut = item.is_sold_out ? "1" : "0";
    const card = document.createElement("article");
    card.className = "af-menu-item";
    card.setAttribute("data-menu-item", "");
    card.setAttribute("data-item-id", item.id);
    card.setAttribute("data-sold-out", soldOut);
    card.setAttribute("data-category", item.categorySlug);
    card.innerHTML = `
      ${item.imageUrl ? `<div class="af-menu-thumb"><img src="${item.imageUrl}" alt="${item.name}" loading="lazy" decoding="async"></div>` : ""}
      <div class="af-menu-body">
      <div class="af-menu-head">
        <h3>${item.name}</h3>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
          <span class="af-pill">${item.categoryName}</span>
          <span
            class="af-pill"
            data-soldout-pill
            style="background:#fef2f2;color:#b91c1c;border-color:#fecdd3;${item.is_sold_out ? "" : "display:none;"}"
          >Sold Out</span>
        </div>
      </div>
      <p>${item.description}</p>
      <div class="af-menu-footer">
        <span class="af-price">${formatMoney(item.price)}</span>
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
      </div>
    `;
    return card;
  };

  const renderMenuError = (message) => {
    const html = `<p style="grid-column:1/-1;text-align:center;">${message}</p>`;
    if (!state.hasSSRMenuItems && dom.menuGrid) dom.menuGrid.innerHTML = html;
    if (!state.hasSSRFeatured && dom.featuredGrid) dom.featuredGrid.innerHTML = html;
    showErrorBanner(message);
  };

  const renderMenu = (items) => {
    if (!dom.menuGrid) return;
    const normalized = items.map(normalizeItem).filter((i) => i.valid);
    const skipped = items.length - normalized.length;
    if (skipped > 0) {
      console.warn("Skipped invalid menu items", { skipped });
    }
    if (!normalized.length) {
      renderMenuError("Menu is coming soon. Please check back.");
      return;
    }

    dom.menuGrid.innerHTML = normalized
      .map(
        (item) => `
        <article
          class="af-menu-item"
          data-menu-item
          data-item-id="${item.id}"
          data-sold-out="${item.is_sold_out ? "1" : "0"}"
          data-category="${item.categorySlug}"
        >
          ${item.imageUrl ? `<div class="af-menu-thumb"><img src="${item.imageUrl}" alt="${item.name}" loading="lazy" decoding="async"></div>` : ""}
          <div class="af-menu-body">
            <div class="af-menu-head">
              <h3>${item.name}</h3>
              <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                <span class="af-pill">${item.categoryName}</span>
                <span
                  class="af-pill"
                  data-soldout-pill
                  style="background:#fef2f2;color:#b91c1c;border-color:#fecdd3;${item.is_sold_out ? "" : "display:none;"}"
                >Sold Out</span>
              </div>
            </div>
            <p>${item.description}</p>
            <div class="af-menu-footer">
              <span class="af-price">${formatMoney(item.price)}</span>
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
          </div>
        </article>
      `
      )
      .join("") || '<p style="grid-column:1/-1;text-align:center;">Menu failed to render.</p>';

    bindAddToCartButtons();
    applyFilter();
  };

  const upsertMenuItem = (rawItem) => {
    const item = normalizeItem(rawItem);
    if (!rawItem || rawItem.is_active === false || !item.valid) {
      if (rawItem && !item.valid) console.warn("Skipping invalid menu item", rawItem);
      return;
    }
    const existing = document.querySelector(`[data-menu-item][data-item-id="${item.id}"]`);
    if (existing) {
      if (item.imageUrl) {
        const img = existing.querySelector("img");
        if (img) {
          img.src = item.imageUrl;
          img.alt = item.name;
        }
      }
      const titleEl = existing.querySelector(".af-menu-head h3, h3");
      if (titleEl) titleEl.textContent = item.name;
      const priceEl = existing.querySelector(".af-price");
      if (priceEl) priceEl.textContent = formatMoney(item.price);
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
    } else if (dom.menuGrid) {
      const card = createMenuCard(item);
      dom.menuGrid.appendChild(card);
      ensureCategoryChip(item.categoryName);
      bindAddToCartButtons();
      applyOrderAvailability();
      applyFilter();
    }
    setSoldOutState(item.id, !!item.is_sold_out);
  };

  const syncMenuAvailability = async () => {
    try {
      const res = await fetch("/api/menu-items?active_only=1", { cache: "no-store" });
      if (!res.ok) return;
      const items = await res.json();
      if (!Array.isArray(items)) return;
      items.forEach((item) => upsertMenuItem(item));
      hideErrorBanner();
    } catch (error) {
      // network errors are ignored; next poll will retry
      showErrorBanner("Live availability check failed", error?.message);
    }
  };

  const loadMenuData = async () => {
    if (!dom.menuGrid && !dom.featuredGrid && !dom.menuFilters) return;
    if (window.location.protocol === "file:") {
      renderMenuError("Menu needs the server running (API unreachable from file://).");
      return;
    }
    try {
      const [itemsRes, categoriesRes] = await Promise.all([
        fetch("/api/menu-items?active_only=1", { cache: "no-store" }),
        fetch("/api/categories?active_only=1", { cache: "no-store" })
      ]);

      if (!itemsRes.ok || !categoriesRes.ok) {
        const statusMsg = `${itemsRes.status}/${categoriesRes.status}`;
        console.error("Menu fetch failed", { status: statusMsg });
        return;
      }

      const items = itemsRes.ok ? await itemsRes.json() : [];
      const categories = categoriesRes.ok ? await categoriesRes.json() : [];
      const safeItems = Array.isArray(items) ? items : [];
      const safeCategories = Array.isArray(categories) ? categories : [];

      console.info("Menu data loaded", {
        items: safeItems.length,
        categories: safeCategories.length
      });

      if (!safeItems.length) {
        console.warn("API returned no items");
        return;
      }

      // Render the full menu from API response
      if (safeItems.length && dom.menuGrid) {
        renderMenu(safeItems);
      }

      if (safeItems.length && dom.featuredGrid) {
        renderFeatured(safeItems);
      }

      applyOrderAvailability();

      // Re-apply current filter
      applyFilter();

      // Rebind add to cart buttons
      bindAddToCartButtons();
      applyOrderAvailability();

      hideErrorBanner();
    } catch (err) {
      console.error("Menu data load failed", err);
      showErrorBanner("Failed to load menu", err?.message);
    }
  };

  const CHECKOUT_CACHE_KEY = "af_last_whatsapp_checkout";
  const CHECKOUT_CACHE_TTL_MS = 30 * 60 * 1000;

  const buildCheckoutSignature = ({ name, phone, note, service, time }) => JSON.stringify({
    name: String(name || "").trim().toLowerCase(),
    phone: String(phone || "").trim(),
    service: String(service || "").trim().toLowerCase(),
    time: String(time || "").trim().toLowerCase(),
    note: String(note || "").trim().toLowerCase(),
    items: state.cart.map((item) => ({
      id: item.id,
      qty: item.qty,
      price: item.price
    }))
  });

  const getCachedCheckoutOrder = (signature) => {
    try {
      const cached = JSON.parse(localStorage.getItem(CHECKOUT_CACHE_KEY) || "null");
      if (!cached || cached.signature !== signature || !cached.order) return null;
      if (Date.now() - Number(cached.savedAt || 0) > CHECKOUT_CACHE_TTL_MS) return null;
      return cached.order;
    } catch {
      return null;
    }
  };

  const cacheCheckoutOrder = (signature, order) => {
    try {
      localStorage.setItem(CHECKOUT_CACHE_KEY, JSON.stringify({
        signature,
        order,
        savedAt: Date.now()
      }));
    } catch {
      // Checkout should still continue if storage is unavailable.
    }
  };

  const createBackendOrder = async ({ name, phone, note, service, time, signature }) => {
    if (!state.cart.length) return;
    const cachedOrder = getCachedCheckoutOrder(signature);
    if (cachedOrder) return cachedOrder;

    if (state.checkout.inFlightSignature === signature && state.checkout.inFlightPromise) {
      return state.checkout.inFlightPromise;
    }

    const payload = {
      channel: "web",
      customer_name: name || null,
      customer_phone: phone || null,
      items: state.cart.map((item) => ({
        menu_item_id: item.id,
        quantity: item.qty,
        price: item.price
      })),
      discount: 0,
      tax: 0,
      send_to_kitchen: false,
      note: note || null,
      service: service || null,
      time: time || null
    };

    state.checkout.inFlightSignature = signature;
    state.checkout.inFlightPromise = (async () => {
      const res = await fetch("/api/orders", {
        method: "POST",
        headers: { "Content-Type": "application/json", Accept: "application/json" },
        body: JSON.stringify(payload),
        cache: "no-store"
      });
      if (!res.ok) {
        let message = `Order save failed (${res.status})`;
        try {
          const data = await res.clone().json();
          if (data?.errors) {
            message = Object.values(data.errors).flat().filter(Boolean).join(" ");
          } else if (data?.message) {
            message = data.message;
          }
        } catch (error) {
          const text = await res.text().catch(() => "");
          if (text) message = text;
        }
        throw new Error(message);
      }
      const order = await res.json();
      cacheCheckoutOrder(signature, order);
      return order;
    })();

    try {
      return await state.checkout.inFlightPromise;
    } finally {
      state.checkout.inFlightSignature = null;
      state.checkout.inFlightPromise = null;
    }
  };

  const buildWhatsAppUrl = ({ name, phone, note, service, time, order }) => {
    const lines = [
      "New Order - Acie Fraiche Cafe",
      "",
      order?.code ? `Order Code: ${order.code}` : "",
      `Name: ${name}`,
      `Phone: ${phone}`,
      `Service: ${service}`,
      `Time: ${time}`,
      note ? `Note: ${note}` : "",
      "",
      "Items:",
      ...state.cart.map((item) => `- ${item.name} (${formatMoney(item.price)} x ${item.qty})`),
      "",
      `Total: ${formatMoney(getCartTotal())}`,
      "",
      "Order Source: Website"
    ].filter((line) => line !== "").join("\n");

    const whatsappNumber = "2348143190700";
    return `https://wa.me/${whatsappNumber}?text=${encodeURIComponent(lines)}`;
  };

  const handleWhatsApp = async (form) => {
    if (!state.cart.length) {
      alert("Your cart is empty.");
      return;
    }
    if (!form) {
      alert("Please fill your details first.");
      return;
    }
    if (!form.checkValidity()) {
      form.reportValidity();
      return;
    }

    const formData = new FormData(form);
    const name = formData.get("name");
    const phone = formData.get("phone");
    const service = formData.get("service");
    const time = formData.get("time");
    const note = formData.get("note");
    const signature = buildCheckoutSignature({ name, phone, note, service, time });
    const whatsappWindow = window.open("", "_blank");

    state.checkout.inProgress = true;
    applyOrderAvailability();
    try {
      await syncOrderAvailability();
      if (state.orderAvailability.is_open === false) {
        if (whatsappWindow && !whatsappWindow.closed) whatsappWindow.close();
        alert(state.orderAvailability.message || "We are currently closed and not accepting orders.");
        openCartOverlay();
        return;
      }

      const order = await createBackendOrder({ name, phone, note, service, time, signature });
      const url = buildWhatsAppUrl({ name, phone, note, service, time, order });
      if (whatsappWindow && !whatsappWindow.closed) {
        whatsappWindow.location.href = url;
      } else {
        window.location.href = url;
      }
    } catch (e) {
      console.warn("Could not create backend order", e);
      if (whatsappWindow && !whatsappWindow.closed) whatsappWindow.close();
      alert(e?.message || "We could not save your order for staff. Please try again.");
      await syncOrderAvailability();
    } finally {
      state.checkout.inProgress = false;
      applyOrderAvailability();
    }
  };

  const bindWhatsAppButtons = () => {
    document.querySelectorAll("[data-whatsapp-btn]").forEach((btn) => {
      btn.addEventListener("click", () => {
        const formId = btn.getAttribute("data-form");
        const form = formId ? document.getElementById(formId) : null;
        handleWhatsApp(form);
      });
    });
  };

  // Surface unexpected runtime errors to the page for quicker debugging
  window.addEventListener("error", (evt) => {
    showErrorBanner("A script error occurred", evt?.message || "Unknown error");
  });
  window.addEventListener("unhandledrejection", (evt) => {
    const msg = evt?.reason?.message || evt?.reason || "Unknown promise rejection";
    showErrorBanner("A network or script error occurred", msg);
  });

  const init = () => {
    setYear();
    initNav();
    initCartOverlay();
    bindCartQuantityButtons();

    // Initialize filter state BEFORE binding filter buttons or applying filters
    if (dom.menuFilters) {
      const initial = dom.menuFilters.querySelector(".af-chip-active") || dom.menuFilters.querySelector(".af-chip");
      if (initial) {
        state.activeFilter = slugify(initial.getAttribute("data-filter") || "all");
      } else {
        state.activeFilter = "all";
      }
    }

    bindFilterButtons();
    bindAddToCartButtons(); // in case items are server-rendered
    syncOrderAvailability();
    applyFilter();
    renderCart();
    bindWhatsAppButtons();

    const hasSSR = state.hasSSRMenuItems || state.hasSSRFeatured;
    if (!hasSSR) {
      loadMenuData();
    }

    // Enable polling: automatically sync menu updates every 10 seconds
    // Using loadMenuData which safely re-renders the menu from API
    const menuPoller = createPoller(loadMenuData, 10000);
    menuPoller.start();

    const availabilityPoller = createPoller(syncOrderAvailability, 60000);
    availabilityPoller.start();
  };

  init();
});
