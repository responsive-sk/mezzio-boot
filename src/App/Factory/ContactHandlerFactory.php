<?php

declare(strict_types=1);

namespace Light\App\Factory;

use Light\App\Handler\ContactHandler;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

use function assert;

class ContactHandlerFactory
{
    public function __invoke(ContainerInterface $container): ContactHandler
    {
        $template = $container->get(TemplateRendererInterface::class);
        assert($template instanceof TemplateRendererInterface);

        return new ContactHandler($template);
    }
}
