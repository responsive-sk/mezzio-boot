<?php

declare(strict_types=1);

use Laminas\ConfigAggregator\ArrayProvider;
use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\ConfigAggregator\PhpFileProvider;

// To enable or disable caching, set the `ConfigAggregator::ENABLE_CACHE` boolean in
// `config/autoload/local.php`.
$cacheConfig = [
    'config_cache_path' => 'var/cache/config-cache.php',
];

// phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName

$aggregator = new ConfigAggregator([
    \Mezzio\Helper\ConfigProvider::class,
    \Laminas\Validator\ConfigProvider::class,
    \Laminas\ServiceManager\ConfigProvider::class,
    \Laminas\HttpHandlerRunner\ConfigProvider::class,
    // Include cache configuration
    new ArrayProvider($cacheConfig),
    \Laminas\Diactoros\ConfigProvider::class,
    \Mezzio\ConfigProvider::class,
    \Mezzio\Router\ConfigProvider::class,
    \Mezzio\Router\FastRouteRouter\ConfigProvider::class,
    // Core infrastructure module (must be loaded BEFORE Mezzio\Twig to override factories)
    \Light\Core\ConfigProvider::class,
    // DebugBar middleware (development only)
    \ResponsiveSk\PhpDebugBarMiddleware\ConfigProvider::class,
    // Dotkernel packages
    \Dot\ErrorHandler\ConfigProvider::class,
    \Dot\Log\ConfigProvider::class,
    // Default App module config
    \Light\App\ConfigProvider::class,
    \Light\Page\ConfigProvider::class,
    // Load application config in a pre-defined order in such a way that local settings
    // overwrite global settings. (Loaded as first to last):
    //   - `global.php`
    //   - `*.global.php`
    //   - `local.php`
    //   - `*.local.php`
    new PhpFileProvider(realpath(__DIR__) . '/autoload/{{,*.}global,{,*.}local}.php'),
    // Load development config if it exists
    new PhpFileProvider(realpath(__DIR__) . '/development.config.php'),
], $cacheConfig['config_cache_path']);

// phpcs:enable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName

return $aggregator->getMergedConfig();
