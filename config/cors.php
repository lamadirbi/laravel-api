<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // Dev: allow Next.js locally. FRONTEND_URL = deployed frontend (Vercel, etc.).
    'allowed_origins' => array_values(array_filter([
        env('FRONTEND_URL', 'http://localhost:3000'),
        'https://react-app-peach-theta.vercel.app',
        'http://localhost:3000',
        'http://127.0.0.1:3000',
        'http://localhost:3001',
        'http://127.0.0.1:3001',
    ])),

    // أي نطاق فرعي على Vercel أو Render (واجهة منشورة) بدون تحديث القائمة يدوياً.
    'allowed_origins_patterns' => [
        '#^https://.*\.vercel\.app$#',
        '#^https://.*\.onrender\.com$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,
];

