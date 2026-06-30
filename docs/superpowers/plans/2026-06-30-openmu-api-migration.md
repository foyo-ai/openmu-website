# OpenMU API Migration — Implementation Plan

> **For agentic workers:** implement task-by-task; each task ends with an independently verifiable deliverable. Verification is integration-style (curl against the local all-in-one game server, and E2E web↔game) because the API wires into the live game framework — unit-TDD of the controller is impractical.

**Goal:** Move all game-data access out of the website and behind an authenticated `api/v1` on the OpenMU game server, so the website never touches `data.*`/`config.*`, reusing real game logic and serving live character data.

**Architecture:** New `api/v1` controller(s) in the OpenMU AdminPanel host (the `openmu-startup` process the website already reaches at `:8080`), guarded by an `X-API-Key`. Online reads come from the live `Player` (memory); offline reads/writes go through `IPersistenceContextProvider`. The Laravel site calls the API via a Guzzle client and keeps only its own SQLite (news + framework). Writes are rejected while the character's account is online.

**Tech Stack:** .NET 10 / ASP.NET Core (OpenMU AdminPanel), C#; Laravel 10 / PHP 8.2; PostgreSQL (game, server-side only); SQLite (website app data).

## Global Constraints
- API key header: `X-API-Key`; server secret from config `OpenMU:WebApiKey` or env `WEB_API_KEY`; website env `OPENMU_API_KEY` + `OPENMU_API_URL` (e.g. `http://openmu-startup:8080`).
- Server changes ship via `foyo-ai/openmu` CI (push `production` → rebuild + restart `openmu-startup`). Website via `foyo-ai/openmu-website` CI.
- Writes are **offline-only** → `409` if online. Errors are JSON `{ "error": <code>, "message": <text> }`. Auth failure → `401`. Ownership → `403`. Missing → `404`. Validation → `422`.
- The website passes only the **session account's** login for user-scoped operations; the API re-checks ownership (character ∈ account), security code (delete), and offline state.
- Reuse OpenMU logic: `ResetConfiguration`/`ResetCharacterAction`, `IncreaseStatsAction`/`CanIncreaseStats`, `DeleteCharacterAction` checks, `AccountService`/`AuthenticateAsync`, BCrypt.
- Local test loop: all-in-one stack (`mu-server/openmu/deploy/all-in-one`) on Postgres `:5433`; the game server (`openmu-startup`) exposes the AdminPanel API on its `8080`.

---

## Component 1 — Server: api/v1 foundation + read endpoints

### Task 1.1: API-key auth + controller skeleton
**Files (in `mu-server/openmu`):**
- Create: `src/Web/AdminPanel/API/WebApiController.cs` — `[Route("api/v1")]`, base for the website API.
- Create: `src/Web/AdminPanel/API/ApiKeyAttribute.cs` — action filter that 401s unless `X-API-Key` matches `IConfiguration["OpenMU:WebApiKey"]` (fallback env `WEB_API_KEY`). Empty/unset key → 401 (fail closed).
- Reference pattern: `src/Web/AdminPanel/API/ServerController.cs` (DI of `IDictionary<int,IGameServer>`), `src/Web/AdminPanel/Startup.cs` (`AddControllers`/`MapControllers`).

**Steps:**
- [ ] Add the `[ApiKey]` filter reading the configured secret; apply at controller level.
- [ ] Inject `IDictionary<int,IGameServer>`, `IPersistenceContextProvider`, `IDataSource<GameConfiguration>`.
- [ ] Add `GET /api/v1/ping` returning `{ "ok": true }` (behind the key) to smoke-test auth + routing.
- [ ] Verify: build locally; `docker compose ... up -d openmu-startup`; `curl -H "X-API-Key: <key>" http://localhost:<startup8080>/api/v1/ping` → `{"ok":true}`; without/with wrong key → 401.
- [ ] Commit.

### Task 1.2: Online/offline lookup helper
**Files:**
- Create: `src/Web/AdminPanel/API/GameDataAccessor.cs` — helper: `FindOnlinePlayer(login|character)` via `server.Context.GetPlayersAsync()`; `OpenPlayerContext()` via `_persistenceProvider.CreateNewPlayerContext(config)`; map a `Player` (live) and a `Character` (entity) to a common `CharacterDto`.
**Interfaces produced:** `CharacterDto { name, class, level, resets, masterLevel, points, masterPoints, kills, status, map, x, y, online }`; `AccountDto { id, login, email, state, registrationDate }`.

**Steps:**
- [ ] Implement live mapping from `Player.Attributes[Stats.*]` (Level, Resets, totals, LevelUpPoints), `SelectedCharacter`, position/map, online=true.
- [ ] Implement offline mapping from the loaded `Character` entity (level/reset from its `StatAttributes` by definition id; PlayerKillCount; CharacterStatus), online=false.
- [ ] Commit (covered by 1.3 verification).

### Task 1.3: Read endpoints
**Files:** Modify `WebApiController.cs`.
- [ ] `GET /api/v1/accounts/{login}` → `AccountDto` (offline persistence load; 404 if missing).
- [ ] `GET /api/v1/accounts/{login}/characters` → `CharacterDto[]` (each: live if its account is online, else DB).
- [ ] `GET /api/v1/characters/{name}` → `CharacterDto` (live if online else DB; 404 if missing).
- [ ] Verify on local all-in-one: create/seed a character; curl all three with the key; confirm fields; log a character in via the game client and confirm `online:true` + live level changes without logout.
- [ ] Commit. **Checkpoint: deploy server to prod (game blip) only after user OK.**

---

## Component 2 — Website: API client + read migration

