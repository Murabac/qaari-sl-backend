# Xulka Quraada — Coolify deploy

Same pattern as Nova and TowerLine: **Dockerfile + FrankenPHP on port 80**, MySQL linked in Coolify, persistent `storage`.

This app also starts a queue worker (ayah sync) and includes FFmpeg + Python in the image. Audio and photos stay on Cloudflare R2, not on the container disk.

## Coolify General screen

| Field | Set to |
|---|---|
| Build Pack | **Dockerfile** (not Nixpacks) |
| Ports Exposes | **80** (not 3000 / 8080) |
| Base Directory | `/` |
| Publish Directory | empty |
| Watch Paths | empty (deploy on every push) |
| Install / Build / Start | leave empty — the Dockerfile handles them |
| Pre-deploy command | empty (entrypoint already migrates) |
| Post-deploy command | empty |

Also:

1. **Git** — `Murabac/qaari-sl-backend`, branch `main`.
2. **Deployment server** — the same Coolify server as the other apps.
3. **MySQL** — MySQL 8 in Coolify, **linked** to this app so `DB_HOST` / `DB_PORT` / `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` are injected. Do not reuse Nova or TowerLine’s database.
4. **Persistent storage** — mount a volume at `/app/storage` (Livewire temp uploads, logs, compiled views).
5. **Domain** — `https://qaari.mahaysaa.com` if that hostname is still pointed here, or the Coolify URL you assign.
6. **Healthcheck** — leave it enabled. The image checks `/healthz` and allows 180s for the first migrate.

`nixpacks.toml` is only a fallback. Leave it unused when Build Pack is Dockerfile.

## Environment tab

Coolify injects `DB_*` when the database is linked. Add the rest. Mark `APP_KEY`, `APP_ENV`, `APP_DEBUG`, `DB_*`, and `AWS_*` as **runtime only** (not available at build).

```env
APP_NAME=Xulka Quraada
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...
APP_URL=https://qaari.mahaysaa.com
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
LOG_CHANNEL=stderr
LOG_LEVEL=error

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=database
DB_QUEUE_RETRY_AFTER=900
FILESYSTEM_DISK=local
LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK=local

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=auto
AWS_BUCKET=qaari-sl
AWS_ENDPOINT=https://YOUR_ACCOUNT_ID.r2.cloudflarestorage.com
AWS_USE_PATH_STYLE_ENDPOINT=true

RUN_MIGRATIONS=true
RUN_QUEUE=true
AYAH_SYNC_AUTO=true
AYAH_SYNC_PYTHON=python

# Hide Moments until the redesigned app ships. Old apps still get an empty list.
MOMENTS_ENABLED=false
```

Generate `APP_KEY` in Coolify, or locally with `php artisan key:generate --show`. If this replaces the existing production app, keep the **current** `APP_KEY` so sessions stay valid.

Copy `AWS_*` from the existing Coolify app or your local `.env`. Do not commit them.

`APP_URL` must be the public **https** URL. `LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK` stays `local` so Filament uploads go through Laravel instead of the browser talking to R2.

`DB_QUEUE_RETRY_AFTER` must stay above the ayah-sync job timeout (600 seconds). The default in `config/queue.php` is already 900.

`MOMENTS_ENABLED` is a Coolify runtime env var. Leave it `false` so Moments stays off the website, the admin panel, and the APIs. `GET /api/v1/moments` still returns an empty paginated list for apps already in the wild. To bring Moments back later, set `MOMENTS_ENABLED=true`, run `php artisan config:clear`, and redeploy.

## First deploy

1. Push `main` so Coolify can see the Dockerfile.
2. Save the app, link MySQL, paste env, add the `/app/storage` volume.
3. Deploy. The first build installs Composer, runs Vite, and pulls FFmpeg (several minutes).
4. Open `/healthz` — it should return `ok`. Then open `/` and `/admin`.

**Empty database:** after the first migrate, run once from Coolify **Execute command**:

```bash
php artisan db:seed --force
```

That loads surahs, ayah text, roles, and the demo staff accounts (`admin@qaarisl.com` / `password`). Change those passwords immediately.

**Existing production data:** do not seed. Point this app at the current MySQL, or import your dump, then deploy with `RUN_MIGRATIONS=true`.

## After it is up

- [ ] `https://…/` loads with CSS (if assets are `http://`, `APP_URL` is wrong)
- [ ] `/admin` login works
- [ ] `/api/v1/surahs` returns JSON
- [ ] A small recitation upload lands on R2 and the queue log shows the sync job (`storage/logs/queue.log`)
- [ ] `php artisan ayah:sync --check` inside the container reports ffmpeg and ffprobe ok

No cron is required. The entrypoint runs one queue worker.

## Files this uses

- `Dockerfile` — Composer + Vite + FrankenPHP PHP 8.4, FFmpeg, Python
- `docker/entrypoint.sh` — storage link, caches, migrate, queue worker
- `public/healthz` — Coolify healthcheck
- `.dockerignore` — keeps `.env` and `node_modules` out of the image
