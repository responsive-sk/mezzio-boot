<?php

declare(strict_types=1);

namespace Light\Core\Middleware;

use DebugBar\DebugBar;
use DebugBar\JavascriptRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * DebugBar Middleware for development
 */
class DebugBarMiddleware implements MiddlewareInterface
{
    private DebugBar $debugBar;
    private JavascriptRenderer $renderer;

    public function __construct()
    {
        $this->debugBar = new \DebugBar\StandardDebugBar();
        $this->renderer = $this->debugBar->getJavascriptRenderer();

        // Set correct base URL to match our route
        $this->renderer->setBaseUrl('/debugbar');
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
        $this->debugBar['messages']->addMessage('Request: ' . $request->getMethod() . ' ' . (string) $request->getUri());

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

    private function isDevelopmentMode(): bool
    {
        // For now, always enable in development
        return php_sapi_name() !== 'cli';
    }

    private function isHtmlResponse(ResponseInterface $response): bool
    {
        $contentType = $response->getHeaderLine('Content-Type');
        return str_contains($contentType, 'text/html') || empty($contentType);
    }

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
            $body = str_replace('</body>', $debugBarHtml . '</body>', $body);

            $response->getBody()->rewind();
            $response->getBody()->write($body);
        } catch (\Exception $e) {
            // Silently fail in case of errors
            error_log('DebugBar injection failed: ' . $e->getMessage());
        }

        return $response;
    }
}