### Task 2.1: OpenMuApiClient + config + SQLite
**Files (in `openmu-website`):**
- Create: `app/Services/OpenMuApiClient.php` — Guzzle wrapper; base `OPENMU_API_URL`, header `X-API-Key: OPENMU_API_KEY`; methods `account($login)`, `characters($login)`, `character($name)`, `rankings($type)`; throws `OpenMuApiException` on non-2xx with mapped code/message.
- Modify: `config/database.php` — add `sqlite` connection; set default to `sqlite`. `.env`/`.env.production.example` — `DB_CONNECTION=sqlite`, `OPENMU_API_URL`, `OPENMU_API_KEY`.
- Create: `database/database.sqlite` (gitignored; created on deploy). News migration runs on sqlite.

**Steps:**
- [ ] Implement the client + exception; bind in a service provider (or resolve via container).
- [ ] Move `news` to sqlite (the migration is connection-agnostic; just change default connection). `php artisan migrate` on sqlite creates `news` + framework tables.
- [ ] Verify: `OpenMuApiClient` unit-callable via `php artisan tinker` against the local game API; news still renders.
- [ ] Commit.

### Task 2.2: Rankings + character reads via API
**Files:** Modify `RankingController`/`RankingService` (call `api->rankings()`), `CharacterController@index/@show` (call `api->characters()/character()`), views adjust to array DTOs instead of Eloquent. Remove `App\Models\Character`/`DataStatAttribute`/`Config*` game-DB reads from these paths.
- [ ] Verify: `/rankings`, `/character`, `/character/{name}` render from the API; online character shows live stats (no logout). Drop the stale-data note for online.
- [ ] Commit + deploy website (no game change).

---

## Component 3 — Character actions via API

### Task 3.1: Server action endpoints
**Files (server):** Modify `WebApiController.cs`; add `src/Web/AdminPanel/API/CharacterActions.cs` (offline-path operations reusing config/validation).
- [ ] `POST /characters/{name}/reset` (409 if online; else reuse `ResetConfiguration` values: check RequiredLevel/ResetLimit, increment `Stats.Resets`, set `Stats.Level`=LevelAfterReset, Experience=0, reset base stats if `ResetStats`, grant points per tiers; SaveChanges).
- [ ] `POST /characters/{name}/add-points` body `{strength,agility,vitality,energy}` (validate `CanIncreaseStats`, increment base stat attributes, decrement LevelUpPoints).
- [ ] `PATCH /characters/{name}/rename` (regex + uniqueness).
- [ ] `POST /characters/{name}/clear-pk`; `POST /characters/{name}/unstick`.
- [ ] `DELETE /characters/{name}` body `{securityCode}` (BCrypt/plaintext check + guild check, then `IContext.DeleteAsync(character)` → real hard delete).
- [ ] All enforce ownership (character ∈ account login) + offline.
- [ ] Verify on local all-in-one against a backed-up DB: each endpoint via curl; delete leaves no orphan rows; online char → 409.
- [ ] Commit. **Checkpoint: deploy server to prod after user OK.**

### Task 3.2: Website actions call the API
**Files (website):** Rewrite `CharacterActionController` + `CharacterPointsController` to call the API client; delete `CharacterActionService`/`OnlineCheckService` direct-DB logic (online check now server-side); update views (real delete, drop soft-delete).
- [ ] Verify E2E: web reset/rename/clear-pk/unstick/delete → reflected in game; online → blocked with friendly message.
- [ ] Commit + deploy website.

---

## Component 4 — Account + auth via API

### Task 4.1: Server account endpoints
**Files (server):** Add account endpoints to `WebApiController.cs`.
- [ ] `POST /accounts` (register: validate login 3–10 unique + 6-char security code; BCrypt; create via persistence).
- [ ] `POST /accounts/authenticate` body `{login,password}` → `AccountDto` or 401 (use `IPlayerContext.AuthenticateAsync`/`GetAccountByLoginNameAsync`).
- [ ] `PATCH /accounts/{login}/password|email|security-code` (verify current password via BCrypt; update; SaveChanges).
- [ ] Verify: register via curl → log into the game client with it; authenticate endpoint returns state; password change → game login with new password.
- [ ] Commit. **Checkpoint: deploy server to prod after user OK.**

### Task 4.2: Website auth refactor + drop pgsql
**Files (website):** Custom guard/user provider (`App\Auth\ApiUserProvider`, `App\Auth\AccountIdentity` implementing `Authenticatable`) authenticating via the API; rewrite `RegisterController`/`LoginController`/`AccountController` to call the API; remove `App\Models\User` game-DB mapping; remove the `pgsql` connection from `config/database.php` and env.
- [ ] Verify E2E: register → login → account page → change password/email/security → logout/login; confirm no pgsql connection remains.
- [ ] Commit + deploy website.

---

## Component 5 — Final sweep
- [ ] `grep -rE "data\.\"|config\.\"|pgsql|DataStatAttribute|ConfigAttributeDefinition" app config` in the website → only the API client + sqlite remain; remove dead models/migrations.
- [ ] Enable + tune OpenMU's reset feature in game config; document the values.
- [ ] Add `WEB_API_KEY`/`OPENMU_API_KEY` to the droplet `.env` (both containers); deploy.
- [ ] Full E2E pass per the spec's verification list.
- [ ] Commit.

## Self-review notes
- Covers spec sections A (Tasks 1.x, 3.1, 4.1), B (2.x, 3.2, 4.2), cross-cutting (global constraints, Task 5), verification (per-task + Task 5).
- Reset rules: single source = OpenMU `ResetConfiguration` (Tasks 3.1 + 5).
- Auth refactor isolated to Task 4.2, after the API is proven — matches the spec's risk ordering.
