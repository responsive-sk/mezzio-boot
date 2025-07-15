<?php

declare(strict_types=1);

namespace Light\App\Factory;

use Light\App\Handler\MainDemoHandler;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;
use ResponsiveSk\Slim4Paths\Paths;

class MainDemoHandlerFactory
{
    public function __invoke(ContainerInterface $container): MainDemoHandler
    {
        $paths = $container->get(Paths::class);
        assert($paths instanceof Paths);

        $template = $container->get(TemplateRendererInterface::class);
        assert($template instanceof TemplateRendererInterface);

        return new MainDemoHandler($paths, $template);
    }
}
