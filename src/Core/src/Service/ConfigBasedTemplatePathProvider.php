<?php

declare(strict_types=1);

namespace Light\Core\Service;

use InvalidArgumentException;
use ResponsiveSk\Slim4Paths\Paths;

use function array_key_exists;
use function array_keys;
use function is_array;
use function sprintf;

/**
 * Configuration-based template path provider.
 *
 * Provides template paths using centralized configuration and Paths service.
 * Follows Zend4Boot protocol and PSR-15 compliance.
 */
class ConfigBasedTemplatePathProvider implements TemplatePathProviderInterface
{
    /** @var array<string, string> */
    private array $templatePaths = [];

    /**
     * @param Paths $paths Paths service instance
     * @param array<string, mixed> $config Application configuration
     */
    public function __construct(
        private readonly Paths $paths,
        private readonly array $config
    ) {
        $this->initializeTemplatePaths();
    }

    /**
     * Get all template paths for Twig loader.
     *
     * @return array<string, string> Template paths indexed by namespace
     */
    public function getTemplatePaths(): array
    {
        return $this->templatePaths;
    }

    /**
     * Get template path for specific namespace.
     *
     * @param string $namespace Template namespace
     * @return string Absolute path to template directory
     * @throws InvalidArgumentException If namespace is not configured.
     */
    public function getTemplatePathForNamespace(string $namespace): string
    {
        if (! $this->hasTemplateNamespace($namespace)) {
            throw new InvalidArgumentException(
                sprintf('Template namespace "%s" is not configured', $namespace)
            );
        }

        return $this->templatePaths[$namespace];
    }

    /**
     * Check if template namespace exists.
     *
     * @param string $namespace Template namespace to check
     * @return bool True if namespace is configured
     */
    public function hasTemplateNamespace(string $namespace): bool
    {
        return array_key_exists($namespace, $this->templatePaths);
    }

    /**
     * Get all available template namespaces.
     *
     * @return array<string> List of configured template namespaces
     */
    public function getAvailableNamespaces(): array
    {
        return array_keys($this->templatePaths);
    }

    /**
     * Initialize template paths from configuration.
     */
    private function initializeTemplatePaths(): void
    {
        if (! isset($this->config['paths']) || ! is_array($this->config['paths'])) {
            return;
        }

        if (! isset($this->config['paths']['templates']) || ! is_array($this->config['paths']['templates'])) {
            return;
        }

        /** @var array<string, string> $templateConfig */
        $templateConfig = $this->config['paths']['templates'];

        foreach ($templateConfig as $namespace => $relativePath) {
            // Use Paths service to get absolute path
            $absolutePath                    = $this->paths->getPath($relativePath, '');
            $this->templatePaths[$namespace] = $absolutePath;
        }
    }
}
