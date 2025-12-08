@php
    use Illuminate\Support\Str;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Acie Fraiche Cafe – Freshly Crafted, Simply Delicious</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="icon" href="{{ asset('assets/logo.png') }}" type="image/png" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600&family=Playfair+Display:wght@600;700&display=swap"
    rel="stylesheet"
  />
  <link rel="stylesheet" href="{{ asset('styles.css') }}?v=5" />
</head>
<body>
  <header class="af-header">
    <div class="af-container af-header-inner">
      <div class="af-logo-wrap">
        <img src="{{ asset('assets/logo.png') }}" alt="Acie Fraiche Cafe Logo" class="af-logo" />
        <div class="af-logo-text">
          <span class="af-logo-name">Acie Fraiche Cafe</span>
          <span class="af-logo-tagline">Freshly Crafted, Simply Delicious</span>
        </div>
      </div>

      <nav class="af-nav">
        <a href="#home">Home</a>
        <a href="#featured">Signatures</a>
        <a href="#menu">Menu</a>
        <a href="#about">Story</a>
        <a href="#contact">Visit</a>
        <a href="#order" class="af-btn af-btn-outline">Order</a>
      </nav>

      <button class="af-nav-toggle" id="navToggle" aria-label="Toggle navigation">
        ☰
      </button>
    </div>
  </header>

  <main>
    <section id="home" class="af-hero">
      <div class="af-hero-gradient"></div>
      <div class="af-container af-hero-inner">
        <div class="af-hero-content">
          <p class="af-kicker">Acie Fraiche Cafe</p>
          <h1>Elevated fast-casual dining with West African flair.</h1>
          <p class="af-lead">
            Comfort classics, elegant plating, and the warmth of our hosts. Crafted fresh,
            served swiftly, and priced for every day indulgence.
          </p>
          <div class="af-hero-actions">
            <a href="#menu" class="af-btn af-btn-primary">View Menu</a>
            <a href="#order" class="af-btn af-btn-ghost">Order Now</a>
          </div>
          <div class="af-hero-metrics">
            <div>
              <strong>20+</strong>
              <span>Signature bowls &amp; grills</span>
            </div>
            <div>
              <strong>15 mins</strong>
              <span>Avg. pickup prep time</span>
            </div>
            <div>
              <strong>Trusted</strong>
              <span>By families &amp; food lovers</span>
            </div>
          </div>
        </div>

        <div class="af-hero-visual">
          <img src="{{ asset('assets/logo2.png') }}" alt="Acie Fraiche Cafe Emblem" class="af-hero-logo" />
          <div class="af-floating-note">
            <span>Opening hours</span>
            <strong>Mon–Sat 6am – 9pm</strong>
            <small>Sun 11am – 9pm · Dine-in · Takeout · Pickup</small>
          </div>
        </div>
      </div>
    </section>

    <section class="af-section" id="featured">
      <div class="af-container">
        <div class="af-section-head">
          <p class="af-kicker">Chef's Signatures</p>
          <h2>Plates our guests keep coming back for.</h2>
          <p>Modern takes on familiar comfort, layered with bold, memorable flavors.</p>
        </div>

        <div class="af-grid af-grid-3 af-cards" id="featuredGrid">
          @forelse ($featured as $item)
            @php $isSoldOut = $item->is_sold_out; @endphp
            <article
              class="af-card"
              data-menu-item
              data-item-id="{{ $item->id }}"
              data-sold-out="{{ $isSoldOut ? '1' : '0' }}"
              data-category="{{ Str::slug(optional($item->category)->name ?? 'menu') }}"
            >
              @if($item->image_url)
                <img src="{{ $item->image_url }}" alt="{{ $item->name }}" class="af-card-img" />
              @endif
              <div class="af-card-body">
                <div class="af-card-top">
                  <h3>{{ $item->name }}</h3>
                  <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                    <span class="af-tag">{{ optional($item->category)->name ?? 'Signature' }}</span>
                    <span
                      class="af-pill"
                      data-soldout-pill
                      style="background:#fef2f2;color:#b91c1c;border-color:#fecdd3;{{ $isSoldOut ? '' : 'display:none;' }}"
                    >Sold Out</span>
                  </div>
                </div>
                <p>{{ $item->description ?? 'Fresh from our kitchen.' }}</p>
                <div class="af-card-footer">
                  <span class="af-price">₦{{ number_format($item->price, 0) }}</span>
                  <button
                    class="af-btn af-btn-sm af-btn-primary"
                    data-item="{{ $item->name }}"
                    data-item-id="{{ $item->id }}"
                    data-item-price="{{ $item->price }}"
                    data-sold-out="{{ $isSoldOut ? '1' : '0' }}"
                    @if($isSoldOut) disabled @endif
                  >
                    {{ $isSoldOut ? 'Sold Out' : 'Add to Cart' }}
                  </button>
                </div>
              </div>
            </article>
          @empty
            <p style="text-align:center; width:100%;">No featured items yet. Check back soon.</p>
          @endforelse
        </div>
      </div>
    </section>

    <section class="af-section af-section-alt" id="menu">
      <div class="af-container">
        <div class="af-section-head">
          <p class="af-kicker">All-Day Menu</p>
          <h2>Freshly prepared, beautifully plated.</h2>
          <p>Choose your craving; we will prepare it hot and have it ready in minutes.</p>
        </div>

          <div class="af-menu-panel">
          <div class="af-menu-filters" id="menuFilters">
            <button class="af-chip af-chip-active" data-filter="all">All</button>
            @foreach ($categories as $category)
              <button class="af-chip" data-filter="{{ Str::slug($category->name) }}">{{ $category->name }}</button>
            @endforeach
          </div>

          <div class="af-grid af-grid-3" id="menuGrid">
            @forelse ($menuItems as $item)
              @php
                  $catSlug = Str::slug(optional($item->category)->name ?? 'menu');
                  $isSoldOut = $item->is_sold_out;
              @endphp
              <article
                class="af-menu-item"
                data-menu-item
                data-item-id="{{ $item->id }}"
                data-sold-out="{{ $isSoldOut ? '1' : '0' }}"
                data-category="{{ $catSlug }}"
              >
                <div class="af-menu-head">
                  <h3>{{ $item->name }}</h3>
                  <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                    <span class="af-pill">{{ optional($item->category)->name ?? 'Menu' }}</span>
                    <span
                      class="af-pill"
                      data-soldout-pill
                      style="background:#fef2f2;color:#b91c1c;border-color:#fecdd3;{{ $isSoldOut ? '' : 'display:none;' }}"
                    >Sold Out</span>
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
                    data-sold-out="{{ $isSoldOut ? '1' : '0' }}"
                    @if($isSoldOut) disabled @endif
                  >
                    {{ $isSoldOut ? 'Sold Out' : 'Add to Cart' }}
                  </button>
                </div>
              </article>
            @empty
              <p style="text-align:center; width:100%;">Menu is coming soon. Please check back.</p>
            @endforelse
          </div>
        </div>
      </div>
    </section>

    <section class="af-section" id="order">
      <div class="af-container af-order-placeholder">
        <div>
          <p class="af-kicker">Checkout</p>
          <h2>Open the cart to review and pay</h2>
          <p>Tap the cart button to see your order summary and complete checkout in the overlay.</p>
        </div>
      </div>
    </section>

    <section class="af-section af-section-alt" id="about">
      <div class="af-container af-about">
        <div class="af-about-copy">
          <div class="af-about-header">
            <p class="af-kicker">Our Story</p>
            <span class="af-about-badge">Rooted in grace</span>
          </div>
          <h2>Food that loves you back.</h2>
          <p>
            Acie Fraiche Cafe was born from a simple belief that everyone deserves food that loves
            them back. Growing up, we watched people around us struggle to find meals that were
            both natural and affordable, and we saw how good food could lift a person's spirit, even
            on their hardest days.
          </p>
          <p>
            So we created a place where every dish is made with intention—organic ingredients,
            natural flavors, and the kind of care you would give to someone you truly value. At Acie
            Fraiche Cafe, we serve more than meals; we serve comfort, dignity, and a reminder that
            quality should never be a luxury. At the heart of it all, we honor the grace of God,
            believing that every hand that prepares, every ingredient that grows, and every life we
            touch is guided by His goodness. Acie Fraiche Cafe is our way of sharing that blessing
            with everyone who walks through our doors.
          </p>
          <div class="af-about-pills">
            <span>Organic ingredients</span>
            <span>Natural flavors</span>
            <span>Everyday affordability</span>
            <span>Guided by grace</span>
          </div>
          <div class="af-about-signoff">
            <span class="af-script">With gratitude,</span>
            <strong>Team Acie Fraiche</strong>
          </div>
        </div>
        <div class="af-about-panel">
          <div class="af-about-card">
            <div class="af-about-orb"></div>
            <div class="af-about-card-head">
              <span class="af-about-pill">Our Promise</span>
              <p>Every plate is crafted with intention, priced for everyday joy.</p>
            </div>
            <ul class="af-about-checklist">
              <li>Comfort and dignity in every serving.</li>
              <li>Organic ingredients and honest, natural flavor.</li>
              <li>Warm hospitality that feels like family.</li>
              <li>Guided by God's grace in how we work and serve.</li>
            </ul>
          </div>
          <div class="af-about-stats">
            <div>
              <strong>15+</strong>
              <span>Team members from top kitchens</span>
            </div>
            <div>
              <strong>Fresh</strong>
              <span>Produce delivered daily</span>
            </div>
            <div>
              <strong>Community</strong>
              <span>We give back every month</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="af-section" id="contact">
      <div class="af-container af-contact">
        <div class="af-contact-card">
          <p class="af-kicker">Visit Us</p>
          <h2>We would love to host you.</h2>
          <p>Book a table, grab takeaway, or call ahead and we'll have it ready for pickup.</p>
          <div class="af-contact-grid">
            <div>
              <strong>Phone</strong>
              <p>+234 701 586 2018</p>
            </div>
            <div>
              <strong>Email</strong>
              <p>hello@aciefraichecafe.com</p>
            </div>
            <div>
              <strong>Address</strong>
              <p>No 1 Artsci Lane opp International Market Park, Celestine Omehia road (Sars road)</p>
            </div>
            <div>
              <strong>Hours</strong>
              <p>Mon. – Sat.: 6am – 9pm<br />Sun.: 11am – 9pm</p>
            </div>
          </div>
        </div>
        <div class="af-contact-cta">
          <h3>Stay Connected</h3>
          <p>Follow us for chef drops, new pairings, and weekly tastings.</p>
          <p class="af-socials">
            <a href="#">Instagram</a> ·
            <a href="#">Facebook</a> ·
            <a href="#">X (Twitter)</a>
          </p>
          <a href="#order" class="af-btn af-btn-primary">Order Your Favorites</a>
        </div>
      </div>
    </section>
  </main>

  <button class="af-cart-fab" id="cartFab" type="button" aria-label="View cart and checkout">
    <span class="af-cart-fab-icon">Cart</span>
    <span class="af-cart-fab-count" id="cartCount">0</span>
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
    <div class="af-container">
      <span>© <span id="year"></span> Acie Fraiche Cafe. Freshly Crafted, Simply Delicious.</span>
    </div>
  </footer>

  <script src="{{ asset('script.js') }}?v=5"></script>
</body>
</html>
