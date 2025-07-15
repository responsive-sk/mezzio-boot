<?php

declare(strict_types=1);

return [
    'templates' => [
        'extension' => 'phtml',
        'paths' => [
            // App Templates
            'app' => ['src/Templates/app'],

            // Layout Templates
            'layout' => ['src/Templates/layout'],

            // Error Templates
            'error' => ['src/Templates/error'],

            // Partial Templates
            'partial' => ['src/Templates/partial'],

            // Page Templates
            'page' => ['src/Page/Templates/page'],
        ],
    ],
];
