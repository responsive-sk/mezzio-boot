<?php

declare(strict_types=1);

namespace Light\App\Factory;

use Light\App\Handler\WhatWeOfferHandler;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

use function assert;

class WhatWeOfferHandlerFactory
{
    public function __invoke(ContainerInterface $container): WhatWeOfferHandler
    {
        $template = $container->get(TemplateRendererInterface::class);
        assert($template instanceof TemplateRendererInterface);

        return new WhatWeOfferHandler($template);
    }
}
