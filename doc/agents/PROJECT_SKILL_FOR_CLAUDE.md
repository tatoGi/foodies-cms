---
name: laravel-cms-block-project
description: >
  Complete skill for working on this Laravel + Next.js CMS project.
  Use this whenever adding features, fixing bugs, creating controllers,
  services, repositories, blocks, API endpoints, or Next.js frontend components.
---

# Project Skill for Claude — Laravel CMS Block

This document is the single reference Claude must read before doing **any** work in this project. It consolidates all rules from the markdown docs found in the repo.

---

## 1. What This Project Is

A **full-stack CMS** with two separate surfaces:

| Layer | Tech | Purpose |
|---|---|---|
| Backend | Laravel 12 (PHP) | Admin panel, CMS data, REST API |
| Frontend | Next.js 16 (SSG) | Public-facing website (`frontend/newhome/`) |

The Laravel backend serves content via API (`/api/web/*`). The Next.js frontend consumes that API for SSG builds. They are **independent** — each has its own `CLAUDE.md`.

Also includes: **BOG Payment** integration (Georgian bank gateway) with mock mode for local testing.

---

## 2. Core Architecture Rules (NEVER Violate These)

### Laravel Request Flow
```
Route → Controller → Service → Repository → Eloquent Model
```

- **Controllers** must stay "thin" (~65 lines max). No inline `validate()`, no business logic.
- **Every endpoint with input** gets its own Form Request class: `StoreXRequest`, `UpdateXRequest`, `ReorderXRequest`, etc.
- **Business logic** lives exclusively in `app/Services/`.
- **Data access** that repeats or is complex goes into `app/Repositories/`.
- Use `Model::query()` + eager loading. Avoid `DB::` and raw SQL.

### After Any PHP Work — Mandatory
```bash
./vendor/bin/pint --dirty --format agent
```
This is not optional. Always run it.

---

## 3. Content Hierarchy (The CMS Model)

```
Pages  (e.g. Home, Services, Projects, Contact)
  └── Posts  (e.g. Service Detail, Project Detail — dynamic "listing items")
        └── Blocks  (reusable content sections embedded in any Page or Post)
```

- `BlockTypeDefinition` — admin-defined block schemas (JSON field definitions + default data)
- `PageContentBlock` / `PostContentBlock` — per-locale block data rows
- `BlockNormalizationService` — normalizes raw form input (including file uploads) against schema

---

## 4. Slug & SEO — Always Dynamic

- Every translation has its own slug.
- When a slug changes → old slug is auto-saved to `slug_aliases` table → `301 Redirect` issued.
- Every Page, Post, and Product has dynamic SEO fields: Meta Title, Meta Description, Keywords, Canonical.
- SEO fallback priority: **requested locale → default locale → first available translation**.

---

## 5. Repository / Service Bindings

Interfaces live in `app/Repositories/Contracts/`, implementations in `app/Repositories/Eloquent/`. Bindings are registered in `AppServiceProvider::register()`.

| Interface | Implementation |
|---|---|
| `PageRepositoryInterface` | `PageRepository` |
| `PostRepositoryInterface` | `PostRepository` |
| `ProductRepositoryInterface` | `ProductRepository` |
| `BlockTypeRepositoryInterface` | `BlockTypeRepository` |
| `LanguageRepositoryInterface` | `LanguageRepository` |
| `WebsitePageRepositoryInterface` | `EloquentWebsitePageRepository` |
| `WebsitePostRepositoryInterface` | `EloquentWebsitePostRepository` |
| `WebsiteMenuRepositoryInterface` | `EloquentWebsiteMenuRepository` |

---

## 6. Route Groups

| File | Middleware | Prefix |
|---|---|---|
| `routes/admin.php` | `web + auth:admin` | `admin.` |
| `routes/auth.php` | `web` | — |
| `routes/blog.php` | `web` | — |
| `routes/web.php` | — | locale switcher `/lang/{locale}` |
| `routes/api.php` | — | API routes |

---

## 7. Authentication Guards

- `web` guard → `User` model (public website users)
- `admin` guard → `AdminUser` model (admin panel users)

Admin panel is protected by `auth:admin` middleware.

---

## 8. Key Schema Conventions

- Translation tables use `locale varchar(8)` — **NOT** a `language_id` FK.
- `pages.template` is a `varchar` slug referencing `page_templates.slug` (not a FK). Accessed via `Page::templateRef()` — `belongsTo(PageTemplate::class, 'template', 'slug')`.
- `posts`: columns include `feature_image`, `block_types` (JSON), `sort_order`, `category`.
- `media`: `uuid`, `user_id`, `path`, `mime_type`, `type`, `disk`; soft deletes; `mediables` pivot via `HasMedia` trait.
- `PostTranslation` has timestamps enabled.

---

## 9. API Endpoints (Laravel → Next.js)

| Endpoint | Returns |
|---|---|
| `GET /api/web/bootstrap` | Global data: nav menu, logos, language tree |
| `GET /api/web/pages/{slug}` | Full page payload: blocks, SEO, relations |

