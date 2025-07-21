<?php

declare(strict_types=1);

namespace Light\App\Handler;

use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ContactHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly TemplateRendererInterface $template
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Contact information
        $contactInfo = [
            'email' => 'hello@responsive.sk',
            'phone' => '+421 XXX XXX XXX',
            'address' => [
                'street' => 'Bratislava',
                'city' => 'Slovakia',
                'country' => 'Europe'
            ],
            'social' => [
                ['name' => 'LinkedIn', 'url' => 'https://linkedin.com/company/responsive-sk', 'icon' => 'linkedin'],
                ['name' => 'GitHub', 'url' => 'https://github.com/responsive-sk', 'icon' => 'github'],
                ['name' => 'Twitter', 'url' => 'https://twitter.com/responsive_sk', 'icon' => 'twitter'],
            ]
        ];

        // Vite compiled assets
        $cssUrl = '/themes/main/assets/main.css';
        $jsUrl = '/themes/main/assets/main.js';

        $html = $this->template->render('app::contact', [
            'contactInfo' => $contactInfo,
            'cssUrl' => $cssUrl,
            'jsUrl' => $jsUrl,
        ]);

        return new HtmlResponse($html);
    }
}
