<?php

declare(strict_types=1);

namespace Light\Core\Factory;

use Light\Core\Service\ConfigBasedTemplatePathProvider;
use Light\Core\Service\TemplatePathProviderInterface;
use Psr\Container\ContainerInterface;
use ResponsiveSk\Slim4Paths\Paths;

use function assert;
use function is_array;

/**
 * Factory for TemplatePathProvider service.
 *
 * Creates ConfigBasedTemplatePathProvider with proper dependencies.
 * Follows PSR-11 container interface and Zend4Boot protocol.
 */
class TemplatePathProviderFactory
{
    /**
     * Create TemplatePathProvider instance.
     *
     * @param ContainerInterface $container DI container
     */
    public function __invoke(ContainerInterface $container): TemplatePathProviderInterface
    {
        // Get Paths service
        $paths = $container->get(Paths::class);
        assert($paths instanceof Paths);

        // Get application configuration
        /** @var array<string, mixed> $config */
        $config = $container->get('config');
        assert(is_array($config));

        return new ConfigBasedTemplatePathProvider($paths, $config);
    }
}
