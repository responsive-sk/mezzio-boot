<?php

declare(strict_types=1);

namespace Light\Core\Template;

use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplatePath;
use Mezzio\Template\TemplateRendererInterface;
use ResponsiveSk\Slim4Paths\Paths;
use RuntimeException;

use function array_merge;
use function explode;
use function file_exists;
use function htmlspecialchars;
use function is_array;
use function is_object;
use function is_scalar;
use function is_string;
use function ltrim;
use function method_exists;
use function ob_get_clean;
use function ob_start;
use function pathinfo;
use function preg_match;
use function realpath;
use function rtrim;
use function str_contains;
use function strpos;
use function urldecode;

use const ENT_QUOTES;
use const ENT_SUBSTITUTE;
use const PATHINFO_EXTENSION;

/**
 * Native PHP template renderer implementation.
 *
 * Implements Mezzio\Template\TemplateRendererInterface using pure PHP templates.
 * Provides fast, native PHP templating with no external dependencies.
 */
class NativePhpRenderer implements TemplateRendererInterface
{
    /** @var array<string, array<string>> */
    private array $paths = [];

    /** @var array<string, mixed> */
    private array $defaultParams = [];

    private ?string $layoutTemplate = null;

    /** @var array<string, mixed> */
    private array $layoutData = [];

    /**
     * @param Paths $pathsService Paths service instance
     * @param array<string, mixed> $config Application configuration
     * @param UrlHelper|null $urlHelper URL helper for route generation
     */
    public function __construct(
        private readonly Paths $pathsService,
        private readonly array $config,
        private readonly ?UrlHelper $urlHelper = null
    ) {
        $this->initializeTemplatePaths();
    }

    /**
     * Render a template, optionally with parameters.
     *
     * @param string $name Template name
     * @param array<string, mixed>|object $params Template parameters
     * @return string Rendered template content
     */
    public function render(string $name, $params = []): string
    {
        // Ensure params is array
        if (! is_array($params)) {
            $params = [];
        }

        // Merge with default parameters
        $allParams = $this->mergeDefaultParams($name, $params);

        // Parse template name (namespace::template)
        if (str_contains($name, '::')) {
            [$namespace, $template] = explode('::', $name, 2);
            $templatePath           = $this->findTemplate($namespace, $template);
        } else {
            $templatePath = $this->findTemplate('', $name);
        }

        if (! $templatePath || ! file_exists($templatePath)) {
            throw new RuntimeException("Template '{$name}' not found");
        }

        // Reset layout for each render
        $this->layoutTemplate = null;
        $this->layoutData     = [];

        // Render template content
        $content = $this->renderTemplate($templatePath, $allParams);

        // If layout is set, render layout with content
        // @phpstan-ignore-next-line notIdentical.alwaysFalse
        if ($this->layoutTemplate !== null) {
            /** @var array<string, mixed> $layoutParams */
            $layoutParams = array_merge($this->layoutData, ['content' => $content]);

            return $this->render($this->layoutTemplate, $layoutParams);
        }

        return $content;
    }

    /**
     * Add a template path to the engine.
     *
     * @param string $path Template path
     * @param string|null $namespace Optional namespace
     * @throws RuntimeException If path contains path traversal attempts.
     */
    public function addPath(string $path, ?string $namespace = null): void
    {
        // Sanitize path to prevent path traversal
        $sanitizedPath = $this->sanitizePath($path);

        $namespace = $namespace ?: '';
        if (! isset($this->paths[$namespace])) {
            $this->paths[$namespace] = [];
        }
        $this->paths[$namespace][] = rtrim($sanitizedPath, '/');
    }

    /**
     * Retrieve configured paths from the engine.
     *
     * @return TemplatePath[]
     */
    public function getPaths(): array
    {
        $templatePaths = [];
        foreach ($this->paths as $namespace => $paths) {
            foreach ($paths as $path) {
                $templatePaths[] = new TemplatePath($path, $namespace === '' ? null : $namespace);
            }
        }

        return $templatePaths;
    }

