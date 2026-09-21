# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## CRITICAL: Mandatory rules for every feature

Before writing code, read [doc/agents/PROJECT_MASTER_GUIDE.md](doc/agents/PROJECT_MASTER_GUIDE.md) — the Master Doc covering content hierarchy (Pages / Posts / Blocks), the dynamic slug + `slug_aliases` 301 lifecycle, dynamic SEO fallback order, and the Laravel ↔ Next.js API contract (`/api/web/bootstrap`, `/api/web/pages/{slug}`).

Every new feature must satisfy:
- Controller stays at orchestration level only (no inline `validate()`, no business logic)
- Every endpoint with input gets its own Form Request class (`Store*Request`, `Update*Request`, `Reorder*Request`, etc.)
- Business logic goes into a Service (`app/Services/`)
- Data access that repeats across controllers or needs complex queries goes into a Repository (`app/Repositories/`)
- Use `Model::query()` + eager loading; avoid `DB::` and raw SQL
- **Always run `./vendor/bin/pint --dirty --format agent` after completing any PHP work** — this is mandatory, not optional

## Commands

```bash
# Initial setup (install deps, generate key, migrate, build assets)
composer setup

# Start development servers (Laravel + queue + Vite, concurrently)
composer dev

# Run all tests
composer test

# Run a single test file
php artisan test tests/Feature/ExampleTest.php

# Code style (Laravel Pint)
./vendor/bin/pint

# Database reset
php artisan migrate:fresh --seed
```

## Architecture

### Request Flow
`Route → Controller → Service → Repository → Eloquent Model`

Controllers are thin (~65 lines); all business logic lives in Services. Services inject Repositories via constructor DI. Repositories implement interfaces bound in `AppServiceProvider::register()`.

### Route Groups (`bootstrap/app.php`)
- `routes/admin.php` — middleware `web + auth:admin`, name prefix `admin.`
- `routes/auth.php` — middleware `web` (login/logout)
- `routes/blog.php` — middleware `web` (website public routes, currently stub)
- `routes/web.php` — locale switcher (`/lang/{locale}`)
- `routes/api.php` — API routes

### Authentication Guards (`config/auth.php`)
Two separate guards:
- `web` → `User` model (public/website users)
- `admin` → `AdminUser` model (admin panel users)

Admin panel is protected by `auth:admin` middleware; login redirects guests to `/login`.

### Repository / Service Layer
Interfaces in `app/Repositories/Contracts/`, Eloquent implementations in `app/Repositories/Eloquent/`. Bindings:

| Interface | Implementation |
|-----------|---------------|
| `PageRepositoryInterface` | `PageRepository` |
| `PostRepositoryInterface` | `PostRepository` |
| `ProductRepositoryInterface` | `ProductRepository` |
| `BlockTypeRepositoryInterface` | `BlockTypeRepository` |
| `LanguageRepositoryInterface` | `LanguageRepository` |
| `WebsitePageRepositoryInterface` | `EloquentWebsitePageRepository` |
| `WebsitePostRepositoryInterface` | `EloquentWebsitePostRepository` |
| `WebsiteMenuRepositoryInterface` | `EloquentWebsiteMenuRepository` |

### Frontend Split

There are three separate frontend surfaces:

| Surface | Location | Tech |
|---------|----------|------|
| Admin panel | `resources/views/admin/` | Blade templates (server-rendered) |
| Website (Laravel-coupled) | `resources/views/website/inertia.blade.php` | Inertia.js + Vue; shared props (`locale`, `appName`) via `HandleInertiaRequests` |
| Website (standalone) | `frontend/newhome/` | **Next.js 16** (App Router, SSG), React 19, TypeScript, Bootstrap 5 + Tailwind v4 |

#### `frontend/newhome/` — Next.js website
- **Stack**: Next.js 16 App Router (SSG), React 19, TypeScript, React-Bootstrap + Bootstrap 5, Tailwind CSS v4, `motion/react`, `lucide-react`
- **Commands** (run from `frontend/newhome/`):
  ```bash
  npm run dev      # dev server at http://localhost:3000
  npm run build    # production SSG build
  npm run start    # serve production build
  ```
- **Pattern**: every route has a server wrapper (`page.tsx` with metadata) + a client UI component (`*Page.tsx` with `'use client'`). Dynamic routes export `generateStaticParams` and `generateMetadata`; data is passed as props — client components do NOT call `useParams`.
- **Data**: all content is hardcoded in `src/lib/data.ts` (products, services, projects). Sitemap and static params derive from these arrays automatically.
- **Styling**: CSS custom properties in `globals.css` — `--primary-color: #0F2E47` (dark blue), `--accent-color: #CC7A50` (terracotta). Bootstrap handles components; Tailwind provides utility classes — do not mix their grid systems.
- **This project is independent of Laravel** — it has its own `CLAUDE.md` at `frontend/newhome/CLAUDE.md`.

### Block System
Content blocks are the core CMS mechanism:
- `BlockTypeDefinition` — admin-defined block types; stores `schema` (JSON field definitions) and `default_data` as JSON
- `block_types` — JSON array on `Page` and `Post` models listing which block type slugs are attached
- `PageContentBlock` / `PostContentBlock` — per-locale block data rows
- `BlockNormalizationService` — processes raw form input (including file uploads) against a `BlockTypeDefinition` schema into normalized block data
- Block types can be scoped to `'page'` or `'post'` via repository filter `getEnabledForScope()`

### Key Models & Schema Conventions
- Translation tables use `locale varchar(8)` column (not a `language_id` FK)
- `pages.template` is a `varchar` slug referencing `page_templates.slug` (not a FK); accessed via `Page::templateRef()` — `belongsTo(PageTemplate::class, 'template', 'slug')`
- `posts`: `feature_image`, `block_types` (JSON), `sort_order`, `category`
- `media`: `uuid`, `user_id`, `path`, `mime_type`, `type`, `disk`; supports soft deletes; uses `mediables` pivot table via `HasMedia` trait
- `PostTranslation` has timestamps enabled

### Services Summary
| Service | Responsibility |
|---------|---------------|
| `PageService` | View data + CRUD orchestration for pages |
| `PostService` | View data + CRUD orchestration for posts |
| `BlockNormalizationService` | Normalize block form data; handle media uploads per field type |
| `BlockTypeService` | CRUD for block type definitions |
| `LanguageService` | Active locale management, sort order, default |
| `SlugService` | Slug generation and alias tracking |
| `MediaUploadService` | File upload, image conversion |
| `MediaConversionService` | Image resizing via `intervention/image` |
| `AdminActivityService` | Admin activity logging |
| `WebsitePostService` | Public post queries for website front-end |

### Localization
Active locales come from the `languages` DB table (managed in admin). Fallback locales are defined in `config/cms.php`. Locale is stored in session; switched via `GET /lang/{locale}`.

## graphify

This project has a knowledge graph at graphify-out/ with god nodes, community structure, and cross-file relationships.

Rules:
- For codebase questions, first run `graphify query "<question>"` when graphify-out/graph.json exists. Use `graphify path "<A>" "<B>"` for relationships and `graphify explain "<concept>"` for focused concepts. These return a scoped subgraph, usually much smaller than GRAPH_REPORT.md or raw grep output.
- If graphify-out/wiki/index.md exists, use it for broad navigation instead of raw source browsing.
- Read graphify-out/GRAPH_REPORT.md only for broad architecture review or when query/path/explain do not surface enough context.
- After modifying code, run `graphify update .` to keep the graph current (AST-only, no API cost).
