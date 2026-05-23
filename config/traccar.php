<?php

declare(strict_types=1);

return [
    'url' => env('TRACCAR_URL', 'http://localhost:8082'),
    'email' => env('TRACCAR_EMAIL'),
    'password' => env('TRACCAR_PASSWORD'),
    'timeout' => (int) env('TRACCAR_TIMEOUT', 10),
    'positions_sync_interval' => (int) env('TRACCAR_POSITIONS_SYNC_INTERVAL', 60),
];
