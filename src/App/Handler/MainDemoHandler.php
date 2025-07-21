<?php

declare(strict_types=1);

namespace Light\App\Handler;

use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MainDemoHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly TemplateRendererInterface $template
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Jednoduché theme info bez zložitého AssetHelper
        $themeInfo = [
            'name'        => 'TailwindCSS + Alpine.js',
            'version'     => '3.3.0',
            'description' => 'Modern utility-first CSS framework with reactive components',
        ];

        // Vite compiled assets (no hashes for easier maintenance)
        $cssUrl = '/themes/main/assets/main.css';
        $jsUrl  = '/themes/main/assets/main.js';

        $html = $this->template->render('app::main-demo', [
            'themeInfo' => $themeInfo,
            'cssUrl'    => $cssUrl,
            'jsUrl'     => $jsUrl,
        ]);

        return new HtmlResponse($html);
    }
}
