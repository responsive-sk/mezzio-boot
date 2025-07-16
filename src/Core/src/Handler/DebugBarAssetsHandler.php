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
        $file = basename($path);
        
        // Serve CSS
        if (str_ends_with($file, '.css')) {
            $content = $renderer->dumpCssAssets();
            return $this->createResponse($content, 'text/css');
        }

        // Serve JS
        if (str_ends_with($file, '.js')) {
            $content = $renderer->dumpJsAssets();
            return $this->createResponse($content, 'application/javascript');
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
