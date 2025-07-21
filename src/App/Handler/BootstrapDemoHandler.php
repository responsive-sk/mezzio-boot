<?php

declare(strict_types=1);

namespace Light\App\Handler;

use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class BootstrapDemoHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly TemplateRendererInterface $template
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Jednoduché theme info bez zložitého AssetHelper
        $themeInfo = [
            'name'        => 'Bootstrap 5.3',
            'version'     => '5.3.0',
            'description' => 'Bootstrap CSS framework demo',
        ];

        // Vite compiled assets (no hashes for easier maintenance)
        $cssUrl = '/themes/bootstrap/assets/main.css';
        $jsUrl  = '/themes/bootstrap/assets/main.js';

        $html = $this->template->render('app::bootstrap-demo', [
            'themeInfo' => $themeInfo,
            'cssUrl'    => $cssUrl,
            'jsUrl'     => $jsUrl,
        ]);

        return new HtmlResponse($html);
    }
}
