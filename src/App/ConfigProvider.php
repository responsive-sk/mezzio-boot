<?php

declare(strict_types=1);

namespace Light\App;

use Light\App\Factory\BootstrapDemoHandlerFactory;
use Light\App\Factory\CommunityHandlerFactory;
use Light\App\Factory\ContactHandlerFactory;
use Light\App\Factory\GetIndexViewHandlerFactory;
use Light\App\Factory\MainDemoHandlerFactory;
use Light\App\Factory\PathsExampleHandlerFactory;
use Light\App\Factory\PathsFactory;
use Light\App\Factory\WhatWeOfferHandlerFactory;
use Light\App\Factory\WorkHandlerFactory;
use Light\App\Handler\BootstrapDemoHandler;
use Light\App\Handler\CommunityHandler;
use Light\App\Handler\ContactHandler;
use Light\App\Handler\GetIndexViewHandler;
use Light\App\Handler\MainDemoHandler;
use Light\App\Handler\PathsExampleHandler;
use Light\App\Handler\WhatWeOfferHandler;
use Light\App\Handler\WorkHandler;
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
            'templates'    => $this->getTemplates(),
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
            'factories'  => [
                GetIndexViewHandler::class  => GetIndexViewHandlerFactory::class,
                PathsExampleHandler::class  => PathsExampleHandlerFactory::class,
                Paths::class                => PathsFactory::class,
                BootstrapDemoHandler::class => BootstrapDemoHandlerFactory::class,
                MainDemoHandler::class      => MainDemoHandlerFactory::class,
                WhatWeOfferHandler::class   => WhatWeOfferHandlerFactory::class,
                CommunityHandler::class     => CommunityHandlerFactory::class,
                WorkHandler::class          => WorkHandlerFactory::class,
                ContactHandler::class       => ContactHandlerFactory::class,
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
