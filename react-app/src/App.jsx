import { useEffect, useMemo, useRef, useState } from "react";

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

function FloatingMascot({ mood }) {
  return (
    <svg
      className={`floating-mascot-figure mascot-${mood}`}
      viewBox="0 0 112 140"
      aria-hidden="true"
    >
      <ellipse cx="57" cy="132" rx="33" ry="5" fill="rgba(57,43,19,.12)" />
      <g className="mascot-walking-legs">
        <path className="mascot-left-leg" d="m43 120-3 10" stroke="#5d402c" strokeLinecap="round" strokeWidth="7" />
        <path className="mascot-right-leg" d="m65 120 4 10" stroke="#5d402c" strokeLinecap="round" strokeWidth="7" />
        <path d="M34 132h9m22 0h9" stroke="#49352c" strokeLinecap="round" strokeWidth="5" />
      </g>
      <path d="M34 99c-13 0-19-4-25-15" fill="none" stroke="#e9b181" strokeLinecap="round" strokeWidth="10" />
      <path d="m10 81-7 1 4 7" fill="none" stroke="#e9b181" strokeLinecap="round" strokeLinejoin="round" strokeWidth="3" />
      <path d="M75 100c14 2 21 9 23 19" fill="none" stroke="#e9b181" strokeLinecap="round" strokeWidth="10" />
      <path d="M39 91h34c13 7 20 18 21 38H19c1-20 8-31 20-38z" fill="#856128" />
      <path d="m43 91 13 13 13-13 7 38H36z" fill="#fffefa" />
      <path d="M48 76h20v21l-10 9-10-9z" fill="#e9b181" />
      <g className="mascot-head">
        <path d="M33 47c0-19 10-30 27-30s27 11 27 30v15c0 18-11 30-27 30S33 80 33 62z" fill="#e9b181" />
        <path d="M31 49c-6-18 4-36 24-38 19-2 33 10 31 31-9-2-14-7-17-15-9 10-21 14-38 13z" fill="#49352c" />
        <path d="M35 32c5-14 17-21 30-16" fill="none" stroke="#725448" strokeLinecap="round" strokeWidth="5" />
        <path d="M34 44c-3-4-5-8-4-13m60 15c3-5 4-9 3-14" fill="none" stroke="#bc8e3f" strokeLinecap="round" strokeWidth="3" />
        <g className="mascot-eyes">
          <ellipse cx="46.5" cy="56" rx="4.3" ry="5.6" fill="#fffaf0" />
          <ellipse cx="73.5" cy="56" rx="4.3" ry="5.6" fill="#fffaf0" />
          <circle className="mascot-pupil" cx="48" cy="56" r="2.5" fill="#49352c" />
          <circle className="mascot-pupil" cx="75" cy="56" r="2.5" fill="#49352c" />
          <path className="mascot-tired-eyelid" d="M42 54c3 2 6 2 9 0m18 0c3 2 6 2 9 0" />
        </g>
        <path d="M50 65c4 3 12 3 17 0" fill="none" stroke="#d38a74" strokeLinecap="round" strokeWidth="2" />
        <path
          d={mood === "happy" ? "M50 64c4 10 16 10 20 0" : mood === "tired" ? "M53 69c2-2 5-2 8 0" : "M53 67c2 2 5 2 8 0"}
          className="mascot-smile"
        />
        <ellipse cx="38" cy="65" rx="4" ry="2.5" fill="#d98272" opacity=".35" />
        <ellipse cx="82" cy="65" rx="4" ry="2.5" fill="#d98272" opacity=".35" />
      </g>
      <path d="M45 112h29" stroke="#d3a94d" strokeLinecap="round" strokeWidth="2" />
      <text x="60" y="124" fill="#fffaf0" fontFamily="sans-serif" fontSize="8" fontWeight="700" textAnchor="middle">AFC</text>
    </svg>
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
  const [mascotReaction, setMascotReaction] = useState({
    mood: "idle",
    sequence: 0,
  });
  const [mascotHelpOpen, setMascotHelpOpen] = useState(false);
  const mascotHelpCloseRef = useRef(null);
  const mascotHelpDialogRef = useRef(null);
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

  useEffect(() => {
    if (mascotReaction.mood === "idle") {
      const timeout = window.setTimeout(() => {
        setMascotReaction((current) =>
          current.mood === "idle" ? { ...current, mood: "tired" } : current,
        );
      }, 18000);

      return () => window.clearTimeout(timeout);
    }

    if (mascotReaction.mood !== "happy") return undefined;

    const timeout = window.setTimeout(() => {
      setMascotReaction((current) => (current.mood === "happy"
        ? { ...current, mood: "idle" }
        : current));
    }, 2400);

    return () => window.clearTimeout(timeout);
  }, [mascotReaction.mood, mascotReaction.sequence]);

  useEffect(() => {
    const dialog = mascotHelpDialogRef.current;
    if (!dialog) return undefined;

    if (mascotHelpOpen && !dialog.open) dialog.showModal();
    if (!mascotHelpOpen && dialog.open) dialog.close();
    if (!mascotHelpOpen) return undefined;

    mascotHelpCloseRef.current?.focus();
    return undefined;
  }, [mascotHelpOpen]);

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
  }, [activeCategory, categories, visibleItems]);

  const cartItemCount = cart.reduce((total, line) => total + line.quantity, 0);
  const cartTotal = cart.reduce(
    (total, line) => total + Number(line.item.price || 0) * line.quantity,
    0,
  );

  const reactMascot = (message) => {
    setMascotReaction((current) => ({
      mood: "happy",
      message: "",
      lastAction: message,
      sequence: current.sequence + 1,
    }));
  };

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
    reactMascot(`Yum! ${item.name} is in your bag.`);
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
    setSearchQuery("");
    setActiveCategory(categoryId);
    const categoryName =
      categories.find((category) => String(category.id) === categoryId)?.name;
    reactMascot(categoryName ? `Great choice! Let's see ${categoryName}.` : "Great choice!");
    window.setTimeout(() => {
      document
        .getElementById("menu-results")
        ?.scrollIntoView({ behavior: "smooth", block: "start" });
    }, 60);
  };

  const browseCategories = () => {
    setSearchQuery("");
    setActiveCategory("all");
    reactMascot("Take your pick! There's something delicious here.");
    window.setTimeout(() => {
      document
        .getElementById("categories")
        ?.scrollIntoView({ behavior: "smooth", block: "start" });
    }, 60);
  };

  const updateSearchQuery = (query) => {
    setSearchQuery(query);
    if (query.trim()) setActiveCategory("all");
    setMascotReaction((current) => ({
      ...current,
      mood: "idle",
      sequence: current.sequence + 1,
    }));
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
            {!searchQuery.trim() && (
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

            <section className="menu-section" id="menu-results" aria-live="polite">
              <div className="section-heading menu-section-heading">
                <div>
                  <p className="eyebrow">
                    {searchQuery.trim()
                      ? `Matches for "${searchQuery.trim()}"`
                      : activeCategory === "all"
                        ? "Made fresh for you"
                        : "A delicious choice"}
                  </p>
                  <h2>
                    {searchQuery.trim()
                      ? "Search results"
                      : activeCategory === "all"
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
                  {searchQuery.trim()
                    ? `We couldn't find a dish matching "${searchQuery.trim()}". Try another search.`
                    : "No dishes are available in this category right now."}
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

      {!cartOpen && (
        <button
          className={`floating-mascot mascot-${mascotReaction.mood}${mascotHelpOpen ? " mascot-help-open" : ""}`}
          type="button"
          aria-label="AFC helper. Tap to see how to order."
          aria-haspopup="dialog"
          aria-expanded={mascotHelpOpen}
          aria-controls="mascot-help-dialog"
          onClick={() => {
            reactMascot("Happy to help! Here's how to order.");
            setMascotHelpOpen(true);
          }}
        >
          <span className="mascot-speech" aria-hidden="true">
            {mascotReaction.mood === "happy"
              ? mascotReaction.lastAction
              : mascotReaction.mood === "tired"
                ? "Need a hand? Tap me!"
                : "Tap me if you need help"}
          </span>
          <FloatingMascot mood={mascotReaction.mood} />
        </button>
      )}

      <dialog
        id="mascot-help-dialog"
        className="mascot-help-dialog"
        aria-labelledby="mascot-help-title"
        onCancel={(event) => {
          event.preventDefault();
          setMascotHelpOpen(false);
        }}
        onClose={() => setMascotHelpOpen(false)}
        onClick={(event) => {
          if (event.target === event.currentTarget) setMascotHelpOpen(false);
        }}
        ref={mascotHelpDialogRef}
      >
        <section className="mascot-help-panel">
          <button
            ref={mascotHelpCloseRef}
            className="cart-close mascot-help-close"
            type="button"
            aria-label="Close ordering guide"
            onClick={() => setMascotHelpOpen(false)}
          >
            ×
          </button>
          <div className="mascot-help-heading">
            <span className="mascot-help-badge" aria-hidden="true">AFC</span>
            <p className="eyebrow">Your little guide</p>
            <h2 id="mascot-help-title">Ordering is easy!</h2>
            <p>Follow these simple steps and I’ll leave the rest to you.</p>
          </div>
          <ol className="mascot-help-steps">
            <li><span>1</span><div><strong>Find something tasty</strong><small>Search for a dish or pick a menu category.</small></div></li>
            <li><span>2</span><div><strong>Add it to your bag</strong><small>Tap “Add to cart” on anything you fancy.</small></div></li>
            <li><span>3</span><div><strong>Tell us where to send it</strong><small>Open your bag, enter your name and phone, then place your order.</small></div></li>
          </ol>
          <button
            className="mascot-help-start"
            type="button"
            onClick={() => {
              setMascotHelpOpen(false);
              window.setTimeout(() => {
                document.getElementById("menu-search")?.focus({ preventScroll: true });
                document.getElementById("menu-search")?.scrollIntoView({
                  behavior: "smooth",
                  block: "center",
                });
              }, 0);
            }}
          >
            Let’s find something <span aria-hidden="true">→</span>
          </button>
        </section>
      </dialog>

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
