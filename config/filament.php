<?php

return [
    'domain' => env('FILAMENT_DOMAIN', 'care.tech360.systems'),
    'path' => env('FILAMENT_PATH', 'admin'),
    'home_url' => env('FILAMENT_HOME_URL', '/admin'),
    'auth' => [
        'guard' => env('FILAMENT_AUTH_GUARD', 'web'),
        'pages' => [
            'login' => \Filament\Pages\Auth\Login::class,
        ],
    ],
    'middleware' => [
        'base' => [
            'web',
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
        'auth' => [
            'auth:web',
        ],
    ],
    'auth_session' => [
        'enabled' => true,
        'key' => 'filament_auth',
    ],
];
