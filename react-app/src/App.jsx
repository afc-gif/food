import { useEffect, useMemo, useState } from "react";

async function fetchCollection(path) {
  const response = await fetch(`/api/${path}`);
  const payload = await response.json().catch(() => null);

  if (!response.ok) {
    throw new Error(payload?.message || `Unable to load ${path}.`);
  }

  if (!Array.isArray(payload)) {
    throw new Error(`The API returned an unexpected response for ${path}.`);
  }

  return payload;
}

function formatPrice(price) {
  return new Intl.NumberFormat("en-NG", {
    style: "currency",
    currency: "NGN",
    maximumFractionDigits: 0,
  }).format(Number(price) || 0);
}

export default function App() {
  const [categories, setCategories] = useState([]);
  const [menuItems, setMenuItems] = useState([]);
  const [activeCategory, setActiveCategory] = useState("all");
  const [status, setStatus] = useState("loading");
  const [error, setError] = useState("");

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
      setStatus("ready");
    } catch (loadError) {
      setError(loadError.message || "The menu could not be loaded.");
      setStatus("error");
    }
  };

  useEffect(() => {
    loadMenu();
  }, []);

  const visibleItems = useMemo(() => {
    if (activeCategory === "all") return menuItems;
    return menuItems.filter(
      (item) => String(item.category_id ?? "") === activeCategory,
    );
  }, [activeCategory, menuItems]);

  return (
    <main className="app-shell">
      <header className="site-header">
        <a className="brand" href="/" aria-label="Acie Fraiche menu preview">
          <span className="brand-mark" aria-hidden="true">AF</span>
          <span>
            <strong>Acie Fraiche</strong>
            <small>React menu preview</small>
          </span>
        </a>
        <span className="connection-status">
          <span className={`status-dot status-dot-${status}`} />
          {status === "ready" ? "Connected to live menu" : status === "error" ? "Menu connection issue" : "Connecting to menu"}
        </span>
      </header>

      <section className="intro" aria-labelledby="page-title">
        <p className="eyebrow">A new experience, built alongside the current site</p>
        <h1 id="page-title">The menu, from your kitchen.</h1>
        <p className="intro-copy">
          This independent React preview reads categories and dishes directly
          from the existing Laravel API.
        </p>
      </section>

      {status === "error" ? (
        <section className="message-panel" role="alert">
          <p>{error}</p>
          <button type="button" className="retry-button" onClick={loadMenu}>
            Try again
          </button>
          <p className="help-text">
            During local development, start Laravel and set BACKEND_URL in
            react-app/.env if your backend is not at http://127.0.0.1:8000.
          </p>
        </section>
      ) : (
        <>
          <nav className="category-list" aria-label="Menu categories">
            <button
              type="button"
              className={activeCategory === "all" ? "category-button is-active" : "category-button"}
              aria-pressed={activeCategory === "all"}
              onClick={() => setActiveCategory("all")}
            >
              All dishes
            </button>
            {categories.map((category) => (
              <button
                type="button"
                key={category.id}
                className={activeCategory === String(category.id) ? "category-button is-active" : "category-button"}
                aria-pressed={activeCategory === String(category.id)}
                onClick={() => setActiveCategory(String(category.id))}
              >
                {category.name}
              </button>
            ))}
          </nav>

          <section className="menu-section" aria-live="polite">
            <div className="section-heading">
              <div>
                <p className="eyebrow">From the current menu</p>
                <h2>{status === "loading" ? "Loading dishes…" : "Made fresh for you"}</h2>
              </div>
              {status === "ready" && (
                <span className="item-count">{visibleItems.length} dishes</span>
              )}
            </div>

            {status === "loading" ? (
              <div className="empty-state">Loading the live menu…</div>
            ) : visibleItems.length ? (
              <div className="menu-grid">
                {visibleItems.map((item) => {
                  const categoryName =
                    item.category?.name ||
                    categories.find((category) => String(category.id) === String(item.category_id))?.name;
                  const soldOut = item.is_sold_out || item.stock === 0;

                  return (
                    <article className="menu-card" key={item.id}>
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
                      <div className="menu-card-content">
                        <div className="menu-card-heading">
                          <h3>{item.name}</h3>
                          <strong>{formatPrice(item.price)}</strong>
                        </div>
                        {categoryName && <span className="menu-category">{categoryName}</span>}
                        {item.description && <p className="menu-description">{item.description}</p>}
                        {soldOut && <span className="sold-out-label">Currently unavailable</span>}
                      </div>
                    </article>
                  );
                })}
              </div>
            ) : (
              <div className="empty-state">
                No dishes are available in this category right now.
              </div>
            )}
          </section>
        </>
      )}

      <footer className="site-footer">
        Preview only · Your current website and database remain unchanged.
      </footer>
    </main>
  );
}
