<?php

declare(strict_types=1);

namespace Light\Core\Factory;

use Light\Core\Template\NativePhpRenderer;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;
use ResponsiveSk\Slim4Paths\Paths;

use function assert;
use function is_array;

/**
 * Factory for Native PHP template renderer.
 *
 * Creates NativePhpRenderer with proper dependencies.
 * Follows PSR-11 container interface and Zend4Boot protocol.
 */
class NativePhpRendererFactory
{
    /**
     * Create NativePhpRenderer instance.
     *
     * @param ContainerInterface $container DI container
     */
    public function __invoke(ContainerInterface $container): TemplateRendererInterface
    {
        // Get Paths service
        $paths = $container->get(Paths::class);
        assert($paths instanceof Paths);

        // Get application configuration
        /** @var array<string, mixed> $config */
        $config = $container->get('config');
        assert(is_array($config));

        // Get URL helper (optional)
        $urlHelper = null;
        if ($container->has(UrlHelper::class)) {
            $urlHelper = $container->get(UrlHelper::class);
            assert($urlHelper instanceof UrlHelper);
        }

        return new NativePhpRenderer($paths, $config, $urlHelper);
    }
}
