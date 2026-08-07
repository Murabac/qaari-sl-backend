<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Ayah ↔ audio sync
    |--------------------------------------------------------------------------
    |
    | After an audio upload, timings are generated once with FFmpeg (+ Python)
    | and stored in recitation_ayah_timings. Listeners never pay this CPU cost.
    |
    */
    'python' => env('AYAH_SYNC_PYTHON', 'python'),
    // Prefer tools/bin or node_modules/ffmpeg-static (resolved in AyahTimingSyncService).
    'ffmpeg' => env('AYAH_SYNC_FFMPEG', ''),
    'ffprobe' => env('AYAH_SYNC_FFPROBE', ''),
    'script' => env('AYAH_SYNC_SCRIPT', base_path('tools/ayah-align/align.py')),
    'timeout' => (int) env('AYAH_SYNC_TIMEOUT', 600),
    'auto_sync' => (bool) env('AYAH_SYNC_AUTO', true),
];
