<?php

declare(strict_types=1);

/**
 * Production paths configuration
 *
 * This configuration moves data and logs to /var directory
 * which is more appropriate for production environments.
 */

return [
    'paths' => [
        // Base paths configuration for production
        'base_path' => dirname(__DIR__, 2),

        // Use mezzio preset but override with custom paths
        'preset' => 'mezzio',

        // Override default paths - PRODUCTION VERSION
        'custom_paths' => [
            'data'  => 'var/data', // CHANGED: moved to var
            'log'   => 'var/logs', // CHANGED: moved to var
            'logs'  => 'var/logs', // CHANGED: moved to var (alias)
            'cache' => 'var/cache', // CHANGED: moved to var
            'tmp'   => 'var/tmp', // CHANGED: moved to var
        ],

        // Template paths - updated for simplified structure
        'templates' => [
            'app'     => 'src/Templates/app',
            'error'   => 'src/Templates/error',
            'layout'  => 'src/Templates/layout',
            'partial' => 'src/Templates/partial',
            'page'    => 'src/Page/Templates/page',
        ],

        // Cache directories - moved to var
        'cache' => [
            'config' => 'var/cache/config', // CHANGED
            'twig'   => 'var/cache/twig', // CHANGED
            'routes' => 'var/cache/routes', // CHANGED
        ],

        // Asset directories remain the same
        'assets' => [
            'css'    => 'public/css',
            'js'     => 'public/js',
            'images' => 'public/images',
            'fonts'  => 'public/fonts',
        ],

        // Module-specific paths remain the same
        'modules' => [
            'user' => 'modules/User',
            'mark' => 'modules/Mark',
            'blog' => 'modules/Blog',
        ],

        // Custom paths - moved to var
        'custom' => [
            'uploads'   => 'public/uploads',
            'downloads' => 'public/downloads',
            'temp'      => 'var/tmp', // Already in var
            'sessions'  => 'var/sessions', // Already in var
            'logs'      => 'var/logs', // CHANGED: moved to var
            'tests'     => 'test',
            'docs'      => 'docs',
            'bin'       => 'bin',
        ],

        // Security configuration remains the same
        'security' => [
            'enable_path_traversal_protection' => true,
            'enable_encoding_protection'       => true,
            'enable_length_validation'         => true,
            'max_path_length'                  => 4096,
            'max_filename_length'              => 255,
            'trusted_paths'                    => [],
        ],

        // Framework preset remains the same
        'framework_preset' => 'mezzio',
    ],
];
