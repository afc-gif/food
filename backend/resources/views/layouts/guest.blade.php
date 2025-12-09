<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Acie Fraiche Admin') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700&family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            :root {
                --af-gold: #cc9933;
                --af-brown: #523700;
                --af-ink: #0f0b05;
                --af-cream: #f7f1e7;
                --af-line: rgba(82, 55, 0, 0.14);
            }
            body {
                margin: 0;
                font-family: 'Manrope', system-ui, -apple-system, sans-serif;
                color: var(--af-ink);
                background:
                    radial-gradient(circle at 14% 18%, rgba(204,153,51,0.12), transparent 28%),
                    radial-gradient(circle at 88% 12%, rgba(82,55,0,0.09), transparent 22%),
                    var(--af-cream);
            }
            .auth-shell {
                min-height: 100vh;
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
            }
            .auth-brand {
                padding: 32px;
                display: grid;
                gap: 18px;
                align-content: center;
                background: linear-gradient(140deg, rgba(204,153,51,0.18), rgba(255,255,255,0.9));
                border-right: 1px solid var(--af-line);
            }
            .brand-logo {
                width: 78px;
                height: 78px;
                border-radius: 18px;
                border: 1px solid var(--af-line);
                background: #fff;
                object-fit: contain;
                box-shadow: 0 16px 40px rgba(0,0,0,0.08);
            }
            .brand-title {
                font-family: 'Playfair Display', Georgia, serif;
                font-size: 28px;
                margin: 0;
            }
            .brand-lead {
                margin: 8px 0 0;
                color: rgba(0,0,0,0.7);
                max-width: 360px;
                line-height: 1.6;
            }
            .pill {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 8px 12px;
                border-radius: 999px;
                border: 1px solid var(--af-line);
                background: #fff;
                font-weight: 600;
                color: var(--af-brown);
                width: fit-content;
            }
            .auth-panel {
                display: grid;
                align-content: center;
                padding: 32px 24px;
            }
            .auth-card {
                width: 100%;
                max-width: 440px;
                margin: 0 auto;
                background: #fff;
                border: 1px solid var(--af-line);
                border-radius: 18px;
                padding: 24px;
                box-shadow: 0 18px 48px rgba(0,0,0,0.12);
            }
            .auth-card h1 {
                margin: 0;
                font-family: 'Playfair Display', Georgia, serif;
                font-size: 24px;
            }
            .auth-card p {
                margin: 6px 0 18px;
                color: rgba(0,0,0,0.65);
            }
            .auth-links {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-top: 14px;
            }
            .auth-links a {
                color: var(--af-brown);
                font-weight: 600;
            }
        </style>
    </head>
    <body>
        <div class="auth-shell">
            <aside class="auth-brand">
                <div style="display:flex;align-items:center;gap:12px;">
                    <img src="/assets/logo2.png" alt="Acie Fraiche Logo" class="brand-logo">
                    <div>
                        <p class="brand-title">Acie Fraiche</p>
                        <p style="margin:0;color:rgba(0,0,0,0.65);">Admin & Staff Portal</p>
                    </div>
                </div>
                <div class="pill">
                    <span>🌿 Freshly crafted · Simply delicious</span>
                </div>
                <p class="brand-lead">
                    Sign in to manage menu, stock, orders, and POS. Brand colors and layout match the main site for consistency.
                </p>
            </aside>
            <main class="auth-panel">
                <div class="auth-card">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </body>
</html>
