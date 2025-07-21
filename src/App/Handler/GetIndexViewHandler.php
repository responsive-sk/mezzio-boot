<?php

declare(strict_types=1);

namespace Light\App\Handler;

use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ResponsiveSk\Slim4Paths\Paths;

class GetIndexViewHandler implements RequestHandlerInterface
{
    public function __construct(
        protected TemplateRendererInterface $template,
        protected Paths $paths
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Example of using Paths service in template data (v6.0 API)
        $templateData = [
            'paths' => [
                'public' => $this->paths->getPath('public'),
                'base'   => $this->paths->getPath('base'),
                'var'    => $this->paths->getPath('var'),
                'logs'   => $this->paths->getPath('logs'),
                'cache'  => $this->paths->getPath('cache'),
            ],
        ];

        return new HtmlResponse(
            $this->template->render('app::index', $templateData)
        );
    }
}
