<?php

declare(strict_types=1);

namespace Light\Core\Handler;

use DebugBar\StandardDebugBar;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response;

/**
 * Handler for DebugBar assets (CSS/JS)
 */
class DebugBarAssetsHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $debugBar = new StandardDebugBar();
        $renderer = $debugBar->getJavascriptRenderer();

        $path = $request->getUri()->getPath();
        $relativePath = str_replace('/debugbar/', '', $path);

        // Try to get asset content from renderer
        try {
            $assets = $renderer->getAssets();

            // Check CSS assets
            if (isset($assets['css'])) {
                foreach ($assets['css'] as $asset) {
                    if (str_contains($asset, $relativePath)) {
                        $content = file_get_contents($asset);
                        if ($content !== false) {
                            return $this->createResponse($content, 'text/css');
                        }
                    }
                }
            }

            // Check JS assets
            if (isset($assets['js'])) {
                foreach ($assets['js'] as $asset) {
                    if (str_contains($asset, $relativePath)) {
                        $content = file_get_contents($asset);
                        if ($content !== false) {
                            return $this->createResponse($content, 'application/javascript');
                        }
                    }
                }
            }

            // Fallback: serve combined assets
            if (str_ends_with($relativePath, '.css')) {
                $content = $renderer->dumpCssAssets();
                return $this->createResponse($content ?: '/* DebugBar CSS */', 'text/css');
            }

            if (str_ends_with($relativePath, '.js')) {
                $content = $renderer->dumpJsAssets();
                return $this->createResponse($content ?: '/* DebugBar JS */', 'application/javascript');
            }

        } catch (\Exception $e) {
            // Fallback for any errors
            if (str_ends_with($relativePath, '.css')) {
                $content = $renderer->dumpCssAssets();
                return $this->createResponse($content ?: '/* DebugBar CSS fallback */', 'text/css');
            }

            if (str_ends_with($relativePath, '.js')) {
                $content = $renderer->dumpJsAssets();
                return $this->createResponse($content ?: '/* DebugBar JS fallback */', 'application/javascript');
            }
        }

        // 404 for unknown assets
        return new Response('php://memory', 404);
    }
    
    private function createResponse(string $content, string $contentType): ResponseInterface
    {
        $response = new Response('php://memory', 200, [
            'Content-Type' => $contentType,
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
        
        $response->getBody()->write($content);
        return $response;
    }
}
