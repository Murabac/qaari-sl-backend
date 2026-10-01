<?php

return [
    /*
    | Short-form Moments (vertical clips). Stored on R2 like audio.
    | Staff should upload H.264/AAC MP4, preferably 9:16, max 60s.
    */
    'max_duration_seconds' => (int) env('MOMENTS_MAX_DURATION', 60),
    'max_upload_kb' => (int) env('MOMENTS_MAX_UPLOAD_KB', 81920), // 80 MB
    'video_directory' => 'moments/videos',
    'poster_directory' => 'moments/posters',
    'signed_url_minutes' => (int) env('MOMENTS_SIGNED_URL_MINUTES', 120),
];
