<?php

declare(strict_types=1);

namespace Light\App;

use Light\App\Factory\GetIndexViewHandlerFactory;
use Light\App\Factory\PathsExampleHandlerFactory;
use Light\App\Factory\PathsFactory;
use Light\App\Handler\GetIndexViewHandler;
use Light\App\Handler\PathsExampleHandler;
use Mezzio\Application;
use ResponsiveSk\Slim4Paths\Paths;

class ConfigProvider
{
    /**
     * Return configuration for this module.
     *
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'templates' => $this->getTemplates(),
        ];
    }

    /**
     * @return array{
     *     delegators: array<class-string, array<class-string>>,
     *     factories: array<class-string, class-string>,
     * }
     */
    public function getDependencies(): array
    {
        return [
            'delegators' => [
                Application::class => [
                    RoutesDelegator::class,
                ],
            ],
            'factories' => [
                GetIndexViewHandler::class => GetIndexViewHandlerFactory::class,
                PathsExampleHandler::class => PathsExampleHandlerFactory::class,
                Paths::class => PathsFactory::class,
                \Light\App\Handler\BootstrapDemoHandler::class => \Light\App\Factory\BootstrapDemoHandlerFactory::class,
                \Light\App\Handler\MainDemoHandler::class => \Light\App\Factory\MainDemoHandlerFactory::class,
            ],
        ];
    }

    /**
     * Returns the templates configuration.
     *
     * NOTE: Template paths are now managed centrally via TemplatePathProvider
     * and configured in config/autoload/paths.global.php.
     *
     * @return array<string, mixed>
     */
    public function getTemplates(): array
    {
        return [
            'paths' => [
                // Template paths are now managed by TemplatePathProvider
                // See config/autoload/paths.global.php for configuration
                // Hardcoded paths removed for PSR-15 compliance
            ],
        ];
    }
}
