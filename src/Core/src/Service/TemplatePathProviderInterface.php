<?php

declare(strict_types=1);

namespace Light\Core\Service;

use InvalidArgumentException;

/**
 * Interface for providing template paths to Twig environment.
 *
 * This interface follows PSR-15 compliance and Zend4Boot protocol
 * for centralized path management using responsive-sk/slim4-paths.
 */
interface TemplatePathProviderInterface
{
    /**
     * Get all template paths for Twig loader.
     *
     * Returns an associative array where keys are namespace names
     * and values are absolute paths to template directories.
     *
     * @return array<string, string> Template paths indexed by namespace
     */
    public function getTemplatePaths(): array;

    /**
     * Get template path for specific namespace.
     *
     * @param string $namespace Template namespace (e.g., 'app', 'error', 'layout')
     * @return string Absolute path to template directory
     * @throws InvalidArgumentException If namespace is not configured.
     */
    public function getTemplatePathForNamespace(string $namespace): string;

    /**
     * Check if template namespace exists.
     *
     * @param string $namespace Template namespace to check
     * @return bool True if namespace is configured
     */
    public function hasTemplateNamespace(string $namespace): bool;

    /**
     * Get all available template namespaces.
     *
     * @return array<string> List of configured template namespaces
     */
    public function getAvailableNamespaces(): array;
}
