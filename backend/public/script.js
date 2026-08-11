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
const escapeHtml = (value) =>
  String(value ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
const formatStockUnit = (quantity, unit) => {
  const cleanUnit = String(unit || "").trim();
  if (!cleanUnit) return "left";
  if (Number(quantity) === 1) return cleanUnit.replace(/s+$/i, "");
  return /s$/i.test(cleanUnit) ? cleanUnit : `${cleanUnit}s`;
};
const formatStockLabel = (stock, unit) => {
  if (stock === null || stock === undefined || stock === "") return "";
  return `${Number(stock).toLocaleString()} ${formatStockUnit(stock, unit)} left`;
};

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
    cartCountWord: document.getElementById("cartCountWord"),
    cartBarTotal: document.getElementById("cartBarTotal"),
    cartFab: document.getElementById("cartFab"),
    cartOverlay: document.getElementById("cartOverlay"),
    cartOverlayClose: document.getElementById("cartOverlayClose"),
    cartOverlayBackdrop: document.getElementById("cartOverlayBackdrop"),
    orderPromptBtn: document.getElementById("orderPromptBtn"),
    featuredGrid: document.getElementById("featuredGrid"),
    menuGrid: document.getElementById("menuGrid"),
    menuFilters: document.getElementById("menuFilters"),
    menuPanel: document.getElementById("menuPanel"),
    categoryGrid: document.getElementById("categoryGrid")
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
    menuSignature: "",
    featuredSignature: "",
    filtersSignature: "",
    filtersBound: false,
    categoryCardsBound: false,
    categoryMode: !!document.getElementById("categoryGrid"),
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

  const setCartOverlayVisible = (visible) => {
    if (!dom.cartOverlay) return;
    dom.cartOverlay.classList.toggle("af-open", visible);
    dom.cartOverlay.setAttribute("aria-hidden", visible ? "false" : "true");
    document.body.classList.toggle("af-modal-open", visible);
  };

  const isCartOverlayOpen = () => !!dom.cartOverlay?.classList.contains("af-open");

  const openCartOverlay = (options = {}) => {
    if (!dom.cartOverlay || isCartOverlayOpen()) return;
    const { updateHistory = true } = options;
    setCartOverlayVisible(true);

    if (updateHistory && window.history?.pushState) {
      const currentState = window.history.state || {};
      if (!currentState.afCartOverlayOpen) {
        window.history.pushState({ ...currentState, afCartOverlayOpen: true }, "", window.location.href);
      }
    }
  };

  const closeCartOverlay = (options = {}) => {
    if (!dom.cartOverlay || !isCartOverlayOpen()) return;
    const { updateHistory = true } = options;
    setCartOverlayVisible(false);

    if (updateHistory && window.history?.back && window.history.state?.afCartOverlayOpen) {
      window.history.back();
    }
  };

  const initCartOverlay = () => {
    if (dom.cartFab) dom.cartFab.addEventListener("click", openCartOverlay);
    if (dom.orderPromptBtn) dom.orderPromptBtn.addEventListener("click", openCartOverlay);
    document.querySelectorAll("[data-cart-open]").forEach((btn) => {
      btn.addEventListener("click", openCartOverlay);
    });
    if (dom.cartOverlayClose) dom.cartOverlayClose.addEventListener("click", closeCartOverlay);
    if (dom.cartOverlayBackdrop) dom.cartOverlayBackdrop.addEventListener("click", closeCartOverlay);

    window.addEventListener("popstate", (event) => {
      if (event.state?.afCartOverlayOpen) {
        openCartOverlay({ updateHistory: false });
      } else {
        closeCartOverlay({ updateHistory: false });
      }
    });
  };

  const bumpCartFab = () => {
    if (!dom.cartFab) return;
    dom.cartFab.classList.remove("af-cart-fab-bump");
    void dom.cartFab.offsetWidth; // force reflow to retrigger animation
    dom.cartFab.classList.add("af-cart-fab-bump");
  };

  const updateCartCount = () => {
    const count = state.cart.reduce((sum, item) => sum + item.qty, 0);
    if (dom.cartCount) dom.cartCount.textContent = count;
    if (dom.cartBarTotal) dom.cartBarTotal.textContent = formatMoney(getCartTotal());
    document.body.classList.toggle("af-cart-has-items", count > 0);
    if (dom.cartCountWord) dom.cartCountWord.textContent = count === 1 ? "item" : "items";
    if (count > 0) bumpCartFab();
  };

  const getCartTotal = () => state.cart.reduce((sum, item) => sum + item.price * item.qty, 0);

  const flyToCart = (sourceEl) => {
    if (!dom.cartFab || !sourceEl) return;
    if (window.getComputedStyle(dom.cartFab).display === "none") return;
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

  const updateStockPill = (card) => {
    if (!card) return;
    card.querySelectorAll("[data-stock-pill]").forEach((pill) => pill.remove());
  };

  const setSoldOutState = (itemId, isSoldOut, stock = null, stockUnit = "") => {
    const soldOut = isSoldOut ? "1" : "0";
    document.querySelectorAll(`[data-item-id="${itemId}"]`).forEach((btn) => {
      btn.setAttribute("data-sold-out", soldOut);
      btn.setAttribute("data-stock", stock ?? "");
      btn.setAttribute("data-stock-unit", stockUnit || "");
      btn.disabled = isSoldOut;
      btn.textContent = isSoldOut ? "Sold Out" : "Add to Cart";
    });

    document.querySelectorAll(`[data-menu-item][data-item-id="${itemId}"]`).forEach((card) => {
      card.setAttribute("data-sold-out", soldOut);
      card.setAttribute("data-stock", stock ?? "");
      card.setAttribute("data-stock-unit", stockUnit || "");
      updateStockPill(card, stock, stockUnit);
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

      if (!state.cart.length) {
        const empty = document.createElement("li");
        empty.className = "af-cart-empty";
        empty.innerHTML = `
          <strong>Your cart is empty.</strong>
          <span>Add something delicious from the menu.</span>
          <button type="button" class="af-btn af-btn-sm af-btn-outline" data-cart-browse>Browse Menu</button>
        `;
        list.appendChild(empty);
      }

      state.cart.forEach((item, index) => {
        const lineTotal = item.price * item.qty;
        const itemName = escapeHtml(item.name);
        const li = document.createElement("li");
        li.className = "af-cart-item";
        li.innerHTML = `
          <div class="af-cart-item-info">
            <div class="af-cart-item-title">
              <span class="af-cart-item-name">${itemName}</span>
              <button class="af-cart-remove" data-action="remove" data-index="${index}" aria-label="Remove ${itemName}">×</button>
            </div>
            <span class="af-cart-item-meta">${formatMoney(item.price)} each</span>
            <strong class="af-cart-item-line-total">${formatMoney(lineTotal)}</strong>
          </div>
          <div class="af-cart-actions">
            <button class="af-qty-btn" data-action="dec" data-index="${index}" aria-label="Decrease ${itemName} quantity">-</button>
            <span class="af-cart-qty" aria-label="Quantity">${item.qty}</span>
            <button class="af-qty-btn" data-action="inc" data-index="${index}" aria-label="Increase ${itemName} quantity">+</button>
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
    const nextQty = existing ? existing.qty + 1 : 1;
    if (item.stock !== null && item.stock !== undefined && nextQty > Number(item.stock)) {
      alert(`Only ${formatStockLabel(item.stock, item.stockUnit || item.stock_unit || "").replace(/ left$/, "")} available.`);
      return;
    }
    if (existing) {
      existing.qty += 1;
    } else {
      state.cart.push({
        id: item.id,
        name: item.name,
        price: item.price || 0,
        qty: 1,
        stock: item.stock,
        stockUnit: item.stockUnit || item.stock_unit || ""
      });
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
        const stockAttr = btn.getAttribute("data-stock");
        const stock = stockAttr === "" || stockAttr === null ? null : Number(stockAttr);
        addToCart({ id, name, price, stock, stockUnit: btn.getAttribute("data-stock-unit") || "" });
        flyToCart(btn);
      });
    });
  };

  const bindCartQuantityButtons = () => {
    ["cartList", "cartListOverlay"].forEach((listId) => {
      const listEl = document.getElementById(listId);
      if (!listEl) return;
      listEl.addEventListener("click", (e) => {
        const browseBtn = e.target.closest("[data-cart-browse]");
        if (browseBtn) {
          closeCartOverlay();
          document.getElementById("menu")?.scrollIntoView({ behavior: "smooth", block: "start" });
          return;
        }

        const btn = e.target.closest(".af-qty-btn");
        const removeBtn = e.target.closest(".af-cart-remove");
        const actionBtn = btn || removeBtn;
        if (!actionBtn) return;
        const index = parseInt(actionBtn.getAttribute("data-index"), 10);
        const action = actionBtn.getAttribute("data-action");
        const item = state.cart[index];
        if (!item) return;

        if (action === "inc") {
          if (item.stock !== null && item.stock !== undefined && item.qty + 1 > Number(item.stock)) {
            alert(`Only ${formatStockLabel(item.stock, item.stockUnit || "").replace(/ left$/, "")} available.`);
            return;
          }
          item.qty += 1;
        }
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

  const setMenuMode = (mode) => {
    state.categoryMode = mode === "categories";
    if (!dom.menuPanel) return;
    dom.menuPanel.classList.toggle("af-category-mode", state.categoryMode);
    dom.menuPanel.classList.toggle("af-products-mode", !state.categoryMode);
  };

  const setActiveFilter = (filter) => {
    state.activeFilter = slugify(filter || "all");
    if (dom.menuFilters) {
      dom.menuFilters.querySelectorAll(".af-chip").forEach((chip) => {
        const active = slugify(chip.getAttribute("data-filter") || "all") === state.activeFilter;
        chip.classList.toggle("af-chip-active", active);
        chip.setAttribute("aria-pressed", active ? "true" : "false");
      });
    }
    applyFilter();
  };

  const bindCategoryCards = () => {
    if (!dom.categoryGrid || state.categoryCardsBound) return;
    state.categoryCardsBound = true;
    dom.categoryGrid.addEventListener("click", (e) => {
      const card = e.target.closest("[data-category-card]");
      if (!card) return;
      setMenuMode("products");
      setActiveFilter(card.getAttribute("data-filter") || "all");
      dom.menuGrid?.scrollIntoView({ behavior: "smooth", block: "start" });
    });
  };

  const bindFilterButtons = () => {
    if (!dom.menuFilters) return;
    if (state.filtersBound) return;
    state.filtersBound = true;
    dom.menuFilters.addEventListener("click", (e) => {
      const chipBtn = e.target.closest(".af-chip");
      if (!chipBtn) return;
      setMenuMode("products");
      setActiveFilter(chipBtn.getAttribute("data-filter") || "all");
    });
  };

  const bindCategoryBack = () => {
    document.querySelectorAll("[data-category-back]").forEach((btn) => {
      if (btn.dataset.bound === "1") return;
      btn.dataset.bound = "1";
      btn.addEventListener("click", () => {
        setMenuMode("categories");
        document.getElementById("menu")?.scrollIntoView({ behavior: "smooth", block: "start" });
      });
    });
  };

  const syncActiveFilterFromDom = () => {
    if (!dom.menuFilters) return;
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
    btn.setAttribute("aria-pressed", "false");
    btn.textContent = catName;
    dom.menuFilters.appendChild(btn);
    bindFilterButtons();
  };

  const renderFilters = (categories) => {
    if (!dom.menuFilters) return;
    const existingActive = state.activeFilter || "all";
    const chips = [
      { slug: "all", name: "All", active: existingActive === "all" },
      ...categories.map((c) => ({
        slug: slugify(c.name),
        name: c.name,
        active: slugify(c.name) === existingActive
      }))
    ];
    if (!chips.some((chip) => chip.active)) {
      chips[0].active = true;
      state.activeFilter = "all";
    }

    dom.menuFilters.innerHTML = chips
      .map(
        (chip) => `
        <button class="af-chip ${chip.active ? "af-chip-active" : ""}" data-filter="${chip.slug}" aria-pressed="${chip.active ? "true" : "false"}">
          ${escapeHtml(chip.name)}
        </button>
      `
      )
      .join("");

    bindFilterButtons();
  };

  const renderCategoryCards = (categories, items) => {
    if (!dom.categoryGrid) return;
    if (!Array.isArray(categories) || !categories.length) {
      dom.categoryGrid.innerHTML = '<p class="af-menu-empty">Menu categories are coming soon. Please check back.</p>';
      return;
    }

    dom.categoryGrid.innerHTML = categories.map((category) => {
      const categoryName = escapeHtml(category?.name || "Menu");
      const categoryDescription = escapeHtml(category?.description || "Browse freshly prepared favorites in this category.");
      const slug = slugify(category?.name || "menu");
      const categoryItems = items.filter((item) => {
        const normalized = normalizeItem(item);
        return normalized.categorySlug === slug;
      });
      const images = [
        category?.image_url,
        ...categoryItems.map((item) => normalizeItem(item).imageUrl)
      ].filter(Boolean).slice(0, 4);
      const uniqueImages = [...new Set(images)];
      const imageHtml = uniqueImages.length
        ? uniqueImages.map((url, index) => `<img src="${escapeHtml(url)}" alt="" loading="lazy" decoding="async" style="--slide-index: ${index};">`).join("")
        : '<span class="af-menu-thumb-fallback"><span>AFC</span></span>';
      return `
        <article class="af-category-card" data-category-card data-filter="${slug}">
          <button type="button" class="af-category-card-action" data-category-card-button aria-label="View ${categoryName} items">
            <span class="af-category-preview" aria-hidden="true">${imageHtml}</span>
            <span class="af-category-card-body">
              <strong>${categoryName}</strong>
              <span>${categoryDescription}</span>
              <span class="af-category-card-meta">${categoryItems.length} ${categoryItems.length === 1 ? "item" : "items"} · View items</span>
            </span>
          </button>
        </article>
      `;
    }).join("");
  };

  const buildMenuSignature = (items) =>
    items
      .map(normalizeItem)
      .filter((item) => item.valid)
      .map((item) => [
        item.id,
        item.name,
        item.description,
        item.price,
        item.categoryName,
        item.imageUrl,
        item.stock ?? "",
        item.stock_unit || "",
        item.is_sold_out ? "1" : "0"
      ].join("|"))
      .join("||");

  const buildFeaturedSignature = (items) =>
    items
      .map(normalizeItem)
      .filter((item) => item.valid)
      .slice(0, 3)
      .map((item) => [
        item.id,
        item.name,
        item.description,
        item.price,
        item.categoryName,
        item.imageUrl,
        item.stock ?? "",
        item.stock_unit || "",
        item.is_sold_out ? "1" : "0"
      ].join("|"))
      .join("||");

  const buildFiltersSignature = (categories) =>
    categories.map((category) => `${slugify(category?.name || "")}|${category?.name || ""}`).join("||");

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
    const stock = item?.stock === null || item?.stock === undefined || item?.stock === "" ? null : Number(item.stock);
    const stockUnit = item?.stock_unit || "";
    const isValid = !!id && !!name && price !== null;
    return {
      ...item,
      id,
      name,
      description,
      price,
      categoryName,
      categorySlug: slugify(categoryName),
      stock,
      stock_unit: stockUnit,
      is_sold_out: !!item?.is_sold_out || stock === 0,
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
        const itemName = escapeHtml(item.name);
        const itemDescription = escapeHtml(item.description);
        const categoryName = escapeHtml(item.categoryName);
        return `
          <article
            class="af-card"
            data-menu-item
            data-item-id="${item.id}"
            data-sold-out="${item.is_sold_out ? "1" : "0"}"
            data-stock="${item.stock ?? ""}"
            data-stock-unit="${item.stock_unit || ""}"
            data-category="${item.categorySlug}"
          >
            ${item.imageUrl ? `<img src="${escapeHtml(item.imageUrl)}" alt="${itemName}" class="af-card-img" loading="lazy" decoding="async" />` : `<div class="af-menu-thumb-fallback" aria-hidden="true"><span>AFC</span></div>`}
            <div class="af-card-body">
              <div class="af-card-top">
                <h3>${itemName}</h3>
                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                  <span class="af-tag">${categoryName}</span>
                  <span
                    class="af-pill"
                    data-soldout-pill
                    style="background:#fef2f2;color:#b91c1c;border-color:#fecdd3;${item.is_sold_out ? "" : "display:none;"}"
                  >Sold Out</span>
                </div>
              </div>
              <p>${itemDescription}</p>
              <div class="af-card-footer">
                <span class="af-price">${formatMoney(item.price)}</span>
                <button
                  class="af-btn af-btn-sm af-btn-primary"
                  data-item="${itemName}"
                  data-item-id="${item.id}"
                  data-item-price="${item.price}"
                  data-sold-out="${item.is_sold_out ? "1" : "0"}"
                  data-stock="${item.stock ?? ""}"
                  data-stock-unit="${item.stock_unit || ""}"
                  aria-label="${item.is_sold_out ? `Sold out: ${itemName}` : `Add ${itemName} to cart`}"
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
    const itemName = escapeHtml(item.name);
    const itemDescription = escapeHtml(item.description);
    const categoryName = escapeHtml(item.categoryName);
    const imageUrl = escapeHtml(item.imageUrl);
    const card = document.createElement("article");
    card.className = "af-menu-item";
    card.setAttribute("data-menu-item", "");
    card.setAttribute("data-item-id", item.id);
    card.setAttribute("data-sold-out", soldOut);
    card.setAttribute("data-stock", item.stock ?? "");
    card.setAttribute("data-stock-unit", item.stock_unit || "");
    card.setAttribute("data-category", item.categorySlug);
    card.innerHTML = `
      <div class="af-menu-thumb">
        ${item.imageUrl ? `<img src="${imageUrl}" alt="${itemName}" loading="lazy" decoding="async">` : `<div class="af-menu-thumb-fallback" aria-hidden="true"><span>AFC</span></div>`}
      </div>
      <div class="af-menu-body">
      <div class="af-menu-head">
        <h3>${itemName}</h3>
        <div class="af-menu-meta">
          <span class="af-pill">${categoryName}</span>
          <span
            class="af-pill"
            data-soldout-pill
            style="background:#fef2f2;color:#b91c1c;border-color:#fecdd3;${item.is_sold_out ? "" : "display:none;"}"
          >Sold Out</span>
        </div>
      </div>
      <p>${itemDescription}</p>
      <div class="af-menu-footer">
        <span class="af-price">${formatMoney(item.price)}</span>
        <button
          class="af-btn af-btn-sm af-btn-outline"
          data-item="${itemName}"
          data-item-id="${item.id}"
          data-item-price="${item.price}"
          data-sold-out="${soldOut}"
          data-stock="${item.stock ?? ""}"
          data-stock-unit="${item.stock_unit || ""}"
          aria-label="${item.is_sold_out ? `Sold out: ${itemName}` : `Add ${itemName} to cart`}"
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
        (item) => {
          const itemName = escapeHtml(item.name);
          const itemDescription = escapeHtml(item.description);
          const categoryName = escapeHtml(item.categoryName);
          const imageUrl = escapeHtml(item.imageUrl);
          return `
        <article
          class="af-menu-item"
          data-menu-item
          data-item-id="${item.id}"
          data-sold-out="${item.is_sold_out ? "1" : "0"}"
          data-stock="${item.stock ?? ""}"
          data-stock-unit="${item.stock_unit || ""}"
          data-category="${item.categorySlug}"
        >
          <div class="af-menu-thumb">
            ${item.imageUrl ? `<img src="${imageUrl}" alt="${itemName}" loading="lazy" decoding="async">` : `<div class="af-menu-thumb-fallback" aria-hidden="true"><span>AFC</span></div>`}
          </div>
          <div class="af-menu-body">
            <div class="af-menu-head">
              <h3>${itemName}</h3>
              <div class="af-menu-meta">
                <span class="af-pill">${categoryName}</span>
                <span
                  class="af-pill"
                  data-soldout-pill
                  style="background:#fef2f2;color:#b91c1c;border-color:#fecdd3;${item.is_sold_out ? "" : "display:none;"}"
                >Sold Out</span>
              </div>
            </div>
            <p>${itemDescription}</p>
            <div class="af-menu-footer">
              <span class="af-price">${formatMoney(item.price)}</span>
              <button
                class="af-btn af-btn-sm af-btn-outline"
                data-item="${itemName}"
                data-item-id="${item.id}"
                data-item-price="${item.price}"
                data-sold-out="${item.is_sold_out ? "1" : "0"}"
                data-stock="${item.stock ?? ""}"
                data-stock-unit="${item.stock_unit || ""}"
                aria-label="${item.is_sold_out ? `Sold out: ${itemName}` : `Add ${itemName} to cart`}"
                ${item.is_sold_out ? "disabled" : ""}
              >
                ${item.is_sold_out ? "Sold Out" : "Add to Cart"}
              </button>
            </div>
          </div>
        </article>
      `;
        }
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
      existing.setAttribute("data-stock", item.stock ?? "");
      existing.setAttribute("data-stock-unit", item.stock_unit || "");
      updateStockPill(existing, item.stock, item.stock_unit);
      const pill = existing.querySelector("[data-soldout-pill]");
      if (pill) pill.style.display = item.is_sold_out ? "inline-flex" : "none";
      const btn = existing.querySelector("[data-item]");
      if (btn) {
        btn.setAttribute("data-sold-out", item.is_sold_out ? "1" : "0");
        btn.setAttribute("data-stock", item.stock ?? "");
        btn.setAttribute("data-stock-unit", item.stock_unit || "");
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
    setSoldOutState(item.id, !!item.is_sold_out, item.stock, item.stock_unit);
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
      const nextMenuSignature = buildMenuSignature(safeItems);
      const nextFeaturedSignature = buildFeaturedSignature(safeItems);
      const nextFiltersSignature = buildFiltersSignature(safeCategories);

      console.info("Menu data loaded", {
        items: safeItems.length,
        categories: safeCategories.length
      });

      if (!safeItems.length) {
        console.warn("API returned no items");
        return;
      }

      if (safeCategories.length && dom.menuFilters && nextFiltersSignature !== state.filtersSignature) {
        renderFilters(safeCategories);
        state.filtersSignature = nextFiltersSignature;
      }

      if (safeCategories.length && dom.categoryGrid) {
        renderCategoryCards(safeCategories, safeItems);
        bindCategoryCards();
      }

      if (safeItems.length && dom.menuGrid && nextMenuSignature !== state.menuSignature) {
        renderMenu(safeItems);
        state.menuSignature = nextMenuSignature;
      }

      if (safeItems.length && dom.featuredGrid && nextFeaturedSignature !== state.featuredSignature) {
        renderFeatured(safeItems);
        state.featuredSignature = nextFeaturedSignature;
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
    bindCategoryCards();
    bindCategoryBack();
    setMenuMode("categories");
    syncActiveFilterFromDom();
    bindAddToCartButtons(); // in case items are server-rendered
    syncOrderAvailability();
    applyFilter();
    renderCart();
    bindWhatsAppButtons();

    const hasSSR = state.hasSSRMenuItems || state.hasSSRFeatured;
    if (!hasSSR) {
      loadMenuData();
    }

    // Poll without an immediate second render; server-rendered pages are already populated.
    const menuPoller = createPoller(loadMenuData, 10000, { immediate: false });
    menuPoller.start();

    const availabilityPoller = createPoller(syncOrderAvailability, 60000);
    availabilityPoller.start();
  };

  init();
});
