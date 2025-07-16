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
        $path = $request->getUri()->getPath();
        $relativePath = str_replace('/debugbar/', '', $path);

        // Map DebugBar resource paths to actual vendor files
        $debugBarResourcesPath = __DIR__ . '/../../../../vendor/php-debugbar/php-debugbar/src/DebugBar/Resources/';
        $filePath = $debugBarResourcesPath . $relativePath;

        // Security check - ensure we're only serving files from DebugBar resources
        $realPath = realpath($filePath);
        $realResourcesPath = realpath($debugBarResourcesPath);

        if ($realPath === false || !str_starts_with($realPath, $realResourcesPath)) {
            return new Response('php://memory', 404);
        }

        // Check if file exists
        if (!file_exists($realPath) || !is_file($realPath)) {
            return new Response('php://memory', 404);
        }

        // Get file content
        $content = file_get_contents($realPath);
        if ($content === false) {
            return new Response('php://memory', 404);
        }

        // Determine content type
        $contentType = 'text/plain';
        $extension = pathinfo($realPath, PATHINFO_EXTENSION);

        switch ($extension) {
            case 'css':
                $contentType = 'text/css';
                break;
            case 'js':
                $contentType = 'application/javascript';
                break;
            case 'png':
                $contentType = 'image/png';
                break;
            case 'gif':
                $contentType = 'image/gif';
                break;
            case 'jpg':
            case 'jpeg':
                $contentType = 'image/jpeg';
                break;
            case 'svg':
                $contentType = 'image/svg+xml';
                break;
            case 'woff':
                $contentType = 'font/woff';
                break;
            case 'woff2':
                $contentType = 'font/woff2';
                break;
            case 'ttf':
                $contentType = 'font/ttf';
                break;
            case 'eot':
                $contentType = 'application/vnd.ms-fontobject';
                break;
        }

        return $this->createResponse($content, $contentType);
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
