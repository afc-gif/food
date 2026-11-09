import { useEffect, useMemo, useRef, useState } from "react";

const CART_STORAGE_KEY = "afc-react-menu-cart";
const CUSTOMER_CARE_WELCOME =
  "Hi! I’m here to help you find a favourite or place an order.";

async function fetchJson(path, options) {
  const response = await fetch(path, {
    headers: {
      Accept: "application/json",
      ...(options?.body ? { "Content-Type": "application/json" } : {}),
      ...options?.headers,
    },
    ...options,
  });
  const payload = await response.json().catch(() => null);

  if (!response.ok) {
    const validationMessage = payload?.errors
      ? Object.values(payload.errors).flat().join(" ")
      : "";
    throw new Error(
      validationMessage || payload?.message || "The request could not be completed.",
    );
  }

  return payload;
}

function fetchCollection(path) {
  return fetchJson(`/api/${path}`).then((payload) => {
    if (!Array.isArray(payload)) {
      throw new Error(`The menu API returned an unexpected response for ${path}.`);
    }

    return payload;
  });
}

function formatPrice(price) {
  return `₦${Number(price || 0).toLocaleString("en-NG", {
    maximumFractionDigits: 0,
  })}`;
}

function buildWhatsAppUrl({ order, cart, total, customer }) {
  const receiptUrl =
    order?.receipt_url ||
    (order?.code
      ? `${window.location.origin}/receipt/${encodeURIComponent(order.code)}`
      : "");
  const lines = [
    "Hello AFC! I'd like to place an order.",
    "",
    order?.code ? `Order code: ${order.code}` : "",
    receiptUrl ? `Receipt: ${receiptUrl}` : "",
    order?.total !== undefined && order?.total !== null
      ? `Official total: ${formatPrice(order.total)}`
      : "",
    "",
    "Order:",
    ...cart.map(
      (line) =>
        `${line.quantity} × ${line.item.name} — ${formatPrice(
          Number(line.item.price || 0) * line.quantity,
        )}`,
    ),
    `Estimated total: ${formatPrice(total)}`,
    "",
    `Name: ${customer.name.trim()}`,
    `Phone: ${customer.phone.trim()}`,
    `Service: ${customer.service}`,
    customer.note.trim() ? `Note: ${customer.note.trim()}` : "",
  ].filter(Boolean);

  return `https://wa.me/2348143190700?text=${encodeURIComponent(lines.join("\n"))}`;
}

function readSavedCart() {
  try {
    const savedCart = JSON.parse(localStorage.getItem(CART_STORAGE_KEY) || "[]");
    return Array.isArray(savedCart)
      ? savedCart.filter(
          (line) =>
            line?.item?.id &&
            Number.isInteger(line.quantity) &&
            line.quantity > 0,
        )
      : [];
  } catch (error) {
    console.warn("Saved menu cart could not be restored.", error);
    return [];
  }
}

function CategoryCard({ category, imageUrl, count, selected, onSelect }) {
  return (
    <button
      className={`category-card${selected ? " is-selected" : ""}`}
      type="button"
      aria-pressed={selected}
      onClick={onSelect}
    >
      <span className="category-card-image">
        {imageUrl ? (
          <img src={imageUrl} alt="" loading="lazy" />
        ) : (
          <span className="category-image-placeholder" aria-hidden="true">
            AF
          </span>
        )}
        <span className="category-card-count">
          {count} {count === 1 ? "dish" : "dishes"}
        </span>
      </span>
      <span className="category-card-copy">
        <strong>{category.name}</strong>
        <span>{category.description || "Fresh from our kitchen"}</span>
      </span>
      <span className="category-card-arrow" aria-hidden="true">
        ↗
      </span>
    </button>
  );
}

function MenuCard({ item, onAdd }) {
  const soldOut = item.is_sold_out || item.stock === 0;
  const stockLabel =
    item.stock !== null && item.stock !== undefined && item.stock !== ""
      ? `${item.stock} ${item.stock_unit || "left"}`
      : "";

  return (
    <article className="menu-card">
      <div className="menu-card-image-wrap">
        {item.image_url ? (
          <img
            className="menu-image"
            src={item.image_url}
            alt={item.name}
            loading="lazy"
          />
        ) : (
          <div className="menu-image image-placeholder" aria-label="No dish photo available">
            <span>Acie Fraiche</span>
          </div>
        )}
        {soldOut && <span className="sold-out-label">Sold out</span>}
      </div>
      <div className="menu-card-content">
        <div className="menu-card-heading">
          <h3>{item.name}</h3>
          <strong>{formatPrice(item.price)}</strong>
        </div>
        {item.description && <p className="menu-description">{item.description}</p>}
        <div className="menu-card-footer">
          <span className="stock-label">{stockLabel}</span>
          <button
            className="add-button"
            type="button"
            disabled={soldOut}
            onClick={() => onAdd(item)}
            aria-label={`Add ${item.name} to cart`}
          >
            <span aria-hidden="true">+</span> Add to cart
          </button>
        </div>
      </div>
    </article>
  );
}

