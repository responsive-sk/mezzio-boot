<?php

declare(strict_types=1);

namespace Light\Page;

use Light\Page\Handler\GetPageViewHandler;
use Mezzio\Application;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

use function assert;
use function is_array;
use function is_string;
use function sprintf;

class RoutesDelegator
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, string $serviceName, callable $callback): Application
    {
        $app = $callback();
        assert($app instanceof Application);

        /** @var array<string, mixed> $config */
        $config = $container->get('config');
        assert(is_array($config));

        /** @var array<string, array<string, string>> $routes */
        $routes = $config['routes'] ?? [];
        assert(is_array($routes));

        foreach ($routes as $prefix => $moduleRoutes) {
            assert(is_string($prefix));
            assert(is_array($moduleRoutes));

            foreach ($moduleRoutes as $routeUri => $templateName) {
                assert(is_string($routeUri));
                assert(is_string($templateName));

                $app->get(
                    sprintf('/%s/%s', $prefix, $routeUri),
                    GetPageViewHandler::class,
                    sprintf('%s::%s', $prefix, $templateName)
                );
            }
        }

        return $app;
    }
}
