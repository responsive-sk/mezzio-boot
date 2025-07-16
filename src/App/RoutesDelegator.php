<?php

declare(strict_types=1);

namespace Light\App;

use function assert;

use Light\App\Handler\BootstrapDemoHandler;
use Light\App\Handler\GetIndexViewHandler;
use Light\App\Handler\MainDemoHandler;
use Light\App\Handler\PathsExampleHandler;
use Mezzio\Application;
use Psr\Container\ContainerInterface;

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

        return $app;
    }
}
