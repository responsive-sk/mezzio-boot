<?php

declare(strict_types=1);

namespace Light\Page;

use Light\Page\Factory\GetPageViewHandlerFactory;
use Light\Page\Factory\PageServiceFactory;
use Light\Page\Handler\GetPageViewHandler;
use Light\Page\Service\PageService;
use Light\Page\Service\PageServiceInterface;
use Mezzio\Application;

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
     * Return dependency configuration.
     *
     * @return array<string, mixed>
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
                GetPageViewHandler::class => GetPageViewHandlerFactory::class,
                PageService::class        => PageServiceFactory::class,
            ],
            'aliases'    => [
                PageServiceInterface::class => PageService::class,
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
            ],
        ];
    }
}
