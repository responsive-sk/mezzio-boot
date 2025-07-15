<?php

declare(strict_types=1);

namespace Light\App\Factory;

use Light\App\Handler\BootstrapDemoHandler;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;
use ResponsiveSk\Slim4Paths\Paths;

class BootstrapDemoHandlerFactory
{
    public function __invoke(ContainerInterface $container): BootstrapDemoHandler
    {
        $paths = $container->get(Paths::class);
        assert($paths instanceof Paths);

        $template = $container->get(TemplateRendererInterface::class);
        assert($template instanceof TemplateRendererInterface);

        return new BootstrapDemoHandler($paths, $template);
    }
}