    /**
     * Add a default parameter to use with a template.
     *
     * @param string $templateName Template name or TEMPLATE_ALL
     * @param string $param Parameter name
     * @param mixed $value Parameter value
     */
    public function addDefaultParam(string $templateName, string $param, $value): void
    {
        if (! isset($this->defaultParams[$templateName])) {
            $this->defaultParams[$templateName] = [];
        }
        if (! is_array($this->defaultParams[$templateName])) {
            $this->defaultParams[$templateName] = [];
        }
        $this->defaultParams[$templateName][$param] = $value;
    }

    /**
     * Initialize template paths from Paths service and configuration.
     */
    private function initializeTemplatePaths(): void
    {
        // Load from templates configuration (new way)
        if (isset($this->config['templates']['paths'])) {
            /** @var array<string, array<string>> $templatePaths */
            $templatePaths = $this->config['templates']['paths'];

            foreach ($templatePaths as $namespace => $paths) {
                foreach ($paths as $path) {
                    $this->addPath($path, $namespace);
                }
            }

            return; // If we have templates config, use only that
        }

        // Fallback: try to load from Paths service (v6.0 way)
        $allPaths           = $this->pathsService->all();
        $templateNamespaces = ['layout', 'app', 'error', 'page', 'partial'];

        foreach ($templateNamespaces as $namespace) {
            if (isset($allPaths[$namespace])) {
                $this->addPath($allPaths[$namespace], $namespace);
            }
        }

        // Fallback to config-based paths (backward compatibility)
        if (! isset($this->config['paths']) || ! is_array($this->config['paths'])) {
            return;
        }

        if (! isset($this->config['paths']['templates']) || ! is_array($this->config['paths']['templates'])) {
            return;
        }

        /** @var array<string, string> $templateConfig */
        $templateConfig = $this->config['paths']['templates'];

        foreach ($templateConfig as $namespace => $relativePath) {
            // Only add if not already added from Paths service
            if (! isset($this->paths[$namespace])) {
                $absolutePath = $this->pathsService->getPath($relativePath, '');
                $this->addPath($absolutePath, $namespace);
            }
        }
    }

    /**
     * Find template file.
     *
     * @param string $namespace Template namespace
     * @param string $template Template name
     * @return string|null Template file path or null if not found
     * @throws RuntimeException If template name contains path traversal attempts.
     */
    private function findTemplate(string $namespace, string $template): ?string
    {
        // Sanitize template name to prevent path traversal
        $sanitizedTemplate = $this->sanitizeTemplateName($template);

        $paths = $this->paths[$namespace] ?? $this->paths[''] ?? [];

        foreach ($paths as $path) {
            $templatePath = $path . '/' . $sanitizedTemplate;

            // Try with .phtml extension if not provided
            if (! pathinfo($templatePath, PATHINFO_EXTENSION)) {
                $templatePath .= '.phtml';
            }

            // Additional security check: ensure resolved path is within allowed directory
            if (file_exists($templatePath) && $this->isPathSafe($templatePath, $path)) {
                return $templatePath;
            }
        }

        return null;
    }

    /**
     * Merge default parameters with render parameters.
     *
     * @param string $templateName Template name
     * @param array<string, mixed> $params Render parameters
     * @return array<string, mixed> Merged parameters
     */
    private function mergeDefaultParams(string $templateName, array $params): array
    {
        $allDefaults      = $this->defaultParams[self::TEMPLATE_ALL] ?? [];
        $templateDefaults = $this->defaultParams[$templateName] ?? [];

        // Ensure defaults are arrays
        if (! is_array($allDefaults)) {
            $allDefaults = [];
        }
        if (! is_array($templateDefaults)) {
            $templateDefaults = [];
        }

        /** @var array<string, mixed> $result */
        $result = array_merge($allDefaults, $templateDefaults, $params);

        return $result;
    }

