<?php

declare(strict_types=1);

namespace ResponsiveSk\PhpDebugBarMiddleware;

/**
 * Configuration provider for Mezzio/Laminas integration
 * 
 * Provides automatic configuration for dependency injection and routing.
 */
class ConfigProvider
{
    /**
     * Return configuration for this component.
     *
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'routes' => $this->getRoutes(),
            'debugbar' => $this->getDebugBarConfig(),
        ];
    }

    /**
     * Return dependency configuration.
     *
     * @return array<string, mixed>
     */
    public function getDependencies(): array
    {
        return [
            'factories' => [
                DebugBarMiddleware::class => DebugBarMiddlewareFactory::class,
                DebugBarAssetsHandler::class => DebugBarAssetsHandlerFactory::class,
            ],
        ];
    }

    /**
     * Return route configuration.
     *
     * @return array<string, mixed>
     */
    public function getRoutes(): array
    {
        return [
            [
                'name' => 'debugbar.assets',
                'path' => '/debugbar/{file:.+}',
                'middleware' => DebugBarAssetsHandler::class,
                'allowed_methods' => ['GET'],
            ],
        ];
    }

    /**
     * Return DebugBar configuration.
     *
     * @return array<string, mixed>
     */
    public function getDebugBarConfig(): array
    {
        return [
            'enabled' => true, // Will be overridden by environment detection
            'asset_path' => '/debugbar',
            'collectors' => [
                'messages' => true,
                'time' => true,
                'memory' => true,
                'exceptions' => true,
                'request' => true,
            ],
        ];
    }
}
