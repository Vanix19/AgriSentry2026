<?php

return [
    'enabled' => env('FIREBASE_ENABLED', false),
    'queue_connection' => env('FIREBASE_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'sync')),
    'credentials' => env('FIREBASE_CREDENTIALS'),
    'farm_id' => env('FIREBASE_FARM_ID', 'agrisentry'),
    'client' => [
        'apiKey' => env('VITE_FIREBASE_API_KEY'),
        'authDomain' => env('VITE_FIREBASE_AUTH_DOMAIN'),
        'databaseURL' => env('VITE_FIREBASE_DATABASE_URL'),
        'projectId' => env('VITE_FIREBASE_PROJECT_ID'),
        'appId' => env('VITE_FIREBASE_APP_ID'),
    ],
];
