<?php

/**
 * Production paths configuration for slim4-paths package.
 *
 * This configuration follows the official slim4-paths documentation format
 * and moves data and logs to /var directory for production environments.
 * See: https://github.com/responsive-sk/slim4-paths
 */

declare(strict_types=1);

return [
    'paths' => [
        // Base path - project root directory
        'base_path' => dirname(__DIR__, 2),

        // Custom paths configuration (relative to base_path) - PRODUCTION VERSION
        'custom_paths' => [
            // Core directories - moved to var for production
            'templates' => 'src/Templates',
            'content'   => 'content',
            'data'      => 'var/data', // PRODUCTION: moved to var
            'logs'      => 'var/logs', // PRODUCTION: moved to var
            'cache'     => 'var/cache', // PRODUCTION: moved to var
            'storage'   => 'var/storage', // PRODUCTION: moved to var
            'uploads'   => 'public/uploads',
            'downloads' => 'public/downloads',

            // Development directories
            'tests' => 'test',
            'docs'  => 'docs',
            'bin'   => 'bin',

            // Asset directories
            'css'    => 'public/css',
            'js'     => 'public/js',
            'images' => 'public/images',
            'fonts'  => 'public/fonts',

            // Module directories
            'modules'     => 'modules',
            'user_module' => 'modules/User',
            'mark_module' => 'modules/Mark',
            'blog_module' => 'modules/Blog',

            // Template subdirectories
            'app_templates'     => 'src/Templates/app',
            'error_templates'   => 'src/Templates/error',
            'layout_templates'  => 'src/Templates/layout',
            'partial_templates' => 'src/Templates/partial',
            'page_templates'    => 'src/Page/templates/page',

            // Cache subdirectories - moved to var for production
            'config_cache' => 'var/cache/config', // PRODUCTION: moved to var
            'twig_cache'   => 'var/cache/twig', // PRODUCTION: moved to var
            'routes_cache' => 'var/cache/routes', // PRODUCTION: moved to var

            // Runtime directories - moved to var for production
            'tmp'      => 'var/tmp', // PRODUCTION: moved to var
            'sessions' => 'var/sessions', // PRODUCTION: moved to var
        ],
    ],
];
