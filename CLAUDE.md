# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

**Start everything (recommended):**
```bash
composer dev
```
This runs Laravel server, queue worker, Pail log viewer, and Vite in parallel via `concurrently`.

**Individually:**
```bash
php artisan serve        # Laravel on port 8000
npm run dev              # Vite (hot reload)
npm run build            # Production assets
php artisan test         # Run tests
vendor/bin/pint          # PHP code style (Laravel Pint)
php artisan api:test-connection  # Test NestJS API credentials
```

**Test a single file:**
```bash
php artisan test tests/Feature/ExampleTest.php
```

## Architecture

This is a **Laravel 12 admin dashboard** that acts as a thin management interface in front of an external **NestJS API** (the DodoVroum backend). Laravel owns no business data locally — all entities (residences, vehicles, bookings, users, offers) live in the NestJS API and are fetched on every request.

### Stack
- **Laravel 12** — routing, auth session, middleware, controllers
- **Inertia.js** — bridges Laravel controllers to Vue pages (no separate API for the frontend)
- **Vue 3 + TypeScript** — single entry point at `resources/js/admin/AppAdmin.ts`
- **Tailwind CSS** — utility-first styling

### Two user roles with separate route prefixes

| Role | Middleware | Prefix | Controller namespace |
|------|-----------|--------|---------------------|
| Admin | `admin` | `/admin` | `App\Http\Controllers\Admin\` |
| Owner | `owner` | `/owner` | `App\Http\Controllers\Owner\` |

The root `/` redirects based on `$user->isAdmin()` / `$user->isOwner()` (backed by the `role` column from the NestJS API schema — values `ADMIN` and `PROPRIETAIRE`).

### Authentication flow

Login credentials are validated **against the NestJS API** (`/api/auth/login`). On success, the JWT is stored in the Laravel session under `nest_jwt_token`, and user data is stored under `api_user`. Laravel then uses a custom `ApiUserProvider` (`app/Auth/ApiUserProvider.php`) that reads these session keys instead of a local database.

`DodoVroumApiService::resolveRequestToken()` resolves which token to use for outbound API calls in this priority order:
1. Session JWT (`nest_jwt_token` or `api_token`)
2. `ApiUser::getApiToken()` from the authenticated user
3. Admin credentials from `.env` (with 55-minute cache via `dodovroum_api_token_<md5(email)>`)

On 401, the service only attempts token refresh if the request was made using admin credentials (not a user's session JWT).

### `DodoVroumApiService`

Central service at `app/Services/DodoVroumApiService.php`. All HTTP calls to NestJS go through its `get()`, `post()`, `patch()`, `put()`, `delete()` protected methods.

**Important API response quirk:** The NestJS API returns either `{ success: true, data: [...] }` or a bare array/object. The service normalises this. It also handles pagination (up to 10 pages of 100 items) for list endpoints like residences and vehicles.

**Vehicle field name inconsistency:** The NestJS API uses both `voiture` and `vehicle` for the vehicle field on bookings and combo offers. Always check both when traversing these structures.

### Owner data isolation

**Critical invariant:** Owner pages must only show data belonging to the authenticated owner. The `BookingOwnerScopeService` (`app/Services/BookingOwnerScopeService.php`) centralises owner-ID resolution for bookings, walking through nested structures (`residence.proprietaireId`, `vehicle.proprietaireId`, `voiture.proprietaireId`, `offer.*`, and top-level IDs) with API fallback lookups if no inline owner is present.

The owner's ID comes from `Auth::user()->id` (the NestJS UUID stored in session).

### Frontend page routing (Inertia)

`AppAdmin.ts` maps Inertia page names to Vue files under `resources/js/admin/Pages/`. Admin pages are at the top level (e.g., `Dashboard`, `Residences/Index`); owner pages are under `Owner/` (e.g., `Owner/Dashboard`, `Owner/Residences/Index`). The default layout is `AdminLayout.vue` for all pages — override with `page.default.layout` to use `OwnerLayout.vue`.

### Required `.env` variables

```env
DODOVROUM_API_URL=http://localhost:3000/api   # NestJS API base URL
DODOVROUM_ADMIN_EMAIL=admin@dodovroum.com
DODOVROUM_ADMIN_PASSWORD=admin123
```

In production, set `APP_ENV=production` and `APP_URL=https://…` — HTTPS is forced and cookies become secure automatically.
