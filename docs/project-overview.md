Project overview — Accenix
==========================

Purpose
-------
This document summarizes the current codebase so we have a single reference before we design and implement the Applications admin page that will integrate with WSO2 Identity Server (using the OpenAPI in `application.yaml`).

Quick facts
-----------
- Repository root: Laravel 12 project (PHP 8.2)
- Frontend: TailwindCSS + vanilla JS (entries: `resources/css/app.css`, `resources/js/app.js`). You stated you will not use Vite.
- JS deps: axios is present; no explicit frontend framework declared (React/Vue not installed)
- API spec present: `application.yaml` (WSO2 Application Management REST API)

Important files & folders
-------------------------
- `app/` — Laravel application code and controllers
- `routes/web.php` — HTTP routes (OAuth redirect/callback and admin routes already present)
- `resources/js/` — frontend JS; current entries include `app.js` and `bootstrap.js`
- `resources/css/` — Tailwind entry files
- `resources/views/` — blade templates (server-rendered pages)
- `docs/` — documentation (contains `applications-wso2.md` and this file)
- `application.yaml` — OpenAPI for WSO2 Application Management

Stack and build commands
------------------------
- Backend (PHP / Laravel)
  - PHP: ^8.2
  - Laravel: ^12
  - Useful composer scripts (from `composer.json`):

    - `composer run-script dev` (project convenience script; note it references `npm run dev` in the current `composer.json` but you indicated you will not use Vite — you can still run the Laravel parts with `php artisan serve` directly)
    - `composer test` runs PHPUnit via `@php artisan test`

- Frontend / assets (no Vite)
  - Primary dev flow: use server-rendered Blade pages and include simple JS modules under `resources/js/` (no bundler required). Example: add `resources/js/apps/applications/main.js` and include it from `resources/views/admin/applications.blade.php` with a `<script type="module" src="/resources/js/apps/applications/main.js"></script>` (or compile to `public/js/` during deploy).
  - Optional: if you need minimal building (concatenation/minification), use a lightweight tool like `esbuild` or a simple npm script. This is optional — development can work without a bundler by using unbundled modules or serving static assets from `public/`.

Auth & current integration pattern
---------------------------------
- The app currently performs OAuth redirects and callback handling server-side:
  - `AuthController::redirectToIdentityServer` and `AuthController::handleCallback` exist in `routes/web.php`.
  - Access tokens are stored in Laravel session (see `validate-token` route which calls WSO2 `/oauth2/userinfo`).
- Implication: the project already implements a backend-for-frontend (BFF) pattern. This is secure for admin operations because secrets and tokens are held server-side.

Where to add the Applications page (recommended integration points)
---------------------------------------------------------------
Recommended pattern (BFF / proxy): keep sensitive admin operations in the backend and expose safe endpoints for the UI.

- Backend service layer
  - Add `app/Services/WSO2ApplicationService.php` to centralize calls to WSO2 APIs (use `Http::withHeaders(['Authorization' => 'Bearer ' . session('access_token')])`).

- Admin controller
  - Add `app/Http/Controllers/Admin/ApplicationsController.php` exposing RESTful endpoints (protected by `auth` and `wso2.role` middleware) that proxy to the service.

-- Frontend
  - Minimal approach: create a small client under `resources/js/apps/applications/` and add a Blade page `resources/views/admin/applications.blade.php` that includes the script(s). Keep logic simple with Axios and optional small UI libraries.
  - If you prefer a full SPA (React/Vue) later, you can add framework deps and a bundler at that time, but for now we will keep the stack bundler-free per your preference.

Suggested API endpoints for the BFF (examples)
----------------------------------------------
- GET `/admin/api/applications?limit=&offset=&filter=` — proxy to WSO2 `GET /applications`
- GET `/admin/api/applications/{id}` — proxy to WSO2 `GET /applications/{applicationId}`
- POST `/admin/api/applications` — create application (proxy to WSO2 `POST /applications`)
- PATCH `/admin/api/applications/{id}` — patch application (proxy to WSO2 `PATCH /applications/{applicationId}`)
- POST `/admin/api/applications/import` — file import (proxy to `POST /applications/import`)
- POST `/admin/api/applications/{id}/inbound/oidc/regenerate-secret` — regenerate secret (proxy to WSO2). Keep this behind admin middleware and audit logs.

Security & UX notes
-------------------
- Keep admin endpoints server-side to avoid exposing admin scopes and secrets to the browser.
- Display regenerated client secrets only once to the user. Do not persist them in client storage.
- Use server-side pagination and filtering to avoid large payloads.
- Ensure WSO2 allows your backend origin (CORS is less of an issue when backend proxies requests).

Development workflow (local)
---------------------------
1. Start Laravel server + Vite (dev):

```powershell
# From repo root (Windows / pwsh)
composer run-script dev
```

2. Run tests:

```powershell
composer test
```

3. Lint / code style: use `php artisan pint` if configured.

Next steps (to prepare the Applications page)
-------------------------------------------
1. I will scaffold `app/Services/WSO2ApplicationService.php` with basic list/get/create/patch helpers.
2. Add `app/Http/Controllers/Admin/ApplicationsController.php` with proxy routes protected by `wso2.role` middleware.
3. Create a minimal frontend mount page and a small Vite entry file under `resources/js/apps/applications/` that lists applications (calls the BFF endpoints).

If you prefer a direct client-side integration (SPA calling WSO2 directly), tell me and I will scaffold a PKCE-based client instead; otherwise I will implement the BFF pattern.

Contact & references
--------------------
- WSO2 API spec: `application.yaml` in repo root
- UI reference: screenshots in the conversation (Applications list and New Application templates)
