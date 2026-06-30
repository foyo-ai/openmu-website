# muss6.org Player Portal — Implementation Plan

## Context

We deploy a MUnique **OpenMU** (.NET) MU Online server on a DigitalOcean droplet. Players can
already connect and play, but the **only way to create an account is through the OpenMU admin
panel** (`admin.muss6.org`) — not something we can hand to friends/players. We want a
**player-facing website at the root domain `muss6.org`** where players self-register and manage
their accounts, characters, and activities, and which we can **grow via SEO**.

We are **extending the existing Laravel app** in this repo (`openmu-website`) rather than building
new. It already solves the hard part: it talks **directly to the OpenMU PostgreSQL database**
(`openmu`, schemas `data.*` / `config.*`) and already does **bcrypt-compatible registration +
login** and **character stat-point allocation**. Server-rendered Blade is also the right tool for
SEO. The gaps are: player self-service, growth/SEO content, i18n, theming, and a production deploy.

## Goals / Scope (v1, "must-haves first")

- **Bilingual**: Vietnamese **default** (root URLs), English under `/en/...`; switcher + `hreflang`.
- **Landing / server-info**: rates, features, client download links, Discord; live online count from OpenMU `/api/status`.
- **Account self-service**: change password / email / security code (current-password confirmation).
- **Character management** (manage existing only — **NO web character creation**, that stays in-game):
  view, rename, delete, reset, clear-PK, unstick-to-safezone, allocate stats. **Offline characters only.**
- **Rankings/leaderboards**: resets, level, kills, (master), guild. Cached.
- **News/announcements**: GM-posted, with a GM-only editor inside this site. Stored in a NEW app-owned table.
- **SEO mechanics**: meta/OpenGraph tags, `sitemap.xml`, `robots.txt`, clean localized slugs, JSON-LD.

**Phase 2 (not v1):** vote-for-server with in-game rewards, events calendar.

## Architecture

One Laravel app, server-rendered Blade, two strictly-separated data domains on the same Postgres:

- **Game data** (`data.*`, `config.*`) — read/write OpenMU tables exactly as existing code does. Never invent columns here.
- **Website data** (`public.*`, our own migrations) — `news` (+ later `votes`/`events`). Keeps the game DB clean.

## Key facts established during exploration

- Models hardcode OpenMU table names and use `public $timestamps = false` (OpenMU tables have no `created_at`).
- `LoginController::login()` is custom: `User::where('LoginName',…)` + `Hash::check($pw, $user->PasswordHash)` + `auth()->loginUsingId()`. `User` → `data.Account`, PK `Id` (uuid string).
- `RegisterController::create()` writes a real account: `Hash::make()` for `PasswordHash`, creates a vault `ItemStorage`, `State=0`. Validation: `LoginName max:10`, `SecurityCode` exactly 6 chars.
- Stat writes template = `CharacterPointsService::update()`: `DB::beginTransaction/commit/rollBack`. Attribute UUIDs in `ConfigAttributeDefinition` (`RESET_ID`, `LEVEL_ID`, `BASE_ENERGY_ID`, `BASE_STRENGHT_ID`).
- Ownership gate = `App\Http\Middleware\VerifyCharacterAccountOwner` (alias `verifyCharacterAccountOwner`).
- `data.Account.State` enum: Normal=0, Spectator=1, **GameMaster=2**, GameMasterInvisible=3, Banned=4, TempBanned=5. GM gate = `State ∈ {2,3}` (NOT `Character.CharacterStatus`).
- `data.Character.Name` has a UNIQUE index → rename must check uniqueness.
- Online check: OpenMU `GET /api/is-online/{LoginName}` (keys on **LoginName**, not character). Fail-closed.
- Deploy target: `mu-server/openmu/deploy/vps/docker-compose.yml`, project `openmu`, Postgres internal-only as `database:5432`, admin app `openmu-startup:8080`, optional `nginx`+`certbot` under `--profile https` already wired for `admin.muss6.org`. Website must join this project/network.
- Frontend is inconsistent: `package.json` declares Bootstrap 5.2.3 via Vite, but `layouts/app.blade.php`/`welcome.blade.php` load **Bootstrap 4.3.1 from CDN** + jquery-slim. No `lang/` dir.

## Build order (sequenced by risk — safe & verifiable first, destructive last)

### Phase 0 — Foundation (no game-DB writes)
- Themed BS5 layout: remove BS4 CDN + jquery-slim from `layouts/app.blade.php` & `welcome.blade.php`; rely on `@vite`. Dark/gaming skin via `resources/sass/_variables.scss` overrides. Navbar + language switcher.
- New landing/server-info page (replace default `welcome.blade.php`): rates, features, downloads, Discord, optional live online count from `/api/status`.
- i18n: `mcamara/laravel-localization`, `vi` default + `hideDefaultLocaleInURL`, localized route group, `lang/vi`+`lang/en`, `hreflang` alternates in `<head>`. Register package middleware in `Kernel.php`.

