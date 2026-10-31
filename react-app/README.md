# React menu preview

This app is an independent React frontend that reads menu data from the existing
Laravel API. It does not replace the current Blade website, change API behavior,
or modify database tables.

## Local development

1. Start the Laravel app from `backend` (for example, `php artisan serve`).
2. In `react-app`, copy `.env.example` to `.env`.
3. If Laravel is not at `http://127.0.0.1:8000`, set `BACKEND_URL` in `.env`.
4. Run `npm ci` and `npm run dev`.

Vite proxies `/api` calls to Laravel during development, so the browser does not
need direct cross-origin access locally.

## Deploy as a separate Railway service

Create another service in the existing Railway project, using the same GitHub
repository, select `react-app` as its root directory, and use its `Dockerfile`.
Add the runtime variable `BACKEND_URL` to the React service with the existing
Laravel service's origin (for example, `https://your-backend.example`, without
a trailing slash). The React service proxies `/api/*` requests to Laravel, so
the browser uses one origin and the Laravel service needs no CORS or code
changes for the preview.

Deploy only this new service. Leave the existing Laravel service's source,
root directory, and deploy settings unchanged.
