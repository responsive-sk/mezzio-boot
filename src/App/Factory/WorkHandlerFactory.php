<?php

declare(strict_types=1);

namespace Light\App\Factory;

use Light\App\Handler\WorkHandler;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

use function assert;

class WorkHandlerFactory
{
    public function __invoke(ContainerInterface $container): WorkHandler
    {
        $template = $container->get(TemplateRendererInterface::class);
        assert($template instanceof TemplateRendererInterface);

        return new WorkHandler($template);
    }
}
