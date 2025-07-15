<?php

declare(strict_types=1);

namespace Light\Core\Factory;

use function assert;
use function is_array;

use Light\Core\Service\TemplatePathProviderInterface;
use Psr\Container\ContainerInterface;
use ResponsiveSk\Slim4Paths\Paths;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Paths-aware Twig Environment Factory.
 *
 * Creates Twig Environment using centralized path configuration
 * via TemplatePathProvider service. Follows PSR-15 compliance
 * and Zend4Boot protocol.
 */
class PathsAwareTwigEnvironmentFactory
{
    /**
     * Create Twig Environment instance.
     *
     * @param ContainerInterface $container DI container
     * @return Environment Configured Twig environment
     */
    public function __invoke(ContainerInterface $container): Environment
    {
        // Get template path provider
        $templatePathProvider = $container->get(TemplatePathProviderInterface::class);
        assert($templatePathProvider instanceof TemplatePathProviderInterface);

        // Get Paths service for cache directory
        $paths = $container->get(Paths::class);
        assert($paths instanceof Paths);

        // Get application configuration
        /** @var array<string, mixed> $config */
        $config = $container->get('config');
        assert(is_array($config));

        /** @var array<string, mixed> $twigConfig */
        $twigConfig = $config['twig'] ?? [];
        assert(is_array($twigConfig));

        // Create filesystem loader
        $loader = new FilesystemLoader();

        // Add template paths using TemplatePathProvider
        $templatePaths = $templatePathProvider->getTemplatePaths();
        foreach ($templatePaths as $namespace => $absolutePath) {
            $loader->addPath($absolutePath, $namespace);
        }

        // Environment options
        $options = [
            'cache' => $paths->getPath('data/cache/twig', ''),
            'debug' => $twigConfig['debug'] ?? false,
            'strict_variables' => $twigConfig['strict_variables'] ?? false,
            'auto_reload' => $twigConfig['auto_reload'] ?? false,
        ];

        $environment = new Environment($loader, $options);

        // Add global variables if configured
        if (isset($twigConfig['globals']) && is_array($twigConfig['globals'])) {
            foreach ($twigConfig['globals'] as $name => $value) {
                $environment->addGlobal($name, $value);
            }
        }

        return $environment;
    }
}
