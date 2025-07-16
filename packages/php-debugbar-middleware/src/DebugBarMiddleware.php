<?php

declare(strict_types=1);

namespace ResponsiveSk\PhpDebugBarMiddleware;

use DebugBar\DebugBar;
use DebugBar\JavascriptRenderer;
use DebugBar\StandardDebugBar;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * PSR-15 middleware for PHP DebugBar integration
 * 
 * Automatically injects DebugBar into HTML responses during development.
 * Completely disabled in production for zero performance impact.
 */
class DebugBarMiddleware implements MiddlewareInterface
{
    private readonly DebugBar $debugBar;
    private readonly JavascriptRenderer $renderer;

    public function __construct(?DebugBar $debugBar = null, ?string $assetPath = null)
    {
        $this->debugBar = $debugBar ?? new StandardDebugBar();
        $this->renderer = $this->debugBar->getJavascriptRenderer();
        
        // Configure renderer for asset serving
        $this->renderer->setBaseUrl($assetPath ?? '/debugbar');
        $this->renderer->setIncludeVendors(true);
        $this->renderer->setEnableJqueryNoConflict(false);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Skip if not in development mode
        if (!$this->isDevelopmentMode()) {
            return $handler->handle($request);
        }

        // Start collecting data
        $timeCollector = $this->debugBar->getCollector('time');
        if ($timeCollector) {
            $timeCollector->startMeasure('request', 'Request Processing');
        }

        // Add request info
        $this->debugBar['messages']->addMessage(
            'Request: ' . $request->getMethod() . ' ' . (string) $request->getUri()
        );

        $response = $handler->handle($request);

        // Stop measuring
        if ($timeCollector) {
            $timeCollector->stopMeasure('request');
        }

        // Inject DebugBar into HTML response
        if ($this->isHtmlResponse($response)) {
            $response = $this->injectDebugBar($response);
        }

        return $response;
    }

    /**
     * Check if we're in development mode
     */
    private function isDevelopmentMode(): bool
    {
        // Check environment variables
        $appEnv = getenv('APP_ENV') ?: 'development';
        $debug = getenv('DEBUG');
        
        // Disable in production
        if ($appEnv === 'production') {
            return false;
        }
        
        // Disable if explicitly set to false
        if ($debug === 'false' || $debug === '0') {
            return false;
        }
        
        // Disable in CLI mode
        if (php_sapi_name() === 'cli') {
            return false;
        }
        
        return true;
    }

    /**
     * Check if response is HTML
     */
    private function isHtmlResponse(ResponseInterface $response): bool
    {
        $contentType = $response->getHeaderLine('Content-Type');
        return str_contains($contentType, 'text/html') || empty($contentType);
    }

    /**
     * Inject DebugBar HTML into response
     */
    private function injectDebugBar(ResponseInterface $response): ResponseInterface
    {
        $body = (string) $response->getBody();
        
        // Only inject if we have a closing body tag
        if (!str_contains($body, '</body>')) {
            return $response;
        }

        try {
            // Use the standard DebugBar rendering approach
            $debugBarHtml = $this->renderer->renderHead() . $this->renderer->render();

            // Add our custom CSS for branding
            $customCss = '<style type="text/css">' . CustomDebugBarStyles::getMinimalCss() . '</style>';
            $debugBarHtml = $customCss . $debugBarHtml;

            $body = str_replace('</body>', $debugBarHtml . '</body>', $body);

            $response->getBody()->rewind();
            $response->getBody()->write($body);
        } catch (\Exception $e) {
            // Silently fail in case of errors to avoid breaking the application
            error_log('DebugBar injection failed: ' . $e->getMessage());
        }

        return $response;
    }

    /**
     * Get the DebugBar instance
     */
    public function getDebugBar(): DebugBar
    {
        return $this->debugBar;
    }

    /**
     * Get the JavaScript renderer
     */
    public function getRenderer(): JavascriptRenderer
    {
        return $this->renderer;
    }

    /**
     * Add a message to the DebugBar
     */
    public function addMessage(string $message, string $label = 'info'): void
    {
        if ($this->isDevelopmentMode()) {
            $this->debugBar['messages']->addMessage($message, $label);
        }
    }

    /**
     * Start a time measure
     */
    public function startMeasure(string $name, ?string $label = null): void
    {
        if ($this->isDevelopmentMode()) {
            $timeCollector = $this->debugBar->getCollector('time');
            if ($timeCollector) {
                $timeCollector->startMeasure($name, $label);
            }
        }
    }

    /**
     * Stop a time measure
     */
    public function stopMeasure(string $name): void
    {
        if ($this->isDevelopmentMode()) {
            $timeCollector = $this->debugBar->getCollector('time');
            if ($timeCollector) {
                $timeCollector->stopMeasure($name);
            }
        }
    }
}