`BlockNormalizationService` ensures blocks and media are delivered in the correct format for Next.js.

---

## 10. Services Reference

| Service | What It Does |
|---|---|
| `PageService` | View data + CRUD for pages |
| `PostService` | View data + CRUD for posts |
| `BlockNormalizationService` | Normalize block form data + handle media uploads |
| `BlockTypeService` | CRUD for block type definitions |
| `LanguageService` | Active locale management, sort order, default |
| `SlugService` | Slug generation + alias tracking |
| `MediaUploadService` | File upload + image conversion |
| `MediaConversionService` | Image resizing via `intervention/image` |
| `AdminActivityService` | Admin activity logging |
| `WebsitePostService` | Public post queries for website front-end |

---

## 11. Next.js Frontend (`frontend/newhome/`)

**Site:** NewHome.ge — Georgian-language furniture & lighting e-commerce.
**Stack:** Next.js 16 App Router (SSG), React 19, TypeScript, Bootstrap 5 + Tailwind v4, `motion/react`, `lucide-react`.

### Page Pattern (every route)
```tsx
// page.tsx  ← SERVER component — exports metadata
export const metadata: Metadata = { title: '...' };
export default function Page() { return <AboutPage />; }

// AboutPage.tsx  ← CLIENT component — actual UI
'use client';
```

Dynamic routes additionally export `generateStaticParams` and `generateMetadata`. Client components do **NOT** call `useParams` — data is passed as props from the server wrapper.

### Adding New Content
Only edit `src/lib/data.ts`. Sitemap, static params, and metadata all derive from these arrays automatically.

### Styling
- `--primary-color: #0F2E47` (dark blue)
- `--accent-color: #CC7A50` (terracotta)
- Bootstrap handles components; Tailwind provides utilities. **Do not mix their grid systems.**
- Font: `Noto Serif Georgian` (all UI text is in Georgian `ka`).

### Frontend Rules
- Prefer Server Components; use Client Components only when browser interactivity is needed.
- No `middleware.ts`. Use proxy patterns if edge routing is needed.
- Keep browser-only code out of Server Components.
- Multiple `main_banner` blocks → merge into one slider. No slides → no hero slider rendered.
- `items_grid` is the frontend alias for the services/items block.

### Frontend Commands (from `frontend/newhome/`)
```bash
npm run dev      # dev server at http://localhost:3000
npm run build    # production SSG build
npm run start    # serve production build
npm run lint     # lint check
```

---

## 12. BOG Payment Integration

Georgian bank (BOG) checkout flow via `tatogi/bog-payment-laravel`.

**Mock mode** (local dev): set `BOG_MOCK_MODE=true` in `.env` — no real credentials needed. Mock gateway handles the full callback/status flow locally.

**Key routes:**
- `GET /checkout`, `POST /checkout/bog/start`, `GET /checkout/bog/success`, `GET /checkout/bog/fail`
- `GET /profile/cards`, `DELETE /profile/cards/{card}`, `PATCH /profile/cards/{card}/default`

---

## 13. Missing Files — What Still Needs to Be Done

The root `CLAUDE.md` references these files that **do not yet exist** in `doc/agents/`:

| File | Purpose | Status |
|---|---|---|
| `doc/agents/AGENTS.md` | Laravel Boost guidelines, PHP conventions, Laravel 12 structure rules | ❌ Missing |
| `doc/agents/ARCHITECTURE_ENFORCEMENT.md` | Mandatory thin controller checklist, Form Request rules, Service/Repository enforcement | ❌ Missing |
| `doc/agents/PROJECT_POLICY.md` | Spatie package guidance, preferred documentation sources | ❌ Missing |

**These should be created** to complete the project documentation. Until they exist, follow the rules defined in `CLAUDE.md` and this skill document.

---

## 14. Checklist Before Submitting Any Feature

- [ ] Controller is thin — no inline `validate()`, no business logic
- [ ] Input handled by a dedicated Form Request class
- [ ] Business logic lives in a Service (`app/Services/`)
- [ ] Repeated or complex data access uses a Repository (`app/Repositories/`)
- [ ] Uses `Model::query()` + eager loading (no `DB::` / raw SQL)
- [ ] Slugs and SEO are dynamic (not hardcoded)
- [ ] Repository interface bound in `AppServiceProvider`
- [ ] Ran `./vendor/bin/pint --dirty --format agent`

---

## 15. Commands Reference

```bash
# Laravel setup
composer setup          # install deps, generate key, migrate, build assets
composer dev            # start Laravel + queue + Vite concurrently
composer test           # run all tests
php artisan test tests/Feature/ExampleTest.php   # single test
./vendor/bin/pint       # code style (always run after PHP changes)
php artisan migrate:fresh --seed   # reset database

# Next.js (from frontend/newhome/)
npm run dev
npm run build
npm run start
npm run lint
```
