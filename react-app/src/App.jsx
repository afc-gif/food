import { useEffect, useMemo, useState } from "react";

const CART_STORAGE_KEY = "afc-react-menu-cart";

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
  const [cart, setCart] = useState(readSavedCart);
  const [cartOpen, setCartOpen] = useState(false);
  const [status, setStatus] = useState("loading");
  const [error, setError] = useState("");
  const [checkoutError, setCheckoutError] = useState("");
  const [checkoutStatus, setCheckoutStatus] = useState("idle");
  const [confirmation, setConfirmation] = useState("");
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
    if (activeCategory === "all") return menuItems;
    return menuItems.filter(
      (item) => String(item.category_id ?? "") === activeCategory,
    );
  }, [activeCategory, menuItems]);

  const groupedItems = useMemo(() => {
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
        items: menuItems.filter(
          (item) => String(item.category_id ?? "") === String(category.id),
        ),
      }))
      .filter((group) => group.items.length > 0);
    const categorizedIds = new Set(categories.map((category) => String(category.id)));
    const uncategorizedItems = menuItems.filter(
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
  }, [activeCategory, categories, menuItems, visibleItems]);

  const cartItemCount = cart.reduce((total, line) => total + line.quantity, 0);
  const cartTotal = cart.reduce(
    (total, line) => total + Number(line.item.price || 0) * line.quantity,
    0,
  );

  const addToCart = (item) => {
    setConfirmation("");
    setCart((currentCart) => {
      const currentLine = currentCart.find(
        (line) => String(line.item.id) === String(item.id),
      );
      const currentQuantity = currentLine?.quantity || 0;
      const stockLimit =
        item.stock === null || item.stock === undefined
          ? Number.POSITIVE_INFINITY
          : Number(item.stock);
      if (currentQuantity >= stockLimit) return currentCart;

      return currentLine
        ? currentCart.map((line) =>
            String(line.item.id) === String(item.id)
              ? { ...line, item, quantity: line.quantity + 1 }
              : line,
          )
        : [...currentCart, { item, quantity: 1 }];
    });
  };

  const changeQuantity = (itemId, difference) => {
    setCart((currentCart) =>
      currentCart
        .map((line) => {
          if (String(line.item.id) !== String(itemId)) return line;
          const nextQuantity = line.quantity + difference;
          if (nextQuantity < 1) return line;
          const stockLimit =
            line.item.stock === null || line.item.stock === undefined
              ? Number.POSITIVE_INFINITY
              : Number(line.item.stock);
          return {
            ...line,
            quantity: Math.min(nextQuantity, stockLimit),
          };
        }),
    );
  };

  const removeFromCart = (itemId) => {
    setCart((currentCart) =>
      currentCart.filter((line) => String(line.item.id) !== String(itemId)),
    );
  };

  const selectCategory = (categoryId) => {
    setActiveCategory(categoryId);
    window.setTimeout(() => {
      document
        .getElementById("menu-results")
        ?.scrollIntoView({ behavior: "smooth", block: "start" });
    }, 60);
  };

  const submitOrder = async (event) => {
    event.preventDefault();
    if (!cart.length || checkoutStatus === "submitting") return;

    setCheckoutStatus("submitting");
    setCheckoutError("");
    try {
      const order = await fetchJson("/api/orders", {
        method: "POST",
        body: JSON.stringify({
          channel: "web",
          customer_name: customer.name.trim(),
          customer_phone: customer.phone.trim(),
          service: customer.service,
          note: customer.note.trim(),
          items: cart.map((line) => ({
            menu_item_id: line.item.id,
            quantity: line.quantity,
          })),
        }),
      });

      setCart([]);
      setCartOpen(false);
      setCheckoutStatus("idle");
      setConfirmation(
        `Order received${order?.id ? ` · Order #${order.id}` : ""}. Thank you!`,
      );
      window.scrollTo({ top: 0, behavior: "smooth" });
    } catch (orderError) {
      setCheckoutError(orderError.message || "We couldn't place your order. Please try again.");
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
            onClick={() => setCartOpen(true)}
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
            <button
              className="hero-cta"
              type="button"
              onClick={() => document.getElementById("categories")?.scrollIntoView({ behavior: "smooth" })}
            >
              Explore the menu <span aria-hidden="true">↓</span>
            </button>
          </div>
          <div className="hero-decoration" aria-hidden="true">
            <span className="hero-orbit hero-orbit-one" />
            <span className="hero-orbit hero-orbit-two" />
            <span className="hero-stamp">Made with love<br />in our kitchen</span>
            <span className="hero-sparkle">✳</span>
            <span className="hero-sparkle hero-sparkle-small">✦</span>
          </div>
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
            <section className="categories-section" id="categories" aria-labelledby="categories-title">
              <div className="section-heading">
                <div>
                  <p className="eyebrow">Find your favourite</p>
                  <h2 id="categories-title">What are you craving?</h2>
                </div>
                {activeCategory !== "all" && (
                  <button
                    className="text-button"
                    type="button"
                    onClick={() => selectCategory("all")}
                  >
                    View all categories <span aria-hidden="true">↗</span>
                  </button>
                )}
              </div>

              {status === "loading" ? (
                <div className="empty-state">Setting the table…</div>
              ) : (
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
              )}
            </section>

            <section className="menu-section" id="menu-results" aria-live="polite">
              <div className="section-heading menu-section-heading">
                <div>
                  <p className="eyebrow">{activeCategory === "all" ? "Made fresh for you" : "A delicious choice"}</p>
                  <h2>
                    {activeCategory === "all"
                      ? "Explore our menu"
                      : categories.find((category) => String(category.id) === activeCategory)?.name || "Our menu"}
                  </h2>
                </div>
                <span className="item-count">
                  {visibleItems.length} {visibleItems.length === 1 ? "dish" : "dishes"}
                </span>
              </div>

              {status === "loading" ? (
                <div className="empty-state">Loading fresh favourites…</div>
              ) : groupedItems.length ? (
                <div className="menu-groups" key={activeCategory}>
                  {groupedItems.map((group, groupIndex) => (
                    <section
                      className="menu-group"
                      id={`category-${group.id}`}
                      key={group.id}
                      style={{ "--reveal-index": groupIndex }}
                    >
                      {activeCategory === "all" && group.id !== "other" && (
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
                      <div className="menu-grid">
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
                    </section>
                  ))}
                </div>
              ) : (
                <div className="empty-state">
                  No dishes are available in this category right now.
                </div>
              )}
            </section>
          </>
        )}
      </main>

      <footer className="site-footer">
        <img src="/brand/logo2.png" alt="" width="44" height="44" loading="lazy" />
        <span>Made fresh with love at Acie Fraiche Café.</span>
      </footer>

      {cartItemCount > 0 && !cartOpen && (
        <button
          className="floating-cart"
          type="button"
          onClick={() => setCartOpen(true)}
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
                  disabled={checkoutStatus === "submitting"}
                >
                  {checkoutStatus === "submitting" ? "Sending your order…" : "Place my order"}
                  {checkoutStatus !== "submitting" && <span aria-hidden="true">→</span>}
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