    /**
     * Render template file.
     *
     * @param string $templatePath Path to template file
     * @param array<string, mixed> $params Template parameters
     * @return string Rendered content
     */
    private function renderTemplate(string $templatePath, array $params): string
    {
        // Make template variables available in local scope (safer than extract)
        foreach ($params as $key => $value) {
            if (is_string($key) && preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $key)) {
                $$key = $value;
            }
        }

        // Helper functions for templates
        $escapeHtml = function (mixed $value): string {
            if ($value === null) {
                return '';
            }
            if (is_scalar($value) || (is_object($value) && method_exists($value, '__toString'))) {
                return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }

            return '';
        };

        // URL helper using Mezzio UrlHelper if available
        $url = function (string $route, array $routeParams = []): string {
            if ($this->urlHelper !== null) {
                /** @var array<string, mixed> $params */
                $params = $routeParams;

                return $this->urlHelper->generate($route !== '' ? $route : null, $params);
            }

            // Fallback for basic URL generation
            return '/' . ltrim($route, '/');
        };

        // Asset helper
        $asset = function (string $path): string {
            return '/' . ltrim($path, '/');
        };

        // Escape helper (alias for escapeHtml)
        $e = $escapeHtml;

        // Layout helper function
        $layout = function (string $layoutName, array $layoutParams = []): void {
            $this->layoutTemplate = $layoutName;
            /** @var array<string, mixed> $params */
            $params           = $layoutParams;
            $this->layoutData = $params;
        };

        ob_start();
        include $templatePath;
        $content = ob_get_clean();

        if ($content === false) {
            throw new RuntimeException('Failed to capture template output');
        }

        return $content;
    }

    /**
     * Sanitize path to prevent path traversal attacks.
     *
     * @param string $path Path to sanitize
     * @return string Sanitized path
     * @throws RuntimeException If path contains dangerous patterns.
     */
    private function sanitizePath(string $path): string
    {
        // Check for path traversal patterns
        if (preg_match('/\.\.\/|\.\.\\\\/', $path)) {
            throw new RuntimeException("Path traversal detected in path: {$path}");
        }

        // Check for null bytes
        if (strpos($path, "\0") !== false) {
            throw new RuntimeException("Null byte detected in path: {$path}");
        }

        // Check for encoded path traversal
        $decoded = urldecode($path);
        if (preg_match('/\.\.\/|\.\.\\\\/', $decoded)) {
            throw new RuntimeException("Encoded path traversal detected in path: {$path}");
        }

        return $path;
    }

    /**
     * Sanitize template name to prevent path traversal attacks.
     *
     * @param string $template Template name to sanitize
     * @return string Sanitized template name
     * @throws RuntimeException If template name contains dangerous patterns.
     */
    private function sanitizeTemplateName(string $template): string
    {
        // Check for path traversal patterns
        if (preg_match('/\.\.\/|\.\.\\\\/', $template)) {
            throw new RuntimeException("Path traversal detected in template name: {$template}");
        }

        // Check for null bytes
        if (strpos($template, "\0") !== false) {
            throw new RuntimeException("Null byte detected in template name: {$template}");
        }

        // Check for absolute paths
        if (strpos($template, '/') === 0 || preg_match('/^[a-zA-Z]:/', $template)) {
            throw new RuntimeException("Absolute path detected in template name: {$template}");
        }

        return $template;
    }

    /**
     * Check if resolved path is safe (within allowed directory).
     *
     * @param string $resolvedPath Full path to template file
     * @param string $allowedBasePath Base path that should contain the file
     * @return bool True if path is safe
     */
    private function isPathSafe(string $resolvedPath, string $allowedBasePath): bool
    {
        $realResolvedPath = realpath($resolvedPath);
        $realBasePath     = realpath($allowedBasePath);

        // If realpath fails, consider it unsafe
        if ($realResolvedPath === false || $realBasePath === false) {
            return false;
        }

        // Check if resolved path starts with base path
        return strpos($realResolvedPath, $realBasePath) === 0;
    }
}