### Phase 1 — Read-only game data (low risk)
- Rankings (`RankingController` + `RankingService`): resets (`RESET_ID`), level (`LEVEL_ID`), kills (`Character.PlayerKillCount`), guild (`guild.Guild.Score`). One query per board, joined to `config.CharacterClass`. **Cache results.**
- SEO: `sitemap.xml` (both locales), real `public/robots.txt`, meta/OG block in layout, JSON-LD (`NewsArticle`, `ItemList`).

### Phase 2 — News + GM editor (app-owned table only)
- Migration: `public.news` (bilingual columns, slug, is_published, published_at, author_account_id). **Delete stock Laravel migrations first** (see Risks).
- Public localized index/show; `Admin\NewsController` CRUD behind new `IsGameMaster` middleware (`State ∈ {2,3}`).

### Phase 3 — Account self-service (single-row game-DB writes)
- `AccountController`: change password (`Hash::make`, reuse pattern), email (`unique` mirror), security code (6 chars). Require current-password confirmation (`Hash::check`).

### Phase 4 — Character actions (ascending risk — **needs a safe test DB**)
- ClearPK / unstick / reset / rename: single-row updates / stat upserts, behind ownership + **offline check** + security-code confirmation, wrapped in transactions. Use **upsert** for stat rows (may not exist); cast `StatAttribute.Value` (float) to int.
- **Delete (HIGH risk, built LAST):** derive child-FK list from live schema (`pg_constraint` query), delete NoAction children + orphaned inventory `ItemStorage`/`Item` in one transaction. **Back up + test against a restored copy before prod.** Offer hard-delete vs soft-delete (`CharacterStatus=Banned` + rename to free unique name).

### Phase 5 — Production deploy
- Replace dev Dockerfile (`php:8.2-cli` + `artisan serve`) with **php-fpm** + Vite asset build + `config/route/view:cache`.
- Add `web` service to `deploy/vps` compose (shared network → `database:5432`, `openmu-startup:8080`).
- Add nginx vhost for `muss6.org`/`www`, extend `certbot` domains. Production `.env` (`APP_ENV=production`, `APP_DEBUG=false`, fresh `APP_KEY`).
- Run **only** the `news` migration.

## i18n & SEO specifics
- URL: `vi` at root (`muss6.org/...`), `en` at `/en/...`; `hideDefaultLocaleInURL=true`, `useAcceptLanguageHeader=true`.
- `hreflang` per page via `LaravelLocalization::getLocalizedURL($locale)`; canonical self-URL.
- News stores `title_vi/en`, `body_vi/en`; serve by `app()->getLocale()`. Vietnamese-aware unique slugs.

## Verification (the canonical loop)
- Register on web → **log into the game client** with that account → it works.
- Change password on web → game login with the new password works.
- Allocate stats / rankings on web → matches a direct SQL spot-check / shows in-game.
- Post news as a GM account → visible on the public localized news page + in sitemap; normal account gets 403.
- Character actions tested **against a restored DB copy first**; delete leaves no orphan child rows; name reusable.
- `https://muss6.org` serves a valid Let's Encrypt cert; `admin.muss6.org` still works; web container reaches `database:5432` + `openmu-startup:8080`.

## Risks & gotchas
- **bcrypt cross-compat**: keep using the `Hash` facade/default driver (proven compatible); never change cost/driver without testing a game login; never string-compare hashes.
- **Online writes (TOCTOU)**: online check is necessary not sufficient; **fail closed** if API unreachable/online. Most dangerous for delete/reset.
- **`is-online` keys on LoginName**, and only covers running game servers.
- **Delete FK/orphans**: derive children from live schema, one transaction, clean orphan inventory, back up + test on a copy. Highest-risk feature.
- **Hardcoded UUIDs/columns** can break on OpenMU upgrades — centralize + smoke-test resolving each UUID on deploy.
- **Two writers**: restrict web writes to offline characters; cache reads.
- **Stock Laravel migrations** must be deleted before any `php artisan migrate` (they pollute the game DB with unused auth tables). Keep only `news`.
- **Never ship the dev `.env`** to production.

## Test/run environment (OPEN — settle before Phase 3+)
No local PHP/Composer; Docker Desktop currently not running; no local Postgres. The only locally
verifiable build step is the Vite/SCSS asset build (Node available). Before destructive features,
we need a **disposable OpenMU Postgres** (the game's `docker-compose` can stand one up against a
throwaway volume) to develop/test against — **never prod**.
