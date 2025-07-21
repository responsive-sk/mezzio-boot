<?php

declare(strict_types=1);

namespace Light\App\Factory;

use Light\App\Handler\CommunityHandler;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

use function assert;

class CommunityHandlerFactory
{
    public function __invoke(ContainerInterface $container): CommunityHandler
    {
        $template = $container->get(TemplateRendererInterface::class);
        assert($template instanceof TemplateRendererInterface);

        return new CommunityHandler($template);
    }
}
