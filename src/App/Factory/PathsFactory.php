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

        // Create Paths instance according to slim4-paths documentation
        // Use basic constructor with base path and custom paths
        return new Paths($basePath, $customPaths);
    }
}
