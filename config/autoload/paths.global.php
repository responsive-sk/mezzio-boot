<?php

/**
 * Paths configuration for slim4-paths package.
 *
 * This configuration follows the official slim4-paths documentation format.
 * See: https://github.com/responsive-sk/slim4-paths
 */

declare(strict_types=1);

return [
    'paths' => [
        // Base path - project root directory
        'base_path' => dirname(__DIR__, 2),

        // Custom paths configuration (relative to base_path)
        'custom_paths' => [
            // Core directories
            'templates' => 'src/Templates',
            'content'   => 'content',
            'data'      => 'var/data',
            'logs'      => 'var/logs',
            'cache'     => 'var/cache',
            'storage'   => 'var/storage',
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

            // Cache subdirectories
            'config_cache' => 'var/cache/config',
            'twig_cache'   => 'var/cache/twig',
            'routes_cache' => 'var/cache/routes',

            // Runtime directories
            'tmp'      => 'var/tmp',
            'sessions' => 'var/sessions',
        ],
    ],
];
