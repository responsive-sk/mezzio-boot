<?php

declare(strict_types=1);

namespace Light\App\Factory;

use Light\App\Handler\PathsExampleHandler;
use Psr\Container\ContainerInterface;
use ResponsiveSk\Slim4Paths\Paths;

use function assert;

class PathsExampleHandlerFactory
{
    public function __invoke(ContainerInterface $container): PathsExampleHandler
    {
        $paths = $container->get(Paths::class);
        assert($paths instanceof Paths);

        return new PathsExampleHandler($paths);
    }
}
