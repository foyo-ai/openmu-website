# Deploying the muss6.org player website

The website runs **alongside the game server** in the existing VPS compose project
(`mu-server/openmu/deploy/vps`, project name `openmu`) on the same droplet, sharing the
internal Postgres and network. The front nginx terminates TLS and reverse-proxies
`muss6.org` → the website container, while `admin.muss6.org` keeps proxying to the admin panel.

```
Internet ──443──> openmu-nginx ──┬── muss6.org      → web:80  (php-fpm + nginx, Laravel)
                                 └── admin.muss6.org → openmu-startup:8080 (admin panel)
web ──> database:5432 (game DB, internal)   web ──> openmu-startup:8080 (online count)
```

## Architecture
- **Image:** `ghcr.io/<owner>/openmu-website:production`, built from `Dockerfile.prod`
  (multi-stage: Vite assets → php-fpm + nginx in one container, serving on port 80).
- **Build/deploy:** `.github/workflows/deploy.yml` builds + pushes to GHCR on a push to the
  `production` branch, then SSHes into the VPS to pull, migrate, and restart `web` — exactly
  like the game server's workflow.
- **Data:** writes only app-owned tables (`public.news`, Laravel bookkeeping); never touches
  `data.*` / `config.*`.

## One-time setup

1. **DNS** — add A records pointing at the droplet IP and wait until they resolve:
   ```
   A  muss6.org       -> <droplet IP>
   A  www.muss6.org   -> <droplet IP>
   ```

2. **GitHub secrets** on the `openmu-website` repo (same values as the game repo):
   `VPS_HOST`, `VPS_USER`, `VPS_SSH_KEY`. GHCR uses the built-in `GITHUB_TOKEN`.

3. **VPS env** — in `/opt/openmu/deploy/vps/.env`, add (see `.env.example`):
   ```
   WEBSITE_APP_KEY=base64:...      # generate once, keep stable
   SERVER_DISCORD_URL=...          # optional landing-page info
   SERVER_DOWNLOAD_FULL=...
   ```
   Generate the key once:
   ```
   docker run --rm ghcr.io/<owner>/openmu-website:production php artisan key:generate --show
   ```

## First deploy

1. **Build + push the image:** push to the `production` branch (or run the *Deploy website to
   VPS* workflow manually). This builds the image, pushes to GHCR, and on the VPS runs
   `migrate` + starts `web`.

   To do it by hand on the VPS instead:
   ```
   cd /opt/openmu/deploy/vps
   docker compose --profile https pull web
   docker compose --profile https run --rm web php artisan migrate --force   # creates public.news
   docker compose --profile https up -d web
   ```

2. **Issue the TLS cert** (the HTTP vhost `conf.d/muss6-http.conf` is already committed and
   serves the ACME challenge):
   ```
   cd /opt/openmu/deploy/vps
   docker compose --profile https up -d nginx certbot      # nginx now answers :80 for muss6.org
   docker exec openmu-certbot certbot certonly --webroot -w /var/www/certbot \
     -d muss6.org -d www.muss6.org \
     --email you@example.com --agree-tos --no-eff-email --non-interactive
   ```

3. **Activate HTTPS** — copy the https vhost into the active conf dir and reload:
   ```
   cp nginx/conf.d-https/muss6-https.conf nginx/conf.d/
   docker exec openmu-nginx nginx -s reload
   ```

4. **Verify:**
   - `https://muss6.org` loads (valid Let's Encrypt cert), `www` redirects to apex.
   - `https://admin.muss6.org` still works.
   - Register a test account on the site, then log into the **game client** with it.

## Posting news
Log into `https://muss6.org` with a **GameMaster** account (`data.Account.State = 2`), then go to
`/admin/news`. Normal accounts get 403.

## Updates
Push to `production` → the workflow rebuilds, runs new migrations, and restarts `web`.
Cert renewal is automatic (the existing `certbot` container renews; `nginx` reloads every 6h).

## Notes / gotchas
- The website is under the compose `https` profile, so bring it up with `--profile https`.
- `webstorage` named volume persists file sessions/logs across deploys.
- `route:cache` is intentionally not used (mcamara localized prefixes don't cache cleanly);
  `config:cache` + `view:cache` run on container start via the entrypoint.
- Only run `php artisan migrate` through this image — it creates app tables in `public` only.
