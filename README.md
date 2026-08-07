# Qaari SL Backend

Laravel 12 API + Filament admin + public Blade website for the Qaari SL Quran reciters platform.

## Stack

- Laravel 12, Filament 5, Spatie Permission, Laravel Sanctum
- Blade + Vite + Tailwind CSS 4 + Alpine.js (public site)
- MySQL / MariaDB, Cloudflare R2 (S3-compatible) for audio and photos

## Local setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
# Configure DB + R2 in .env
php artisan migrate --seed
npm run build          # or: npm run dev
php artisan serve --host=127.0.0.1
```

- Public website: `http://127.0.0.1:8000/`
- Admin panel: `http://127.0.0.1:8000/admin`

Seeded staff (password: `password`):

| Email | Role |
|-------|------|
| admin@qaarisl.com | Super Admin |
| reviewer@qaarisl.com | Admin |
| production@qaarisl.com | Production |

## Public website

| Path | Page |
|------|------|
| `/` | Homepage (hero, featured reciters, stats, partners) |
| `/reciters` | Reciter list (search + region filter) |
| `/reciters/{id}` | Reciter detail + approved recordings (`?play=` deep link) |
| `/listen/{recitation}` | Follow-along Arabic text synced to audio |
| `/story` | The Story So Far (patrons, leadership, team) |
| `/login`, `/register` | Optional listener accounts (session) |
| `/library/favorites` | Saved favorites (auth) |
| `/library/playlists` | Playlists CRUD (auth) |
| `/locale/{en\|so\|ar}` | Language switch (session + RTL for Arabic) |

Approved recitations only. Sticky bottom player uses temporary R2 audio URLs. Share uses the Web Share API (clipboard fallback). Follow-along uses **per-recitation ayah timestamps** generated once with FFmpeg + Python (`php artisan ayah:sync --check`, admin **Sync text now**). Equal-time fallback only until a recitation is synced.

**Ayah text:** Uthmani dataset in `database/data/ayahs-uthmani.json` (seeded by `AyahSeeder`).
**Ayah sync tools:** see `tools/README.md` (portable FFmpeg in `tools/bin/`).

**Super Admin only (Filament → Story Page):** hero/closing copy, patrons & leadership, Behind the Voices team, partners list, and the homepage partners on/off toggle.

## Public API (`/api/v1`)

Public catalog returns **approved** recitations only. Media URLs are temporary R2 signed links when available.

### Catalog (no auth)

| Method | Path | Notes |
|--------|------|-------|
| GET | `/api/v1/reciters` | Paginated; optional `region`, `q`, `per_page` |
| GET | `/api/v1/reciters/{id}` | Detail + approved recitations |
| GET | `/api/v1/surahs` | Optional `q`, `per_page` |
| GET | `/api/v1/surahs/{id}` | Detail |
| GET | `/api/v1/recitations` | Filters: `reciter_id`, `surah_id` |
| GET | `/api/v1/recitations/{id}` | 404 if not approved |
| GET | `/api/v1/search?q=` | Reciters, surahs, and recitations |

### Auth (Sanctum token)

| Method | Path | Notes |
|--------|------|-------|
| POST | `/api/v1/auth/register` | `name`, `email`, `password`, `password_confirmation` |
| POST | `/api/v1/auth/login` | `email`, `password` → Bearer token |
| POST | `/api/v1/auth/logout` | Requires `Authorization: Bearer {token}` |
| GET | `/api/v1/auth/me` | Current user |

### Favorites & playlists (auth required)

| Method | Path |
|--------|------|
| GET/POST | `/api/v1/favorites` |
| DELETE | `/api/v1/favorites/{recitation}` |
| GET/POST | `/api/v1/playlists` |
| GET/PUT/PATCH/DELETE | `/api/v1/playlists/{playlist}` |
| POST | `/api/v1/playlists/{playlist}/items` |
| DELETE | `/api/v1/playlists/{playlist}/items/{item}` |
| PUT | `/api/v1/playlists/{playlist}/reorder` (`item_ids` array) |

## Staff API (`/api/staff`)

Internal Sanctum API for the `qaari-sl-staff` Flutter app (Production + Admin). Not for consumers. Roles: `super_admin`, `admin`, `production`.

| Method | Path | Notes |
|--------|------|-------|
| POST | `/api/staff/login` | Staff roles only → Bearer token + roles |
| POST | `/api/staff/logout` | Auth required |
| GET | `/api/staff/me` | Includes Spatie `roles` |
| GET | `/api/staff/dashboard` | Role-scoped counts |
| GET | `/api/staff/surahs` | Full surah list for upload picker |
| GET/POST | `/api/staff/reciters` | Scoped by ownership for Production |
| GET/PUT/POST | `/api/staff/reciters/{id}` | POST accepts multipart photo update |
| GET/POST | `/api/staff/reciters/{id}/recitations` | Create with multipart `audio` |
| POST | `/api/staff/recitations/{id}/submit` | draft/rejected → pending_review |
| POST | `/api/staff/recitations/{id}/replace-audio` | Re-upload; optional `submit` |
| GET | `/api/staff/reviews` | Admin queue (`status=pending_review`) |
| POST | `/api/staff/recitations/{id}/approve` | Reviewer only |
| POST | `/api/staff/recitations/{id}/reject` | Multipart `voice_note` required |
| GET | `/api/staff/recitations/{id}/review-notes` | Voice note history |
| GET | `/api/staff/recitations/{id}/ayah-sync` | Admin: ayahs + starts (seconds) + resume |
| PUT | `/api/staff/recitations/{id}/ayah-sync` | Admin: save manual timings (`ayah_starts`, `resume_ayah`) |
| POST | `/api/staff/recitations/{id}/ayah-sync/auto` | Admin: run auto aligner (`overwrite_manual` optional) |

## Tests

```bash
php artisan test
```
