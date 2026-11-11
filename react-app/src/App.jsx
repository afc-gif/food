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

function getCartLineKey(line) {
  return `${line.item.id}::${line.variation_choice || ""}`;
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
        `${line.quantity} × ${line.item.name}${line.variation_choice ? ` (${line.variation_choice})` : ""} — ${formatPrice(
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
      </span>
      <span className="category-card-copy">
        <strong>{category.name}</strong>
        <span>{category.description || "Fresh from our kitchen"}</span>
        <span className="category-card-count">
          {count} {count === 1 ? "dish" : "dishes"}
        </span>
      </span>
      <span className="category-card-arrow" aria-hidden="true">
        ↗
      </span>
    </button>
  );
}

function MenuCard({ item, onAdd, gallery = false }) {
  const soldOut = item.is_sold_out || item.stock === 0;
  const hasVariationOptions =
    Array.isArray(item.variation_options) && item.variation_options.length > 0;
  const stockLabel =
    item.stock !== null && item.stock !== undefined && item.stock !== ""
      ? `${item.stock} ${item.stock_unit || "left"}`
      : "";

  return (
    <article className={`menu-card${gallery ? " menu-card-gallery" : ""}`}>
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
          <span className="stock-label">
            {hasVariationOptions ? "Choose an option" : stockLabel}
          </span>
          <button
            className="add-button"
            type="button"
            disabled={soldOut}
            onClick={(event) => onAdd(item, event.currentTarget)}
            aria-label={`Add ${item.name} to cart`}
          >
            <span aria-hidden="true">{hasVariationOptions ? "✦" : "+"}</span>
            {hasVariationOptions ? "Choose option" : "Add to cart"}
          </button>
        </div>
      </div>
    </article>
  );
}

function CartLine({ line, onChangeQuantity, onRemove }) {
  const lineKey = getCartLineKey(line);
  return (
    <li className="cart-line">
      {line.item.image_url ? (
        <img src={line.item.image_url} alt="" loading="lazy" />
      ) : (
        <span className="cart-line-placeholder" aria-hidden="true">AF</span>
      )}
      <div className="cart-line-info">
        <strong>{line.item.name}</strong>
        {line.variation_choice && (
          <span className="cart-line-variation">Choice: {line.variation_choice}</span>
        )}
        <span>{formatPrice(line.item.price)}</span>
        <button
          className="remove-line"
          type="button"
          onClick={() => onRemove(lineKey)}
        >
          Remove
        </button>
      </div>
      <div className="quantity-control" aria-label={`Quantity of ${line.item.name}${line.variation_choice ? `, ${line.variation_choice}` : ""}`}>
        <button
          type="button"
          aria-label={`Remove one ${line.item.name}${line.variation_choice ? `, ${line.variation_choice}` : ""}`}
          onClick={() => onChangeQuantity(lineKey, -1)}
        >
          −
        </button>
        <span aria-live="polite">{line.quantity}</span>
        <button
          type="button"
          aria-label={`Add one ${line.item.name}${line.variation_choice ? `, ${line.variation_choice}` : ""}`}
          onClick={() => onChangeQuantity(lineKey, 1)}
        >
          +
        </button>
      </div>
    </li>
  );
}

function VariationPicker({ item, selectedChoice, dialogRef, onSelect, onClose, onConfirm }) {
  const options = item.variation_options || [];

  return (
    <div
      className="variation-picker-overlay"
      onMouseDown={(event) => {
        if (event.target === event.currentTarget) onClose();
      }}
    >
      <section
        ref={dialogRef}
        className="variation-picker-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="variation-picker-title"
        tabIndex="-1"
        onKeyDown={(event) => {
          if (event.key !== "Tab") return;
          const focusable = event.currentTarget.querySelectorAll(
            'button:not(:disabled), [href], input:not(:disabled), select:not(:disabled), textarea:not(:disabled), [tabindex]:not([tabindex="-1"])',
          );
          if (!focusable.length) {
            event.preventDefault();
            return;
          }

          const first = focusable[0];
          const last = focusable[focusable.length - 1];
          if (
            event.shiftKey &&
            (document.activeElement === first ||
              document.activeElement === event.currentTarget)
          ) {
            event.preventDefault();
            last.focus();
          } else if (
            !event.shiftKey &&
            (document.activeElement === last ||
              document.activeElement === event.currentTarget)
          ) {
            event.preventDefault();
            first.focus();
          }
        }}
      >
        <div className="variation-picker-handle" aria-hidden="true" />
        <header className="variation-picker-header">
          <div>
            <p className="eyebrow"><span aria-hidden="true">✦</span> Make it yours</p>
            <h2 id="variation-picker-title">Choose your favourite</h2>
            <p>{item.name} · Select one option</p>
          </div>
          <button
            className="variation-picker-close"
            type="button"
            onClick={onClose}
            aria-label="Close choices"
          >
            ×
          </button>
        </header>

        <div className="variation-picker-options" role="group" aria-label={`Choose one option for ${item.name}`}>
          {options.map((option, index) => {
            const selected = selectedChoice === option;
            return (
              <button
                className={`variation-option${selected ? " is-selected" : ""}`}
                key={`${item.id}-${option}`}
                type="button"
                aria-pressed={selected}
                onClick={() => onSelect(option)}
                style={{ "--option-index": index }}
              >
                <span className="variation-option-icon" aria-hidden="true">
                  {selected ? "✓" : "✦"}
                </span>
                <span>{option}</span>
                <span className="variation-option-check" aria-hidden="true">
                  {selected ? "Selected" : "Choose"}
                </span>
              </button>
            );
          })}
        </div>

        <footer className="variation-picker-footer">
          <div className="variation-picker-price">
            <span>{selectedChoice || "Choose an option to continue"}</span>
            {selectedChoice && <strong>{formatPrice(item.price)}</strong>}
          </div>
          <button
            className="variation-picker-add"
            type="button"
            disabled={!selectedChoice}
            onClick={onConfirm}
          >
            Add to bag <span aria-hidden="true">→</span>
          </button>
        </footer>
      </section>
    </div>
  );
}

export default function App() {
  const [categories, setCategories] = useState([]);
  const [menuItems, setMenuItems] = useState([]);
  const [activeCategory, setActiveCategory] = useState("all");
  const [galleryOpen, setGalleryOpen] = useState(false);
  const [searchQuery, setSearchQuery] = useState("");
  const [customerCareMessage, setCustomerCareMessage] = useState(CUSTOMER_CARE_WELCOME);
  const [customerCareOpen, setCustomerCareOpen] = useState(false);
  const [dishCarouselIndex, setDishCarouselIndex] = useState(0);
  const [cartAnnouncement, setCartAnnouncement] = useState("");
  const customerCareLauncherRef = useRef(null);
  const customerCarePanelRef = useRef(null);
  const dishCarouselRef = useRef(null);
  const dishGalleryDialogRef = useRef(null);
  const cartDialogRef = useRef(null);
  const categoryTriggerRef = useRef(null);
  const cartTriggerRef = useRef(null);
  const variationPickerRef = useRef(null);
  const variationTriggerRef = useRef(null);
  const modalHistoryRef = useRef(false);
  const modalTypeRef = useRef(null);
  const restoreFocusRef = useRef(null);
  const [cart, setCart] = useState(readSavedCart);
  const [cartOpen, setCartOpen] = useState(false);
  const [variationPicker, setVariationPicker] = useState(null);
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
    const handlePopState = () => {
      if (!modalHistoryRef.current) return;
      const closingModal = modalTypeRef.current;
      modalHistoryRef.current = false;
      modalTypeRef.current = null;
      restoreFocusRef.current = closingModal;
      setVariationPicker(null);
      setGalleryOpen(false);
      setCartOpen(false);
      setActiveCategory("all");
    };

    window.addEventListener("popstate", handlePopState);
    return () => window.removeEventListener("popstate", handlePopState);
  }, []);

  useEffect(() => {
    document.body.classList.toggle("cart-is-open", cartOpen);
    document.body.classList.toggle("gallery-is-open", galleryOpen);
    document.body.classList.toggle("variation-picker-is-open", Boolean(variationPicker));
    const hasMenuOverlay = cartOpen || galleryOpen;
    if (hasMenuOverlay) setCustomerCareOpen(false);
    const handleKeyDown = (event) => {
      if (event.key !== "Escape") return;
      event.preventDefault();
      if (variationPicker) {
        event.stopImmediatePropagation();
        closeVariationPicker();
        return;
      }
      if (modalHistoryRef.current) {
        window.history.back();
      } else {
        dismissOverlay();
        setGalleryOpen(false);
        setActiveCategory("all");
      }
    };
    if (hasMenuOverlay) window.addEventListener("keydown", handleKeyDown);

    return () => {
      document.body.classList.remove("cart-is-open", "gallery-is-open");
      document.body.classList.remove("variation-picker-is-open");
      if (hasMenuOverlay) window.removeEventListener("keydown", handleKeyDown);
    };
  }, [cartOpen, galleryOpen, variationPicker]);

  useEffect(() => {
    if (!variationPicker) return undefined;
    variationPickerRef.current?.focus();
    const handleKeyDown = (event) => {
      if (event.key !== "Escape") return;
      event.preventDefault();
      event.stopPropagation();
      closeVariationPicker();
    };
    window.addEventListener("keydown", handleKeyDown);
    return () => window.removeEventListener("keydown", handleKeyDown);
  }, [variationPicker]);

  useEffect(() => {
    if (galleryOpen) {
      dishGalleryDialogRef.current?.focus();
      dishCarouselRef.current?.scrollTo({ left: 0 });
    }
  }, [galleryOpen, activeCategory]);

  useEffect(() => {
    if (cartOpen) cartDialogRef.current?.focus();
  }, [cartOpen]);

  useEffect(() => {
    if (cartOpen || galleryOpen || !restoreFocusRef.current) return;
    const closingModal = restoreFocusRef.current;
    restoreFocusRef.current = null;
    window.setTimeout(() => {
      if (closingModal === "gallery") {
        const categoryTrigger = categoryTriggerRef.current;
        const focusTarget =
          categoryTrigger?.isConnected && !categoryTrigger.closest("[inert]")
            ? categoryTrigger
            : document.querySelector(".category-card");
        focusTarget?.focus();
      }
      if (closingModal === "cart") {
        const cartTrigger = cartTriggerRef.current;
        const focusTarget =
          cartTrigger?.isConnected && !cartTrigger.closest("[inert]")
            ? cartTrigger
            : document.querySelector(".header-cart-button");
        focusTarget?.focus();
      }
    }, 0);
  }, [cartOpen, galleryOpen]);

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

  const galleryItems = useMemo(
    () =>
      menuItems.filter(
        (item) => String(item.category_id ?? "") === activeCategory,
      ),
    [activeCategory, menuItems],
  );
  const galleryCategory = categories.find(
    (category) => String(category.id) === activeCategory,
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

  const addToCart = (item, trigger = null, variationChoice = null) => {
    const variationOptions = Array.isArray(item.variation_options)
      ? item.variation_options
      : [];
    if (variationOptions.length && !variationChoice) {
      variationTriggerRef.current = trigger || document.activeElement;
      setVariationPicker({ item, selectedChoice: "" });
      return;
    }

    setConfirmation("");
    const candidateLine = { item, variation_choice: variationChoice };
    const stockLimit =
      item.stock === null || item.stock === undefined
        ? Number.POSITIVE_INFINITY
        : Number(item.stock);
    const itemQuantity = cart
      .filter((line) => String(line.item.id) === String(item.id))
      .reduce((total, line) => total + line.quantity, 0);
    if (itemQuantity >= stockLimit) return;

    const nextItemCount = cartItemCount + 1;
    const choiceAnnouncement = variationChoice ? ` (${variationChoice})` : "";
    setCartAnnouncement(
      `${item.name}${choiceAnnouncement} added to your cart. ${nextItemCount} ${
        nextItemCount === 1 ? "item" : "items"
      } in your cart.`,
    );
    setCart((currentCart) => {
      const currentLine = currentCart.find((line) => getCartLineKey(line) === getCartLineKey(candidateLine));
      const currentItemQuantity = currentCart
        .filter((line) => String(line.item.id) === String(item.id))
        .reduce((total, line) => total + line.quantity, 0);
      if (currentItemQuantity >= stockLimit) return currentCart;

      return currentLine
        ? currentCart.map((line) =>
            getCartLineKey(line) === getCartLineKey(candidateLine)
              ? { ...line, item, quantity: line.quantity + 1 }
              : line,
          )
        : [...currentCart, { item, quantity: 1, variation_choice: variationChoice }];
    });
    setCustomerCareMessage(
      `Added ${item.name}${choiceAnnouncement}. Your bag now has ${cartItemCount + 1} ${
        cartItemCount === 0 ? "item" : "items"
      }.`,
    );
    if (variationPicker) closeVariationPicker();
  };

  const closeVariationPicker = () => {
    setVariationPicker(null);
    window.requestAnimationFrame(() => variationTriggerRef.current?.focus());
  };

  const confirmVariationChoice = () => {
    if (!variationPicker?.selectedChoice) return;
    const { item, selectedChoice } = variationPicker;
    addToCart(item, null, selectedChoice);
  };

  const changeQuantity = (lineKey, difference) => {
    const line = cart.find((entry) => getCartLineKey(entry) === lineKey);
    if (!line) return;
    const stockLimit =
      line.item.stock === null || line.item.stock === undefined
        ? Number.POSITIVE_INFINITY
        : Number(line.item.stock);
    const otherItemQuantity = cart
      .filter((entry) => String(entry.item.id) === String(line.item.id) && getCartLineKey(entry) !== lineKey)
      .reduce((total, entry) => total + entry.quantity, 0);
    const nextQuantity = Math.min(line.quantity + difference, stockLimit - otherItemQuantity);
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
          if (getCartLineKey(line) !== lineKey) return line;
          const currentNextQuantity = line.quantity + difference;
          if (currentNextQuantity < 1) return line;
          const currentStockLimit =
            line.item.stock === null || line.item.stock === undefined
              ? Number.POSITIVE_INFINITY
              : Math.max(0, Number(line.item.stock) - currentCart
                .filter((entry) => String(entry.item.id) === String(line.item.id) && getCartLineKey(entry) !== lineKey)
                .reduce((total, entry) => total + entry.quantity, 0));
          return {
            ...line,
            quantity: Math.min(currentNextQuantity, currentStockLimit),
          };
        }),
    );
  };

  const removeFromCart = (lineKey) => {
    const removedLine = cart.find(
      (line) => getCartLineKey(line) === lineKey,
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
      currentCart.filter((line) => getCartLineKey(line) !== lineKey),
    );
  };

  const openOverlay = (type) => {
    if (!modalHistoryRef.current) {
      const currentState =
        window.history.state && typeof window.history.state === "object"
          ? window.history.state
          : {};
      window.history.pushState(
        { ...currentState, afcMenuOverlay: true },
        "",
        window.location.href,
      );
      modalHistoryRef.current = true;
    }

    modalTypeRef.current = type;
    if (type === "gallery") {
      setCartOpen(false);
      setGalleryOpen(true);
    } else {
      setGalleryOpen(false);
      setCartOpen(true);
    }
  };

  const dismissOverlay = () => {
    if (modalHistoryRef.current) {
      window.history.back();
      return;
    }

    modalTypeRef.current = null;
    setGalleryOpen(false);
    setCartOpen(false);
    setActiveCategory("all");
  };

  const selectCategory = (categoryId, event) => {
    categoryTriggerRef.current = event?.currentTarget || null;
    setSearchQuery("");
    setActiveCategory(categoryId);
    setDishCarouselIndex(0);
    openOverlay("gallery");
    const categoryName =
      categories.find((category) => String(category.id) === categoryId)?.name;
    setCustomerCareMessage(
      categoryName
        ? `Great choice. Have a look at ${categoryName}.`
        : "Have a look through all our favourites.",
    );
  };

  const browseCategories = () => {
    dismissOverlay();
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
    const cards = carousel.querySelectorAll(".dish-gallery-slide");
    const targetIndex = Math.max(
      0,
      Math.min(dishCarouselIndex + direction, cards.length - 1),
    );
    const targetLeft =
      cards[targetIndex].offsetLeft +
      cards[targetIndex].offsetWidth / 2 -
      carousel.clientWidth / 2;
    carousel.scrollTo({
      left: targetLeft,
      behavior: window.matchMedia("(prefers-reduced-motion: reduce)").matches
        ? "auto"
        : "smooth",
    });
  };

  const updateDishCarouselIndex = (event) => {
    const carousel = event.currentTarget;
    const cards = carousel.querySelectorAll(".dish-gallery-slide");
    if (cards.length < 2) {
      setDishCarouselIndex((currentIndex) => (currentIndex === 0 ? currentIndex : 0));
      return;
    }

    const carouselCenter = carousel.scrollLeft + carousel.clientWidth / 2;
    let nearestIndex = 0;
    let nearestDistance = Number.POSITIVE_INFINITY;
    cards.forEach((card, index) => {
      const cardCenter = card.offsetLeft + card.offsetWidth / 2;
      const distance = Math.abs(carouselCenter - cardCenter);
      if (distance < nearestDistance) {
        nearestDistance = distance;
        nearestIndex = index;
      }
    });
    setDishCarouselIndex((currentIndex) =>
      currentIndex === nearestIndex ? currentIndex : nearestIndex,
    );
  };

  const updateSearchQuery = (query) => {
    setSearchQuery(query);
    if (query.trim()) setActiveCategory("all");
  };

  const openCart = (event) => {
    cartTriggerRef.current =
      galleryOpen ? categoryTriggerRef.current : event?.currentTarget || document.activeElement;
    setCustomerCareMessage(
      cartItemCount
        ? `Your bag has ${cartItemCount} ${
            cartItemCount === 1 ? "item" : "items"
          }. Adjust quantities, add your details, then continue with WhatsApp.`
        : "Your bag is empty. I can help you find something tasty.",
    );
    setCustomerCareOpen(false);
    openOverlay("cart");
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
      const order = await fetchJson("/api/orders", {
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
            variation_choice: line.variation_choice || null,
          })),
        }),
      });

      const redirectUrl = buildWhatsAppUrl({
        order,
        cart: orderCart,
        total: orderTotal,
        customer,
      });
      dismissOverlay();
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
      <header className="site-header" inert={galleryOpen}>
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

      <main inert={galleryOpen}>
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
                          onSelect={(event) => selectCategory(String(category.id), event)}
                        />
                      </div>
                    ))}
                  </div>
                </div>
              )}
              </section>
            )}

            {!searchQuery.trim() ? (
              <section className="menu-invitation" aria-labelledby="menu-invitation-title">
                <span className="menu-invitation-sparkle" aria-hidden="true">✦</span>
                <p className="eyebrow">A little something for every craving</p>
                <h2 id="menu-invitation-title">Pick a category to explore</h2>
                <p>Choose what sounds good and we’ll bring the menu right to you.</p>
              </section>
            ) : (
            <section
              className="menu-section is-search-open"
              id="menu-results"
              aria-live="polite"
            >
              <div className="section-heading menu-section-heading">
                <div>
                  <p className="eyebrow">Matches for “{searchQuery.trim()}”</p>
                  <h2>Let’s find your favourite</h2>
                </div>
                <span className="item-count">
                  {visibleItems.length} {visibleItems.length === 1 ? "dish" : "dishes"}
                </span>
              </div>

              {status === "loading" ? (
                <div className="empty-state">Loading fresh favourites…</div>
              ) : groupedItems.length ? (
                <div className="menu-groups" key={searchQuery}>
                  {groupedItems.map((group, groupIndex) => (
                    <section
                      className="menu-group"
                      key={group.id}
                      style={{ "--reveal-index": groupIndex }}
                    >
                      <div className="menu-group-heading">
                        <h3>Search results</h3>
                        <button type="button" onClick={browseCategories}>
                          Browse categories <span aria-hidden="true">↗</span>
                        </button>
                      </div>
                      <div
                        className="menu-grid"
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

      <footer className="site-footer" inert={galleryOpen}>
        <img src="/brand/logo2.png" alt="" width="44" height="44" loading="lazy" />
        <span>Made fresh with love at Acie Fraiche Café.</span>
      </footer>

      {variationPicker && (
        <VariationPicker
          item={variationPicker.item}
          selectedChoice={variationPicker.selectedChoice}
          dialogRef={variationPickerRef}
          onSelect={(selectedChoice) =>
            setVariationPicker((current) => ({ ...current, selectedChoice }))
          }
          onClose={closeVariationPicker}
          onConfirm={confirmVariationChoice}
        />
      )}

      {galleryOpen && activeCategory !== "all" && (
        <div
          className="dish-gallery-overlay"
          onMouseDown={(event) => {
            if (event.target === event.currentTarget) dismissOverlay();
          }}
        >
          <section
            ref={dishGalleryDialogRef}
            className="dish-gallery-dialog"
            role="dialog"
            aria-modal="true"
            aria-labelledby="dish-gallery-title"
            tabIndex="-1"
            onMouseDown={(event) => event.stopPropagation()}
          >
            <header className="dish-gallery-header">
              <div>
                <p className="eyebrow">Fresh from our kitchen</p>
                <h2 id="dish-gallery-title">{galleryCategory?.name || "Our menu"}</h2>
                <p className="dish-gallery-subtitle">
                  {galleryItems.length
                    ? `${galleryItems.length} ${galleryItems.length === 1 ? "favourite" : "favourites"} to explore`
                    : "We’re preparing this category’s menu."}
                </p>
              </div>
              <div className="dish-gallery-header-actions">
                {cartItemCount > 0 && (
                  <button className="dish-gallery-bag" type="button" onClick={openCart}>
                    Bag <span>{cartItemCount}</span>
                  </button>
                )}
                <button
                  className="dish-gallery-close"
                  type="button"
                  onClick={dismissOverlay}
                  aria-label="Close dish gallery"
                >
                  ×
                </button>
              </div>
            </header>

            {status === "loading" ? (
              <div className="empty-state">Setting the table…</div>
            ) : galleryItems.length ? (
              <>
                <div className="dish-gallery-stage">
                  <div
                    ref={dishCarouselRef}
                    className="dish-gallery-track"
                    onScroll={updateDishCarouselIndex}
                    role="region"
                    aria-label={`${galleryCategory?.name || "Category"} dishes`}
                    tabIndex="0"
                  >
                    {galleryItems.map((item, index) => {
                      const distance = index - dishCarouselIndex;
                      const positionClass =
                        distance === 0
                          ? "is-current"
                          : distance === -1
                            ? "is-previous"
                            : distance === 1
                              ? "is-next"
                              : "";
                      return (
                        <div
                          className={`dish-gallery-slide ${positionClass}`}
                          key={item.id}
                          aria-current={index === dishCarouselIndex ? "true" : undefined}
                        >
                          <MenuCard item={item} onAdd={addToCart} gallery />
                        </div>
                      );
                    })}
                  </div>
                </div>
                <div className="dish-gallery-controls">
                  <button
                    type="button"
                    aria-label="Previous dish"
                    disabled={dishCarouselIndex === 0}
                    onClick={() => moveDishCarousel(-1)}
                  >
                    ←
                  </button>
                  <span className="dish-gallery-count" aria-live="polite">
                    {String(dishCarouselIndex + 1).padStart(2, "0")}
                    <span aria-hidden="true"> / </span>
                    {String(galleryItems.length).padStart(2, "0")}
                  </span>
                  <button
                    type="button"
                    aria-label="Next dish"
                    disabled={dishCarouselIndex >= galleryItems.length - 1}
                    onClick={() => moveDishCarousel(1)}
                  >
                    →
                  </button>
                </div>
                <p className="dish-gallery-hint">Swipe left or right to explore</p>
              </>
            ) : (
              <div className="dish-gallery-empty">
                No dishes are available in this category right now.
              </div>
            )}
          </section>
        </div>
      )}

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
        inert={galleryOpen}
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
          inert={galleryOpen}
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
          if (event.target === event.currentTarget) dismissOverlay();
        }}
      >
        <aside
          id="shopping-cart"
          className="cart-drawer"
          role="dialog"
          aria-modal="true"
          aria-labelledby="cart-title"
          inert={!cartOpen}
          ref={cartDialogRef}
          tabIndex="-1"
        >
          <div className="cart-drawer-header">
            <div>
              <p className="eyebrow">Your delicious choices</p>
              <h2 id="cart-title">Your bag <span>{cartItemCount}</span></h2>
            </div>
            <button
              className="cart-close"
              type="button"
              onClick={dismissOverlay}
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
                    key={getCartLineKey(line)}
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
                  dismissOverlay();
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
                  dismissOverlay();
                  setCustomerCareMessage("Your bag is empty. I can help you find something tasty.");
                  setCustomerCareOpen(true);
                }}
              >
                Need a hand? See how to order
              </button>
              <button type="button" onClick={dismissOverlay}>
                Back to the menu
              </button>
            </div>
          )}
        </aside>
      </div>
    </div>
  );
}
