<?php

declare(strict_types=1);

namespace Light\App;

use Light\App\Handler\BootstrapDemoHandler;
use Light\App\Handler\CommunityHandler;
use Light\App\Handler\ContactHandler;
use Light\App\Handler\GetIndexViewHandler;
use Light\App\Handler\MainDemoHandler;
use Light\App\Handler\PathsExampleHandler;
use Light\App\Handler\WhatWeOfferHandler;
use Light\App\Handler\WorkHandler;
use Mezzio\Application;
use Psr\Container\ContainerInterface;
use ResponsiveSk\PhpDebugBarMiddleware\DebugBarAssetsHandler;

use function assert;

class RoutesDelegator
{
    public function __invoke(ContainerInterface $container, string $serviceName, callable $callback): Application
    {
        $app = $callback();
        assert($app instanceof Application);

        $app->get('/', [GetIndexViewHandler::class], 'app::index');
        $app->get('/paths-example', [PathsExampleHandler::class], 'app::paths-example');
        $app->get('/bootstrap-demo', [BootstrapDemoHandler::class], 'app::bootstrap-demo');
        $app->get('/main-demo', [MainDemoHandler::class], 'app::main-demo');

        // New Roave-inspired pages
        $app->get('/what-we-offer', [WhatWeOfferHandler::class], 'app::what-we-offer');
        $app->get('/community', [CommunityHandler::class], 'app::community');
        $app->get('/work', [WorkHandler::class], 'app::work');
        $app->get('/contact', [ContactHandler::class], 'app::contact');

        // DebugBar assets route (from responsive-sk/php-debugbar-middleware)
        $app->get('/debugbar/{file:.+}', [DebugBarAssetsHandler::class], 'debugbar::assets');

        return $app;
    }
}
