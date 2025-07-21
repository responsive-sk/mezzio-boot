<?php

declare(strict_types=1);

namespace Light\App\Factory;

use Light\App\Handler\MainDemoHandler;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

use function assert;

class MainDemoHandlerFactory
{
    public function __invoke(ContainerInterface $container): MainDemoHandler
    {
        $template = $container->get(TemplateRendererInterface::class);
        assert($template instanceof TemplateRendererInterface);

        return new MainDemoHandler($template);
    }
}
