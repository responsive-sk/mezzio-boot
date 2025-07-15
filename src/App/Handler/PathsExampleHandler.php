<?php

declare(strict_types=1);

namespace Light\App\Handler;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ResponsiveSk\Slim4Paths\Paths;

/**
 * Example handler demonstrating Paths usage
 */
class PathsExampleHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly Paths $paths
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Demonstrate various path methods (v6.0 API)
        $pathsInfo = [
            'base_path'      => $this->paths->getPath('base'),
            'config_path'    => $this->paths->getPath('config'),
            'src_path'       => $this->paths->getPath('src'),
            'public_path'    => $this->paths->getPath('public'),
            'templates_path' => $this->paths->getPath('templates'),
            'cache_path'     => $this->paths->getPath('cache'),
            'logs_path'      => $this->paths->getPath('logs'),
            'uploads_path'   => $this->paths->getPath('uploads'),
            'var_path'       => $this->paths->getPath('var'),
            'data_path'      => $this->paths->getPath('data'),

            // Specific file examples using buildPath
            'config_file' => $this->paths->buildPath('config/config.php'),
            'log_file'    => $this->paths->buildPath('var/logs/app.log'),
            'upload_file' => $this->paths->buildPath('var/uploads/example.jpg'),

            // Asset paths (using buildPath for files)
            'css_file'   => $this->paths->buildPath('public/css/app.css'),
            'js_file'    => $this->paths->buildPath('public/js/main.js'),
            'image_file' => $this->paths->buildPath('public/images/logo.png'),

            // All configured paths
            'all_paths' => $this->paths->all(),

            // Base path info
            'base_path_info' => $this->paths->getBasePath(),
        ];

        return new JsonResponse([
            'message' => 'Paths service example',
            'paths'   => $pathsInfo,
        ]);
    }
}
