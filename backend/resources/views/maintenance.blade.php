<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="robots" content="noindex, nofollow" />
  <meta name="description" content="Acie Fraiche Cafe's website is under maintenance. Message us on WhatsApp for the current menu." />
  <title>Website Maintenance | Acie Fraiche Cafe</title>
  <link rel="icon" href="{{ asset('assets/logo2.png') }}" type="image/png" />
  <style>
    :root {
      color-scheme: light;
      font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      color: #29251f;
      background: #f7f3eb;
      font-synthesis: none;
      text-rendering: optimizeLegibility;
    }

    * {
      box-sizing: border-box;
    }

    body {
      display: grid;
      min-height: 100vh;
      min-height: 100svh;
      margin: 0;
      padding: 24px;
      place-items: center;
      background:
        radial-gradient(ellipse at 50% 0%, rgba(205, 166, 94, 0.13), transparent 52%),
        #f7f3eb;
    }

    main {
      width: min(100%, 560px);
      padding: clamp(32px, 8vw, 64px);
      border: 1px solid rgba(98, 76, 43, 0.12);
      border-radius: 28px;
      background: rgba(255, 253, 249, 0.94);
      box-shadow: 0 24px 80px rgba(62, 47, 28, 0.09);
      text-align: center;
    }

    .brand {
      display: inline-flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 38px;
      color: inherit;
      text-align: left;
      text-decoration: none;
    }

    .brand img {
      width: 52px;
      height: 52px;
      border-radius: 14px;
      object-fit: contain;
    }

    .brand-copy {
      display: grid;
      gap: 3px;
    }

    .brand-copy strong {
      font-size: 15px;
      letter-spacing: 0.02em;
    }

    .brand-copy span {
      color: #817663;
      font-size: 12px;
    }

    .eyebrow {
      margin: 0 0 12px;
      color: #97733b;
      font-size: 12px;
      font-weight: 700;
      letter-spacing: 0.16em;
      text-transform: uppercase;
    }

    h1 {
      margin: 0;
      font-family: Georgia, "Times New Roman", serif;
      font-size: clamp(34px, 8vw, 48px);
      font-weight: 500;
      letter-spacing: -0.035em;
      line-height: 1.08;
    }

    .message {
      margin: 20px 0 28px;
      color: #6d6559;
      font-size: 16px;
      line-height: 1.75;
    }

    .whatsapp-link {
      display: inline-flex;
      min-height: 52px;
      align-items: center;
      justify-content: center;
      padding: 0 24px;
      border-radius: 999px;
      background: #594524;
      color: #fffdf8;
      font-size: 15px;
      font-weight: 700;
      text-decoration: none;
      transition: background-color 160ms ease, transform 160ms ease;
    }

    .whatsapp-link:hover {
      transform: translateY(-1px);
      background: #43351d;
    }

    .whatsapp-link:focus-visible {
      outline: 3px solid #c8a05b;
      outline-offset: 4px;
    }

    .phone {
      display: block;
      margin-top: 14px;
      color: #817663;
      font-size: 14px;
    }

    footer {
      margin-top: 42px;
      color: #a1988a;
      font-size: 12px;
    }

    @media (max-width: 480px) {
      body {
        padding: 16px;
      }

      main {
        border-radius: 22px;
      }

      .brand {
        margin-bottom: 32px;
      }
    }

    @media (prefers-reduced-motion: reduce) {
      *,
      *::before,
      *::after {
        scroll-behavior: auto !important;
        transition-duration: 0.01ms !important;
      }
    }
  </style>
</head>
<body>
  <main aria-labelledby="maintenance-title">
    <a class="brand" href="/" aria-label="Acie Fraiche Cafe">
      <img src="{{ asset('assets/logo2.png') }}" alt="" width="52" height="52" />
      <span class="brand-copy">
        <strong>Acie Fraiche Cafe</strong>
        <span>Freshly Crafted, Simply Delicious</span>
      </span>
    </a>

    <p class="eyebrow">A little pause while we get things ready</p>
    <h1 id="maintenance-title">Our website is under maintenance</h1>
    <p class="message">
      We’re updating our website and menu. To see the current menu, send us a
      message on WhatsApp and our team will share it with you and help you choose.
      Thank you for your patience.
    </p>
    <a
      class="whatsapp-link"
      href="https://wa.me/2348143190700?text=Hello%20Acie%20Fraiche%20Cafe%2C%20please%20send%20me%20the%20current%20menu."
      target="_blank"
      rel="noopener noreferrer"
    >
      Chat with us on WhatsApp
    </a>
    <span class="phone">+234 814 319 0700</span>
    <footer>We look forward to serving you.</footer>
  </main>
</body>
</html>
