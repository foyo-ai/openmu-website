# Spec: Game-Server API + Website Data-Layer Migration

## Context & goal
The website (`openmu-website`, Laravel) currently reads/writes the OpenMU game database
(`data.*` / `config.*`) directly. That causes two problems the user hit:
1. **Stale data** — OpenMU caches online characters in memory; the DB (hence the website)
   only reflects them after logout.
2. **Unsafe writes** — reset / add-points / delete bypass the game's own validated logic and
   risk conflicts; delete couldn't cascade correctly (forced a soft-delete workaround).

**Goal:** the website **never touches the game DB**. The game server (`foyo-ai/openmu`) exposes
an authenticated `api/v1`; the website calls it. Game logic is reused (real reset/delete/validation),
and online characters return **live** stats from memory. Confirmed decisions: full migration in one
project; **writes are rejected while the character is online**; **news moves to the website's own
SQLite**; **reset reuses OpenMU's built-in `ResetConfiguration`**; **auth becomes API-based**.

## Architecture
```
Browser ─▶ Laravel (owns only SQLite: news + framework tables)
              │ OpenMuApiClient (Guzzle) + X-API-Key
              ▼
        OpenMU api/v1  (controller inside the AdminPanel host = openmu-startup:8080)
              │ reuse game logic + IPersistenceContextProvider + live IGameServer context
              ▼
        Game data (data.* / config.*)
```
Trusted-service model: the API key authenticates the **website**; the website authenticates the
**user** and passes the acting account's login. The API still enforces ownership, security code,
offline-state, and validation.

## A. Game server — `api/v1` (repo `foyo-ai/openmu`)
New controller(s) in `src/Web/AdminPanel/API/` following `ServerController` (`[Route("api/v1")]`),
DI: `IDictionary<int,IGameServer>` (live), `IPersistenceContextProvider` + `IDataSource<GameConfiguration>`
(offline), and existing services. **Auth:** middleware checks `X-API-Key` on `/api/v1/*` against a
secret from config/env (`OpenMU:WebApiKey` / `WEB_API_KEY`); 401 on mismatch.

Online detection reuses `server.Context.GetPlayersAsync()` (as `/api/is-online` does). Online → read
from the live `Player` (attribute system: `Stats.Level`, `Stats.Resets`, totals, `Money`, position,
map); offline → `IPlayerContext` load. **Writes: reject with 409 if online.**

**Endpoints**
- Account: `POST /accounts` (register, BCrypt, validate login 3–10 unique + 6-char security code);
  `POST /accounts/authenticate` (`AuthenticateAsync` → account id/login/email/state);
  `GET /accounts/{login}`; `PATCH /accounts/{login}/password|email|security-code` (current-password
  verify via BCrypt).
- Characters: `GET /accounts/{login}/characters` (live-or-DB list);
  `GET /characters/{name}` (live-or-DB detail; includes `online` flag);
  `POST /characters/{name}/reset` (reuse `ResetConfiguration`: RequiredLevel, LevelAfterReset,
  ResetStats, PointsTiers, ResetLimit, cost — offline path replicates `ResetCharacterAction` against
  the entity using the same config);
  `POST /characters/{name}/add-points` (validate `LevelUpPoints`, increment base stat attribute);
  `PATCH /characters/{name}/rename` (CharacterNameRegex + uniqueness);
  `POST /characters/{name}/clear-pk` (PlayerKillCount=0, HeroState.Normal=3, StateRemainingSeconds=0);
  `POST /characters/{name}/unstick` (CurrentMap=home/Lorencia + safe coords);
  `DELETE /characters/{name}` (security-code + guild-membership checks, then real delete via
  `IContext.DeleteAsync` — correct cascade, **hard delete**).
- Rankings: `GET /rankings?type=resets|level|kills|guild&limit=` (persistence query, server-cached
  ~60s).

All endpoints take/return JSON; ownership enforced (character ∈ account); errors structured
`{ "error": <code>, "message": <text> }`.

## B. Website — data layer (repo `foyo-ai/openmu-website`)
- `App\Services\OpenMuApiClient`: typed methods per endpoint, injects `X-API-Key`, base URL from
  `OPENMU_API_URL`, maps API errors → exceptions/validation.
- Replace Eloquent-on-`data.*` usage in: `RegisterController`, `LoginController`, `AccountController`,
  `CharacterController`, `CharacterActionController`, `CharacterPointsController`, `RankingController`,
  `PageController` (online count already via API).
- **Auth:** custom Laravel guard + user provider authenticating via `POST /accounts/authenticate`;
  the authenticated user is a lightweight `AccountIdentity` (login, id, email, state) stored in the
  session — no DB-backed user. Registration calls `POST /accounts`.
- Remove the `pgsql` game connection; move `news` + framework tables to **SQLite** (`database/database.sqlite`,
  persisted via a volume). `News` model + migration unchanged except connection.
- Drop the soft-delete workaround (real delete now); drop the stale-data note for online chars
  (reads are live); keep `verifyCharacterAccountOwner` semantics but enforce server-side too.

## Cross-cutting
- **Security:** `WEB_API_KEY` is a strong random secret, set as env on both the game container and the
  website container (same compose `.env`). API key required on every `/api/v1` call. The website passes
  only the session account's login for user-scoped operations.
- **Errors / degradation:** game API unreachable → read pages show a notice, writes blocked with a
  friendly message; never a white-screen.
- **Reset config:** enable + tune OpenMU's reset feature in game config (off by default); document it.
- **Deploy:** server → `foyo-ai/openmu` CI (rebuild + restart `openmu-startup`; brief game blip — do at
  low-traffic times, checkpoint with user). website → its own CI. API key added to the droplet `.env`.

## Implementation order (each verified locally before prod)
1. **Server: api/v1 foundation + auth** (`X-API-Key` middleware, controller skeleton, online/offline
   helper, `GET /accounts/{login}` + `GET /characters/{name}` reads). Curl-test on local all-in-one.
2. **Website: API client + read migration** — character list/detail + rankings + account page read via
   API; add SQLite; keep auth on DB temporarily. Verify pages render from API (live data when online).
3. **Server + website: character actions** — reset/add-points/rename/clear-pk/unstick/delete endpoints;
   website actions call them; remove direct-DB writes. Verify each in-game.
4. **Server + website: account + auth** — register/authenticate/password/email/security; Laravel custom
   guard; remove the pgsql connection (news already on SQLite). Verify register→game-login + login.
5. **Rankings** via API (if not already in step 2). Final sweep: confirm zero `data.*`/`config.*`
   access remains in the website.

## Verification (end state)
- `grep` the website for `data.`/`config.`/`pgsql` → only SQLite + API client remain.
- Register on web → log into the **game client**; change password on web → game login works.
- Online character shows **live** level/stats on the web (no logout needed).
- Reset/rename/delete on web → correct in-game; delete leaves no orphan rows.
- API rejects writes for online characters (409) and bad/missing API key (401).

## Risks
- **Auth refactor** is the riskiest website change — do it last, behind the working API, test login/logout/register thoroughly before prod.
- **Game redeploys** cause brief downtime — batch server changes, checkpoint with user, deploy at low traffic.
- **In-process API** assumes the all-in-one `openmu-startup` (single process with game servers) — true for this deployment; a future Dapr split would change online-player access.
- **SQLite** is fine for this scale (news + sessions); note it for future growth.
