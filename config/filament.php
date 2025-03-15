<?php

return [
    'path' => 'admin',
    'domain' => null,
    'home_url' => '/',
    'layout' => [
        'sidebar' => [
            'is_collapsible_on_desktop' => true,
        ],
    ],
    'auth' => [
        'guard' => env('FILAMENT_AUTH_GUARD', 'web'),
    ],
];