function CartLine({ line, onChangeQuantity, onRemove }) {
  return (
    <li className="cart-line">
      {line.item.image_url ? (
        <img src={line.item.image_url} alt="" loading="lazy" />
      ) : (
        <span className="cart-line-placeholder" aria-hidden="true">AF</span>
      )}
      <div className="cart-line-info">
        <strong>{line.item.name}</strong>
        <span>{formatPrice(line.item.price)}</span>
        <button
          className="remove-line"
          type="button"
          onClick={() => onRemove(line.item.id)}
        >
          Remove
        </button>
      </div>
      <div className="quantity-control" aria-label={`Quantity of ${line.item.name}`}>
        <button
          type="button"
          aria-label={`Remove one ${line.item.name}`}
          onClick={() => onChangeQuantity(line.item.id, -1)}
        >
          −
        </button>
        <span aria-live="polite">{line.quantity}</span>
        <button
          type="button"
          aria-label={`Add one ${line.item.name}`}
          onClick={() => onChangeQuantity(line.item.id, 1)}
        >
          +
        </button>
      </div>
    </li>
  );
}

export default function App() {
  const [categories, setCategories] = useState([]);
  const [menuItems, setMenuItems] = useState([]);
  const [activeCategory, setActiveCategory] = useState("all");
  const [searchQuery, setSearchQuery] = useState("");
  const [customerCareMessage, setCustomerCareMessage] = useState(CUSTOMER_CARE_WELCOME);
  const [customerCareOpen, setCustomerCareOpen] = useState(false);
  const [dishCarouselIndex, setDishCarouselIndex] = useState(0);
  const [cartAnnouncement, setCartAnnouncement] = useState("");
  const customerCareLauncherRef = useRef(null);
  const customerCarePanelRef = useRef(null);
  const dishCarouselRef = useRef(null);
  const [cart, setCart] = useState(readSavedCart);
  const [cartOpen, setCartOpen] = useState(false);
  const [status, setStatus] = useState("loading");
  const [error, setError] = useState("");
  const [checkoutError, setCheckoutError] = useState("");
  const [checkoutStatus, setCheckoutStatus] = useState("idle");
  const [confirmation, setConfirmation] = useState("");
  const [whatsappRedirectUrl, setWhatsappRedirectUrl] = useState("");
  const [customer, setCustomer] = useState({
    name: "",
    phone: "",
    service: "Pickup",
    note: "",
  });

  const loadMenu = async () => {
    setStatus("loading");
    setError("");

    try {
      const [categoryData, itemData] = await Promise.all([
        fetchCollection("categories?active_only=true"),
        fetchCollection("menu-items?active_only=true"),
      ]);
      setCategories(categoryData);
      setMenuItems(itemData);
      setCart((savedCart) =>
        savedCart
          .map((line) => {
            const currentItem = itemData.find(
              (item) => String(item.id) === String(line.item.id),
            );
            return currentItem ? { ...line, item: currentItem } : null;
          })
          .filter(Boolean),
      );
      setStatus("ready");
    } catch (loadError) {
      setError(loadError.message || "The menu could not be loaded.");
      setStatus("error");
    }
  };

  useEffect(() => {
    loadMenu();
  }, []);

  useEffect(() => {
    try {
      localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(cart));
    } catch (storageError) {
      console.warn("Menu cart could not be saved in this browser.", storageError);
    }
  }, [cart]);

  useEffect(() => {
    if (!cartOpen) return undefined;

    const handleKeyDown = (event) => {
      if (event.key === "Escape") setCartOpen(false);
    };
    document.body.classList.add("cart-is-open");
    window.addEventListener("keydown", handleKeyDown);

    return () => {
      document.body.classList.remove("cart-is-open");
      window.removeEventListener("keydown", handleKeyDown);
    };
  }, [cartOpen]);

  useEffect(() => {
    if (!whatsappRedirectUrl) return undefined;

    const timeout = window.setTimeout(() => {
      try {
        localStorage.removeItem(CART_STORAGE_KEY);
      } catch (storageError) {
        console.warn("Completed menu cart could not be cleared from this browser.", storageError);
      }
      setCart([]);
      window.location.assign(whatsappRedirectUrl);
    }, 1350);

    return () => window.clearTimeout(timeout);
  }, [whatsappRedirectUrl]);

  useEffect(() => {
    setDishCarouselIndex(0);
    if (dishCarouselRef.current) dishCarouselRef.current.scrollLeft = 0;
  }, [activeCategory, searchQuery]);

  useEffect(() => {
    if (cartOpen) setCustomerCareOpen(false);
  }, [cartOpen]);

  useEffect(() => {
    if (!customerCareOpen) return undefined;

    customerCarePanelRef.current?.focus();
    const handleKeyDown = (event) => {
      if (event.key !== "Escape") return;
      event.preventDefault();
      setCustomerCareOpen(false);
      window.requestAnimationFrame(() => customerCareLauncherRef.current?.focus());
    };
    document.addEventListener("keydown", handleKeyDown);

    return () => document.removeEventListener("keydown", handleKeyDown);
  }, [customerCareOpen]);

  const itemCountByCategory = useMemo(
    () =>
      menuItems.reduce((counts, item) => {
        const categoryId = String(item.category_id ?? "");
        counts[categoryId] = (counts[categoryId] || 0) + 1;
        return counts;
      }, {}),
    [menuItems],
  );

  const visibleItems = useMemo(() => {
    const query = searchQuery.trim().toLocaleLowerCase();
    return menuItems.filter((item) => {
      if (
        activeCategory !== "all" &&
        String(item.category_id ?? "") !== activeCategory
      ) {
        return false;
      }

      if (!query) return true;

      const categoryName =
        categories.find(
          (category) => String(category.id) === String(item.category_id ?? ""),
        )?.name || "";
      return [item.name, item.description, categoryName]
        .join(" ")
        .toLocaleLowerCase()
        .includes(query);
    });
  }, [activeCategory, categories, menuItems, searchQuery]);

  const groupedItems = useMemo(() => {
    if (searchQuery.trim()) {
      return [{ id: "search-results", name: "Search results", items: visibleItems }];
    }

    if (activeCategory !== "all") {
      const category = categories.find(
        (entry) => String(entry.id) === activeCategory,
      );
      return [{ id: activeCategory, name: category?.name || "Our menu", items: visibleItems }];
    }

    const groups = categories
      .map((category) => ({
        id: String(category.id),
        name: category.name,
        items: visibleItems.filter(
          (item) => String(item.category_id ?? "") === String(category.id),
        ),
      }))
      .filter((group) => group.items.length > 0);
    const categorizedIds = new Set(categories.map((category) => String(category.id)));
    const uncategorizedItems = visibleItems.filter(
      (item) => !categorizedIds.has(String(item.category_id ?? "")),
    );

    if (uncategorizedItems.length) {
      groups.push({
        id: "other",
        name: "More favourites",
        items: uncategorizedItems,
      });
    }

    return groups;
  }, [activeCategory, categories, searchQuery, visibleItems]);

  const cartItemCount = cart.reduce((total, line) => total + line.quantity, 0);
  const cartTotal = cart.reduce(
    (total, line) => total + Number(line.item.price || 0) * line.quantity,
    0,
  );

  const addToCart = (item) => {
    setConfirmation("");
    const currentLine = cart.find(
      (line) => String(line.item.id) === String(item.id),
    );
    const stockLimit =
      item.stock === null || item.stock === undefined
        ? Number.POSITIVE_INFINITY
        : Number(item.stock);
    if ((currentLine?.quantity || 0) >= stockLimit) return;

    const nextItemCount = cartItemCount + 1;
    setCartAnnouncement(
      `${item.name} added to your cart. ${nextItemCount} ${
        nextItemCount === 1 ? "item" : "items"
      } in your cart.`,
    );
    setCart((currentCart) => {
      const currentLine = currentCart.find(
        (line) => String(line.item.id) === String(item.id),
      );
      const currentQuantity = currentLine?.quantity || 0;
      if (currentQuantity >= stockLimit) return currentCart;

      return currentLine
        ? currentCart.map((line) =>
            String(line.item.id) === String(item.id)
              ? { ...line, item, quantity: line.quantity + 1 }
              : line,
          )
        : [...currentCart, { item, quantity: 1 }];
    });
    setCustomerCareMessage(
      `Added ${item.name}. Your bag now has ${cartItemCount + 1} ${
        cartItemCount === 0 ? "item" : "items"
      }.`,
    );
  };

  const changeQuantity = (itemId, difference) => {
    const line = cart.find((entry) => String(entry.item.id) === String(itemId));
    if (!line) return;
    const stockLimit =
      line.item.stock === null || line.item.stock === undefined
        ? Number.POSITIVE_INFINITY
        : Number(line.item.stock);
    const nextQuantity = Math.min(line.quantity + difference, stockLimit);
    if (nextQuantity < 1 || nextQuantity === line.quantity) return;

    const nextItemCount = cartItemCount + nextQuantity - line.quantity;
    setCustomerCareMessage(
      `Your bag now has ${nextItemCount} ${
        nextItemCount === 1 ? "item" : "items"
      }.`,
    );
    setCart((currentCart) =>
      currentCart
        .map((line) => {
          if (String(line.item.id) !== String(itemId)) return line;
          const currentNextQuantity = line.quantity + difference;
          if (currentNextQuantity < 1) return line;
          const currentStockLimit =
            line.item.stock === null || line.item.stock === undefined
              ? Number.POSITIVE_INFINITY
              : Number(line.item.stock);
          return {
            ...line,
            quantity: Math.min(currentNextQuantity, currentStockLimit),
          };
        }),
    );
  };

  const removeFromCart = (itemId) => {
    const removedLine = cart.find(
      (line) => String(line.item.id) === String(itemId),
    );
    if (!removedLine) return;
    const nextItemCount = cartItemCount - removedLine.quantity;
    setCustomerCareMessage(
      nextItemCount
        ? `Removed ${removedLine.item.name}. Your bag now has ${nextItemCount} ${
            nextItemCount === 1 ? "item" : "items"
          }.`
        : "Your bag is empty. I can help you find something tasty.",
    );
    setCart((currentCart) =>
      currentCart.filter((line) => String(line.item.id) !== String(itemId)),
    );
  };

  const selectCategory = (categoryId) => {
    setSearchQuery("");
    setActiveCategory(categoryId);
    const categoryName =
      categories.find((category) => String(category.id) === categoryId)?.name;
    setCustomerCareMessage(
      categoryName
        ? `Great choice. Have a look at ${categoryName}.`
        : "Have a look through all our favourites.",
    );
    window.setTimeout(() => {
      document
        .getElementById("menu-results")
        ?.scrollIntoView({ behavior: "smooth", block: "start" });
    }, 60);
  };

  const browseCategories = () => {
    setSearchQuery("");
    setActiveCategory("all");
    setCustomerCareMessage("Take your time. I’m here if you need a hand.");
    window.setTimeout(() => {
      document
        .getElementById("categories")
        ?.scrollIntoView({ behavior: "smooth", block: "start" });
    }, 60);
  };

  const moveDishCarousel = (direction) => {
    const carousel = dishCarouselRef.current;
    if (!carousel) return;
    const cards = carousel.querySelectorAll(".menu-card-reveal");
    const targetIndex = Math.max(
      0,
      Math.min(dishCarouselIndex + direction, cards.length - 1),
    );
    const targetLeft = cards[targetIndex].offsetLeft - cards[0].offsetLeft;
    carousel.scrollTo({ left: targetLeft, behavior: "smooth" });
  };

  const updateDishCarouselIndex = (event) => {
    const carousel = event.currentTarget;
    const cards = carousel.querySelectorAll(".menu-card-reveal");
    if (cards.length < 2) {
      setDishCarouselIndex(0);
      return;
    }

    const cardStep = cards[1].offsetLeft - cards[0].offsetLeft;
    if (cardStep > 0) setDishCarouselIndex(Math.round(carousel.scrollLeft / cardStep));
  };

  const updateSearchQuery = (query) => {
    setSearchQuery(query);
    if (query.trim()) setActiveCategory("all");
  };

  const openCart = () => {
    setCustomerCareMessage(
      cartItemCount
        ? `Your bag has ${cartItemCount} ${
            cartItemCount === 1 ? "item" : "items"
          }. Adjust quantities, add your details, then continue with WhatsApp.`
        : "Your bag is empty. I can help you find something tasty.",
    );
    setCustomerCareOpen(false);
    setCartOpen(true);
  };

  const submitOrder = async (event) => {
    event.preventDefault();
    if (!cart.length || checkoutStatus === "submitting" || checkoutStatus === "redirecting") return;

    setCustomerCareMessage("Preparing your order. WhatsApp will open next.");
    setCheckoutStatus("submitting");
    setCheckoutError("");
    try {
      const orderCart = cart;
      const orderTotal = cartTotal;
      let order = null;
      try {
        order = await fetchJson("/api/orders", {
          method: "POST",
          body: JSON.stringify({
            channel: "web",
            customer_name: customer.name.trim(),
            customer_phone: customer.phone.trim(),
            service: customer.service,
            note: customer.note.trim(),
            send_to_kitchen: false,
            items: orderCart.map((line) => ({
              menu_item_id: line.item.id,
              quantity: line.quantity,
            })),
          }),
        });
      } catch (orderError) {
        console.warn("Order could not be saved before WhatsApp checkout.", orderError);
      }

      const redirectUrl = buildWhatsAppUrl({
        order,
        cart: orderCart,
        total: orderTotal,
        customer,
      });
      setCartOpen(false);
      setCheckoutStatus("redirecting");
      setCustomerCareMessage("Opening WhatsApp to send your order.");
      setWhatsappRedirectUrl(redirectUrl);
    } catch (checkoutFailure) {
      console.error("WhatsApp checkout could not be prepared.", checkoutFailure);
      setCheckoutError("We couldn't prepare your WhatsApp order. Please try again.");
      setCustomerCareMessage("I couldn't prepare WhatsApp. Please try again.");
      setCheckoutStatus("error");
    }
  };

  return (
    <div className="app-shell">
      <header className="site-header">
        <a className="brand" href="#" aria-label="Acie Fraiche home">
          <img
            className="brand-logo"
            src="/brand/logo2.png"
            alt="Acie Fraiche Café"
            width="72"
            height="72"
          />
          <span className="brand-caption">Freshly crafted. Simply delicious.</span>
        </a>
        <div className="header-actions">
          <span className={`connection-status status-${status}`}>
            <span className="status-dot" />
            {status === "ready"
              ? "Fresh from our kitchen"
              : status === "error"
                ? "Menu connection issue"
                : "Loading today's menu"}
          </span>
          <button
            className="header-cart-button"
            type="button"
            onClick={openCart}
            aria-label={`Open cart, ${cartItemCount} items`}
          >
            <span aria-hidden="true">Bag</span>
            <span className="header-cart-count">{cartItemCount}</span>
          </button>
        </div>
      </header>

      <main>
        <section className="hero" aria-labelledby="page-title">
          <div className="hero-content">
            <p className="eyebrow"><span /> Good food, good mood</p>
            <h1 id="page-title">A little taste of <em>happiness.</em></h1>
            <p className="intro-copy">
              Discover your favourites, freshly made with love at Acie Fraiche.
            </p>
            <div className="menu-guide">
              <div className="menu-guide-content">
                <p className="menu-guide-message">
                  Search for a favourite or browse by category.
                </p>
                <form
                  className="menu-search-form"
                  role="search"
                  onSubmit={(event) => event.preventDefault()}
                >
                  <label className="visually-hidden" htmlFor="menu-search">
                    Search dishes and categories
                  </label>
                  <svg
                    className="menu-search-icon"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                  >
                    <circle cx="10.8" cy="10.8" r="6.8" />
                    <path d="m16 16 4.5 4.5" />
                  </svg>
                  <input
                    id="menu-search"
                    type="search"
                    value={searchQuery}
                    onChange={(event) => updateSearchQuery(event.target.value)}
                    placeholder="Search the menu"
                    autoComplete="off"
                  />
                  {searchQuery && (
                    <button
                      className="menu-search-clear"
                      type="button"
                      onClick={() => updateSearchQuery("")}
                      aria-label="Clear menu search"
                    >
                      ×
                    </button>
                  )}
                </form>
                <button className="browse-categories-link" type="button" onClick={browseCategories}>
                  Browse all categories <span aria-hidden="true">↓</span>
                </button>
              </div>
            </div>
          </div>
          <figure className="hero-chef">
            <img
              src="/images/chef.jpeg"
              alt="Acie Fraiche chef ready to prepare your meal"
              width="826"
              height="1280"
              fetchPriority="high"
            />
            <figcaption>
              <span aria-hidden="true">✦</span>
              Freshly made, with care
            </figcaption>
          </figure>
        </section>

        {confirmation && (
          <div className="confirmation-banner" role="status">
            <span aria-hidden="true">✓</span> {confirmation}
            <button type="button" onClick={() => setConfirmation("")} aria-label="Dismiss confirmation">×</button>
          </div>
        )}

        {status === "error" ? (
          <section className="message-panel" role="alert">
            <p>{error}</p>
            <button type="button" className="retry-button" onClick={loadMenu}>
              Try again
            </button>
          </section>
        ) : (
          <>
            {!searchQuery.trim() && activeCategory === "all" && (
              <section className="categories-section" id="categories" aria-labelledby="categories-title">
              <div className="section-heading">
                <div>
                  <p className="eyebrow">Find your favourite</p>
                  <h2 id="categories-title">What are you craving?</h2>
                </div>
              </div>

              {status === "loading" ? (
                <div className="empty-state">Setting the table…</div>
              ) : (
                <div className="category-stage">
                  <div className="category-side-art category-side-art-left" aria-hidden="true">
                    <span>✦</span>
                  </div>
                  <div className="category-side-art category-side-art-right" aria-hidden="true">
                    <span>✳</span>
                  </div>
                  <div className="category-grid">
                    {categories.map((category, index) => (
                      <div
                        className="category-card-reveal"
                        style={{ "--reveal-index": index }}
                        key={category.id}
                      >
                        <CategoryCard
                          category={category}
                          imageUrl={
                            category.image_url ||
                            menuItems.find(
                              (item) =>
                                String(item.category_id) === String(category.id) &&
                                item.image_url,
                            )?.image_url
                          }
                          count={itemCountByCategory[String(category.id)] || 0}
                          selected={activeCategory === String(category.id)}
                          onSelect={() => selectCategory(String(category.id))}
                        />
                      </div>
                    ))}
                  </div>
                </div>
              )}
              </section>
            )}

            {activeCategory === "all" && !searchQuery.trim() ? (
              <section className="menu-invitation" aria-labelledby="menu-invitation-title">
                <span className="menu-invitation-sparkle" aria-hidden="true">✦</span>
                <p className="eyebrow">A little something for every craving</p>
                <h2 id="menu-invitation-title">Pick a category to explore</h2>
                <p>Choose what sounds good and we’ll bring the menu right to you.</p>
              </section>
            ) : (
            <section
              className={`menu-section${activeCategory !== "all" ? " is-category-open" : " is-search-open"}`}
              id="menu-results"
              aria-live="polite"
            >
              <div className="section-heading menu-section-heading">
                <div>
                  {activeCategory !== "all" ? (
                    <>
                      <p className="eyebrow">Made fresh for your craving</p>
                      <h2>
                        {categories.find((category) => String(category.id) === activeCategory)?.name || "Our menu"}
                      </h2>
                    </>
                  ) : (
                    <>
                      <p className="eyebrow">Matches for “{searchQuery.trim()}”</p>
                      <h2>Let’s find your favourite</h2>
                    </>
                  )}
                </div>
                <span className="item-count">
                  {visibleItems.length} {visibleItems.length === 1 ? "dish" : "dishes"}
                </span>
              </div>

              {activeCategory !== "all" && (
                <div className="category-view-toolbar">
                  <button
                    className="category-back-button"
                    type="button"
                    onClick={browseCategories}
                  >
                    <span aria-hidden="true">←</span> All categories
                  </button>
                  <span className="category-view-prompt">Swipe, browse, and pick a favourite</span>
                </div>
              )}

              {status === "loading" ? (
                <div className="empty-state">Loading fresh favourites…</div>
              ) : groupedItems.length ? (
                <div className={`menu-groups${activeCategory !== "all" ? " is-focused-category" : ""}`} key={`${activeCategory}-${searchQuery}`}>
                  {groupedItems.map((group, groupIndex) => (
                    <section
                      className={`menu-group${activeCategory !== "all" ? " is-focused-category-group" : ""}`}
                      id={`category-${group.id}`}
                      key={group.id}
                      style={{ "--reveal-index": groupIndex }}
                    >
                      {activeCategory === "all" && searchQuery.trim() && (
                        <div className="menu-group-heading">
                          <h3>Search results</h3>
                          <button type="button" onClick={browseCategories}>
                            Browse categories <span aria-hidden="true">↗</span>
                          </button>
                        </div>
                      )}
                      {activeCategory === "all" && !searchQuery.trim() && group.id !== "other" && (
                        <div className="menu-group-heading">
                          <h3>{group.name}</h3>
                          <button
                            type="button"
                            onClick={() => selectCategory(group.id)}
                            aria-label={`View all ${group.name}`}
                          >
                            View category <span aria-hidden="true">↗</span>
                          </button>
                        </div>
                      )}
                      <div
                        className={`menu-grid${activeCategory !== "all" ? " menu-carousel" : ""}`}
                        ref={activeCategory !== "all" ? dishCarouselRef : undefined}
                        onScroll={activeCategory !== "all" ? updateDishCarouselIndex : undefined}
                        role={activeCategory !== "all" ? "region" : undefined}
                        aria-label={activeCategory !== "all" ? `${group.name} dishes` : undefined}
                      >
                        {group.items.map((item, index) => (
                          <div
                            className="menu-card-reveal"
                            style={{ "--reveal-index": index }}
                            key={item.id}
                          >
                            <MenuCard item={item} onAdd={addToCart} />
                          </div>
                        ))}
                      </div>
                      {activeCategory !== "all" && group.items.length > 1 && (
                        <div className="dish-carousel-controls">
                          <span className="dish-carousel-count" aria-live="polite">
                            {String(Math.min(dishCarouselIndex + 1, group.items.length)).padStart(2, "0")}
                            <span aria-hidden="true"> / </span>
                            {String(group.items.length).padStart(2, "0")}
                          </span>
                          <div className="dish-carousel-progress" aria-hidden="true">
                            <span
                              style={{
                                transform: `scaleX(${(dishCarouselIndex + 1) / group.items.length})`,
                              }}
                            />
                          </div>
                          <div className="dish-carousel-buttons">
                            <button
                              type="button"
                              aria-label="Previous dishes"
                              disabled={dishCarouselIndex === 0}
                              onClick={() => moveDishCarousel(-1)}
                            >
                              ←
                            </button>
                            <button
                              type="button"
                              aria-label="Next dishes"
                              disabled={dishCarouselIndex >= group.items.length - 1}
                              onClick={() => moveDishCarousel(1)}
                            >
                              →
                            </button>
                          </div>
                        </div>
                      )}
                    </section>
                  ))}
                </div>
              ) : (
                <div className="empty-state">
                  {searchQuery.trim()
                    ? `We couldn't find a dish matching "${searchQuery.trim()}". Try another search.`
                    : "No dishes are available in this category right now."}
                </div>
              )}
            </section>
            )}
          </>
        )}
      </main>

      <footer className="site-footer">
        <img src="/brand/logo2.png" alt="" width="44" height="44" loading="lazy" />
        <span>Made fresh with love at Acie Fraiche Café.</span>
      </footer>

      {customerCareOpen && (
        <section
          id="customer-care-panel"
          ref={customerCarePanelRef}
          className={`customer-care-panel${cartItemCount > 0 && !cartOpen ? " has-floating-cart" : ""}`}
          role="dialog"
          aria-modal="false"
          aria-labelledby="customer-care-title"
          tabIndex="-1"
        >
          <header className="customer-care-header">
            <img src="/images/chef.jpeg" alt="" width="44" height="44" />
            <div>
              <strong id="customer-care-title">AFC Customer Care</strong>
              <span>Here to help</span>
            </div>
          </header>
          <p className="customer-care-message" aria-live="polite">
            {customerCareMessage}
          </p>
        </section>
      )}

      <div
        className={`customer-care-widget${cartItemCount > 0 && !cartOpen ? " has-floating-cart" : ""}${cartOpen ? " is-cart-open" : ""}`}
      >
        <button
          ref={customerCareLauncherRef}
          className="customer-care-launcher"
          type="button"
          aria-label={customerCareOpen ? "Close AFC customer care" : "Open AFC customer care"}
          aria-expanded={customerCareOpen}
          aria-controls={customerCareOpen ? "customer-care-panel" : undefined}
          onClick={() => setCustomerCareOpen((isOpen) => !isOpen)}
        >
          <img src="/images/chef.jpeg" alt="" width="48" height="48" />
          <span className="customer-care-launcher-label">Need a hand?</span>
        </button>
      </div>

      <span className="visually-hidden" aria-live="polite" aria-atomic="true">
        {cartAnnouncement}
      </span>

      {cartItemCount > 0 && !cartOpen && checkoutStatus !== "redirecting" && (
        <button
          className="floating-cart"
          type="button"
          onClick={openCart}
          aria-label={`View ${cartItemCount} items in your cart`}
        >
          <span className="floating-cart-icon" aria-hidden="true">Bag</span>
          <span className="floating-cart-label">
            <strong>{cartItemCount} {cartItemCount === 1 ? "item" : "items"} in your bag</strong>
            <small>View your order</small>
          </span>
          <strong className="floating-cart-total">{formatPrice(cartTotal)}</strong>
          <span className="floating-cart-arrow" aria-hidden="true">→</span>
        </button>
      )}

      <div
        className={`cart-overlay${cartOpen ? " is-open" : ""}`}
        aria-hidden={!cartOpen}
        onMouseDown={(event) => {
          if (event.target === event.currentTarget) setCartOpen(false);
        }}
      >
        <aside
          id="shopping-cart"
          className="cart-drawer"
          role="dialog"
          aria-modal="true"
          aria-labelledby="cart-title"
          inert={!cartOpen}
        >
          <div className="cart-drawer-header">
            <div>
              <p className="eyebrow">Your delicious choices</p>
              <h2 id="cart-title">Your bag <span>{cartItemCount}</span></h2>
            </div>
            <button
              className="cart-close"
              type="button"
              onClick={() => setCartOpen(false)}
              aria-label="Close cart"
            >
              ×
            </button>
          </div>

          {cart.length ? (
            <>
              <ul className="cart-lines">
                {cart.map((line) => (
                  <CartLine
                    key={line.item.id}
                    line={line}
                    onChangeQuantity={changeQuantity}
                    onRemove={removeFromCart}
                  />
                ))}
              </ul>

              <button
                className="cart-help-button"
                type="button"
                onClick={() => {
                  setCartOpen(false);
                  setCustomerCareMessage(
                    "Review your bag, enter your name and phone, then send your order with WhatsApp.",
                  );
                  setCustomerCareOpen(true);
                }}
              >
                Need help with checkout?
              </button>

              <form className="checkout-form" onSubmit={submitOrder}>
                <p className="checkout-label">Where should we send your order?</p>
                <label>
                  Your name
                  <input
                    name="customer_name"
                    type="text"
                    autoComplete="name"
                    required
                    maxLength={255}
                    value={customer.name}
                    onChange={(event) => setCustomer({ ...customer, name: event.target.value })}
                  />
                </label>
                <label>
                  Phone number
                  <input
                    name="customer_phone"
                    type="tel"
                    autoComplete="tel"
                    required
                    maxLength={32}
                    value={customer.phone}
                    onChange={(event) => setCustomer({ ...customer, phone: event.target.value })}
                  />
                </label>
                <label>
                  How would you like it?
                  <select
                    name="service"
                    value={customer.service}
                    onChange={(event) => setCustomer({ ...customer, service: event.target.value })}
                  >
                    <option>Pickup</option>
                    <option>Takeout</option>
                    <option>Dine-in</option>
                  </select>
                </label>
                <label>
                  Note for the kitchen <span className="optional-label">Optional</span>
                  <textarea
                    name="note"
                    rows="2"
                    maxLength={500}
                    value={customer.note}
                    onChange={(event) => setCustomer({ ...customer, note: event.target.value })}
                  />
                </label>
                {checkoutError && (
                  <p className="checkout-error" role="alert">{checkoutError}</p>
                )}
                <div className="cart-total-row">
                  <span>Estimated total</span>
                  <strong>{formatPrice(cartTotal)}</strong>
                </div>
                <button
                  className="checkout-button"
                  type="submit"
                  disabled={checkoutStatus === "submitting" || checkoutStatus === "redirecting"}
                  aria-live="polite"
                >
                  {checkoutStatus === "submitting"
                    ? "Preparing your order…"
                    : checkoutStatus === "redirecting"
                      ? "Opening WhatsApp…"
                      : "Checkout with WhatsApp"}
                  {checkoutStatus === "idle" && <span aria-hidden="true">→</span>}
                </button>
                <p className="checkout-footnote">
                  Your final total is confirmed by our kitchen when your order is received.
                </p>
              </form>
            </>
          ) : (
            <div className="cart-empty">
              <span className="empty-bag" aria-hidden="true">♡</span>
              <h3>Your bag is waiting for something delicious.</h3>
              <p>Pick a favourite from the menu and it will appear here.</p>
              <button
                className="cart-help-button"
                type="button"
                onClick={() => {
                  setCartOpen(false);
                  setCustomerCareMessage("Your bag is empty. I can help you find something tasty.");
                  setCustomerCareOpen(true);
                }}
              >
                Need a hand? See how to order
              </button>
              <button type="button" onClick={() => setCartOpen(false)}>
                Back to the menu
              </button>
            </div>
          )}
        </aside>
      </div>
    </div>
  );
}
