@php
    use Illuminate\Support\Str;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Acie Fraiche Cafe – Freshly Crafted, Simply Delicious</title>
  <meta name="description" content="Discover Acie Fraiche Cafe - freshly crafted, simply delicious food. Browse our menu, place orders online, and enjoy quality meals delivered with care." />
  <meta name="keywords" content="cafe, fresh food, restaurant, online ordering, menu, food delivery, Acie Fraiche" />
  <meta name="author" content="Acie Fraiche Cafe" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />

  <!-- Open Graph / Facebook -->
  <meta property="og:type" content="website" />
  <meta property="og:url" content="https://www.afc.com.ng/" />
  <meta property="og:title" content="Acie Fraiche Cafe – Freshly Crafted, Simply Delicious" />
  <meta property="og:description" content="Discover Acie Fraiche Cafe - freshly crafted, simply delicious food. Browse our menu and place orders online." />
  <meta property="og:image" content="{{ asset('assets/logo2.png') }}" />

  <!-- Twitter -->
  <meta property="twitter:card" content="summary_large_image" />
  <meta property="twitter:url" content="https://www.afc.com.ng/" />
  <meta property="twitter:title" content="Acie Fraiche Cafe – Freshly Crafted, Simply Delicious" />
  <meta property="twitter:description" content="Discover Acie Fraiche Cafe - freshly crafted, simply delicious food. Browse our menu and place orders online." />
  <meta property="twitter:image" content="{{ asset('assets/logo2.png') }}" />

  <!-- Canonical URL -->
  <link rel="canonical" href="https://www.afc.com.ng/" />

  <!-- Favicon -->
  <link rel="icon" href="{{ asset('assets/logo2.png') }}" type="image/png" />
  <link rel="stylesheet" href="{{ asset('styles.css') }}?v={{ filemtime(public_path('styles.css')) }}" />
