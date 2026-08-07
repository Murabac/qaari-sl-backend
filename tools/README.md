# Ayah sync tools (FFmpeg)

Follow-along verse highlighting needs **one-time** timestamps per recitation.
Admins upload audio; the app runs the aligner once and stores the results.

## Install (easiest)

From `qaari-sl-backend`:

```bash
npm install
```

This pulls `ffmpeg-static` + `ffprobe-static`. No separate FFmpeg/Python install required for day-to-day use.

Optional: if `python` is on PATH, `tools/ayah-align/align.py` is used automatically. Otherwise PHP drives FFmpeg directly (same algorithm).

## Verify

```bash
php artisan ayah:sync --check
```

## Sync a recitation

1. Admin → Recitations → open a recording → **Sync text now**
2. Fine-tune in the **Text sync** panel: play audio, **Set start here** (or press `S`) on each drifting ayah, then **Save timings**
3. Or: `php artisan ayah:sync {id}` / `--pending` / `--all`
4. Keep a queue worker running for automatic sync after upload (`composer run dev`)

Manual saves set `sync_method=manual` and immediately update follow-along.

## Optional system tools

```powershell
powershell -ExecutionPolicy Bypass -File tools/install-sync-tools.ps1
# or
winget install -e --id Gyan.FFmpeg
winget install -e --id Python.Python.3.12
```
