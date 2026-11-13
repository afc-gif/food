# React public website

The React app serves the public homepage at `/` and reads menu data from the
existing Laravel API. Laravel remains responsible for every other web route,
including `/login`, registration, staff pages, and the admin/manager/kitchen
dashboards. The React app does not replace Laravel's Blade pages or modify API
behavior or database tables.

## Local development

1. Start Laravel from `backend` (for example, `php artisan serve`).
2. In `react-app`, copy `.env.example` to `.env`.
3. Set `BACKEND_URL` to the Laravel origin if it is not `http://127.0.0.1:8000`.
4. Run `npm ci` and `npm run dev`.

Vite proxies `/api` during local development. The Nginx configuration used in
deployment serves React only at `/`, serves React build assets when present,
and forwards all other paths to Laravel.

## Deploy behind the public domain

Deploy the React app as a separate Railway service using `react-app` as its
root directory and its `Dockerfile`. Set the React service's runtime
`BACKEND_URL` to the Laravel Railway service's origin, without a trailing
slash, for example `https://your-laravel-service.up.railway.app`.

Point the public domain (for example, `afc.com.ng`) at the React service. Its
Nginx server serves the React homepage at `/` and proxies `/login`, `/admin`,
`/manager`, `/kitchen`, other non-API routes, and missing React assets to the
Laravel service. `/api/*` is also proxied to Laravel for the public site's menu
requests. This keeps browser navigation and Laravel authentication on the same
public origin while leaving Laravel as the owner of all non-homepage routes.

Do not change the existing Laravel service's root directory or deploy settings
for this setup. After deploying, verify `/` shows the React site and `/login`
shows Laravel's login page; then sign in and confirm the role-based dashboard
loads through the public domain.
