<?php

declare(strict_types=1);

namespace Light\App\Factory;

use Psr\Container\ContainerInterface;
use ResponsiveSk\Slim4Paths\Paths;

use function assert;
use function dirname;
use function is_array;
use function is_string;

class PathsFactory
{
    public function __invoke(ContainerInterface $container): Paths
    {
        /** @var array<string, mixed> $config */
        $config = $container->get('config');
        assert(is_array($config));

        /** @var array<string, mixed> $pathsConfig */
        $pathsConfig = $config['paths'] ?? [];
        assert(is_array($pathsConfig));

        // Get base path
        /** @var string $basePath */
        $basePath = $pathsConfig['base_path'] ?? dirname(__DIR__, 4);
        assert(is_string($basePath));

        // Get custom paths
        /** @var array<string, string> $customPaths */
        $customPaths = $pathsConfig['custom_paths'] ?? [];
        assert(is_array($customPaths));

        // Get preset
        /** @var string $preset */
        $preset = $pathsConfig['preset'] ?? 'mezzio';
        assert(is_string($preset));

        // Get template paths
        /** @var array<string, string> $templatePaths */
        $templatePaths = $pathsConfig['templates'] ?? [];
        assert(is_array($templatePaths));

        // Merge template paths with custom paths
        $allCustomPaths = array_merge($customPaths, $templatePaths);

        // Create lightweight Paths instance with preset and template paths
        return Paths::withPreset($preset, $basePath, $allCustomPaths);
    }
}