</head>
<body>
  <header class="af-header">
    <div class="af-container af-header-inner">
      <div class="af-logo-wrap">
        <img src="{{ asset('assets/logo2.png') }}" alt="Acie Fraiche Cafe Logo" class="af-logo" />
        <div class="af-logo-text">
          <span class="af-logo-name">Acie Fraiche Cafe</span>
          <span class="af-logo-tagline">Freshly Crafted, Simply Delicious</span>
        </div>
      </div>

      <nav class="af-nav">
        <a href="#menu">Menu</a>
        <a href="#menu" class="af-btn af-btn-outline" data-cart-open>Order</a>
      </nav>

      <button class="af-nav-toggle" id="navToggle" aria-label="Toggle navigation">
        ☰
      </button>
    </div>
  </header>

  <main>
    <section class="af-section af-section-alt" id="menu">
      <div class="af-container">
        <div class="af-section-head">
          <p class="af-kicker">All-Day Menu</p>
          <h2>Freshly prepared, beautifully plated.</h2>
          <p>Choose your craving; we will prepare it hot and have it ready in minutes.</p>
        </div>

        <div class="af-menu-panel af-category-mode" id="menuPanel">
          <div class="af-menu-search-wrapper" id="menuSearchWrapper">
            <div class="af-search-input-wrapper">
              <svg class="af-search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
              </svg>
              <input
                type="search"
                id="menuSearchInput"
                class="af-search-input"
                placeholder="Search dishes or ingredients (e.g. chicken, turkey, rice)..."
                aria-label="Search menu items"
                autocomplete="off"
              />
              <button type="button" class="af-search-clear" id="menuSearchClear" aria-label="Clear search" hidden>&times;</button>
            </div>
            <div class="af-search-dropdown" id="menuSearchDropdown" hidden>
              <div class="af-search-suggestions" id="menuSearchSuggestions" role="listbox"></div>
            </div>
          </div>

          <div class="af-menu-filters" id="menuFilters" aria-label="Menu categories">
            <button class="af-chip af-chip-active" data-filter="all" data-category-id="" aria-pressed="true">All</button>
            @foreach ($categories as $category)
              <button class="af-chip" data-filter="{{ Str::slug($category->name) }}" data-category-id="{{ $category->id }}" aria-pressed="false">{{ $category->name }}</button>
            @endforeach
          </div>

          <div class="af-mobile-category-bar" data-mobile-category-bar>
            <button class="af-mobile-category-back" type="button" data-category-back aria-label="Back to categories">
              <span aria-hidden="true">&larr;</span>
              <span>Categories</span>
            </button>
            <strong data-mobile-category-current>All Menu</strong>
            <button class="af-mobile-category-toggle" type="button" data-mobile-category-toggle aria-expanded="false" aria-controls="mobileCategoryMenu">
              Change
            </button>
            <div class="af-mobile-category-menu" id="mobileCategoryMenu" data-mobile-category-menu hidden>
              <button type="button" data-mobile-filter="all" data-category-id="">All Menu</button>
              @foreach ($categories as $category)
                <button type="button" data-mobile-filter="{{ Str::slug($category->name) }}" data-category-id="{{ $category->id }}">{{ $category->name }}</button>
              @endforeach
            </div>
          </div>

          <div class="af-menu-shell">
            <div class="af-menu-products">
              <div class="af-category-browser" id="categoryGrid" aria-label="Menu category previews">
                @forelse ($categories as $category)
                  @php
                    $categoryItems = $menuItems->filter(fn ($menuItem) => (int) $menuItem->category_id === (int) $category->id);
                    $previewImages = collect([$category->image_url])
                      ->merge($categoryItems->pluck('image_url'))
                      ->filter()
                      ->unique()
                      ->take(4)
                      ->values();
                  @endphp
                  <article class="af-category-card" data-category-card data-filter="{{ Str::slug($category->name) }}" data-category-id="{{ $category->id }}">
                    <button type="button" class="af-category-card-action" data-category-card-button aria-label="View {{ $category->name }} items">
                      <span class="af-category-preview" aria-hidden="true">
                        @forelse ($previewImages as $image)
                          <img src="{{ $image }}" alt="" loading="lazy" decoding="async" class="{{ $loop->first ? 'is-active' : '' }}">
                        @empty
                          <span class="af-menu-thumb-fallback"><span>AFC</span></span>
                        @endforelse
                        <span class="af-category-overlay">
                          <strong>{{ $category->name }}</strong>
                        </span>
                      </span>
                      <span class="af-category-card-body">
                        <span class="af-category-copy">{{ $category->description ?: 'Freshly prepared favorites from our kitchen.' }}</span>
                        <span class="af-category-card-meta">
                          <span>View dishes</span>
                          <span aria-hidden="true">&rarr;</span>
                        </span>
                      </span>
                    </button>
                  </article>
                @empty
                  <p class="af-menu-empty">Menu categories are coming soon. Please check back.</p>
                @endforelse
              </div>

              <div class="af-menu-count">
                <button class="af-menu-back" type="button" data-category-back>Categories</button>
                <span>Products</span>
                <strong>{{ $menuItems->count() }}</strong>
              </div>

              <div class="af-grid af-grid-3" id="menuGrid">
                @forelse ($menuItems as $item)
                  @php
                      $catSlug = Str::slug(optional($item->category)->name ?? 'menu');
                      $isSoldOut = $item->is_sold_out || $item->stock === 0;
                  @endphp
                  <article
                    class="af-menu-item"
                    data-menu-item
                    data-item-id="{{ $item->id }}"
                    data-item-name="{{ e($item->name) }}"
                    data-category-name="{{ e(optional($item->category)->name ?? 'Menu') }}"
                    data-item-price="{{ $item->price }}"
                    data-description="{{ e($item->description ?? '') }}"
                    data-image-url="{{ e($item->image_url ?? '') }}"
                    data-sides="{{ !empty($item->sides) ? json_encode($item->sides) : '' }}"
                    data-sold-out="{{ $isSoldOut ? '1' : '0' }}"
                    data-stock="{{ $item->stock ?? '' }}"
                    data-stock-unit="{{ $item->stock_unit ?? '' }}"
                    data-category="{{ $catSlug }}"
                    data-category-id="{{ $item->category_id ?? '' }}"
                  >
                    <div class="af-menu-thumb">
                      @if($item->image_url)
                        <img src="{{ $item->image_url }}" alt="{{ $item->name }}" loading="lazy" decoding="async">
                      @else
                        <div class="af-menu-thumb-fallback" aria-hidden="true">
                          <span>AFC</span>
                        </div>
                      @endif
                    </div>
                    <div class="af-menu-body">
                      <div class="af-menu-head">
                        <h3>{{ $item->name }}</h3>
                        <div class="af-menu-meta">
                          <span class="af-pill">{{ optional($item->category)->name ?? 'Menu' }}</span>
                        </div>
                      </div>
                      <p>{{ $item->description ?? 'Freshly prepared from our kitchen.' }}</p>
                      <div class="af-menu-footer">
                        <span class="af-price">₦{{ number_format($item->price, 0) }}</span>
                        <button
                          class="af-btn af-btn-sm af-btn-outline"
                          data-item="{{ $item->name }}"
                          data-item-id="{{ $item->id }}"
                          data-item-price="{{ $item->price }}"
                          data-sides="{{ !empty($item->sides) ? json_encode($item->sides) : '' }}"
                          data-sold-out="{{ $isSoldOut ? '1' : '0' }}"
                          data-stock="{{ $item->stock ?? '' }}"
                          data-stock-unit="{{ $item->stock_unit ?? '' }}"
                          aria-label="{{ $isSoldOut ? 'Sold out: '.$item->name : 'Add '.$item->name.' to cart' }}"
                          @if($isSoldOut) disabled @endif
                        >
                          {{ $isSoldOut ? 'Sold Out' : 'Add to Cart' }}
                        </button>
                      </div>
                    </div>
                  </article>
                @empty
                  <p class="af-menu-empty">Menu is coming soon. Please check back.</p>
                @endforelse
              </div>
            </div>

            <aside class="af-menu-cart" aria-label="Your order">
              <div class="af-menu-cart-head">
                <p class="af-kicker">Your Order</p>
                <h3>Your order</h3>
              </div>
              <ul id="cartList" class="af-cart-list"></ul>
              <div class="af-cart-summary">
                <span>Total</span>
                <strong id="cartTotal">₦0</strong>
              </div>
              <button class="af-btn af-btn-primary af-menu-checkout-btn" type="button" data-cart-open>
                Checkout
              </button>
            </aside>
          </div>
        </div>
      </div>
    </section>

  </main>

  <button class="af-cart-fab" id="cartFab" type="button" aria-label="View cart and checkout">
    <span class="af-cart-fab-main">
      <span class="af-cart-fab-icon">Cart</span>
      <span><span id="cartCount">0</span> <span id="cartCountWord">items</span></span>
    </span>
    <strong id="cartBarTotal">₦0</strong>
    <span class="af-cart-fab-action">View Cart</span>
  </button>

  <div class="af-cart-overlay" id="cartOverlay" aria-hidden="true">
    <div class="af-cart-overlay-backdrop" id="cartOverlayBackdrop"></div>
    <div class="af-cart-overlay-card">
      <button class="af-cart-overlay-close" id="cartOverlayClose" aria-label="Close cart overlay">
        ×
      </button>
      <div class="af-cart-overlay-head">
        <p class="af-kicker">Your Order</p>
        <h3>Review and checkout</h3>
        <p>Confirm your selection, service option, and preferred time.</p>
      </div>

      <div class="af-cart-overlay-body">
        <div class="af-cart-overlay-list">
          <ul id="cartListOverlay" class="af-cart-list"></ul>
          <div class="af-cart-summary">
            <span>Total</span>
            <strong id="cartTotalOverlay">₦0</strong>
          </div>
        </div>
        <div class="af-cart-overlay-form">
          <form id="checkoutFormOverlay">
            <label>
              Full Name
              <input type="text" name="name" required />
            </label>

            <label>
              Phone Number
              <input type="tel" name="phone" required />
            </label>

            <label>
              Service Option
              <select name="service" required>
                <option value="Pickup">Pickup</option>
                <option value="Takeout">Takeout</option>
                <option value="Dine-in">Dine-in</option>
              </select>
            </label>

            <label>
              Preferred Time
              <input type="text" name="time" placeholder="e.g., 6:30 PM" required />
            </label>

            <label>
              Order Note (optional)
              <textarea name="note" rows="2"></textarea>
            </label>

            <div class="af-payment-options">
              <button
                type="button"
                class="af-btn af-btn-primary"
                id="whatsappBtnOverlay"
                data-whatsapp-btn
                data-form="checkoutFormOverlay"
              >
                Complete Order via WhatsApp
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <footer class="af-footer">
    <div class="af-container af-footer-inner">
      <div class="af-footer-brand">
        <strong>Acie Fraiche Cafe</strong>
        <p>Freshly Crafted, Simply Delicious.</p>
      </div>

      <div class="af-footer-contact">
        <h2>Contact</h2>
        <a href="tel:08143190700">08143190700</a>
        <a href="mailto:support@afc.com.ng">support@afc.com.ng</a>
        <p><strong>SARS ROAD</strong><br>Immediately after the SARS Police Station, you will see a Smart Home Office.</p>
      </div>

      <div class="af-footer-hours">
        <h2>Opening Hours</h2>
        <p><span data-business-hours-weekday>Mon. – Sat.: 8am - 10pm</span><br><span data-business-hours-sunday>Sun.: 12noon - 10pm</span></p>
        <h3>Follow Us</h3>
        <div class="af-socials" aria-label="Social media links">
          <a class="af-social-link" href="https://www.instagram.com/afc.ng?igsh=MTQxMGV4bzhmM2I2dg%3D%3D&utm_source=qr" target="_blank" rel="noopener noreferrer" aria-label="Follow Acie Fraiche Cafe on Instagram">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
              <rect x="3" y="3" width="18" height="18" rx="5"></rect>
              <circle cx="12" cy="12" r="4"></circle>
              <circle cx="17.5" cy="6.5" r="1.2" fill="currentColor" stroke="none"></circle>
            </svg>
          </a>
          <a class="af-social-link" href="https://www.tiktok.com/@acie_fraiche_cafe?_r=1&_t=ZS-98WFafKvipp" target="_blank" rel="noopener noreferrer" aria-label="Follow Acie Fraiche Cafe on TikTok">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
              <path d="M16.75 3c.34 2.16 1.58 3.54 3.75 3.75v3.21a7.12 7.12 0 0 1-3.75-1.1v5.95c0 3.15-2.12 5.19-5.23 5.19-2.88 0-5.02-2.02-5.02-4.72 0-2.83 2.22-4.81 5.32-4.81.29 0 .56.02.82.06v3.31a3.2 3.2 0 0 0-.82-.11c-1.17 0-1.93.6-1.93 1.55 0 .9.7 1.52 1.68 1.52 1.1 0 1.82-.67 1.82-2.01V3h3.36Z"></path>
            </svg>
          </a>
        </div>
      </div>
    </div>
    <div class="af-container af-footer-bottom">
      <span>© <span id="year"></span> Acie Fraiche Cafe.</span>
    </div>
  </footer>

  <script src="{{ asset('script.js') }}?v={{ filemtime(public_path('script.js')) }}" defer></script>
</body>
</html>
