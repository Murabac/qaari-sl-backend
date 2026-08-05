# Qaari SL Backend

Laravel 12 API + Filament admin for the Qaari SL Quran reciters platform.

## Stack

- Laravel 12, Filament 5, Spatie Permission, Laravel Sanctum
- MySQL / MariaDB, Cloudflare R2 (S3-compatible) for audio and photos

## Local setup

```bash
composer install
cp .env.example .env
php artisan key:generate
# Configure DB + R2 in .env
php artisan migrate --seed
php artisan serve --host=127.0.0.1
```

Admin panel: `http://127.0.0.1:8000/admin`

Seeded staff (password: `password`):

| Email | Role |
|-------|------|
| admin@qaarisl.com | Super Admin |
| reviewer@qaarisl.com | Admin |
| production@qaarisl.com | Production |

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

## Tests

```bash
php artisan test
```
