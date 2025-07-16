<?php

declare(strict_types=1);

namespace ResponsiveSk\PhpDebugBarMiddleware;

use Laminas\Diactoros\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * PSR-15 request handler for serving DebugBar static assets
 * 
 * Serves CSS, JavaScript, fonts, and images from the DebugBar vendor directory
 * with proper MIME types and security protection against path traversal.
 */
class DebugBarAssetsHandler implements RequestHandlerInterface
{
    private readonly string $debugBarResourcesPath;

    public function __construct(?string $debugBarResourcesPath = null)
    {
        $this->debugBarResourcesPath = $debugBarResourcesPath ?? 
            $this->findDebugBarResourcesPath();
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        $relativePath = str_replace('/debugbar/', '', $path);
        
        // Build full file path
        $filePath = $this->debugBarResourcesPath . DIRECTORY_SEPARATOR . $relativePath;
        
        // Security check - ensure we're only serving files from DebugBar resources
        $realPath = realpath($filePath);
        $realResourcesPath = realpath($this->debugBarResourcesPath);
        
        if ($realPath === false || $realResourcesPath === false || 
            !str_starts_with($realPath, $realResourcesPath)) {
            return $this->createNotFoundResponse();
        }
        
        // Check if file exists and is readable
        if (!file_exists($realPath) || !is_file($realPath) || !is_readable($realPath)) {
            return $this->createNotFoundResponse();
        }
        
        // Get file content
        $content = file_get_contents($realPath);
        if ($content === false) {
            return $this->createNotFoundResponse();
        }
        
        // Determine content type
        $contentType = $this->getContentType($realPath);
        
        return $this->createResponse($content, $contentType);
    }

    /**
     * Find the DebugBar resources path automatically
     */
    private function findDebugBarResourcesPath(): string
    {
        // Try common vendor locations
        $possiblePaths = [
            __DIR__ . '/../../../php-debugbar/php-debugbar/src/DebugBar/Resources',
            __DIR__ . '/../../../../vendor/php-debugbar/php-debugbar/src/DebugBar/Resources',
            __DIR__ . '/../vendor/php-debugbar/php-debugbar/src/DebugBar/Resources',
            getcwd() . '/vendor/php-debugbar/php-debugbar/src/DebugBar/Resources',
        ];
        
        foreach ($possiblePaths as $path) {
            if (is_dir($path)) {
                return $path;
            }
        }
        
        throw new \RuntimeException(
            'Could not find DebugBar resources directory. ' .
            'Please ensure php-debugbar/php-debugbar is installed.'
        );
    }

    /**
     * Determine MIME type based on file extension
     */
    private function getContentType(string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        return match ($extension) {
            'css' => 'text/css',
            'js' => 'application/javascript',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'jpg', 'jpeg' => 'image/jpeg',
            'svg' => 'image/svg+xml',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject',
            'ico' => 'image/x-icon',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'txt' => 'text/plain',
            default => 'application/octet-stream',
        };
    }

    /**
     * Create a successful response with content
     */
    private function createResponse(string $content, string $contentType): ResponseInterface
    {
        $response = new Response('php://memory', 200, [
            'Content-Type' => $contentType,
            'Cache-Control' => 'public, max-age=3600', // Cache for 1 hour in development
            'Content-Length' => (string) strlen($content),
        ]);
        
        $response->getBody()->write($content);
        return $response;
    }

    /**
     * Create a 404 Not Found response
     */
    private function createNotFoundResponse(): ResponseInterface
    {
        return new Response('php://memory', 404, [
            'Content-Type' => 'text/plain',
        ]);
    }

    /**
     * Get the DebugBar resources path
     */
    public function getDebugBarResourcesPath(): string
    {
        return $this->debugBarResourcesPath;
    }
}
